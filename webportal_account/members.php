<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$me = current_user_id();

$rows = $pdo->query(
    'SELECT u.user_id, u.first_name, u.last_name, u.username, u.age, u.added_by, u.last_updated,
            ab.username AS added_by_name, ub.username AS updated_by_name
     FROM users u
     LEFT JOIN users ab ON ab.user_id = u.added_by
     LEFT JOIN users ub ON ub.user_id = u.updated_by
     ORDER BY u.user_id'
)->fetchAll();

$pageTitle = 'Members';
include __DIR__ . '/includes/header.php';
?>
<div class="row between">
    <div>
        <h1>Members</h1>
        <p class="muted">Every user, with who added them and who last updated them.</p>
    </div>
    <a class="btn" href="<?= BASE_URL ?>/member_form.php">Add member</a>
</div>

<div class="glass card table-wrap">
<table>
    <thead>
        <tr><th>ID</th><th>Name</th><th>Username</th><th>Age</th><th>Added by</th><th>Last updated by</th><th>Last updated</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): $self = (int)$r['user_id'] === $me; ?>
        <tr>
            <td><?= (int)$r['user_id'] ?></td>
            <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?><?= $self ? ' <span class="muted small">(you)</span>' : '' ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= (int)$r['age'] ?></td>
            <td><?= (int)$r['added_by'] === (int)$r['user_id'] ? 'Self-registered' : e(who($r['added_by_name'])) ?></td>
            <td><?= e(who($r['updated_by_name'])) ?></td>
            <td><?= e($r['last_updated']) ?></td>
            <td class="actions">
                <?php if ($self): ?>
                    <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/profile.php">Edit</a>
                    <a class="btn btn-sm btn-danger" href="<?= BASE_URL ?>/profile.php#close">Close account</a>
                <?php else: ?>
                    <span class="muted small">-</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
