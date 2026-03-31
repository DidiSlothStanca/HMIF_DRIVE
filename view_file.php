<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

if (isset($_GET['file'])) {
    $user = $_SESSION['username'];
    $fileName = basename($_GET['file']);
    $filePath = "data/" . $user . "/" . $fileName;

    if (file_exists($filePath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        header("Content-Type: " . $mimeType);
        header("Content-Length: " . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
header("HTTP/1.1 404 Not Found");
?>
