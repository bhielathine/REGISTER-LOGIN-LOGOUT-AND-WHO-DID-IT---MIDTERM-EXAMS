<?php /* expects $pageTitle; functions.php already loaded by the page */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Account Portal') ?> | Account Portal</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="bg-orb orb-1"></div><div class="bg-orb orb-2"></div>
<header class="nav glass">
    <a class="brand" href="<?= BASE_URL ?>/<?= !empty($_SESSION['user_id']) ? 'index.php' : 'login.php' ?>">Account Portal</a>
    <?php if (!empty($_SESSION['user_id'])): ?>
    <nav>
        <a href="<?= BASE_URL ?>/index.php">Home</a>
        <a href="<?= BASE_URL ?>/seller/records.php">My Records</a>
        <a href="<?= BASE_URL ?>/market/index.php">Market</a>
        <a href="<?= BASE_URL ?>/members.php">Members</a>
        <a href="<?= BASE_URL ?>/activity.php">Activity</a>
        <a class="who" href="<?= BASE_URL ?>/profile.php"><?= e($_SESSION['username']) ?></a>
        <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/logout.php">Log out</a>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <?php foreach ($_SESSION['flash'] as [$type, $msg]): ?>
        <div class="alert alert-<?= $type === 'error' ? 'error' : 'success' ?>"><?= e($msg) ?></div>
    <?php endforeach; unset($_SESSION['flash']); ?>
<?php endif; ?>
