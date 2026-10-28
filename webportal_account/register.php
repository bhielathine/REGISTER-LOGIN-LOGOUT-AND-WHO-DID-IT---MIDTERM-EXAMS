<?php
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['user_id'])) redirect('/index.php');

$v = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v = $_POST;
    [$data, $errors] = validate_person($_POST);
    $username = trim($_POST['username'] ?? '');
    $errors = array_merge($errors, validate_credentials($username, $_POST['password'] ?? '', $_POST['confirm'] ?? ''));

    if (!$errors) {
        try {
            create_user($data, $username, $_POST['password'], null);   // null = self-registered
            flash('success', 'Account created. You can log in now.');
            redirect('/login.php');
        } catch (PDOException $ex) {
            $errors[] = 'Could not create the account. The username may already be taken.';
        }
    }
    foreach ($errors as $err) flash('error', $err);
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<section class="glass card medium">
    <h1>Create account</h1>
    <form method="post" autocomplete="off" class="form-grid">
        <?= csrf_field() ?>
        <?php include __DIR__ . '/includes/person_fields.php'; ?>

        <h2 class="full">Login details</h2>
        <label class="full">Username
            <input type="text" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" value="<?= e($v['username'] ?? '') ?>">
        </label>
        <label>Password
            <input type="password" name="password" required minlength="8">
        </label>
        <label>Confirm password
            <input type="password" name="confirm" required minlength="8">
        </label>
        <div class="full"><button class="btn btn-block">Register</button></div>
    </form>
    <p class="muted center">Already registered? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
