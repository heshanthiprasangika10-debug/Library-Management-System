<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare('SELECT * FROM users WHERE email=?'); $st->execute([trim($_POST['email'])]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'], $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
        header('Location: dashboard.php'); exit;
    }
    flash('Invalid email or password.', 'err');
}
$f = flash();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login</title><link rel="stylesheet" href="css/style.css"></head><body>
<div class="card auth"><h2>📚 Smart Library Login</h2>
<?php if ($f): ?><div class="msg <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<input name="email" type="email" placeholder="Email" required>
<input name="password" type="password" placeholder="Password" required>
<button>Login</button> <a href="register.php">Register</a></form></div></body></html>
