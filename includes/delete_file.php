<?php
session_start();
if (!isset($_SESSION['username'])) exit("Akses ditolak");

if (isset($_GET['name'])) {
    $user = $_SESSION['username'];
    $file_name = basename($_GET['name']);
    $file_path = "../data/" . $user . "/" . $file_name;

    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            header("Location: ../dashboard.php?status=deleted");
        } else {
            echo "Gagal menghapus file sistem.";
        }
    } else {
        header("Location: ../dashboard.php?status=not_found");
    }
}
