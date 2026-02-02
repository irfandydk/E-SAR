<?php 
session_start();
if($_SESSION['role'] != 'admin'){ header("location:login.php"); exit; }
include 'config/koneksi.php';

$id = $_GET['id'];
$q = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id'");
if(mysqli_num_rows($q) == 0){ header("location:data_user.php"); exit; }
$d = mysqli_fetch_assoc($q);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit User - SARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>body{background:#f8f9fa;}</style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-warning"><i class="bi bi-pencil-square me-2"></i>Edit Data User</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="proses_user.php" method="POST">
                            <input type="hidden" name="id_user" value="<?php echo $d['id_user']; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Username (Tidak dapat diubah)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $d['username']; ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?php echo $d['nama_lengkap']; ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">NIP / NRP</label>
                                    <input type="text" name="nip" class="form-control" value="<?php echo $d['nip']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Unit Kerja</label>
                                    <input type="text" name="unit_kerja" class="form-control" value="<?php echo $d['unit_kerja']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Jabatan</label>
                                <input type="text" name="jabatan" class="form-control" value="<?php echo $d['jabatan']; ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Password Baru <span class="fw-normal text-danger">(Kosongkan jika tidak ubah)</span></label>
                                <input type="password" name="password" class="form-control" placeholder="***">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Level Akses (Role)</label>
                                <select name="role" class="form-select">
                                    <option value="user" <?php echo ($d['role']=='user')?'selected':''; ?>>User Biasa</option>
                                    <option value="admin" <?php echo ($d['role']=='admin')?'selected':''; ?>>Administrator</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="data_user.php" class="btn btn-light">Batal</a>
                                <button type="submit" name="edit_user" class="btn btn-warning px-4 fw-bold">Update Data</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>