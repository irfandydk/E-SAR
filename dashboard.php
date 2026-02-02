<?php 
// File: sarsip/dashboard.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ 
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

$id_user = $_SESSION['id_user'];
$role    = $_SESSION['role'];

// --- FUNGSI WARNA KATEGORI ---
function getBadgeColor($cat){
    switch($cat){
        case 'Surat Masuk': return 'bg-primary';       // Biru
        case 'Surat Keluar': return 'bg-success';      // Hijau
        case 'SK': return 'bg-warning text-dark';      // Kuning
        case 'Surat Perintah': return 'bg-danger';     // Merah
        case 'Arsip Keuangan': return 'bg-info text-dark'; // Cyan
        case 'Arsip Lainnya': return 'bg-dark';        // Hitam
        default: return 'bg-secondary';                // Abu-abu
    }
}

// 1. CONFIG FILTER PRIVASI
// User biasa hanya melihat dokumen Publik ATAU miliknya sendiri
$filter_user = ($role != 'admin') ? " AND (visibility='public' OR uploader_id='$id_user') " : "";

// 2. QUERY STATISTIK
// A. Total Arsip
$q_all = "SELECT COUNT(*) as total FROM documents WHERE 1=1 $filter_user";
$d_all = mysqli_fetch_assoc(mysqli_query($koneksi, $q_all));
$total_docs = $d_all['total'];

// B. Pending Tanda Tangan (Khusus User ybs)
$q_pending = "SELECT COUNT(*) as total FROM doc_signers WHERE id_user='$id_user' AND status='pending'";
$d_pending = mysqli_fetch_assoc(mysqli_query($koneksi, $q_pending));
$count_pending = $d_pending['total'];

// C. Statistik Per Kategori
$stats = [
    'Surat Masuk'=>0, 'Surat Keluar'=>0, 'SK'=>0, 
    'Surat Perintah'=>0, 'Surat Pernyataan'=>0, 
    'Arsip Keuangan'=>0, 'Arsip Operasi SAR'=>0, 
    'Arsip Sumberdaya'=>0, 'Arsip Lainnya'=>0
];
$q_kat = "SELECT kategori, COUNT(*) as jumlah FROM documents WHERE 1=1 $filter_user GROUP BY kategori";
$res_kat = mysqli_query($koneksi, $q_kat);
while($row = mysqli_fetch_assoc($res_kat)){
    if(isset($stats[$row['kategori']])) $stats[$row['kategori']] = $row['jumlah'];
}

// 3. PENCARIAN & DATA TERBARU
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$is_searching = !empty($keyword);
$results = [];

