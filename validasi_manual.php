<?php
// File: sarsip/validasi_manual.php
include 'config/koneksi.php';

if(isset($_GET['kode'])){
    $kode = mysqli_real_escape_string($koneksi, $_GET['kode']);
    
    // 1. Cek Apakah ini TOKEN (QR Token)
    // Cek di Documents (Internal)
    $q1 = mysqli_query($koneksi, "SELECT qr_token FROM doc_signers WHERE qr_token = '$kode'");
    if(mysqli_num_rows($q1) > 0){ header("location:validasi.php?token=$kode"); exit; }


    // 2. Cek Apakah ini NOMOR SURAT
    // Internal
    $q4 = mysqli_query($koneksi, "SELECT id_doc, signed_file_hash, file_hash FROM documents WHERE nomor_surat = '$kode'");
    if(mysqli_num_rows($q4) > 0){
        $d = mysqli_fetch_assoc($q4);
        // Jika ada versi signed, prioritaskan
        header("location:validasi.php?doc_id=" . $d['id_doc']); exit;
    }


    // Jika Tidak Ditemukan
    header("location:validasi.php?status=not_found&code=".urlencode($kode));
} else {
    header("location:index.php");
}
?>