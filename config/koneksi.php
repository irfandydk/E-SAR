<?php
// File: /config/koneksi.php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_sarsip";

// Melakukan koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Set base URL agar mudah memanggil link (Sesuaikan jika port diubah)
$base_url = "http://localhost/sarsip/";

// --- AMBIL CONFIG LOGO ---
// Query data pengaturan (ID 1)
$q_set = mysqli_query($koneksi, "SELECT * FROM app_settings WHERE id=1");
$d_set = mysqli_fetch_assoc($q_set);

// Tentukan path logo
$logo_db = $d_set['logo_path'];
$app_name = $d_set['nama_aplikasi'];

// Cek apakah file fisik ada? Jika tidak, pakai placeholder
if(!empty($logo_db) && file_exists(__DIR__ . "/../assets/" . $logo_db)){
    $logo_url = "assets/" . $logo_db;
} else {
    // Logo Default jika belum upload (bisa ganti link gambar online atau biarkan kosong)
    $logo_url = "https://via.placeholder.com/150?text=LOGO"; 
}
// Tambahkan timestamp agar browser merefresh cache saat logo diganti
$logo_url .= "?t=".time();

?>
