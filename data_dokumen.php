<?php
// File: sarsip/data_dokumen.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}

// --- SETUP VARIABEL ---
$role    = $_SESSION['role'];
$id_user = $_SESSION['id_user'];
$cari    = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : "";
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : "";

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

// --- KONFIGURASI PAGINATION ---
$batas = 10;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman>1) ? ($halaman * $batas) - $batas : 0;
$nomor = $halaman_awal + 1;

// --- MEMBANGUN QUERY ---
$where = " WHERE 1=1 "; 

// 1. FILTER PRIVASI
if($role != 'admin'){
    $where .= " AND (d.visibility = 'public' OR d.id_user = '$id_user') ";
}

// 2. FILTER PENCARIAN
if(!empty($cari)){
    $where .= " AND (d.judul LIKE '%$cari%' OR d.nomor_surat LIKE '%$cari%' OR d.kategori LIKE '%$cari%' OR d.asal_surat LIKE '%$cari%' OR d.tujuan_surat LIKE '%$cari%') ";
}

// 3. FILTER KATEGORI
if(!empty($kategori_filter)){
    $where .= " AND d.kategori = '$kategori_filter' ";
}

// Hitung Total Data
$query_total = "SELECT count(*) as total FROM documents d $where";
$result_total = mysqli_query($koneksi, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_data = $row_total['total'];
$total_halaman = ceil($total_data / $batas);

// Ambil Data Utama
$query = "SELECT d.*, u.nama_lengkap 
          FROM documents d 
          LEFT JOIN users u ON d.id_user = u.id_user 
          $where 
          ORDER BY d.created_at DESC 
          LIMIT $halaman_awal, $batas";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Dokumen - SARSIP</title>
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
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-primary">
                <i class="bi bi-folder-fill me-2"></i>Data Arsip 
                <?php if(!empty($kategori_filter)) echo "- " . htmlspecialchars($kategori_filter); ?>
            </h4>
            
            <a href="tambah_dokumen.php<?php echo !empty($kategori_filter) ? '?kategori='.$kategori_filter : ''; ?>" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Upload Dokumen
            </a>
        </div>

        <?php if(isset($_GET['pesan'])){ ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                <?php 
                    if($_GET['pesan']=="sukses") echo "Dokumen berhasil disimpan!";
                    elseif($_GET['pesan']=="update") echo "Dokumen berhasil diperbarui!";
                    elseif($_GET['pesan']=="hapus") echo "Dokumen berhasil dihapus!";
                    elseif($_GET['pesan']=="hapus_banyak") echo "Dokumen terpilih berhasil dihapus!";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <form action="" method="GET">
                            <?php if(!empty($kategori_filter)){ ?>
                                <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori_filter); ?>">
                            <?php } ?>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="cari" class="form-control border-start-0 bg-light" placeholder="Cari Judul / Nomor / Asal / Tujuan..." value="<?php echo htmlspecialchars($cari); ?>">
                                <button class="btn btn-primary" type="submit">Cari</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill" onclick="submitBulk('download_zip')">
                            <i class="bi bi-file-zip me-1"></i> Download ZIP
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" onclick="submitBulk('hapus_banyak')">
                            <i class="bi bi-trash me-1"></i> Hapus Terpilih
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <form action="proses_dokumen.php" method="POST" id="formBulk">
                    <input type="hidden" name="aksi" id="bulkAksi" value="">
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="5%"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                                    <th>No</th>
                                    <th>Nomor & Judul</th>
                                    
                                    <th width="20%">
                                        <?php 
                                        if($kategori_filter == 'Surat Masuk'){
                                            echo "Asal Surat";
                                        } elseif($kategori_filter == 'Surat Keluar'){
                                            echo "Tujuan Surat";
                                        } else {
                                            echo "Asal / Tujuan";
                                        }
                                        ?>
                                    </th>

                                    <th>Kategori</th>
                                    <th>Status / Exp</th>
                                    <th class="text-center" width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(mysqli_num_rows($result) > 0){
                                    while($row = mysqli_fetch_assoc($result)){
                                        
                                        $vis = ($row['visibility']=='public') 
                                            ? '<i class="bi bi-globe text-success" title="Publik"></i>' 
                                            : '<i class="bi bi-lock-fill text-danger" title="Private"></i>';
                                        
                                        $uploader = !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : 'User Dihapus';
                                        $path_file = "uploads/doc_asli/" . $row['file_path'];

                                        // Badge Status Retensi
                                        $badge_retensi = ($row['status_retensi'] == 'aktif') 
                                            ? '<span class="badge bg-success bg-opacity-10 text-success">Aktif</span>'
                                            : '<span class="badge bg-danger bg-opacity-10 text-danger">Inaktif</span>';
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="pilih[]" value="<?php echo $row['id_doc']; ?>" class="form-check-input check-item">
                                    </td>
                                    <td><?php echo $nomor++; ?></td>
                                    
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">
                                            <?php echo htmlspecialchars($row['judul']); ?> <?php echo $vis; ?>
                                        </div>
                                        <small class="text-muted border px-2 rounded bg-light"><?php echo htmlspecialchars($row['nomor_surat']); ?></small>
                                        <div class="small text-muted fst-italic mt-1" style="font-size: 11px;">
                                            By: <?php echo $uploader; ?> • <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php 
                                        if($row['kategori'] == 'Surat Masuk'){
                                            echo '<small class="text-muted d-block">Dari:</small>';
                                            echo '<span class="fw-bold text-dark">'.(!empty($row['asal_surat']) ? htmlspecialchars($row['asal_surat']) : '-').'</span>';
                                        } elseif($row['kategori'] == 'Surat Keluar'){
                                            echo '<small class="text-muted d-block">Kepada:</small>';
                                            echo '<span class="fw-bold text-dark">'.(!empty($row['tujuan_surat']) ? htmlspecialchars($row['tujuan_surat']) : '-').'</span>';
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo getBadgeColor($row['kategori']); ?> rounded-pill fw-normal">
                                            <?php echo $row['kategori']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo $badge_retensi; ?><br>
                                        <small class="text-muted" style="font-size:10px;">
                                            Exp: <?php echo ($row['tgl_retensi']=='9999-12-31') ? '∞' : date('d/m/Y', strtotime($row['tgl_retensi'])); ?>
                                        </small>
                                    </td>

                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="previewFile('<?php echo $path_file; ?>', '<?php echo htmlspecialchars($row['judul'], ENT_QUOTES); ?>')"
                                                    title="Preview"><i class="bi bi-eye"></i></button>

                                            <a href="<?php echo $path_file; ?>" download class="btn btn-sm btn-outline-success" title="Download"><i class="bi bi-download"></i></a>

                                            <a href="form_ttd.php?id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-dark" title="Tanda Tangan"><i class="bi bi-pen"></i></a>

                                            <?php if($role == 'admin' || $row['id_user'] == $id_user) { ?>
                                                <a href="edit_dokumen.php?id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="proses_dokumen.php?aksi=hapus&id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center py-5 text-muted'>Tidak ada dokumen ditemukan.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-white py-3">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php if($halaman <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?halaman=<?php echo $halaman-1; ?>&cari=<?php echo $cari; ?>&kategori=<?php echo $kategori_filter; ?>">Prev</a>
                        </li>
                        <?php for($x=1; $x<=$total_halaman; $x++){ ?>
                            <li class="page-item <?php if($halaman == $x) echo 'active'; ?>">
                                <a class="page-link" href="?halaman=<?php echo $x; ?>&cari=<?php echo $cari; ?>&kategori=<?php echo $kategori_filter; ?>"><?php echo $x; ?></a>
                            </li>
                        <?php } ?>
                        <li class="page-item <?php if($halaman >= $total_halaman) echo 'disabled'; ?>">
                            <a class="page-link" href="?halaman=<?php echo $halaman+1; ?>&cari=<?php echo $cari; ?>&kategori=<?php echo $kategori_filter; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
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
            <div class="modal-body p-0" style="height: 85vh; background: #525659;">
                <iframe id="pdfFrame" src="" width="100%" height="100%" style="border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Check All
    document.getElementById('checkAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.check-item');
        for (var checkbox of checkboxes) { checkbox.checked = this.checked; }
    });

    // Bulk Action
    function submitBulk(action) {
        var checkedCount = document.querySelectorAll('.check-item:checked').length;
        if(checkedCount === 0){ alert('Pilih minimal satu dokumen!'); return; }
        if(action === 'hapus_banyak' && !confirm('Yakin hapus ' + checkedCount + ' dokumen terpilih?')) return;

        document.getElementById('bulkAksi').value = action;
        document.getElementById('formBulk').submit();
    }

    // Preview File
    function previewFile(url, title) {
        var modal = new bootstrap.Modal(document.getElementById('previewModal'));
        document.getElementById('pdfFrame').src = url + "#toolbar=0"; 
        document.getElementById('previewTitle').innerText = "Preview: " + title;
        modal.show();
    }
</script>
</body>
</html>
