<?php
// File: sarsip/validasi_file.php
session_start();
include 'config/koneksi.php';

if(isset($_POST['cek_validasi'])){
    
    // Cek apakah ada file yang diupload
    if(empty($_FILES['file_dokumen']['name'])){
        header("location:index.php?pesan=kosong");
        exit;
    }

    $tmp_file = $_FILES['file_dokumen']['tmp_name'];
    
    // 1. Generate Hash SHA256 dari file yang diupload
    $file_hash = hash_file('sha256', $tmp_file);

    // =========================================================
    // PENCARIAN DI BERBAGAI TABEL
    // =========================================================

    // A. CEK DI TABEL DOCUMENTS (Internal - Signed Version)
    $q1 = mysqli_query($koneksi, "SELECT id_doc FROM documents WHERE signed_file_hash = '$file_hash'");
    if(mysqli_num_rows($q1) > 0){
        $d = mysqli_fetch_assoc($q1);
        header("location:validasi.php?doc_id=" . $d['id_doc']);
        exit;
    }

    // B. CEK DI TABEL DOCUMENTS (Internal - Original Version)
    $q2 = mysqli_query($koneksi, "SELECT id_doc FROM documents WHERE file_hash = '$file_hash'");
    if(mysqli_num_rows($q2) > 0){
        $d = mysqli_fetch_assoc($q2);
        header("location:validasi.php?doc_id=" . $d['id_doc']);
        exit;
    }

    
    // =========================================================
    // JIKA TIDAK DITEMUKAN DIMANAPUN
    // =========================================================
    header("location:validasi.php?status=not_found");

} else {
    header("location:index.php");
}
?>