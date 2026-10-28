<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

function load_listing(PDO $pdo, int $id): ?array {
    // PUBLIC columns only
    $st = $pdo->prepare(
        "SELECT a.account_id, a.seller_id, g.game_name, a.pseudonym, a.selling_price, a.account_level, a.rank_tier,
                a.featured_items, a.description, a.birthday_binded, a.username_binded, a.email_binded,
                a.trade_posted, a.status, u.username AS trader
         FROM accounts a
         JOIN games g ON g.game_id = a.game_id
         JOIN users u ON u.user_id = a.seller_id
         WHERE a.account_id = ? AND a.status IN ('available','sold')"
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

$a = load_listing($pdo, $id);
if (!$a) { flash('error', 'That listing is no longer on the market.'); redirect('/market/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ((int)$a['seller_id'] === $uid) {
        flash('error', 'You cannot make an offer on your own listing.');
    } elseif ($action === 'offer') {
        if ($a['status'] !== 'available') {
            flash('error', 'This account is already sold.');
        } else {
            $st = $pdo->prepare("SELECT 1 FROM offers WHERE account_id=? AND buyer_id=? AND status='pending'");
            $st->execute([$id, $uid]);
            if ($st->fetchColumn()) {
                flash('error', 'You already have an offer on this account.');
            } else {
                $pdo->prepare('INSERT INTO offers (account_id, buyer_id) VALUES (?, ?)')->execute([$id, $uid]);
                log_activity('INSERT', 'offers', (int)$pdo->lastInsertId(), "Made an offer on account #$id");
                flash('success', 'Offer sent. The seller can now mark the account as sold.');
            }
        }
    } elseif ($action === 'cancel') {
        $pdo->prepare("UPDATE offers SET status='cancelled' WHERE account_id=? AND buyer_id=? AND status='pending'")->execute([$id, $uid]);
        log_activity('UPDATE', 'offers', null, "Cancelled offer on account #$id");
        flash('success', 'Offer cancelled.');
    }
    redirect('/market/view.php?id=' . $id);
}

// my latest offer on this listing
$st = $pdo->prepare('SELECT status FROM offers WHERE account_id=? AND buyer_id=? ORDER BY offer_id DESC LIMIT 1');
$st->execute([$id, $uid]);
$myOffer = $st->fetchColumn() ?: null;

$meta = game_meta($a['game_name']);
$mine = (int)$a['seller_id'] === $uid;
$pageTitle = $a['pseudonym'];
include __DIR__ . '/../includes/header.php';
?>
<a class="muted" href="<?= BASE_URL ?>/market/index.php">&larr; Back to market</a>
<section class="glass card detail">
    <div class="row between">
        <span class="badge badge-game"><?= e($a['game_name']) ?></span>
        <span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span>
    </div>
    <h1><?= e($a['pseudonym']) ?></h1>
    <p class="price big"><?= peso($a['selling_price']) ?></p>

    <dl class="details">
        <dt><?= e($meta['level_label'] ?? 'Level') ?></dt><dd><?= (int)$a['account_level'] ?></dd>
        <?php if ($a['rank_tier']): ?><dt>Current rank</dt><dd><?= e($a['rank_tier']) ?></dd><?php endif; ?>
        <dt><?= e($meta['items_label'] ?? 'Featured items') ?></dt><dd><?= nl2br(e($a['featured_items'] ?: 'None listed')) ?></dd>
        <dt>Description</dt><dd><?= nl2br(e($a['description'] ?: 'None')) ?></dd>
        <?php if (($meta['group'] ?? '') === 'hoyo'): ?>
            <dt>Birthday binded</dt><dd><?= $a['birthday_binded'] ? 'Yes' : 'No' ?></dd>
            <dt>Username binded</dt><dd><?= $a['username_binded'] ? 'Yes' : 'No' ?></dd>
        <?php else: ?>
            <dt>Email binded</dt><dd><?= $a['email_binded'] ? 'Yes' : 'No' ?></dd>
        <?php endif; ?>
        <dt>Trade posted</dt><dd><?= e($a['trade_posted']) ?></dd>
        <dt>Trader</dt><dd><?= e($a['trader']) ?></dd>
    </dl>

    <?php if ($mine): ?>
        <p class="muted">This is your listing. Manage it from My Records.</p>
    <?php elseif ($a['status'] === 'sold'): ?>
        <p class="muted">This account has been sold.</p>
    <?php elseif ($myOffer === 'pending'): ?>
        <form method="post" class="row">
            <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
            <span class="badge badge-available">Offer sent</span>
            <button class="btn btn-danger">Cancel offer</button>
        </form>
    <?php else: ?>
        <form method="post" class="row">
            <?= csrf_field() ?><input type="hidden" name="action" value="offer">
            <button class="btn">Make offer</button>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/market/index.php">Back</a>
        </form>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
