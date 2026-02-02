<?php
// File: sarsip/arsip_aktif.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php"); exit;
}

// --- LAZY UPDATE (Otomatis pindahkan yang kadaluarsa ke Inaktif) ---
mysqli_query($koneksi, "UPDATE documents SET status_retensi = 'inaktif' WHERE tgl_retensi < CURDATE() AND status_retensi = 'aktif'");

// Query Arsip Aktif
$query = "SELECT documents.*, users.nama_lengkap 
          FROM documents 
          LEFT JOIN users ON documents.id_user = users.id_user 
          WHERE status_retensi = 'aktif' 
          ORDER BY tgl_upload DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Arsip Aktif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>
    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        .badge-sisa { font-size: 0.85em; }
    </style>
</head>
<body>

<?php include 'sidebar_menu.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <h4 class="fw-bold mb-4 text-success"><i class="bi bi-archive-fill me-2"></i>Daftar Arsip Aktif</h4>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <div>
                        Dokumen di halaman ini adalah dokumen yang <b>masih dalam masa retensi</b> (Berlaku).
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nomor & Judul Surat</th>
                                <th>Kategori</th>
                                <th>Tgl Upload</th>
                                <th>Masa Retensi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($result) > 0){
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)){
                                    
                                    // Hitung Sisa Waktu
                                    $tgl_retensi = new DateTime($row['tgl_retensi']);
                                    $hari_ini    = new DateTime();
                                    
                                    if($row['tgl_retensi'] == '9999-12-31'){
                                        $sisa_waktu = "<span class='badge bg-primary badge-sisa'>Permanen</span>";
                                    } else {
                                        $jarak = $hari_ini->diff($tgl_retensi);
                                        // Jika kurang dari 30 hari, beri warna merah (warning)
                                        $warna = ($jarak->days < 30) ? 'bg-danger' : 'bg-success';
                                        $sisa_waktu = "<span class='badge $warna badge-sisa'>".$jarak->days." Hari Lagi</span>";
                                    }
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['judul']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['nomor_surat']); ?></small>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?php echo $row['kategori']; ?></span></td>
                                <td class="small"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo ($row['tgl_retensi']=='9999-12-31') ? 'Selamanya' : date('d M Y', strtotime($row['tgl_retensi'])); ?></div>
                                    <?php echo $sisa_waktu; ?>
                                </td>
                                <td class="text-center">
                                    <a href="uploads/doc_asli/<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Belum ada arsip aktif.</td></tr>";
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