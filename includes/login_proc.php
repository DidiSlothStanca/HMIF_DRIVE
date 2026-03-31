<?php
session_start();
require_once('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    die("Akses langsung ditolak!");
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Permintaan tidak valid (CSRF Token mismatch).");
}

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['last_attempt_time'])) $_SESSION['last_attempt_time'] = 0;

$max_attempts = 3;
$lockout_time = 30;

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_passed = time() - $_SESSION['last_attempt_time'];

    if ($time_passed < $lockout_time) {
        $wait = $lockout_time - $time_passed;
        header("Location: ../index.php?error=too_many_attempts&wait=$wait");
        exit();
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = strtolower(trim($_POST['username']));
    $pass_input = $_POST['password'];

    if (!preg_match('/^[a-z0-9]+$/', $user_input)) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        header("Location: ../index.php?error=login_failed");
        exit();
    }

    $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $user_input);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($pass_input, $row['password'])) {
            
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);
            
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            session_regenerate_id(true);

            header("Location: ../dashboard.php");
            exit();
        }
    }

    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();

    header("Location: ../index.php?error=login_failed");
    exit();
}
