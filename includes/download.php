<?php
session_start();
if (!isset($_SESSION['username'])) exit();

require_once('../config/db.php');
$user = $_SESSION['username'];
$file = $_GET['file'] ?? '';

$file = basename($file);
$filePath = "../data/" . $user . "/" . $file;

if (file_exists($filePath) && $file !== 'index.php') {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    echo "File tidak ditemukan.";
}
?>
