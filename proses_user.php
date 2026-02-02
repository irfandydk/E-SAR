<?php
// File: sarsip/proses_user.php
session_start();
include 'config/koneksi.php';

// Cek Login & Role Admin
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php"); exit;
}
if($_SESSION['role'] != 'admin'){
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; exit;
}

// =================================================================================
// 1. PROSES TAMBAH USER BARU
// =================================================================================
if(isset($_POST['simpan_user'])){
    
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = md5($_POST['password']); 
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $nip          = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $jabatan      = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $role         = mysqli_real_escape_string($koneksi, $_POST['role']);
    $unit_kerja   = mysqli_real_escape_string($koneksi, $_POST['unit_kerja']);
    $instansi     = mysqli_real_escape_string($koneksi, $_POST['instansi']);

    // Cek Username Ganda
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Username sudah digunakan! Ganti yang lain.'); window.location='tambah_user.php';</script>";
        exit;
    }

    $query = "INSERT INTO users (username, password, nama_lengkap, nip, jabatan, role, unit_kerja, instansi) 
              VALUES ('$username', '$password', '$nama_lengkap', '$nip', '$jabatan', '$role', '$unit_kerja', '$instansi')";

    if(mysqli_query($koneksi, $query)){
        header("location:data_user.php?pesan=tambah_sukses");
    } else {
        echo "<script>alert('Gagal menyimpan data: ".mysqli_error($koneksi)."'); window.location='tambah_user.php';</script>";
    }
}

// =================================================================================
// 2. PROSES EDIT USER
// =================================================================================
elseif(isset($_POST['edit_user'])){
    
    $id_user      = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $nip          = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $jabatan      = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $role         = mysqli_real_escape_string($koneksi, $_POST['role']);
    $unit_kerja   = mysqli_real_escape_string($koneksi, $_POST['unit_kerja']);
    $instansi     = mysqli_real_escape_string($koneksi, $_POST['instansi']);

    if(!empty($_POST['password'])){
        $password = md5($_POST['password']);
        $query = "UPDATE users SET 
                  nama_lengkap='$nama_lengkap', 
                  nip='$nip', 
                  jabatan='$jabatan', 
                  role='$role', 
                  unit_kerja='$unit_kerja', 
                  instansi='$instansi', 
                  password='$password' 
                  WHERE id_user='$id_user'";
    } else {
        $query = "UPDATE users SET 
                  nama_lengkap='$nama_lengkap', 
                  nip='$nip', 
                  jabatan='$jabatan', 
                  role='$role', 
                  unit_kerja='$unit_kerja', 
                  instansi='$instansi' 
                  WHERE id_user='$id_user'";
    }

    if(mysqli_query($koneksi, $query)){
        header("location:data_user.php?pesan=edit_sukses");
    } else {
        echo "<script>alert('Gagal update data: ".mysqli_error($koneksi)."'); window.history.back();</script>";
    }
}

// =================================================================================
// 3. PROSES HAPUS USER
// =================================================================================
elseif(isset($_GET['aksi']) && $_GET['aksi'] == 'hapus'){
    
    $id_user = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    if($id_user == $_SESSION['id_user']){
        echo "<script>alert('Anda tidak bisa menghapus akun yang sedang digunakan!'); window.location='data_user.php';</script>";
        exit;
    }

    $query = "DELETE FROM users WHERE id_user='$id_user'";
    
    if(mysqli_query($koneksi, $query)){
        header("location:data_user.php?pesan=hapus_sukses");
    } else {
        echo "<script>alert('Gagal menghapus user!'); window.location='data_user.php';</script>";
    }
}

// =================================================================================
// 4. PROSES APPROVE/REJECT REQUEST PROFIL
// =================================================================================
elseif(isset($_GET['aksi']) && ($_GET['aksi'] == 'approve_req' || $_GET['aksi'] == 'reject_req')){
    
    if(!isset($_GET['id']) || empty($_GET['id'])){
        echo "<script>alert('ID Request tidak ditemukan!'); window.location='admin_persetujuan.php';</script>"; exit;
    }

    $id_req = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil Data Request
    $q_req = mysqli_query($koneksi, "SELECT * FROM user_change_requests WHERE id_req='$id_req'");
    $req   = mysqli_fetch_assoc($q_req);

    if(!$req){
        echo "<script>alert('Data request tidak ditemukan!'); window.location='admin_persetujuan.php';</script>"; exit;
    }

    if($_GET['aksi'] == 'approve_req'){
        // --- LOGIKA SETUJUI ---
        $id_user = $req['id_user'];
        
        // Update data user termasuk INSTANSI dan UNIT KERJA
        $update_parts = [];
        $update_parts[] = "nama_lengkap='".$req['nama_lengkap']."'";
        $update_parts[] = "nip='".$req['nip']."'";
        $update_parts[] = "jabatan='".$req['jabatan']."'";
        $update_parts[] = "instansi='".$req['instansi']."'";     // Field Baru
        $update_parts[] = "unit_kerja='".$req['unit_kerja']."'"; // Field Baru
        
        if(!empty($req['password_baru'])){
            $update_parts[] = "password='".$req['password_baru']."'";
        }
        if(!empty($req['foto_baru'])){
            $update_parts[] = "foto_path='".$req['foto_baru']."'";
        }
        
        $sql_update = "UPDATE users SET " . implode(", ", $update_parts) . " WHERE id_user='$id_user'";
        
        if(mysqli_query($koneksi, $sql_update)){
            mysqli_query($koneksi, "UPDATE user_change_requests SET status='approved' WHERE id_req='$id_req'");
            header("location:admin_persetujuan.php?pesan=approve");
        } else {
            echo "Error Update DB: " . mysqli_error($koneksi);
        }

    } elseif($_GET['aksi'] == 'reject_req'){
        // --- LOGIKA TOLAK ---
        if(!empty($req['foto_baru'])){
            $path = "uploads/profil/" . $req['foto_baru'];
            if(file_exists($path)) unlink($path);
        }
        mysqli_query($koneksi, "UPDATE user_change_requests SET status='rejected' WHERE id_req='$id_req'");
        header("location:admin_persetujuan.php?pesan=reject");
    }
}

// =================================================================================
// 5. REDIRECT DEFAULT
// =================================================================================
else {
    header("location:data_user.php");
}
?>