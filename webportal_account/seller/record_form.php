<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$uid = current_user_id();
$id  = (int)($_GET['id'] ?? $_POST['account_id'] ?? 0);
$rec = null;

if ($id) {
    $st = $pdo->prepare(
        'SELECT a.*, g.game_name FROM accounts a JOIN games g ON g.game_id = a.game_id
         WHERE a.account_id = ? AND a.seller_id = ?'
    );
    $st->execute([$id, $uid]);
    $rec = $st->fetch();
    if (!$rec) { flash('error', 'Record not found.'); redirect('/seller/records.php'); }
}

$games = $pdo->query('SELECT game_id, game_name FROM games ORDER BY game_id')->fetchAll();
$typedPassword = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $errors = [];

    // game: chosen on create, fixed on edit
    if ($rec) {
        $gameId   = (int)$rec['game_id'];
        $gameName = $rec['game_name'];
    } else {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $gameName = '';
        foreach ($games as $g) if ((int)$g['game_id'] === $gameId) $gameName = $g['game_name'];
    }
    $meta = game_meta($gameName);
    if (!$meta) $errors[] = 'Choose a game.';

    $pseudonym = trim($_POST['pseudonym'] ?? '');
    $price     = $_POST['selling_price'] ?? '';
    $level     = $_POST['account_level'] ?? '';
    $items     = trim($_POST['featured_items'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $notes     = trim($_POST['user_notes'] ?? '');
    $pwdInput  = $_POST['account_password'] ?? '';

    if ($pseudonym === '' || mb_strlen($pseudonym) > 100) $errors[] = 'Pseudonym is required (max 100 characters).';
    if (!is_numeric($price) || $price < 0 || $price > 99999999.99) $errors[] = 'Enter a valid selling price in pesos.';
    if ($meta) {
        if (!ctype_digit((string)$level) || $level < 1 || $level > $meta['level_max'])
            $errors[] = $meta['level_label'] . ' must be between 1 and ' . $meta['level_max'] . '.';
    }
    $rank = null;
    if ($meta && $meta['has_rank']) {
        $rank = $_POST['rank_tier'] ?? '';
        if (!in_array($rank, RANK_TIERS, true)) $errors[] = 'Choose a rank.';
    }
    if (!$rec && $pwdInput === '') $errors[] = 'Account password is required.';
    if (strlen($pwdInput) > 100)   $errors[] = 'Account password is too long (max 100 characters).';

    $bday  = ($meta && $meta['group'] === 'hoyo' && isset($_POST['birthday_binded'])) ? 1 : 0;
    $uname = ($meta && $meta['group'] === 'hoyo' && isset($_POST['username_binded'])) ? 1 : 0;
    $email = ($meta && $meta['group'] === 'ml'   && isset($_POST['email_binded']))    ? 1 : 0;

    if (!$errors) {
        $price = (float)$price;
        $level = (int)$level;
        if ($rec) {
            $enc = $pwdInput === '' ? $rec['account_password'] : encrypt_text($pwdInput);
            $pdo->prepare(
                'UPDATE accounts SET pseudonym=?, selling_price=?, account_level=?, rank_tier=?, featured_items=?, description=?,
                        birthday_binded=?, username_binded=?, email_binded=?, account_password=?, user_notes=?,
                        updated_by=?, last_updated=NOW()
                 WHERE account_id=? AND seller_id=?'
            )->execute([$pseudonym, $price, $level, $rank, $items, $desc, $bday, $uname, $email, $enc, $notes, $uid, $id, $uid]);
            log_activity('UPDATE', 'accounts', $id, "Edited $gameName record");
            flash('success', 'Record updated.');
        } else {
            $enc = encrypt_text($pwdInput);
            $pdo->prepare(
                'INSERT INTO accounts (seller_id, game_id, pseudonym, selling_price, account_level, rank_tier, featured_items, description,
                        birthday_binded, username_binded, email_binded, account_password, user_notes, added_by, updated_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$uid, $gameId, $pseudonym, $price, $level, $rank, $items, $desc, $bday, $uname, $email, $enc, $notes, $uid, $uid]);
            log_activity('INSERT', 'accounts', (int)$pdo->lastInsertId(), "Added $gameName record");
            flash('success', 'Record added.');
        }
        redirect('/seller/records.php');
    }
    foreach ($errors as $err) flash('error', $err);
    // keep what the user typed
    $rec = array_merge($rec ?? [], [
        'game_id' => $gameId, 'game_name' => $gameName, 'pseudonym' => $pseudonym, 'selling_price' => $_POST['selling_price'] ?? '',
        'account_level' => $_POST['account_level'] ?? '', 'rank_tier' => $_POST['rank_tier'] ?? '',
        'featured_items' => $items, 'description' => $desc, 'user_notes' => $notes,
        'birthday_binded' => $bday, 'username_binded' => $uname, 'email_binded' => $email,
    ]);
    $typedPassword = $pwdInput;
}

