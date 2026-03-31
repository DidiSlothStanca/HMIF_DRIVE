<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hmif_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Maaf, terjadi gangguan koneksi ke sistem.");
}
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
mysqli_set_charset($conn, "utf8mb4");
?>
