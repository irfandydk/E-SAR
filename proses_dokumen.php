<?php
// File: sarsip/proses_dokumen.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}

$role = $_SESSION['role'];
$aksi = isset($_REQUEST['aksi']) ? $_REQUEST['aksi'] : ''; // Menangkap aksi dari GET/POST

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
if($aksi == 'tambah' && isset($_POST['simpan'])){
    
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    if(!is_allowed($role, $kategori)){
        echo "<script>alert('AKSES DITOLAK!'); window.location='tambah_dokumen.php';</script>"; exit;
    }

    $nomor      = mysqli_real_escape_string($koneksi, $_POST['nomor']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $visibility = mysqli_real_escape_string($koneksi, $_POST['visibility']);
    $asal       = isset($_POST['asal_surat']) ? mysqli_real_escape_string($koneksi, $_POST['asal_surat']) : NULL;
    $tujuan     = isset($_POST['tujuan_surat']) ? mysqli_real_escape_string($koneksi, $_POST['tujuan_surat']) : NULL;
    
    $id_user    = $_SESSION['id_user'];
    $tgl_upload = date('Y-m-d H:i:s');

    // Logic Retensi
    $retensi = $_POST['retensi'];
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

                // INSERT (Menggunakan id_user)
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

    // Cek upload ulang file
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
// 3. PROSES HAPUS SATUAN
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
// 4. PROSES HAPUS SEKALIGUS (BULK DELETE)
// ----------------------------------------------------------------------
elseif(isset($_POST['aksi']) && $_POST['aksi'] == 'hapus_banyak'){
    if(!empty($_POST['pilih'])){
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
    } else {
        header("location:data_dokumen.php?pesan=gagal_pilih");
    }
}

// ----------------------------------------------------------------------
// 5. PROSES DOWNLOAD SEKALIGUS (ZIP)
// ----------------------------------------------------------------------
elseif(isset($_POST['aksi']) && $_POST['aksi'] == 'download_zip'){
    if(!empty($_POST['pilih'])){
        
        $zip = new ZipArchive();
        $zip_name = "Arsip_Terpilih_" . date('Ymd_His') . ".zip";
        $zip_path = "uploads/" . $zip_name; // Simpan sementara di uploads/
        
        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            die("Gagal membuat file ZIP.");
        }

        foreach($_POST['pilih'] as $id){
            $id = mysqli_real_escape_string($koneksi, $id);
            $q = mysqli_query($koneksi, "SELECT file_path, judul FROM documents WHERE id_doc='$id'");
            
            if($r = mysqli_fetch_assoc($q)){
                // Cek file mana yang ada (Signed atau Asli)
                $path_asli   = "uploads/doc_asli/" . $r['file_path'];
                $path_signed = "uploads/doc_signed/SIGNED_" . $r['file_path'];
                
                $file_to_add = file_exists($path_signed) ? $path_signed : (file_exists($path_asli) ? $path_asli : null);

                if($file_to_add){
                    // Bersihkan nama file agar aman di dalam ZIP
                    $ext = pathinfo($r['file_path'], PATHINFO_EXTENSION);
                    $clean_judul = preg_replace('/[^A-Za-z0-9_\- ]/', '', $r['judul']);
                    $clean_judul = str_replace(' ', '_', $clean_judul);
                    
                    // Tambahkan ke ZIP dengan nama: JUDUL.pdf
                    $zip->addFile($file_to_add, $clean_judul . "." . $ext);
                }
            }
        }
        $zip->close();

        // Download File ZIP
        if (file_exists($zip_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name . '"');
            header('Content-Length: ' . filesize($zip_path));
            readfile($zip_path);
            
            // Hapus file ZIP setelah didownload agar hemat storage
            unlink($zip_path); 
            exit;
        } else {
            echo "<script>alert('Gagal membuat ZIP atau file tidak ditemukan.'); window.location='data_dokumen.php';</script>";
        }

    } else {
        header("location:data_dokumen.php?pesan=gagal_pilih");
    }
}

else {
    header("location:data_dokumen.php");
}
?>
