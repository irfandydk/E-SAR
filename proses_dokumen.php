<?php
// File: sarsip/proses_dokumen.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}

$role = $_SESSION['role'];

function is_allowed($role, $category){
    if($role == 'admin') return true;
    $map = [
        'pic_admin'      => ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan'],
        'pic_keuangan'   => ['Arsip Keuangan'],
        'pic_ops'        => ['Arsip Operasi SAR'],
        'pic_sumberdaya' => ['Arsip Sumberdaya'],
        'user'           => ['Arsip Lainnya']
    ];
    return (isset($map[$role]) && in_array($category, $map[$role]));
}

// ----------------------------------------------------------------------
// 1. PROSES SIMPAN BARU
// ----------------------------------------------------------------------
if(isset($_POST['simpan'])){
    
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    if(!is_allowed($role, $kategori)){
        echo "<script>alert('AKSES DITOLAK!'); window.location='tambah_dokumen.php';</script>"; exit;
    }

    $nomor      = mysqli_real_escape_string($koneksi, $_POST['nomor']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $visibility = mysqli_real_escape_string($koneksi, $_POST['visibility']);
    
    // Fitur Asal/Tujuan (TETAP ADA)
    $asal       = isset($_POST['asal_surat']) ? mysqli_real_escape_string($koneksi, $_POST['asal_surat']) : NULL;
    $tujuan     = isset($_POST['tujuan_surat']) ? mysqli_real_escape_string($koneksi, $_POST['tujuan_surat']) : NULL;
    
    $id_user    = $_SESSION['id_user'];
    $tgl_upload = date('Y-m-d H:i:s');

    // === [LOGIKA RETENSI BARU] ===
    $retensi = $_POST['retensi'];
    if($retensi == 'permanen'){
        $tgl_retensi = '9999-12-31'; // Tahun jauh untuk permanen
    } else {
        $tgl_retensi = date('Y-m-d', strtotime("+$retensi years"));
    }
    // =============================

    // PROSES UPLOAD FILE (TETAP SAMA)
    if(!empty($_FILES['file_dokumen']['name'])){
        $filename = $_FILES['file_dokumen']['name'];
        $tmp_name = $_FILES['file_dokumen']['tmp_name'];
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if($ext == 'pdf'){
            $target_dir = "uploads/doc_asli/"; // Pastikan folder ini ada
            if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            // Nama File Unik (Logika Lama)
            $clean_cat    = strtoupper(substr(str_replace(' ', '', $kategori), 0, 5));
            $new_filename = $clean_cat . "_" . date('YmdHis') . "_" . rand(100,999) . ".pdf";
            $path_upload  = $target_dir . $new_filename;
            
            if(move_uploaded_file($tmp_name, $path_upload)){
                
                // Hitung Hash (Fitur Keamanan Lama)
                $file_hash = hash_file('sha256', $path_upload);

                // INSERT KE DATABASE (Ditambah kolom tgl_retensi & status_retensi)
                $query = "INSERT INTO documents 
                          (nomor_surat, judul, kategori, file_path, id_user, created_at, file_hash, visibility, asal_surat, tujuan_surat, tgl_retensi, status_retensi) 
                          VALUES 
                          ('$nomor', '$judul', '$kategori', '$new_filename', '$id_user', '$tgl_upload', '$file_hash', '$visibility', '$asal', '$tujuan', '$tgl_retensi', 'aktif')";
                
                if(mysqli_query($koneksi, $query)){
                    header("location:data_dokumen.php?pesan=sukses");
                } else {
                    echo "Error DB: " . mysqli_error($koneksi);
                }

            } else {
                echo "<script>alert('Gagal Upload File!'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Hanya File PDF!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Pilih file dulu!'); window.history.back();</script>";
    }
}

// ----------------------------------------------------------------------
// 2. PROSES UPDATE (Tanpa Upload Ulang)
// ----------------------------------------------------------------------
elseif(isset($_POST['update'])){
    
    // Fitur edit belum tentu perlu mengubah retensi, jadi saya biarkan standar dulu
    // agar tidak merusak logika edit yang sudah ada.
    
    $id_doc     = mysqli_real_escape_string($koneksi, $_POST['id_doc']);
    $nomor      = mysqli_real_escape_string($koneksi, $_POST['nomor']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $kategori   = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $visibility = mysqli_real_escape_string($koneksi, $_POST['visibility']);
    
    $asal       = isset($_POST['asal_surat']) ? mysqli_real_escape_string($koneksi, $_POST['asal_surat']) : NULL;
    $tujuan     = isset($_POST['tujuan_surat']) ? mysqli_real_escape_string($koneksi, $_POST['tujuan_surat']) : NULL;

    $query_update = "UPDATE documents SET 
                     nomor_surat='$nomor', judul='$judul', kategori='$kategori', visibility='$visibility',
                     asal_surat='$asal', tujuan_surat='$tujuan'
                     WHERE id_doc='$id_doc'";
    
    mysqli_query($koneksi, $query_update);

    // Cek jika ada upload ulang file
    if(!empty($_FILES['file_dokumen']['name'])){
        $filename = $_FILES['file_dokumen']['name'];
        $tmp_name = $_FILES['file_dokumen']['tmp_name'];
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if($ext == 'pdf'){
            $target_dir = "uploads/doc_asli/";
            $clean_cat = strtoupper(substr(str_replace(' ', '', $kategori), 0, 5));
            $new_filename = $clean_cat . "_" . date('YmdHis') . "_" . rand(100,999) . ".pdf";
            $path_upload  = $target_dir . $new_filename;
            
            if(move_uploaded_file($tmp_name, $path_upload)){
                $file_hash = hash_file('sha256', $path_upload);
                
                // Hapus file lama (Opsional, ambil dari DB dulu)
                // ...

                $q_file = "UPDATE documents SET file_path='$new_filename', file_hash='$file_hash' WHERE id_doc='$id_doc'";
                mysqli_query($koneksi, $q_file);
            }
        }
    }
    
    header("location:data_dokumen.php?pesan=update");
}

// ----------------------------------------------------------------------
// 3. PROSES HAPUS
// ----------------------------------------------------------------------
elseif(isset($_GET['hapus'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil info file untuk dihapus
    $q = mysqli_query($koneksi, "SELECT file_path FROM documents WHERE id_doc='$id'");
    $d = mysqli_fetch_assoc($q);
    
    if($d['file_path'] && file_exists("uploads/doc_asli/".$d['file_path'])){
        unlink("uploads/doc_asli/".$d['file_path']);
    }
    
    mysqli_query($koneksi, "DELETE FROM documents WHERE id_doc='$id'");
    header("location:data_dokumen.php?pesan=hapus");
}
?>
