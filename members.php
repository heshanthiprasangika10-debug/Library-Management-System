<?php
require 'includes/header.php'; admin_required();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='member'")->execute([(int)$_POST['id']]);
    flash('Member removed.'); header('Location: members.php'); exit;
}
$rows = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM loans l WHERE l.user_id=u.id AND l.return_date IS NULL) AS active
                     FROM users u ORDER BY u.role, u.name")->fetchAll();
?>
<h2>Members</h2>
<table><tr><th>Name</th><th>Email</th><th>Role</th><th>Active Loans</th><th></th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= $r['active'] ?></td>
<td><?php if ($r['role'] === 'member'): ?>
<form method="post" onsubmit="return confirm('Remove member?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="red">Remove</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?></table>
<?php require 'includes/footer.php'; ?>
