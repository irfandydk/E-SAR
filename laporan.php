<?php 
// File: sarsip/laporan.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ 
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

// --- FUNGSI WARNA KATEGORI (TETAP ADA) ---
function getBadgeColor($cat){
    switch($cat){
        case 'Surat Masuk': return 'bg-primary';
        case 'Surat Keluar': return 'bg-success';
        case 'SK': return 'bg-warning text-dark';
        case 'Surat Perintah': return 'bg-danger';
        case 'Arsip Keuangan': return 'bg-info text-dark';
        case 'Arsip Lainnya': return 'bg-dark';
        default: return 'bg-secondary';
    }
}

// Default Data
$data_laporan = [];
$tgl_awal  = date('Y-m-01');
$tgl_akhir = date('Y-m-d');
$kategori  = "";
$status_retensi = ""; // Variabel baru
$show      = false;

if(isset($_GET['filter'])){
    $kategori  = isset($_GET['kategori']) ? $_GET['kategori'] : '';
    $status_retensi = isset($_GET['status']) ? $_GET['status'] : ''; // Tangkap filter status
    $tgl_awal  = $_GET['tgl_awal'];
    $tgl_akhir = $_GET['tgl_akhir'];
    $show      = true;

    // QUERY DASAR (JOIN USERS TETAP ADA)
    $q = "SELECT d.*, u.nama_lengkap 
          FROM documents d 
          LEFT JOIN users u ON d.id_user = u.id_user 
          WHERE DATE(d.created_at) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
    
    // Filter Kategori
    if(!empty($kategori)){
        $q .= " AND d.kategori = '$kategori'";
    }

    // Filter Status Retensi (BARU)
    if(!empty($status_retensi) && $status_retensi != 'semua'){
        $q .= " AND d.status_retensi = '$status_retensi'";
    }

    $q .= " ORDER BY d.created_at ASC";
    
    $exec = mysqli_query($koneksi, $q);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arsip</title>
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
            
            <h4 class="fw-bold mb-4"><i class="bi bi-printer-fill me-2 text-primary"></i>Laporan Arsip</h4>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form action="" method="GET">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Dari Tanggal</label>
                                <input type="date" name="tgl_awal" class="form-control" value="<?php echo $tgl_awal; ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Sampai Tanggal</label>
                                <input type="date" name="tgl_akhir" class="form-control" value="<?php echo $tgl_akhir; ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="">-- Semua Kategori --</option>
                                    <option value="Surat Masuk" <?php echo ($kategori == 'Surat Masuk') ? 'selected' : ''; ?>>Surat Masuk</option>
                                    <option value="Surat Keluar" <?php echo ($kategori == 'Surat Keluar') ? 'selected' : ''; ?>>Surat Keluar</option>
                                    <option value="SK" <?php echo ($kategori == 'SK') ? 'selected' : ''; ?>>SK</option>
                                    <option value="Surat Perintah" <?php echo ($kategori == 'Surat Perintah') ? 'selected' : ''; ?>>Surat Perintah</option>
                                    <option value="Arsip Keuangan" <?php echo ($kategori == 'Arsip Keuangan') ? 'selected' : ''; ?>>Arsip Keuangan</option>
                                    <option value="Arsip Lainnya" <?php echo ($kategori == 'Arsip Lainnya') ? 'selected' : ''; ?>>Arsip Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Status Arsip</label>
                                <select name="status" class="form-select border-primary text-primary fw-bold">
                                    <option value="semua" <?php echo ($status_retensi == 'semua') ? 'selected' : ''; ?>>-- Semua --</option>
                                    <option value="aktif" <?php echo ($status_retensi == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="inaktif" <?php echo ($status_retensi == 'inaktif') ? 'selected' : ''; ?>>Inaktif (Exp)</option>
                                </select>
                            </div>

                            <div class="col-md-12 d-flex gap-2">
                                <button type="submit" name="filter" class="btn btn-primary px-4">
                                    <i class="bi bi-search me-1"></i> Tampilkan
                                </button>
                                
                                <?php if($show){ ?>
                                    <a href="cetak_laporan.php?tgl_awal=<?php echo $tgl_awal; ?>&tgl_akhir=<?php echo $tgl_akhir; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status_retensi; ?>" target="_blank" class="btn btn-danger px-4">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Cetak PDF
                                    </a>
                                <?php } ?>
                                
                                <a href="laporan.php" class="btn btn-light border px-4">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if($show){ ?>
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0">Preview Data Laporan</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        
                                        <?php if($kategori == 'Surat Masuk'){ ?>
                                            <th>Tgl Terima</th>
                                            <th>Asal Surat</th>
                                            <th>Nomor Surat</th>
                                        <?php } else { ?>
                                            <th>Nomor Dokumen</th>
                                            <?php if(empty($kategori)){ ?> <th>Kategori</th> <?php } ?>
                                        <?php } ?>

                                        <th>Judul / Perihal</th>
                                        
                                        <th>Status Retensi</th>
                                        <th>Tgl Expired</th>

                                        <?php if($kategori != 'Surat Masuk'){ ?>
                                            <th>Pengunggah</th>
                                        <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if(mysqli_num_rows($exec) > 0){
                                        $no=1;
                                        while($d = mysqli_fetch_assoc($exec)){
                                            
                                            // Badge Status
                                            $st_badge = ($d['status_retensi'] == 'aktif') 
                                                ? '<span class="badge bg-success">Aktif</span>' 
                                                : '<span class="badge bg-danger">Inaktif</span>';
                                            
                                            $tgl_exp = ($d['tgl_retensi'] == '9999-12-31') ? '∞' : date('d/m/Y', strtotime($d['tgl_retensi']));
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>

                                            <?php if($kategori == 'Surat Masuk'){ ?>
                                                <td><?php echo date('d/m/Y', strtotime($d['created_at'])); ?></td>
                                                <td><?php echo $d['asal_surat']; ?></td>
                                                <td><?php echo $d['nomor_surat']; ?></td>
                                                <td><?php echo $d['judul']; ?></td>
                                            <?php } else { ?>
                                                <td><?php echo $d['nomor_surat']; ?></td>
                                                
                                                <?php if(empty($kategori)){ ?>
                                                    <td>
                                                        <span class="badge <?php echo getBadgeColor($d['kategori']); ?>"><?php echo $d['kategori']; ?></span>
                                                    </td>
                                                <?php } ?>
                                                
                                                <td><?php echo $d['judul']; ?></td>
                                            <?php } ?>
                                            
                                            <td><?php echo $st_badge; ?></td>
                                            <td class="text-muted small"><?php echo $tgl_exp; ?></td>

                                            <?php if($kategori != 'Surat Masuk'){ ?>
                                                <td><?php echo $d['nama_lengkap']; ?></td>
                                            <?php } ?>
                                        </tr>
                                    <?php }} else { ?>
                                        <tr><td colspan="8" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
