<?php
require 'includes/header.php';
if (is_admin()) {
    $s = [
        'Total Books'   => $pdo->query('SELECT COALESCE(SUM(total_copies),0) FROM books')->fetchColumn(),
        'Members'       => $pdo->query("SELECT COUNT(*) FROM users WHERE role='member'")->fetchColumn(),
        'Active Loans'  => $pdo->query('SELECT COUNT(*) FROM loans WHERE return_date IS NULL')->fetchColumn(),
        'Overdue'       => $pdo->query('SELECT COUNT(*) FROM loans WHERE return_date IS NULL AND due_date < CURDATE()')->fetchColumn(),
        'Fines Collected (Rs.)' => $pdo->query('SELECT COALESCE(SUM(fine),0) FROM loans')->fetchColumn(),
    ];
} else {
    $uid = $_SESSION['user']['id'];
    $q = fn($sql) => $pdo->prepare($sql);
    $a = $q('SELECT COUNT(*) FROM loans WHERE user_id=? AND return_date IS NULL'); $a->execute([$uid]);
    $o = $q('SELECT COUNT(*) FROM loans WHERE user_id=? AND return_date IS NULL AND due_date < CURDATE()'); $o->execute([$uid]);
    $s = ['Books Borrowed' => $a->fetchColumn(), 'Overdue' => $o->fetchColumn()];
}
?>
<h2>Dashboard</h2>
<div class="stats"><?php foreach ($s as $k => $v): ?><div class="stat"><b><?= e($v) ?></b><?= e($k) ?></div><?php endforeach; ?></div>
<div class="card">Loan period: <b><?= LOAN_DAYS ?> days</b> · Fine: <b>Rs. <?= FINE_PER_DAY ?>/day</b> · Max loans: <b><?= MAX_LOANS ?></b></div>
<?php require 'includes/footer.php'; ?>
