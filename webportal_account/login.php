<?php
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['user_id'])) redirect('/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_SESSION['locked_until'] ?? 0) > time()) {
        flash('error', 'Too many failed attempts. Wait a minute and try again.');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $st = $pdo->prepare('SELECT user_id, username, first_name, password_hash FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int)$u['user_id'];
            $_SESSION['username']   = $u['username'];
            $_SESSION['first_name'] = $u['first_name'];
            $_SESSION['fails'] = 0;
            redirect('/index.php');
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['locked_until'] = time() + 60; $_SESSION['fails'] = 0; }
        flash('error', 'Wrong username or password.');
    }
}

$pageTitle = 'Log in';
include __DIR__ . '/includes/header.php';
?>
<section class="glass card narrow">
    <h1>Log in</h1>
    <p class="muted">Keep records of your game accounts and sell them on the market.</p>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <label>Username
            <input type="text" name="username" required maxlength="50" autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-block">Log in</button>
    </form>
    <p class="muted center">No account yet? <a href="<?= BASE_URL ?>/register.php">Register</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
