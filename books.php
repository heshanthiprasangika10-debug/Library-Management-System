<?php
require 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_required(); csrf_check();
    if ($_POST['action'] === 'add') {
        $copies = max(1, (int)$_POST['copies']);
        $pdo->prepare('INSERT INTO books(title,author,isbn,category,total_copies,available_copies) VALUES(?,?,?,?,?,?)')
            ->execute([trim($_POST['title']), trim($_POST['author']), trim($_POST['isbn']), trim($_POST['category']), $copies, $copies]);
        flash('Book added.');
    } elseif ($_POST['action'] === 'delete') {
        $pdo->prepare('DELETE FROM books WHERE id=?')->execute([(int)$_POST['id']]);
        flash('Book deleted.');
    }
    header('Location: books.php'); exit;
}

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ? ORDER BY title');
$st->execute(array_fill(0, 4, "%$q%"));
$books = $st->fetchAll();
?>
<h2>Books</h2>
<form method="get" class="row"><input name="q" value="<?= e($q) ?>" placeholder="Search title, author, ISBN, category"><button>Search</button></form>

<?php if (is_admin()): ?>
<div class="card"><h3>Add Book</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
<div class="row"><input name="title" placeholder="Title" required><input name="author" placeholder="Author" required></div>
<div class="row"><input name="isbn" placeholder="ISBN"><input name="category" placeholder="Category"><input name="copies" type="number" min="1" value="1"></div>
<button class="green">Add</button></form></div>
<?php endif; ?>

<table>
<tr><th>Title</th><th>Author</th><th>Category</th><th>Available</th><th>Action</th></tr>
<?php foreach ($books as $b): ?>
<tr><td><?= e($b['title']) ?><br><small><?= e($b['isbn']) ?></small></td><td><?= e($b['author']) ?></td><td><?= e($b['category']) ?></td>
<td><?= $b['available_copies'] ?> / <?= $b['total_copies'] ?></td>
<td>
<?php if (!is_admin() && $b['available_copies'] > 0): ?>
  <form method="post" action="loans.php" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="issue"><input type="hidden" name="book_id" value="<?= $b['id'] ?>"><button>Borrow</button></form>
<?php endif; ?>
<?php if (is_admin()): ?>
  <form method="post" class="inline" onsubmit="return confirm('Delete this book?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="red">Delete</button></form>
<?php endif; ?>
</td></tr>
<?php endforeach; if (!$books): ?><tr><td colspan="5">No books found.</td></tr><?php endif; ?>
</table>
<?php require 'includes/footer.php'; ?>
