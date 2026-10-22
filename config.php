<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=library_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

define('LOAN_DAYS', 14);     // loan period
define('FINE_PER_DAY', 10);  // Rs. per overdue day
define('MAX_LOANS', 3);      // max active loans per member

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function is_admin() { return ($_SESSION['user']['role'] ?? '') === 'admin'; }
function login_required() { if (empty($_SESSION['user'])) { header('Location: login.php'); exit; } }
function admin_required() { login_required(); if (!is_admin()) { http_response_code(403); exit('Access denied'); } }
function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request'); }
}
