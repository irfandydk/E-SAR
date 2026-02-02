<?php
session_start();
include 'config/koneksi.php';

// Pastikan user sudah login
if($_SESSION['status'] != "login"){
    header("location:login.php");
    exit;
}

if(isset($_POST['upload'])){
    
    // 1. TANGKAP DATA TEXT
    $id_uploader = $_SESSION['id_user'];
    $judul       = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $nomor_surat = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $kategori    = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    // Penandatangan (Array)
    $penandatangan = isset($_POST['penandatangan']) ? $_POST['penandatangan'] : [];

    // Validasi sederhana: Penandatangan wajib dipilih
    if(empty($penandatangan)){
        header("location:data_dokumen.php?pesan=gagal_no_signer");
        exit;
    }

    // 2. PROSES UPLOAD FILE
    $rand = rand();
    $filename = $_FILES['file_dokumen']['name'];
    $ukuran = $_FILES['file_dokumen']['size'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Cek ekstensi harus PDF
    if(strtolower($ext) != 'pdf'){
        header("location:data_dokumen.php?pesan=gagal_ekstensi");
        exit;
    }

    // Nama file baru (Internal)
    $new_filename = "INT_" . $rand . "_" . preg_replace("/[^a-zA-Z0-9]/", "", $nomor_surat) . "." . $ext;
    
    // Pastikan folder ada
    $target_dir = "uploads/doc_asli/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Pindahkan file
    if(move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $target_dir . $new_filename)){
        
        // 3. HITUNG HASH FILE (UNTUK VALIDASI)
        $file_hash = hash_file('sha256', $target_dir . $new_filename);

        // 4. SIMPAN KE TABEL DOCUMENTS
        $query_doc = "INSERT INTO documents (uploader_id, judul, nomor_surat, kategori, file_path, file_hash, created_at) 
                      VALUES ('$id_uploader', '$judul', '$nomor_surat', '$kategori', '$new_filename', '$file_hash', NOW())";
        
        if(mysqli_query($koneksi, $query_doc)){
            
            // Ambil ID Dokumen yang barusan dibuat
            $id_doc_baru = mysqli_insert_id($koneksi);

            // 5. SIMPAN KE TABEL DOC_SIGNERS (LOOPING)
            foreach($penandatangan as $id_signer){
                // Buat Token Unik untuk setiap penandatangan
                // Token ini nanti jadi QR Code ketika dia Tanda Tangan
                $qr_token = "SIGN-" . md5(uniqid(rand(), true));
                
                $query_sign = "INSERT INTO doc_signers (id_doc, id_user, status, qr_token) 
                               VALUES ('$id_doc_baru', '$id_signer', 'pending', '$qr_token')";
                
                mysqli_query($koneksi, $query_sign);
            }

            header("location:data_dokumen.php?pesan=sukses");

        } else {
            echo "Gagal insert database: " . mysqli_error($koneksi);
        }

    } else {
        header("location:data_dokumen.php?pesan=gagal_upload");
    }

} else {
    // Jika akses langsung tanpa tombol submit
    header("location:data_dokumen.php");
}
?>