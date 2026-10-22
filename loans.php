<?php
require_once 'config.php'; login_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    try {
        $pdo->beginTransaction();
        if ($action === 'issue') {
            $book_id = (int)$_POST['book_id'];
            $user_id = is_admin() ? (int)$_POST['user_id'] : $_SESSION['user']['id'];

            $b = $pdo->prepare('SELECT available_copies FROM books WHERE id=? FOR UPDATE'); $b->execute([$book_id]);
            $book = $b->fetch();
            if (!$book || $book['available_copies'] < 1) throw new Exception('Book not available.');

            $c = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE user_id=? AND return_date IS NULL'); $c->execute([$user_id]);
            if ($c->fetchColumn() >= MAX_LOANS) throw new Exception('Max ' . MAX_LOANS . ' active loans reached.');

            $d = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE user_id=? AND book_id=? AND return_date IS NULL'); $d->execute([$user_id, $book_id]);
            if ($d->fetchColumn() > 0) throw new Exception('You already have this book.');

            $pdo->prepare('INSERT INTO loans(book_id,user_id,issue_date,due_date) VALUES(?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL ? DAY))')
                ->execute([$book_id, $user_id, LOAN_DAYS]);
            $pdo->prepare('UPDATE books SET available_copies=available_copies-1 WHERE id=?')->execute([$book_id]);
            flash('Book issued. Due in ' . LOAN_DAYS . ' days.');
        } elseif ($action === 'return') {
            admin_required();
            $l = $pdo->prepare('SELECT *, GREATEST(DATEDIFF(CURDATE(), due_date),0) AS late FROM loans WHERE id=? AND return_date IS NULL FOR UPDATE');
            $l->execute([(int)$_POST['loan_id']]);
            $loan = $l->fetch();
            if (!$loan) throw new Exception('Loan not found.');
            $fine = $loan['late'] * FINE_PER_DAY;
            $pdo->prepare('UPDATE loans SET return_date=CURDATE(), fine=? WHERE id=?')->execute([$fine, $loan['id']]);
            $pdo->prepare('UPDATE books SET available_copies=available_copies+1 WHERE id=?')->execute([$loan['book_id']]);
            flash($fine > 0 ? "Returned. Fine: Rs. $fine ({$loan['late']} days late)" : 'Returned on time. No fine.');
        }
        $pdo->commit();
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($ex->getMessage(), 'err');
    }
    header('Location: loans.php'); exit;
}

require 'includes/header.php';
$sql = 'SELECT l.*, b.title, u.name, GREATEST(DATEDIFF(CURDATE(), l.due_date),0) AS late
        FROM loans l JOIN books b ON b.id=l.book_id JOIN users u ON u.id=l.user_id';
if (is_admin()) { $st = $pdo->query("$sql ORDER BY l.return_date IS NOT NULL, l.due_date"); }
else { $st = $pdo->prepare("$sql WHERE l.user_id=? ORDER BY l.return_date IS NOT NULL, l.due_date"); $st->execute([$_SESSION['user']['id']]); }
$loans = $st->fetchAll();
?>
<h2><?= is_admin() ? 'All Loans' : 'My Loans' ?></h2>

<?php if (is_admin()):
  $books = $pdo->query('SELECT id,title FROM books WHERE available_copies>0 ORDER BY title')->fetchAll();
  $members = $pdo->query("SELECT id,name,email FROM users WHERE role='member' ORDER BY name")->fetchAll(); ?>
<div class="card"><h3>Issue Book</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="issue">
<div class="row">
<select name="user_id" required><option value="">Select member</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['email']) ?>)</option><?php endforeach; ?></select>
<select name="book_id" required><option value="">Select book</option><?php foreach ($books as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['title']) ?></option><?php endforeach; ?></select>
</div><button class="green">Issue</button></form></div>
<?php endif; ?>

<table>
<tr><?php if (is_admin()): ?><th>Member</th><?php endif; ?><th>Book</th><th>Issued</th><th>Due</th><th>Status</th><th>Fine</th><?php if (is_admin()): ?><th></th><?php endif; ?></tr>
<?php foreach ($loans as $l): $active = !$l['return_date']; $over = $active && $l['late'] > 0; ?>
<tr class="<?= $over ? 'overdue' : '' ?>">
<?php if (is_admin()): ?><td><?= e($l['name']) ?></td><?php endif; ?>
<td><?= e($l['title']) ?></td><td><?= $l['issue_date'] ?></td><td><?= $l['due_date'] ?></td>
<td><?= $active ? ($over ? "Overdue ({$l['late']}d)" : 'Borrowed') : 'Returned ' . $l['return_date'] ?></td>
<td>Rs. <?= $active ? $l['late'] * FINE_PER_DAY : number_format($l['fine'], 2) ?></td>
<?php if (is_admin()): ?><td><?php if ($active): ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="return"><input type="hidden" name="loan_id" value="<?= $l['id'] ?>"><button>Return</button></form>
<?php endif; ?></td><?php endif; ?>
</tr>
<?php endforeach; if (!$loans): ?><tr><td colspan="7">No loans yet.</td></tr><?php endif; ?>
</table>
<?php require 'includes/footer.php'; ?>
