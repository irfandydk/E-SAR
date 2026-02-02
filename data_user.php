<?php 
// File: sarsip/data_user.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != 'admin'){
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - <?php echo isset($app_name)?$app_name:'SARSIP'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; min-height: 100vh; background: #fff; position: fixed; top: 0; left: 0; z-index: 100; border-right: 1px solid #dee2e6; }
        .main-content { margin-left: 280px; padding: 30px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <?php include 'sidebar_menu.php'; ?>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            
            <?php if(isset($_GET['pesan']) && $_GET['pesan']=='tambah_sukses'){ ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">User berhasil ditambahkan!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php } elseif(isset($_GET['pesan']) && $_GET['pesan']=='hapus_sukses'){ ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">User berhasil dihapus.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php } elseif(isset($_GET['pesan']) && $_GET['pesan']=='gagal_hapus_diri'){ ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">Anda tidak dapat menghapus akun sendiri!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php } ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark mb-0">Manajemen Pengguna</h4>
                <a href="tambah_user.php" class="btn btn-primary shadow-sm">
                    <i class="bi bi-person-plus me-1"></i> Tambah User
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tableUser" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>NIP / Username</th>
                                    <th>Jabatan</th>
                                    <th>Role</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $query = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id_user DESC");
                                while($row = mysqli_fetch_assoc($query)){
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['nama_lengkap']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['unit_kerja']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['nip']); ?></div>
                                        <small class="text-primary fst-italic">@<?php echo htmlspecialchars($row['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                                    <td>
                                        <?php if($row['role']=='admin'){ ?>
                                            <span class="badge bg-danger rounded-pill">Administrator</span>
                                        <?php } else { ?>
                                            <span class="badge bg-success rounded-pill">User</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="edit_user.php?id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if($row['id_user'] != $_SESSION['id_user']){ ?>
                                            <a href="proses_user.php?aksi=hapus&id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-outline-danger btn-alert-hapus" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Hanya inisialisasi Datatable
            $('#tableUser').DataTable({ 
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json" } 
            });
            
            // CODE YANG MENGGUNAKAN 'confirm()' DIHAPUS
            // KARENA SUDAH DITANGANI OLEH SWEETALERT DI sidebar_menu.php
        });
    </script>
</body>
</html>