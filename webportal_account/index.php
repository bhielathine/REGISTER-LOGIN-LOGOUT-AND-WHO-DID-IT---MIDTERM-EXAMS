<?php
require_once __DIR__ . '/includes/functions.php';
require_login();   // not logged in -> redirected to login.php

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>
<h1>Welcome, <?= e($_SESSION['first_name'] ?? $_SESSION['username']) ?></h1>
<p class="muted">What do you want to do?</p>
<div class="grid two">
    <a class="glass card choice" href="<?= BASE_URL ?>/seller/records.php">
        <h2>Start selling</h2>
        <p>Keep records of your accounts, then publish them to the market.</p>
    </a>
    <a class="glass card choice" href="<?= BASE_URL ?>/market/index.php">
        <h2>View market</h2>
        <p>Browse available accounts and make an offer.</p>
    </a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
