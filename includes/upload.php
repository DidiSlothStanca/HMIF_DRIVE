<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    die("Akses langsung ditolak!");
}

session_start();
require_once('../config/db.php'); 

if (!isset($_SESSION['username'])) exit("Akses ditolak");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user = $_SESSION['username'];
    $target_dir = "../data/" . $user . "/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
        file_put_contents($target_dir . "index.php", ""); 
    }

    $files = $_FILES['file'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt', 'mp4', 'mp3', 'mov', 'mkv', 'tar', 'odt', 'wav', 'psd', 'xcf', 'pptx', 'ppt', 'svg', 'ico'];
    
    $success_count = 0;
    $error_status = "";

    foreach ($files['name'] as $key => $val) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $original_name = basename($files['name'][$key]);
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $file_size = $files['size'][$key];
            $tmp_name = $files['tmp_name'][$key];

            if (!in_array($file_extension, $allowed_ext)) {
                $error_status = "invalid_type";
                continue;
            }
/*
            if ($file_size > 10 * 1024 * 1024) {
                $error_status = "too_large";
                continue;
            }
 */
            $final_name = $original_name;
            if (file_exists($target_dir . $final_name)) {
                $final_name = time() . "_" . $key . "_" . $original_name;
            }

            $target_file = $target_dir . $final_name;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $success_count++;
            }
        }
    }

    if ($success_count > 0) {
        header("Location: ../dashboard.php?status=upload_success&count=" . $success_count);
    } else {
        header("Location: ../dashboard.php?status=" . ($error_status ?: "upload_failed"));
    }
    exit();
}
?>
