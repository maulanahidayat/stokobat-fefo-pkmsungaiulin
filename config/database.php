<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "stokobat_fefo";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$base_url = "http://localhost/stokobat_fefo";

function base_url($path = '') {
    global $base_url;
    return $base_url . '/' . ltrim($path, '/');
}
?>
