<?php 
// File: sarsip/laporan.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ 
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

// --- FUNGSI WARNA KATEGORI ---
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
$show      = false;

if(isset($_GET['filter'])){
    $kategori  = isset($_GET['kategori']) ? $_GET['kategori'] : '';
    $tgl_awal  = $_GET['tgl_awal'];
    $tgl_akhir = $_GET['tgl_akhir'];
    $show      = true;

    // QUERY TUNGGAL KE TABEL DOCUMENTS
    $sql = "SELECT d.*, u.nama_lengkap FROM documents d 
            LEFT JOIN users u ON d.uploader_id = u.id_user
            WHERE DATE(d.created_at) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
    
    if(!empty($kategori)){
        $sql .= " AND d.kategori = '$kategori'";
    }
    
    $sql .= " ORDER BY d.created_at ASC";
    $q = mysqli_query($koneksi, $sql);
    while($d = mysqli_fetch_assoc($q)){ $data_laporan[] = $d; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - <?php echo isset($app_name)?$app_name:'SARSIP'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; min-height: 100vh; background: #fff; position: fixed; top: 0; left: 0; z-index: 100; border-right: 1px solid #dee2e6; }
        .main-content { margin-left: 280px; padding: 30px; }
        
        .sheet { background: white; border: 1px solid #dee2e6; padding: 40px; margin-top: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); min-height: 297mm; }
        .header-laporan { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <?php include 'sidebar_menu.php'; ?>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Laporan Arsip</h4>
                    <p class="text-muted small mb-0">Rekapitulasi Surat Masuk dan Dokumen Internal.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form action="" method="GET">
                        <div class="row align-items-end g-3">
                            
                            <div class="col-md-5">
                                <label class="fw-bold small mb-1">Pilih Kategori Laporan</label>
                                <select name="kategori" class="form-select">
                                    <option value="">-- Semua Dokumen --</option>
                                    <optgroup label="Administrasi Umum">
                                        <option value="Surat Masuk" <?php echo ($kategori=='Surat Masuk')?'selected':''; ?>>Surat Masuk (Agenda)</option>
                                        <option value="Surat Keluar" <?php echo ($kategori=='Surat Keluar')?'selected':''; ?>>Surat Keluar</option>
                                        <option value="SK" <?php echo ($kategori=='SK')?'selected':''; ?>>SK</option>
                                        <option value="Surat Perintah" <?php echo ($kategori=='Surat Perintah')?'selected':''; ?>>Surat Perintah</option>
                                        <option value="Surat Pernyataan" <?php echo ($kategori=='Surat Pernyataan')?'selected':''; ?>>Surat Pernyataan</option>
                                    </optgroup>
                                    <optgroup label="Unit / Bidang">
                                        <option value="Arsip Keuangan" <?php echo ($kategori=='Arsip Keuangan')?'selected':''; ?>>Arsip Keuangan</option>
                                        <option value="Arsip Operasi SAR" <?php echo ($kategori=='Arsip Operasi SAR')?'selected':''; ?>>Arsip Operasi SAR</option>
                                        <option value="Arsip Sumberdaya" <?php echo ($kategori=='Arsip Sumberdaya')?'selected':''; ?>>Arsip Sumberdaya</option>
                                    </optgroup>
                                    <option value="Arsip Lainnya" <?php echo ($kategori=='Arsip Lainnya')?'selected':''; ?>>Arsip Lainnya</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="fw-bold small mb-1">Dari Tanggal</label>
                                <input type="date" name="tgl_awal" class="form-control" value="<?php echo $tgl_awal; ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="fw-bold small mb-1">Sampai Tanggal</label>
                                <input type="date" name="tgl_akhir" class="form-control" value="<?php echo $tgl_akhir; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="filter" class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if($show){ ?>
                <div class="d-flex justify-content-end mb-3">
                    <a href="cetak_laporan.php?kategori=<?php echo urlencode($kategori); ?>&tgl_awal=<?php echo $tgl_awal; ?>&tgl_akhir=<?php echo $tgl_akhir; ?>" target="_blank" class="btn btn-danger shadow-sm fw-bold">
                        <i class="bi bi-file-pdf-fill me-2"></i> Download PDF
                    </a>
                </div>

                <div class="sheet">
                    <div class="header-laporan">
                        <h4 class="fw-bold text-uppercase mb-1"><?php echo isset($app_name)?$app_name:'INSTANSI SAYA'; ?></h4>
                        <h5 class="fw-bold text-uppercase">
                            <?php 
                                if($kategori == 'Surat Masuk') echo "LAPORAN AGENDA SURAT MASUK";
                                elseif(!empty($kategori)) echo "LAPORAN ARSIP " . strtoupper($kategori);
                                else echo "LAPORAN SELURUH ARSIP DOKUMEN";
                            ?>
                        </h5>
                        <p class="mb-0">Periode: <?php echo date('d/m/Y', strtotime($tgl_awal)) . " s/d " . date('d/m/Y', strtotime($tgl_akhir)); ?></p>
                    </div>

                    <table class="table table-bordered align-middle">
                        <thead class="table-light text-center fw-bold">
                            <tr>
                                <th width="5%">No</th>
                                <?php if($kategori == 'Surat Masuk'){ ?>
                                    <th width="20%">Asal Surat (Pengirim)</th>
                                    <th width="20%">Nomor Surat</th>
                                    <th>Perihal</th>
                                    <th width="15%">Tgl Terima</th>
                                <?php } else { ?>
                                    <th width="20%">Nomor Dokumen</th>
                                    <?php if(empty($kategori)) echo "<th width='15%'>Kategori</th>"; ?>
                                    <th>Perihal / Judul</th>
                                    <th width="15%">Tanggal</th>
                                    <th width="15%">Pengupload</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(empty($data_laporan)){
                                echo "<tr><td colspan='6' class='text-center py-5 text-muted fst-italic'>Tidak ada data ditemukan.</td></tr>";
                            } else {
                                $no=1;
                                foreach($data_laporan as $d){
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    
                                    <?php if($kategori == 'Surat Masuk'){ ?>
                                        <td><?php echo $d['asal_surat']; ?></td>
                                        <td><?php echo $d['nomor_surat']; ?></td>
                                        <td><?php echo $d['judul']; ?></td>
                                        <td class="text-center"><?php echo date('d/m/Y', strtotime($d['created_at'])); ?></td>
                                    <?php } else { ?>
                                        <td><?php echo $d['nomor_surat']; ?></td>
                                        
                                        <?php if(empty($kategori)){ ?>
                                            <td class="text-center">
                                                <span class="badge <?php echo getBadgeColor($d['kategori']); ?>"><?php echo $d['kategori']; ?></span>
                                            </td>
                                        <?php } ?>
                                        
                                        <td><?php echo $d['judul']; ?></td>
                                        <td class="text-center"><?php echo date('d/m/Y', strtotime($d['created_at'])); ?></td>
                                        <td><?php echo $d['nama_lengkap']; ?></td>
                                    <?php } ?>
                                </tr>
                            <?php }} ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>