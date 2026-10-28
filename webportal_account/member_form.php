<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$v = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v = $_POST;
    [$data, $errors] = validate_person($_POST);
    $username = trim($_POST['username'] ?? '');
    $errors = array_merge($errors, validate_credentials($username, $_POST['password'] ?? '', $_POST['confirm'] ?? ''));

    if (!$errors) {
        try {
            create_user($data, $username, $_POST['password'], current_user_id());   // added_by = me
            flash('success', "Member '$username' added.");
            redirect('/members.php');
        } catch (PDOException $ex) {
            $errors[] = 'Could not add the member. The username may already be taken.';
        }
    }
    foreach ($errors as $err) flash('error', $err);
}

$pageTitle = 'Add member';
include __DIR__ . '/includes/header.php';
?>
<h1>Add member</h1>
<form method="post" autocomplete="off" class="glass card form-grid">
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
    <div class="full row">
        <button class="btn">Add member</button>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/members.php">Cancel</a>
    </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
