<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name']); $email = trim($_POST['email']); $pw = $_POST['password'];
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) {
        flash('Valid name, email and 6+ char password required.', 'err');
    } else {
        $exists = $pdo->prepare('SELECT id FROM users WHERE email=?'); $exists->execute([$email]);
        if ($exists->fetch()) { flash('Email already registered.', 'err'); }
        else {
            // First registered user becomes admin
            $role = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() == 0 ? 'admin' : 'member';
            $pdo->prepare('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)')
                ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role]);
            flash("Registered as $role. Please login.");
            header('Location: login.php'); exit;
        }
    }
}
$f = flash();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register</title><link rel="stylesheet" href="css/style.css"></head><body>
<div class="card auth"><h2>Register</h2>
<?php if ($f): ?><div class="msg <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<input name="name" placeholder="Full name" required>
<input name="email" type="email" placeholder="Email" required>
<input name="password" type="password" placeholder="Password (6+ chars)" required>
<button>Register</button> <a href="login.php">Login</a></form></div></body></html>
