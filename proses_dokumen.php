<?php
// File: sarsip/proses_dokumen.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}

$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'user';
$aksi = isset($_REQUEST['aksi']) ? $_REQUEST['aksi'] : ''; 

// FUNGSI CEK HAK AKSES
function is_allowed($role, $category){
    if($role == 'admin') return true;
    
    // Pastikan key role menggunakan huruf kecil semua
    $map = [
        'pic_admin'      => ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan'],
        'pic_keuangan'   => ['Arsip Keuangan'],
        'pic_ops'        => ['Arsip Operasi SAR'],
        'pic_sumberdaya' => ['Arsip Sumberdaya'],
        'user'           => ['Arsip Lainnya'] // Role default untuk user biasa
    ];
    
    // Cek apakah kategori ada dalam daftar role tersebut
    if(isset($map[$role])){
        return in_array($category, $map[$role]);
    }
    
    // Default Tolak jika role tidak dikenali
    return false;
}

// ----------------------------------------------------------------------
// 1. PROSES SIMPAN BARU
// ----------------------------------------------------------------------
// LOGIKA BARU: Jika Metode POST DAN (aksi=tambah ATAU tombol simpan diklik)
if($_SERVER['REQUEST_METHOD'] == 'POST' && ($aksi == 'tambah' || isset($_POST['simpan']))){
    
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    // Validasi Hak Akses
    if(!is_allowed($role, $kategori)){
        echo "<script>
            alert('AKSES DITOLAK! Role Anda ($role) tidak diizinkan untuk kategori ($kategori).'); 
            window.location='tambah_dokumen.php';
        </script>"; 
        exit;
    }

    $nomor      = mysqli_real_escape_string($koneksi, $_POST['nomor']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $visibility = mysqli_real_escape_string($koneksi, $_POST['visibility']);
    $asal       = isset($_POST['asal_surat']) ? mysqli_real_escape_string($koneksi, $_POST['asal_surat']) : NULL;
    $tujuan     = isset($_POST['tujuan_surat']) ? mysqli_real_escape_string($koneksi, $_POST['tujuan_surat']) : NULL;
    
    $id_user    = $_SESSION['id_user'];
    $tgl_upload = date('Y-m-d H:i:s');

    // Logic Retensi
    $retensi = isset($_POST['retensi']) ? $_POST['retensi'] : '5';
    if($retensi == 'permanen'){
        $tgl_retensi = '9999-12-31'; 
    } else {
        $tgl_retensi = date('Y-m-d', strtotime("+$retensi years"));
    }

    if(!empty($_FILES['file_dokumen']['name'])){
        $filename = $_FILES['file_dokumen']['name'];
        $tmp_name = $_FILES['file_dokumen']['tmp_name'];
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if($ext == 'pdf'){
            $target_dir = "uploads/doc_asli/";
            if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            $clean_cat    = strtoupper(substr(str_replace(' ', '', $kategori), 0, 5));
            $new_filename = $clean_cat . "_" . date('YmdHis') . "_" . rand(100,999) . ".pdf";
            $path_upload  = $target_dir . $new_filename;
            
            if(move_uploaded_file($tmp_name, $path_upload)){
                $file_hash = hash_file('sha256', $path_upload);

                $query = "INSERT INTO documents 
                          (nomor_surat, judul, kategori, file_path, id_user, created_at, file_hash, visibility, asal_surat, tujuan_surat, tgl_retensi, status_retensi) 
                          VALUES 
                          ('$nomor', '$judul', '$kategori', '$new_filename', '$id_user', '$tgl_upload', '$file_hash', '$visibility', '$asal', '$tujuan', '$tgl_retensi', 'aktif')";
                
                if(mysqli_query($koneksi, $query)){
                    header("location:data_dokumen.php?pesan=sukses");
                } else {
                    echo "Error Database: " . mysqli_error($koneksi);
                }
            } else {
                echo "<script>alert('Gagal Upload File ke Server!'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Hanya File PDF!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Pilih file PDF!'); window.history.back();</script>";
    }
}

// ----------------------------------------------------------------------
// 2. PROSES UPDATE (Edit)
// ----------------------------------------------------------------------
elseif($aksi == 'update' && isset($_POST['update'])){
    
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
                $q_file = "UPDATE documents SET file_path='$new_filename', file_hash='$file_hash' WHERE id_doc='$id_doc'";
                mysqli_query($koneksi, $q_file);
            }
        }
    }
    header("location:data_dokumen.php?pesan=update");
}

