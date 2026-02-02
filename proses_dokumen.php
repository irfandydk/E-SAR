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

    $nomor    = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $judul    = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $visibility = mysqli_real_escape_string($koneksi, $_POST['visibility']); 
    $tgl_dok  = mysqli_real_escape_string($koneksi, $_POST['tgl_dokumen']);
    $asal     = isset($_POST['asal_surat']) ? mysqli_real_escape_string($koneksi, $_POST['asal_surat']) : NULL;
    $tujuan   = isset($_POST['tujuan_surat']) ? mysqli_real_escape_string($koneksi, $_POST['tujuan_surat']) : NULL;
    $uploader = $_SESSION['id_user']; 

    if(empty($_FILES['file_dokumen']['name'])){
        echo "<script>alert('File kosong!'); window.location='tambah_dokumen.php';</script>"; exit;
    }

    $filename  = $_FILES['file_dokumen']['name'];
    $tmp_name  = $_FILES['file_dokumen']['tmp_name'];
    $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if($ext != 'pdf'){ echo "<script>alert('Hanya PDF!'); window.location='tambah_dokumen.php';</script>"; exit; }

    $target_dir = "uploads/doc_asli/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $clean_cat = strtoupper(substr(str_replace(' ', '', $kategori), 0, 5));
    $new_filename = $clean_cat . "_" . date('YmdHis') . "_" . rand(100,999) . ".pdf";
    $path_upload  = $target_dir . $new_filename;

    if(move_uploaded_file($tmp_name, $path_upload)){
        $file_hash = hash_file('sha256', $path_upload);
        $waktu_fix = (!empty($tgl_dok)) ? $tgl_dok . " " . date("H:i:s") : date("Y-m-d H:i:s");

        $query = "INSERT INTO documents (nomor_surat, judul, kategori, visibility, asal_surat, tujuan_surat, file_path, file_hash, uploader_id, created_at)
                  VALUES ('$nomor', '$judul', '$kategori', '$visibility', '$asal', '$tujuan', '$new_filename', '$file_hash', '$uploader', '$waktu_fix')";
        
        if(mysqli_query($koneksi, $query)){
            header("location:data_dokumen.php?kategori=".urlencode($kategori)."&pesan=upload_sukses");
        } else {
            echo "<script>alert('DB Error'); window.location='tambah_dokumen.php';</script>";
        }
    } else {
        echo "<script>alert('Gagal Upload!'); window.location='tambah_dokumen.php';</script>";
    }
}

// ----------------------------------------------------------------------
// 2. PROSES DOWNLOAD ZIP (BULK)
// ----------------------------------------------------------------------
elseif(isset($_POST['aksi']) && $_POST['aksi'] == 'download_zip'){
    
    if(isset($_POST['pilih']) && count($_POST['pilih']) > 0){
        
        $zip = new ZipArchive();
        $zip_name = "Arsip_Download_" . date('Ymd_His') . ".zip";
        $temp_dir = sys_get_temp_dir(); // Folder Temp Sistem
        $zip_path = $temp_dir . "/" . $zip_name;

        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            echo "<script>alert('Gagal membuat ZIP'); window.history.back();</script>"; exit;
        }

        foreach($_POST['pilih'] as $id){
            $id_safe = mysqli_real_escape_string($koneksi, $id);
            // Ambil info file (Cek hak akses jika perlu, tapi download biasanya boleh untuk viewable docs)
            $q = mysqli_query($koneksi, "SELECT file_path, nomor_surat FROM documents WHERE id_doc='$id_safe'");
            
            if($r = mysqli_fetch_assoc($q)){
                // Cek apakah ada file signed? Jika ada, prioritas download yang signed
                $path_asli   = "uploads/doc_asli/" . $r['file_path'];
                $path_signed = "uploads/doc_signed/SIGNED_" . $r['file_path'];
                
                $file_to_add = file_exists($path_signed) ? $path_signed : (file_exists($path_asli) ? $path_asli : null);
                
                if($file_to_add){
                    // Nama file dalam ZIP (Gunakan Nomor Surat agar rapi)
                    // Bersihkan karakter aneh di nomor surat
                    $clean_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $r['nomor_surat']) . ".pdf";
                    $zip->addFile($file_to_add, $clean_name);
                }
            }
        }
        $zip->close();

        // Paksa Download
        if(file_exists($zip_path)){
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$zip_name.'"');
            header('Content-Length: ' . filesize($zip_path));
            flush();
            readfile($zip_path);
            unlink($zip_path); // Hapus file zip temp setelah download
            exit;
        } else {
            echo "<script>alert('File ZIP kosong atau gagal dibuat.'); window.history.back();</script>";
        }

    } else {
        echo "<script>alert('Tidak ada file yang dipilih!'); window.history.back();</script>";
    }
}

// ----------------------------------------------------------------------
// 3. PROSES HAPUS (SATUAN & BANYAK)
// ----------------------------------------------------------------------
elseif((isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') || (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus_banyak')){
    
    $ids_to_delete = [];
    
    if(isset($_POST['pilih'])){
        $ids_to_delete = $_POST['pilih']; // Dari Checkbox
    } elseif(isset($_GET['id'])){
        $ids_to_delete[] = $_GET['id']; // Dari Tombol Sampah
    }

    if(count($ids_to_delete) > 0){
        foreach($ids_to_delete as $id){
            $id_safe = mysqli_real_escape_string($koneksi, $id);
            $cek = mysqli_query($koneksi, "SELECT file_path, uploader_id, kategori FROM documents WHERE id_doc='$id_safe'");
            
            if($r = mysqli_fetch_assoc($cek)){
                 // SECURITY CHECK: Hanya Admin, Pemilik, atau PIC Kategori yg boleh hapus
                 if($role != 'admin' && $_SESSION['id_user'] != $r['uploader_id'] && !is_allowed($role, $r['kategori'])){
                     continue; // Skip file ini
                 }

                 $f1 = "uploads/doc_asli/" . $r['file_path'];
                 $f2 = "uploads/doc_signed/SIGNED_" . $r['file_path'];
                 if(file_exists($f1)) unlink($f1);
                 if(file_exists($f2)) unlink($f2);
                 
                 mysqli_query($koneksi, "DELETE FROM documents WHERE id_doc='$id_safe'");
            }
        }
        header("location:data_dokumen.php?pesan=hapus_sukses");
    } else {
        header("location:data_dokumen.php");
    }
}

// ----------------------------------------------------------------------
// 4. PROSES UPDATE DOKUMEN
// ----------------------------------------------------------------------
elseif(isset($_POST['update_dokumen'])){
    // ... (Kode update dokumen sama seperti sebelumnya, tidak berubah) ...
    // Saya singkat agar tidak kepanjangan, karena fokus kita di Download ZIP.
    // Jika Anda butuh kode lengkap bagian ini, silakan minta lagi.
    
    $id_doc     = mysqli_real_escape_string($koneksi, $_POST['id_doc']);
    $kategori   = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $nomor      = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
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
                mysqli_query($koneksi, "UPDATE documents SET file_path='$new_filename', file_hash='$file_hash' WHERE id_doc='$id_doc'");
            }
        }
    }
    header("location:data_dokumen.php?pesan=edit_sukses");
}

else {
    header("location:data_dokumen.php");
}
?>