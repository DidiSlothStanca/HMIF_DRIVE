<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_POST['files'])) {
    header("Location: ../dashboard.php");
    exit();
}

$user = htmlspecialchars($_SESSION['username']);
$user_dir = "../data/" . $user;
$files_to_delete = $_POST['files'];
$deleted_count = 0;

foreach ($files_to_delete as $file) {
    $file = str_replace(['../', '..\\'], '', $file); // Security check
    $path = $user_dir . "/" . $file;
    
    if (file_exists($path) && $file !== 'index.php') {
        if (unlink($path)) {
            $deleted_count++;
        }
    }
}

header("Location: ../dashboard.php?status=deleted&count=" . $deleted_count);
exit();