// ----------------------------------------------------------------------
// 3. HAPUS SATUAN
// ----------------------------------------------------------------------
elseif($aksi == 'hapus' && isset($_GET['id'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    $q = mysqli_query($koneksi, "SELECT file_path FROM documents WHERE id_doc='$id'");
    $d = mysqli_fetch_assoc($q);
    
    if($d){
        $path1 = "uploads/doc_asli/".$d['file_path'];
        $path2 = "uploads/doc_signed/SIGNED_".$d['file_path'];
        if(file_exists($path1)) unlink($path1);
        if(file_exists($path2)) unlink($path2);
    }
    
    mysqli_query($koneksi, "DELETE FROM documents WHERE id_doc='$id'");
    header("location:data_dokumen.php?pesan=hapus");
}

// ----------------------------------------------------------------------
// 4. HAPUS BANYAK
// ----------------------------------------------------------------------
elseif($aksi == 'hapus_banyak' && !empty($_POST['pilih'])){
    foreach($_POST['pilih'] as $id){
        $id = mysqli_real_escape_string($koneksi, $id);
        $q = mysqli_query($koneksi, "SELECT file_path FROM documents WHERE id_doc='$id'");
        $d = mysqli_fetch_assoc($q);
        
        if($d){
            $path1 = "uploads/doc_asli/".$d['file_path'];
            $path2 = "uploads/doc_signed/SIGNED_".$d['file_path'];
            if(file_exists($path1)) unlink($path1);
            if(file_exists($path2)) unlink($path2);
            mysqli_query($koneksi, "DELETE FROM documents WHERE id_doc='$id'");
        }
    }
    header("location:data_dokumen.php?pesan=hapus_banyak");
}

// ----------------------------------------------------------------------
// 5. DOWNLOAD ZIP
// ----------------------------------------------------------------------
elseif($aksi == 'download_zip' && !empty($_POST['pilih'])){
    $zip = new ZipArchive();
    $zip_name = "Arsip_" . date('Ymd_His') . ".zip";
    $zip_path = "uploads/" . $zip_name;
    
    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
        die("Gagal membuat ZIP.");
    }

    foreach($_POST['pilih'] as $id){
        $id = mysqli_real_escape_string($koneksi, $id);
        $q = mysqli_query($koneksi, "SELECT file_path, judul FROM documents WHERE id_doc='$id'");
        if($r = mysqli_fetch_assoc($q)){
            $path_asli   = "uploads/doc_asli/" . $r['file_path'];
            $path_signed = "uploads/doc_signed/SIGNED_" . $r['file_path'];
            $file_to_add = file_exists($path_signed) ? $path_signed : (file_exists($path_asli) ? $path_asli : null);

            if($file_to_add){
                $ext = pathinfo($r['file_path'], PATHINFO_EXTENSION);
                $clean_judul = preg_replace('/[^A-Za-z0-9_\- ]/', '', $r['judul']);
                $zip->addFile($file_to_add, $clean_judul . "." . $ext);
            }
        }
    }
    $zip->close();

    if (file_exists($zip_path)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        readfile($zip_path);
        unlink($zip_path); 
        exit;
    }
}

// Fallback jika tidak ada aksi yang cocok
else {
    header("location:data_dokumen.php");
}
?>
