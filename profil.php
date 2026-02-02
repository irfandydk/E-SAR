<?php
// File: sarsip/profil.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

$id_user = $_SESSION['id_user'];
$role    = $_SESSION['role'];

// Ambil Data User
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
$data  = mysqli_fetch_assoc($query);

// Cek Status Pending
$pending_msg = "";
if($role != 'admin'){
    $cek = mysqli_query($koneksi, "SELECT * FROM user_change_requests WHERE id_user='$id_user' AND status='pending'");
    if(mysqli_num_rows($cek) > 0) $pending_msg = "Perubahan profil sedang menunggu persetujuan Admin.";
}

// PROSES UPDATE
if(isset($_POST['update_profil'])){
    $nama       = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $nip        = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $jabatan    = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $instansi   = mysqli_real_escape_string($koneksi, $_POST['instansi']);
    $unit_kerja = mysqli_real_escape_string($koneksi, $_POST['unit_kerja']);
    
    // Password
    $pass_val = NULL;
    if(!empty($_POST['password_baru'])){
        $pass_val = md5($_POST['password_baru']);
    }

    // Foto
    $foto_name = NULL;
    if(!empty($_FILES['foto']['name'])){
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png'])){
            $new_name = "PROFILE_".$id_user."_".date('YmdHis').".".$ext;
            $target   = "uploads/profil/";
            if(!file_exists($target)) mkdir($target, 0777, true);
            
            if(move_uploaded_file($_FILES['foto']['tmp_name'], $target.$new_name)){
                $foto_name = $new_name;
            }
        }
    }

    // A. JIKA ADMIN (LANGSUNG UBAH)
    if($role == 'admin'){
        $sql_pass = $pass_val ? ", password='$pass_val'" : "";
        $sql_foto = $foto_name ? ", foto_path='$foto_name'" : "";
        
        $q = "UPDATE users SET 
              nama_lengkap='$nama', 
              nip='$nip', 
              jabatan='$jabatan', 
              instansi='$instansi', 
              unit_kerja='$unit_kerja' 
              $sql_pass $sql_foto 
              WHERE id_user='$id_user'";
              
        if(mysqli_query($koneksi, $q)){
            $_SESSION['nama'] = $nama; 
            echo "<script>alert('Profil diperbarui!'); window.location='profil.php';</script>";
        }
    } 
    // B. JIKA USER/PIC (REQ KE ADMIN)
    else {
        // Hapus request pending lama
        mysqli_query($koneksi, "DELETE FROM user_change_requests WHERE id_user='$id_user' AND status='pending'");
        
        $p_sql = $pass_val ? "'$pass_val'" : "NULL";
        $f_sql = $foto_name ? "'$foto_name'" : "NULL";

        // Masukkan data request termasuk instansi dan unit kerja
        $q = "INSERT INTO user_change_requests (id_user, nama_lengkap, nip, jabatan, instansi, unit_kerja, password_baru, foto_baru, status) 
              VALUES ('$id_user', '$nama', '$nip', '$jabatan', '$instansi', '$unit_kerja', $p_sql, $f_sql, 'pending')";
        
        if(mysqli_query($koneksi, $q)){
            echo "<script>alert('Permintaan dikirim! Menunggu Admin.'); window.location='profil.php';</script>";
        } else {
            echo "<script>alert('Gagal mengirim request: ".mysqli_error($koneksi)."');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .main-content { margin-left: 280px; padding: 30px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        .foto-profil { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php include 'sidebar_menu.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <h4 class="fw-bold mb-4">Profil Pengguna</h4>
            
            <?php if($pending_msg){ ?>
                <div class="alert alert-warning"><i class="bi bi-clock"></i> <?php echo $pending_msg; ?></div>
            <?php } ?>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm p-4 text-center">
                        <div class="mb-3">
                            <?php 
                                $path = "uploads/profil/".($data['foto_path'] ?? 'default.png');
                                if(!file_exists($path)) $path = "https://ui-avatars.com/api/?name=".urlencode($data['nama_lengkap']);
                            ?>
                            <img src="<?php echo $path; ?>" class="foto-profil">
                        </div>
                        <h5><?php echo htmlspecialchars($data['nama_lengkap']); ?></h5>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($data['jabatan']); ?></p>
                        <small class="text-primary fw-bold d-block mb-2"><?php echo htmlspecialchars($data['unit_kerja']); ?></small>
                        <span class="badge bg-primary"><?php echo strtoupper($data['role']); ?></span>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nama Lengkap</label>
                                        <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($data['nama_lengkap']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">NIP</label>
                                        <input type="text" name="nip" class="form-control" value="<?php echo htmlspecialchars($data['nip']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jabatan</label>
                                    <input type="text" name="jabatan" class="form-control" value="<?php echo htmlspecialchars($data['jabatan']); ?>">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Instansi</label>
                                        <input type="text" name="instansi" class="form-control" value="<?php echo htmlspecialchars($data['instansi']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Unit Kerja</label>
                                        <input type="text" name="unit_kerja" class="form-control" value="<?php echo htmlspecialchars($data['unit_kerja']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Password Baru (Opsional)</label>
                                    <input type="password" name="password_baru" class="form-control" placeholder="Biarkan kosong jika tidak diganti">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ganti Foto</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>