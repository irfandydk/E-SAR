<?php
// File: sarsip/proses_surat_masuk.php
session_start();
include 'config/koneksi.php';

// Cek Login & Role (Hanya Admin yang boleh akses)
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != 'admin'){
    header("location:login.php?pesan=belum_login"); exit;
}

// ============================================================
// 1. PROSES SIMPAN SURAT MASUK
// ============================================================
if(isset($_POST['simpan_surat'])){
    
    // Tangkap Input
    $nomor      = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $asal       = mysqli_real_escape_string($koneksi, $_POST['asal_surat']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $tgl_terima = mysqli_real_escape_string($koneksi, $_POST['tgl_terima']);
    $visibility = mysqli_real_escape_string($koneksi, $_POST['visibility']); // public / private
    
    // Validasi File
    if(empty($_FILES['file_surat']['name'])){
        echo "<script>alert('File tidak boleh kosong!'); window.location='surat_masuk.php';</script>"; exit;
    }

    $filename  = $_FILES['file_surat']['name'];
    $tmp_name  = $_FILES['file_surat']['tmp_name'];
    $file_size = $_FILES['file_surat']['size'];
    $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Validasi Ekstensi & Ukuran
    if($ext != 'pdf'){
        echo "<script>alert('Hanya file PDF yang diperbolehkan!'); window.location='surat_masuk.php';</script>"; exit;
    }
    if($file_size > 10485760){ // 10 MB
        echo "<script>alert('Ukuran file terlalu besar (Maks 10MB)!'); window.location='surat_masuk.php';</script>"; exit;
    }

    // Siapkan Folder
    $target_dir = "uploads/arsip_lain/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    // Rename File Unik: IN_TAHUN_ACAK.pdf
    $new_filename = "IN_" . date('YmdHis') . "_" . rand(100,999) . ".pdf";
    $path_upload  = $target_dir . $new_filename;

    // Proses Upload
    if(move_uploaded_file($tmp_name, $path_upload)){
        
        $file_hash = hash_file('sha256', $path_upload);

        $query = "INSERT INTO incoming_mail (nomor_surat, asal_surat, judul, tgl_terima, file_path, file_hash, visibility, created_at)
                  VALUES ('$nomor', '$asal', '$judul', '$tgl_terima', '$new_filename', '$file_hash', '$visibility', NOW())";
        
        if(mysqli_query($koneksi, $query)){
            // PERBAIKAN: Redirect ke surat_masuk.php (Bukan surat_arsip.php)
            header("location:surat_masuk.php?pesan=sukses");
        } else {
            echo "<script>alert('Database Error: ".mysqli_error($koneksi)."'); window.location='surat_masuk.php';</script>";
        }

    } else {
        echo "<script>alert('Gagal mengupload file ke server.'); window.location='surat_masuk.php';</script>";
    }
}

// ============================================================
// 2. PROSES HAPUS (Backup Logic jika diakses via GET langsung)
// ============================================================
// Note: Logic hapus utama sebenarnya sudah ada di dalam file surat_masuk.php (function hapus_surat),
// tapi kode ini tetap disiapkan untuk handling request eksternal jika diperlukan.

elseif(isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    $q = mysqli_query($koneksi, "SELECT file_path FROM incoming_mail WHERE id_mail='$id'");
    if(mysqli_num_rows($q) > 0){
        $d = mysqli_fetch_assoc($q);
        $file = "uploads/arsip_lain/" . $d['file_path'];
        if(file_exists($file)) unlink($file);
        
        mysqli_query($koneksi, "DELETE FROM incoming_mail WHERE id_mail='$id'");
        
        // PERBAIKAN: Redirect ke surat_masuk.php
        header("location:surat_masuk.php?pesan=hapus_sukses");
    }
}

else {
    header("location:surat_masuk.php");
}
?>