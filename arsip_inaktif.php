<?php
// File: sarsip/arsip_inaktif.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php"); exit;
}

// Query Arsip INAKTIF (Sudah lewat tanggal)
$query = "SELECT documents.*, users.nama_lengkap 
          FROM documents 
          LEFT JOIN users ON documents.id_user = users.id_user 
          WHERE status_retensi = 'inaktif' 
          ORDER BY tgl_retensi DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Arsip Inaktif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>
    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
    </style>
</head>
<body>

<?php include 'sidebar_menu.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <h4 class="fw-bold mb-4 text-danger"><i class="bi bi-trash3-fill me-2"></i>Daftar Arsip Inaktif</h4>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        Dokumen ini <b>sudah habis masa retensinya</b>. Siap untuk dipindahkan ke Gudang Arsip atau dimusnahkan.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nomor & Judul Surat</th>
                                <th>Tgl Upload</th>
                                <th>Tgl Kadaluarsa (Retensi)</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($result) > 0){
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)){
                            ?>
                            <tr class="table-danger border-danger-subtle">
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['judul']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['nomor_surat']); ?></small>
                                </td>
                                <td class="small"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td class="fw-bold text-danger">
                                    <?php echo date('d M Y', strtotime($row['tgl_retensi'])); ?>
                                </td>
                                <td><span class="badge bg-danger">INAKTIF</span></td>
                                <td class="text-center">
                                    <a href="proses_dokumen.php?aksi=hapus&id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus Permanen/Musnahkan arsip ini?');" title="Musnahkan">
                                        <i class="bi bi-fire"></i> Musnahkan
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Tidak ada arsip inaktif. Semua dokumen masih berlaku.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>