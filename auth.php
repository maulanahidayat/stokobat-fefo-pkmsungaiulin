<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); 

    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['success'] = "Login berhasil! Selamat datang, " . $user['nama_lengkap'] . ".";

        header("Location: dashboard.php");
    } else {
        $_SESSION['error'] = "Username atau password salah!";
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>
