<?php
session_start();
require_once('../config/db.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    die("Akses langsung ditolak!");
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    exit("Akses Ditolak");
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Permintaan tidak valid (CSRF Token mismatch).");
}

if (isset($_POST['add_member'])) {
    $user = strtolower(trim($_POST['new_user'])); 
    $password_raw = $_POST['new_pass'];
    $role = $_POST['new_role'];
    $errors = [];

    if (!preg_match('/^[a-z0-9]+$/', $user)) {
        $errors[] = "format_user";
    }

    $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt_check, "s", $user);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $errors[] = "duplicate_user";
    }

    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password_raw)) {
        $errors[] = "format_password";
    }

    if (!empty($errors)) {
        $error_string = implode(",", $errors);
        header("Location: ../admin_manage.php?status=error&types=" . $error_string);
        exit();
    }

    $pass_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $user, $pass_hashed, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin_manage.php?status=add_success");
    } else {
        header("Location: ../admin_manage.php?status=reset_failed");
    }
    exit();
}

if (isset($_POST['confirm_delete'])) {
    $id = $_POST['delete_id'];
    $stmt_select = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_select, "i", $id);
    mysqli_stmt_execute($stmt_select);
    $res = mysqli_stmt_get_result($stmt_select);
    $data = mysqli_fetch_assoc($res);
    
    if ($data) {
        $username_target = $data['username'];
        $target_folder = "../data/" . $username_target;

        $stmt_del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        
        if (mysqli_stmt_execute($stmt_del)) {
            if (is_dir($target_folder)) {
                shell_exec("rm -rf " . escapeshellarg($target_folder));
            }
            header("Location: ../admin_manage.php?status=delete_success");
        }
    }
    exit();
}

if (isset($_POST['reset_password'])) {
    $id = $_POST['user_id'];
    $password = $_POST['new_password'];

    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
        header("Location: ../admin_manage.php?status=invalid_password");
        exit();
    }

    $new_pass = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_pass, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../admin_manage.php?status=reset_success");
    } else {
        header("Location: ../admin_manage.php?status=reset_failed");
    }
    exit();
}
