<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$uid = current_user_id();

$st = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$st->execute([$uid]);
$user = $st->fetch();
if (!$user) { session_destroy(); redirect('/login.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ----- UPDATE my information ----- */
    if ($action === 'update') {
        [$data, $errors] = validate_person($_POST);
        $newPw = $_POST['new_password'] ?? '';
        if ($newPw !== '') {
            if (strlen($newPw) < 8) $errors[] = 'New password must be at least 8 characters.';
            if ($newPw !== ($_POST['confirm'] ?? '')) $errors[] = 'New passwords do not match.';
        }
        if (!$errors) {
            $sql = 'UPDATE users SET first_name=?, last_name=?, age=?, address=?, contact_number=?, email=?, updated_by=?, last_updated=NOW()';
            $params = [
                $data['first_name'], $data['last_name'], (int)$data['age'],
                $data['address'] !== '' ? $data['address'] : null,
                $data['contact_number'] !== '' ? $data['contact_number'] : null,
                $data['email'] !== '' ? $data['email'] : null,
                $uid,
            ];
            if ($newPw !== '') { $sql .= ', password_hash=?'; $params[] = password_hash($newPw, PASSWORD_DEFAULT); }
            $sql .= ' WHERE user_id=?';
            $params[] = $uid;
            $pdo->prepare($sql)->execute($params);
            $_SESSION['first_name'] = $data['first_name'];
            log_activity('UPDATE', 'users', $uid, "Updated profile of '{$user['username']}'");
            flash('success', 'Profile updated.');
            redirect('/profile.php');
        }
        foreach ($errors as $err) flash('error', $err);
        $user = array_merge($user, $_POST);   // keep what was typed

    /* ----- CLOSE ACCOUNT (DELETE the parent record) ----- */
    } elseif ($action === 'close') {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $st->execute([$uid]);
        $hash = $st->fetchColumn();

        if (!$hash || !password_verify($_POST['password'] ?? '', $hash)) {
            flash('error', 'Wrong password. Your account was not closed.');
        } else {
            $st = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE seller_id = ?');
            $st->execute([$uid]);
            $n = (int)$st->fetchColumn();

            $pdo->beginTransaction();
            log_activity('DELETE', 'users', $uid, "Closed account '{$user['username']}' ($n account record(s) removed)");
            $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$uid]);   // cascades to accounts + offers
            $pdo->commit();

            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Your account was closed and all its records were deleted.');
            redirect('/login.php');
        }
    }
}

$st = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE seller_id = ?');
$st->execute([$uid]);
$recordCount = (int)$st->fetchColumn();

$pageTitle = 'Profile';
include __DIR__ . '/includes/header.php';
$v = $user;
?>
<h1>Profile</h1>

<form method="post" autocomplete="off" class="glass card form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <label class="full">Username
        <input type="text" value="<?= e($user['username']) ?>" disabled>
    </label>
    <?php include __DIR__ . '/includes/person_fields.php'; ?>

    <h2 class="full">Change password <span class="muted small">(leave blank to keep it)</span></h2>
    <label>New password
        <input type="password" name="new_password" minlength="8">
    </label>
    <label>Confirm new password
        <input type="password" name="confirm" minlength="8">
    </label>
    <div class="full"><button class="btn">Save changes</button></div>
</form>

<section class="glass card danger-zone" id="close">
    <h2>Close account</h2>
    <p class="muted">You have <strong><?= $recordCount ?></strong> account record(s). Closing your account deletes your user, all of those records, and your offers. This cannot be undone.</p>
    <form method="post" data-confirm="Close your account and delete everything permanently?">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="close">
        <label>Enter your password to confirm
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-danger">Close account</button>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
