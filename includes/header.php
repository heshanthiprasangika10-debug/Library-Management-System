<?php require_once __DIR__ . '/../config.php'; login_required(); $f = flash(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Smart Library</title>
<link rel="stylesheet" href="css/style.css"></head><body>
<nav>
  <b>📚 Smart Library</b>
  <a href="dashboard.php">Dashboard</a>
  <a href="books.php">Books</a>
  <a href="loans.php"><?= is_admin() ? 'Loans' : 'My Loans' ?></a>
  <?php if (is_admin()): ?><a href="members.php">Members</a><?php endif; ?>
  <span class="right"><?= e($_SESSION['user']['name']) ?> (<?= e($_SESSION['user']['role']) ?>) <a href="logout.php">Logout</a></span>
</nav>
<main>
<?php if ($f): ?><div class="msg <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif; ?>
