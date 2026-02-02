<?php
// File: sarsip/proses_pengaturan.php
session_start();
include 'config/koneksi.php';

// Validasi Login Admin
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ header("location:login.php"); exit; }
if($_SESSION['role'] != 'admin'){ header("location:dashboard.php"); exit; }

// Tangkap Aksi
$aksi = isset($_REQUEST['aksi']) ? $_REQUEST['aksi'] : '';

// 1. UPDATE PENGATURAN UMUM
if($aksi == 'update_settings'){
    
    // Tangkap Input
    $id         = mysqli_real_escape_string($koneksi, $_POST['id_settings']);
    $nama_app   = mysqli_real_escape_string($koneksi, $_POST['nama_aplikasi']);
    $nama_ins   = mysqli_real_escape_string($koneksi, $_POST['nama_instansi']);
    $nama_unit  = mysqli_real_escape_string($koneksi, $_POST['nama_unit_kerja']); // Field Baru
    $alamat     = mysqli_real_escape_string($koneksi, $_POST['alamat_instansi']);
    $email      = mysqli_real_escape_string($koneksi, $_POST['email_instansi']);
    $telp       = mysqli_real_escape_string($koneksi, $_POST['telepon_instansi']);

    // Logika Upload Logo
    $sql_logo = "";
    if(!empty($_FILES['logo']['name'])){
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['png','jpg','jpeg'])){
            $new_name = "logo_app_" . time() . "." . $ext; // Kasih time agar cache browser refresh
            $target = "assets/" . $new_name;
            
            // Hapus logo lama (opsional, agar tidak menumpuk sampah file)
            $q_old = mysqli_query($koneksi, "SELECT logo_path FROM app_settings WHERE id='$id'");
            $d_old = mysqli_fetch_assoc($q_old);
            if(!empty($d_old['logo_path']) && $d_old['logo_path'] != 'default_logo.png'){
                if(file_exists("assets/".$d_old['logo_path'])) unlink("assets/".$d_old['logo_path']);
            }

            if(move_uploaded_file($_FILES['logo']['tmp_name'], $target)){
                $sql_logo = ", logo_path='$new_name'";
            }
        }
    }

    // Cek apakah baris data sudah ada?
    $cek = mysqli_query($koneksi, "SELECT id FROM app_settings WHERE id='$id'");
    
    if(mysqli_num_rows($cek) > 0){
        // UPDATE Existing
        $query = "UPDATE app_settings SET 
                  nama_aplikasi='$nama_app',
                  nama_instansi='$nama_ins',
                  nama_unit_kerja='$nama_unit',
                  alamat_instansi='$alamat',
                  email_instansi='$email',
                  telepon_instansi='$telp'
                  $sql_logo
                  WHERE id='$id'";
    } else {
        // INSERT New (Jika tabel kosong)
        $logo_val = !empty($sql_logo) ? $new_name : "default_logo.png";
        $query = "INSERT INTO app_settings (id, nama_aplikasi, nama_instansi, nama_unit_kerja, alamat_instansi, email_instansi, telepon_instansi, logo_path) 
                  VALUES (1, '$nama_app', '$nama_ins', '$nama_unit', '$alamat', '$email', '$telp', '$logo_val')";
    }

    if(mysqli_query($koneksi, $query)){
        header("location:pengaturan.php?pesan=sukses");
    } else {
        echo "<script>alert('Gagal menyimpan pengaturan: ".mysqli_error($koneksi)."'); window.history.back();</script>";
    }
}

else {
    header("location:pengaturan.php");
}
?>