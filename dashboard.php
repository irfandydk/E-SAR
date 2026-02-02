<?php 
// File: sarsip/dashboard.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ 
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

$id_user = $_SESSION['id_user'];
$role    = $_SESSION['role'];

// --- 1. OTOMATISASI STATUS RETENSI (AUTO-CHECK) ---
// Sistem otomatis mengubah status menjadi 'inaktif' jika tanggal retensi sudah lewat hari ini
mysqli_query($koneksi, "UPDATE documents SET status_retensi = 'inaktif' WHERE tgl_retensi < CURDATE() AND status_retensi = 'aktif'");
// ---------------------------------------------------------------

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

// 2. CONFIG FILTER PRIVASI (FIXED AMBIGUOUS COLUMN)
// Kita tambahkan 'd.' di depan id_user agar tidak bentrok dengan tabel users
$filter_user = ($role != 'admin') ? " AND (d.visibility='public' OR d.id_user='$id_user') " : "";

// 3. QUERY STATISTIK (Semua tabel documents dialiaskan sebagai 'd')
// A. Total Arsip
$q_all = "SELECT COUNT(*) as total FROM documents d WHERE 1=1 $filter_user";
$d_all = mysqli_fetch_assoc(mysqli_query($koneksi, $q_all));

// B. Dokumen Terbaru (30 Hari)
$q_new = "SELECT COUNT(*) as total FROM documents d WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) $filter_user";
$d_new = mysqli_fetch_assoc(mysqli_query($koneksi, $q_new));

// C. Upload Saya (User Only)
// Query ini aman karena tidak pakai filter_user (spesifik id_user)
$q_my  = "SELECT COUNT(*) as total FROM documents WHERE id_user='$id_user'";
$d_my  = mysqli_fetch_assoc(mysqli_query($koneksi, $q_my));

// D. Total User (Admin Only)
$d_user['total'] = 0;
if($role == 'admin'){
    $q_usr = "SELECT COUNT(*) as total FROM users";
    $d_usr = mysqli_fetch_assoc(mysqli_query($koneksi, $q_usr));
    $d_user['total'] = $d_usr['total'];
}

// E. Arsip Aktif
$q_aktif = "SELECT COUNT(*) as total FROM documents d WHERE d.status_retensi='aktif' $filter_user";
$d_aktif = mysqli_fetch_assoc(mysqli_query($koneksi, $q_aktif));

// F. Arsip Inaktif
$q_inaktif = "SELECT COUNT(*) as total FROM documents d WHERE d.status_retensi='inaktif' $filter_user";
$d_inaktif = mysqli_fetch_assoc(mysqli_query($koneksi, $q_inaktif));

// 4. QUERY TABEL DATA (LIMIT 5 TERBARU)
// JOIN aman karena filter menggunakan 'd.id_user'
$query = "SELECT d.*, u.nama_lengkap AS uploader 
          FROM documents d
          JOIN users u ON d.id_user = u.id_user
          WHERE 1=1 $filter_user
          ORDER BY d.created_at DESC LIMIT 5";
$exec = mysqli_query($koneksi, $query);

// Cek error jika query utama gagal
if (!$exec) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sistem Arsip Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>
    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        
        /* Style Search Ajax */
        #suggesstion-box { display:none; position:absolute; z-index:999; background:#fff; width:100%; border:1px solid #ddd; border-radius:0 0 5px 5px; max-height:200px; overflow-y:auto; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
        #suggesstion-box li { padding:10px; cursor:pointer; border-bottom:1px solid #f1f1f1; list-style:none; }
        #suggesstion-box li:hover { background:#f8f9fa; color:#0d6efd; }
    </style>
