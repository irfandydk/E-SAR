<?php 
// Mengaktifkan session php
session_start();

// Menghubungkan dengan koneksi
include 'config/koneksi.php';

// Menangkap data yang dikirim dari form
$username = $_POST['username'];
$password = md5($_POST['password']); // Hashing MD5 sesuai database awal

// Menyeleksi data user dengan username dan password yang sesuai
$data = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");

// Menghitung jumlah data yang ditemukan
$cek = mysqli_num_rows($data);

if($cek > 0){
    $row = mysqli_fetch_assoc($data);
    
    // Menyimpan data user ke dalam session
    $_SESSION['username'] = $username;
    $_SESSION['id_user']  = $row['id_user'];
    $_SESSION['nama']     = $row['nama_lengkap'];
    $_SESSION['role']     = $row['role']; // admin atau user
    $_SESSION['status']   = "login";

    // Alihkan ke halaman dashboard
    header("location:dashboard.php");
}else{
    // Alihkan kembali ke login jika gagal
    header("location:index.php?pesan=gagal");
}
?>