if($is_searching){
    $key_safe = mysqli_real_escape_string($koneksi, $keyword);
    // Cari di Nomor, Judul, Asal, atau Tujuan
    $q_s = "SELECT * FROM documents 
            WHERE (nomor_surat LIKE '%$key_safe%' OR judul LIKE '%$key_safe%' OR asal_surat LIKE '%$key_safe%' OR tujuan_surat LIKE '%$key_safe%') 
            $filter_user 
            ORDER BY created_at DESC LIMIT 20";
    $exec = mysqli_query($koneksi, $q_s);
    while($r = mysqli_fetch_assoc($exec)) $results[] = $r;
} else {
    // Data Terbaru (Recent Activity)
    $q_r = "SELECT * FROM documents WHERE 1=1 $filter_user ORDER BY created_at DESC LIMIT 7";
    $exec = mysqli_query($koneksi, $q_r);
    while($r = mysqli_fetch_assoc($exec)) $results[] = $r;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard - <?php echo isset($app_name)?$app_name:'SARSIP'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root { --primary-orange: #fd7e14; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        
        /* Layout Utama */
        .main-content { margin-left: 280px; padding: 30px; transition: margin 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0 !important; padding: 15px; padding-top: 80px; } }
        
        /* Kartu Statistik */
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: 0.3s; background: white; }
        .card-stat:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        
        .bg-soft-orange { background-color: #fff4e6; color: #fd7e14; }
        .bg-soft-green { background-color: #d1e7dd; color: #198754; }
        .bg-soft-red { background-color: #f8d7da; color: #dc3545; }
        .bg-soft-blue { background-color: #e7f5ff; color: #0d6efd; }
        .bg-soft-purple { background-color: #e0cffc; color: #6f42c1; }

        /* --- STYLE SEARCH BOX & SUGGESTION --- */
        .search-container { position: relative; max-width: 400px; width: 100%; }
        .search-box { border-radius: 50px; padding-left: 20px; border: 1px solid #ced4da; }
        
        #suggesstion-box {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 999;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        #suggesstion-box ul { list-style: none; padding: 0; margin: 0; }
        #suggesstion-box li {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            font-size: 0.9rem;
            display: block;
        }
        #suggesstion-box li:hover { background-color: #f8f9fa; color: #fd7e14; }
        #suggesstion-box li small { display: block; color: #888; font-size: 0.75rem; }

        /* --- MODAL LIVE PREVIEW --- */
        .modal-fullscreen-custom { max-width: 90%; margin: 1.75rem auto; }
        .pdf-frame { width: 100%; height: 80vh; border: none; background-color: #525659; }
    </style>
</head>
<body>

    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Dashboard Arsip</h4>
                    <p class="text-muted small mb-0">Selamat datang, <b><?php echo $_SESSION['nama']; ?></b></p>
                </div>
                
                <div class="search-container">
                    <form action="" method="GET" class="d-flex" autocomplete="off" id="search-form">
                        <input type="text" name="keyword" id="search-input" class="form-control search-box me-2" placeholder="Cari surat / dokumen..." value="<?php echo htmlspecialchars($keyword); ?>">
                        <button type="submit" class="btn btn-primary rounded-circle"><i class="bi bi-search"></i></button>
                    </form>
                    <div id="suggesstion-box"></div>
                </div>
            </div>

            <?php if($count_pending > 0): ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>Perhatian!</strong> Anda memiliki <span class="badge bg-white text-danger fs-6 mx-1"><?php echo $count_pending; ?></span> dokumen yang menunggu tanda tangan.
                    <a href="tanda_tangan.php" class="alert-link ms-2">Proses Sekarang</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($is_searching): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Hasil Pencarian: "<?php echo htmlspecialchars($keyword); ?>"</h6>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-light">Reset</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th>Kategori</th><th>No. Dokumen</th><th>Perihal</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($results as $res): 
                                    // Logika File (untuk tombol aksi di hasil pencarian)
                                    $path_asli   = "uploads/doc_asli/" . $res['file_path'];
                                    $path_signed = "uploads/doc_signed/SIGNED_" . $res['file_path'];
                                    $file_final  = file_exists($path_signed) ? $path_signed : $path_asli;
                                ?>
                                <tr>
                                    <td><span class="badge <?php echo getBadgeColor($res['kategori']); ?>"><?php echo $res['kategori']; ?></span></td>
                                    <td class="fw-bold"><?php echo $res['nomor_surat']; ?></td>
                                    <td><?php echo $res['judul']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($res['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="previewFile('<?php echo $file_final; ?>', '<?php echo $res['nomor_surat']; ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; if(empty($results)) echo "<tr><td colspan='5' class='text-center py-4'>Tidak ditemukan data yang sesuai.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-warning">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-soft-orange me-3"><i class="bi bi-folder-fill"></i></div>
                            <div><h3 class="fw-bold mb-0 text-dark"><?php echo $total_docs; ?></h3><small class="text-muted">Total Arsip</small></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-success">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-soft-green me-3"><i class="bi bi-envelope-paper-fill"></i></div>
                            <div><h3 class="fw-bold mb-0 text-dark"><?php echo $stats['Surat Masuk']; ?></h3><small class="text-muted">Surat Masuk</small></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-stat p-3 h-100 border-start border-4 border-danger">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-soft-red me-3"><i class="bi bi-pen-fill"></i></div>
                            <div><h3 class="fw-bold mb-0 text-dark"><?php echo $count_pending; ?></h3><small class="text-muted">Pending Tanda Tangan</small></div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="text-uppercase text-muted fw-bold small mb-3">Detail Kategori</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat p-3 h-100">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-soft-blue text-primary">Admin Umum</span>
                            <i class="bi bi-file-text text-muted"></i>
                        </div>
                        <div class="small text-muted mb-1">Surat Keluar: <b class="text-dark"><?php echo $stats['Surat Keluar']; ?></b></div>
                        <div class="small text-muted mb-1">SK (Keputusan): <b class="text-dark"><?php echo $stats['SK']; ?></b></div>
                        <div class="small text-muted">Perintah: <b class="text-dark"><?php echo $stats['Surat Perintah']; ?></b></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat p-3 h-100">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-soft-green text-success">Unit / Bidang</span>
                            <i class="bi bi-diagram-3 text-muted"></i>
                        </div>
                        <div class="small text-muted mb-1">Keuangan: <b class="text-dark"><?php echo $stats['Arsip Keuangan']; ?></b></div>
                        <div class="small text-muted mb-1">Operasi SAR: <b class="text-dark"><?php echo $stats['Arsip Operasi SAR']; ?></b></div>
                        <div class="small text-muted">Sumberdaya: <b class="text-dark"><?php echo $stats['Arsip Sumberdaya']; ?></b></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat p-3 h-100">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-soft-purple text-dark">Lainnya</span>
                            <i class="bi bi-archive text-muted"></i>
                        </div>
                        <div class="small text-muted mb-1">Pernyataan: <b class="text-dark"><?php echo $stats['Surat Pernyataan']; ?></b></div>
                        <div class="small text-muted mb-1">Umum: <b class="text-dark"><?php echo $stats['Arsip Lainnya']; ?></b></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat h-100 bg-primary text-white text-center d-flex flex-column justify-content-center p-3" style="min-height: 120px;">
                        <a href="tambah_dokumen.php" class="text-white text-decoration-none stretched-link">
                            <i class="bi bi-cloud-arrow-up fs-1 mb-2"></i>
                            <h6 class="fw-bold mb-0">Upload Arsip Baru</h6>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Terbaru</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Kategori</th><th>Nomor</th><th>Judul / Perihal</th><th>Sifat</th><th>Waktu</th><th class="text-end">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($results as $doc): 
                                    // LOGIKA FILE TERAKHIR (Tampilkan file SIGNED jika ada)
                                    $path_asli   = "uploads/doc_asli/" . $doc['file_path'];
                                    $path_signed = "uploads/doc_signed/SIGNED_" . $doc['file_path'];
                                    
                                    // Jika file signed ada, pakai itu. Jika tidak, pakai file asli.
                                    $file_final  = file_exists($path_signed) ? $path_signed : $path_asli;
                                    $has_signed  = file_exists($path_signed);
                                ?>
                                <tr>
                                    <td><span class="badge <?php echo getBadgeColor($doc['kategori']); ?> rounded-pill" style="font-size: 0.75rem;"><?php echo $doc['kategori']; ?></span></td>
                                    
                                    <td class="fw-bold text-dark small"><?php echo $doc['nomor_surat']; ?></td>
                                    <td><?php echo $doc['judul']; ?></td>
                                    <td>
                                        <?php if($doc['visibility']=='private'){ ?>
                                            <i class="bi bi-lock-fill text-danger" title="Rahasia"></i>
                                        <?php } else { ?>
                                            <i class="bi bi-globe-americas text-success" title="Publik"></i>
                                        <?php } ?>
                                    </td>
                                    <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="previewFile('<?php echo $file_final; ?>', '<?php echo $doc['nomor_surat']; ?>')" title="Preview Dokumen">
                                            <i class="bi bi-eye"></i> 
                                            <?php if($has_signed) echo "<i class='bi bi-qr-code ms-1' style='font-size:0.7em'></i>"; ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-custom"> 
            <div class="modal-content">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title" id="previewTitle">Preview Dokumen</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 bg-secondary">
                    <iframe id="pdfFrame" class="pdf-frame" src=""></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function(){
        
        // 1. AJAX AUTO-SUGGEST PENCARIAN
        $("#search-input").keyup(function(){
            var query = $(this).val();
            if(query.length > 1){
                $.ajax({
                    type: "POST",
                    url: "ajax_search.php",
                    data: { keyword: query },
                    success: function(data){
                        $("#suggesstion-box").show();
                        $("#suggesstion-box").html(data);
                    }
                });
            } else {
                $("#suggesstion-box").hide();
            }
        });

        // 2. KLIK HASIL SUGESTI
        $(document).on('click', '#suggesstion-box li', function(){
            var val = $(this).data('search'); // Ambil data bersih
            $("#search-input").val(val);
            $("#suggesstion-box").hide();
            $("#search-form").submit(); // Langsung cari
        });

        // 3. TUTUP SUGESTI JIKA KLIK LUAR
        $(document).click(function(e) {
            if (!$(e.target).closest('.search-container').length) {
                $('#suggesstion-box').hide();
            }
        });

    });

    // 4. FUNGSI PREVIEW MODAL
    function previewFile(url, title) {
        // Tambahkan #toolbar=0 agar tampilan PDF bersih dari menu browser
        document.getElementById('pdfFrame').src = url + "#toolbar=0"; 
        document.getElementById('previewTitle').innerText = "Preview: " + title;
        
        var myModal = new bootstrap.Modal(document.getElementById('previewModal'));
        myModal.show();
    }
    </script>
</body>
</html>