</head>
<body>

    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-6">
                    <h4 class="fw-bold mb-0">Dashboard Overview</h4>
                    <p class="text-muted small">Selamat datang, <span class="text-primary fw-bold"><?php echo htmlspecialchars($_SESSION['nama']); ?></span></p>
                </div>
                <div class="col-md-6">
                    <div class="position-relative search-container">
                        <form action="data_dokumen.php" method="GET" id="search-form">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="cari" id="search-input" class="form-control border-start-0 ps-0" placeholder="Cari dokumen cepat (AJAX)..." autocomplete="off">
                                <button class="btn btn-primary" type="submit">Cari</button>
                            </div>
                        </form>
                        <ul id="suggesstion-box" class="list-group"></ul>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary text-white">
                        <div class="card-body">
                            <h6 class="text-white-50"><i class="bi bi-folder2-open me-2"></i>Total Arsip</h6>
                            <h2 class="fw-bold mb-0"><?php echo $d_all['total']; ?></h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                        <div class="card-body">
                            <h6 class="text-white-50"><i class="bi bi-check-circle me-2"></i>Arsip Aktif</h6>
                            <h2 class="fw-bold mb-0"><?php echo $d_aktif['total']; ?></h2>
                            <small class="text-white-50" style="font-size: 0.75rem;">Masa berlaku aktif</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-danger text-white">
                        <div class="card-body">
                            <h6 class="text-white-50"><i class="bi bi-trash3 me-2"></i>Arsip Inaktif</h6>
                            <h2 class="fw-bold mb-0"><?php echo $d_inaktif['total']; ?></h2>
                            <small class="text-white-50" style="font-size: 0.75rem;">Siap dimusnahkan</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <?php if($role == 'admin'){ ?>
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-info text-white">
                            <div class="card-body">
                                <h6 class="text-white-50"><i class="bi bi-people me-2"></i>Total User</h6>
                                <h2 class="fw-bold mb-0"><?php echo $d_user['total']; ?></h2>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-secondary text-white">
                            <div class="card-body">
                                <h6 class="text-white-50"><i class="bi bi-cloud-upload me-2"></i>Upload Saya</h6>
                                <h2 class="fw-bold mb-0"><?php echo $d_my['total']; ?></h2>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-clock-history me-2"></i>5 Dokumen Terakhir</h6>
                    <a href="data_dokumen.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th>Nomor & Judul</th>
                                    <th>Kategori</th>
                                    <th>Pengunggah</th>
                                    <th>Status Retensi</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(mysqli_num_rows($exec) > 0){
                                    $no=1;
                                    while($row = mysqli_fetch_assoc($exec)){
                                        // Badge Visibility
                                        $vis_badge = ($row['visibility'] == 'public') 
                                            ? '<i class="bi bi-globe text-success" title="Publik"></i>' 
                                            : '<i class="bi bi-lock-fill text-danger" title="Private"></i>';

                                        // Badge Retensi
                                        $ret_badge = ($row['status_retensi'] == 'aktif') 
                                            ? '<span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Aktif</span>'
                                            : '<span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">Inaktif</span>';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width:250px;">
                                            <?php echo htmlspecialchars($row['judul']); ?> <?php echo $vis_badge; ?>
                                        </div>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($row['nomor_surat']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getBadgeColor($row['kategori']); ?> fw-normal">
                                            <?php echo $row['kategori']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-dark fw-semibold"><?php echo htmlspecialchars($row['uploader']); ?></small><br>
                                        <small class="text-muted" style="font-size:0.75rem;">
                                            <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo $ret_badge; ?><br>
                                        <small class="text-muted" style="font-size:0.7rem;">
                                            Exp: <?php echo ($row['tgl_retensi'] == '9999-12-31') ? '∞' : date('d/m/Y', strtotime($row['tgl_retensi'])); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light border shadow-sm text-primary" 
                                                onclick="previewFile('uploads/doc_asli/<?php echo $row['file_path']; ?>', '<?php echo htmlspecialchars($row['judul'], ENT_QUOTES); ?>')" 
                                                data-bs-toggle="modal" data-bs-target="#previewModal">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Belum ada dokumen yang diupload.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 overflow-hidden">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title" id="previewTitle"><i class="bi bi-file-pdf me-2"></i>Preview Dokumen</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh; background: #525659;">
                    <iframe id="pdfFrame" src="" width="100%" height="100%" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function(){
            // 1. EVENT KETIK DI SEARCH
            $("#search-input").keyup(function(){
                var query = $(this).val();
                if(query != "" && query.length > 1){
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
                var val = $(this).data('search'); 
                $("#search-input").val(val);
                $("#suggesstion-box").hide();
                $("#search-form").submit(); 
            });

            // 3. TUTUP SUGESTI JIKA KLIK LUAR
            $(document).click(function(e) {
                if (!$(e.target).closest('.search-container').length) {
                    $('#suggesstion-box').hide();
                }
            });
        });

        function previewFile(url, title) {
            document.getElementById('pdfFrame').src = url + "#toolbar=0"; 
            document.getElementById('previewTitle').innerText = "Preview: " + title;
        }
    </script>
</body>
</html>
