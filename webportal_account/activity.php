<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$rows = $pdo->query(
    'SELECT log_id, username, action, table_name, record_id, details, created_at
     FROM activity_log ORDER BY log_id DESC LIMIT 100'
)->fetchAll();

$pageTitle = 'Activity';
include __DIR__ . '/includes/header.php';
?>
<h1>Activity</h1>
<p class="muted">The 100 most recent inserts, updates, and deletes, and the user who did each one.</p>

<?php if (!$rows): ?>
    <div class="glass card center"><p>Nothing recorded yet.</p></div>
<?php else: ?>
<div class="glass card table-wrap">
<table>
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['created_at']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><span class="badge badge-<?= $r['action'] === 'DELETE' ? 'sold' : ($r['action'] === 'INSERT' ? 'available' : 'game') ?>"><?= e($r['action']) ?></span></td>
            <td><?= e($r['table_name']) ?></td>
            <td><?= e($r['record_id'] ?? '-') ?></td>
            <td><?= e($r['details']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
