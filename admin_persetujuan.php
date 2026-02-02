<?php 
// File: sarsip/admin_persetujuan.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}
if($_SESSION['role'] != 'admin'){
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; exit;
}
include 'config/koneksi.php';
$app_name = "SARSIP"; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Profil - <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        .img-req { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #0d6efd; }
    </style>
</head>
<body>

    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h4 class="fw-bold mb-4"><i class="bi bi-patch-check-fill me-2 text-primary"></i>Persetujuan Profil</h4>

            <?php if(isset($_GET['pesan'])){ ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php 
                        if($_GET['pesan']=='approve') echo "Permintaan berhasil <b>DISETUJUI</b>. Data pengguna telah diperbarui.";
                        if($_GET['pesan']=='reject') echo "Permintaan berhasil <b>DITOLAK</b>.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-secondary">Daftar Permintaan Pending</h6>
                </div>
                <div class="card-body p-0">
                    
                    <?php 
                    // QUERY UPDATE: Ambil juga Instansi & Unit Kerja
                    $query_req = "SELECT 
                                    req.id_req, req.tgl_request,
                                    req.nama_lengkap AS nama_baru, 
                                    req.nip AS nip_baru, 
                                    req.jabatan AS jabatan_baru,
                                    req.instansi AS instansi_baru,
                                    req.unit_kerja AS unit_kerja_baru,
                                    req.password_baru, req.foto_baru,
                                    u.username, 
                                    u.nama_lengkap AS nama_lama, 
                                    u.nip AS nip_lama, 
                                    u.jabatan AS jabatan_lama,
                                    u.instansi AS instansi_lama,
                                    u.unit_kerja AS unit_kerja_lama,
                                    u.foto_path AS foto_lama
                                 FROM user_change_requests req 
                                 JOIN users u ON req.id_user = u.id_user
                                 WHERE req.status = 'pending' 
                                 ORDER BY req.tgl_request ASC";
                    
                    $exec_req = mysqli_query($koneksi, $query_req);
                    
                    if(mysqli_num_rows($exec_req) > 0){
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Pemohon</th>
                                        <th>Perubahan Data</th>
                                        <th>Foto Baru</th>
                                        <th>Waktu</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php while($r = mysqli_fetch_assoc($exec_req)){ ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['nama_lama']); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($r['username']); ?></small>
                                        </td>
                                        <td>
                                            <ul class="mb-0 ps-3 small text-secondary">
                                                <li><span class="fw-bold">Nama:</span> <?php echo htmlspecialchars($r['nama_baru']); ?></li>
                                                <li><span class="fw-bold">NIP:</span> <?php echo htmlspecialchars($r['nip_baru']); ?></li>
                                                <li><span class="fw-bold">Jabatan:</span> <?php echo htmlspecialchars($r['jabatan_baru']); ?></li>
                                                
                                                <li>
                                                    <span class="fw-bold">Instansi:</span> 
                                                    <?php echo htmlspecialchars($r['instansi_baru']); ?>
                                                    <?php if($r['instansi_baru'] != $r['instansi_lama']) echo " <span class='badge bg-warning text-dark ms-1'>Ubah</span>"; ?>
                                                </li>
                                                <li>
                                                    <span class="fw-bold">Unit:</span> 
                                                    <?php echo htmlspecialchars($r['unit_kerja_baru']); ?>
                                                    <?php if($r['unit_kerja_baru'] != $r['unit_kerja_lama']) echo " <span class='badge bg-warning text-dark ms-1'>Ubah</span>"; ?>
                                                </li>

                                                <?php if(!empty($r['password_baru'])){ ?>
                                                    <li class="text-danger fw-bold mt-1"><i class="bi bi-key-fill me-1"></i> Ganti Password</li>
                                                <?php } ?>
                                            </ul>
                                        </td>
                                        <td>
                                            <?php if(!empty($r['foto_baru'])){ ?>
                                                <a href="uploads/profil/<?php echo $r['foto_baru']; ?>" target="_blank" title="Lihat Foto">
                                                    <img src="uploads/profil/<?php echo $r['foto_baru']; ?>" class="img-req">
                                                </a>
                                            <?php } else { echo "<span class='text-muted small'>-</span>"; } ?>
                                        </td>
                                        <td class="small text-muted"><?php echo date('d M H:i', strtotime($r['tgl_request'])); ?></td>
                                        <td class="text-center">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a href="proses_user.php?aksi=approve_req&id=<?php echo $r['id_req']; ?>" class="btn btn-sm btn-success fw-bold text-white shadow-sm" onclick="return confirm('Setujui perubahan ini?');"><i class="bi bi-check-lg"></i> Setujui</a>
                                                <a href="proses_user.php?aksi=reject_req&id=<?php echo $r['id_req']; ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Tolak permintaan ini?');"><i class="bi bi-x-lg"></i> Tolak</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="text-center py-5">
                            <h6 class="fw-bold text-secondary">Tidak ada permintaan baru</h6>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>