$isEdit   = $id > 0;
$curGame  = $rec['game_name'] ?? '';
$curPass  = $typedPassword ?? (($isEdit && !empty($rec['account_password'])) ? decrypt_text($rec['account_password']) : '');
$pageTitle = $isEdit ? 'Edit record' : 'Add record';
include __DIR__ . '/../includes/header.php';
?>
<script>window.GAME_META = <?= json_encode(game_meta_all(), JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<h1><?= $isEdit ? 'Edit record' : 'Add record' ?></h1>

<form method="post" class="glass card form-grid" id="recordForm" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="account_id" value="<?= $id ?>">

    <label>Game
        <?php if ($isEdit): ?>
            <input type="text" value="<?= e($curGame) ?>" disabled>
            <input type="hidden" id="gameName" value="<?= e($curGame) ?>">
        <?php else: ?>
            <select name="game_id" id="gameSelect" required>
                <option value="">Choose a game</option>
                <?php foreach ($games as $g): ?>
                    <option value="<?= (int)$g['game_id'] ?>" data-name="<?= e($g['game_name']) ?>"
                        <?= (int)($rec['game_id'] ?? 0) === (int)$g['game_id'] ? 'selected' : '' ?>><?= e($g['game_name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </label>

    <label>Account pseudonym
        <input type="text" name="pseudonym" maxlength="100" required value="<?= e($rec['pseudonym'] ?? '') ?>">
    </label>

    <label>Selling price (₱)
        <input type="number" name="selling_price" min="0" step="0.01" required value="<?= e($rec['selling_price'] ?? '') ?>">
    </label>

    <label><span id="levelLabel">Level</span>
        <input type="number" name="account_level" id="levelInput" min="1" max="300" required value="<?= e($rec['account_level'] ?? '') ?>">
    </label>

    <label data-group="ml">Current rank
        <select name="rank_tier">
            <?php foreach (RANK_TIERS as $t): ?>
                <option value="<?= e($t) ?>" <?= ($rec['rank_tier'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="full"><span id="itemsLabel">Featured items</span>
        <textarea name="featured_items" rows="2"><?= e($rec['featured_items'] ?? '') ?></textarea>
    </label>

    <label class="full">Additional description
        <textarea name="description" rows="4"><?= e($rec['description'] ?? '') ?></textarea>
    </label>

    <div class="full checks">
        <label class="check" data-group="hoyo"><input type="checkbox" name="birthday_binded" <?= !empty($rec['birthday_binded']) ? 'checked' : '' ?>> Birthday binded</label>
        <label class="check" data-group="hoyo"><input type="checkbox" name="username_binded" <?= !empty($rec['username_binded']) ? 'checked' : '' ?>> Username binded</label>
        <label class="check" data-group="ml"><input type="checkbox" name="email_binded" <?= !empty($rec['email_binded']) ? 'checked' : '' ?>> Email binded</label>
    </div>

    <h2 class="full">Seller only <span class="muted small">(hidden from the market)</span></h2>

    <label>Account password
        <span class="pw">
            <input type="password" name="account_password" id="pwInput" maxlength="100"
                   value="<?= e($curPass) ?>" <?= $isEdit ? '' : 'required' ?>>
            <button type="button" class="btn btn-sm btn-ghost" id="pwToggle">Show</button>
        </span>
    </label>

    <label class="full">User notes
        <textarea name="user_notes" rows="3"><?= e($rec['user_notes'] ?? '') ?></textarea>
    </label>

    <div class="full row">
        <button class="btn"><?= $isEdit ? 'Save changes' : 'Add record' ?></button>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/seller/records.php">Cancel</a>
    </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
