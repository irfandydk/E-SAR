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

// --- MEMBANGUN QUERY UTAMA (CORE FIX) ---
// Kita gunakan alias d (documents) dan u (users)
// LEFT JOIN: Agar jika user dihapus, dokumen tetap tampil (dengan nama uploader kosong)

$where = " WHERE 1=1 "; // Base condition

// 1. FILTER PRIVASI (VISIBILITY)
if($role != 'admin'){
    // User biasa/PIC melihat: Dokumen Publik ATAU Dokumen Milik Sendiri
    // Logika ini menjamin dokumen yang baru diupload (milik sendiri) PASTI MUNCUL
    $where .= " AND (d.visibility = 'public' OR d.id_user = '$id_user') ";
}

// 2. FILTER PENCARIAN
if(!empty($cari)){
    $where .= " AND (d.judul LIKE '%$cari%' OR d.nomor_surat LIKE '%$cari%' OR d.kategori LIKE '%$cari%') ";
}

// 3. FILTER KATEGORI (Dari URL / Sidebar)
if(!empty($kategori_filter)){
    $where .= " AND d.kategori = '$kategori_filter' ";
}

// --- EKSEKUSI QUERY ---
// Hitung Total Data (untuk Pagination)
$query_total = "SELECT count(*) as total FROM documents d $where";
$result_total = mysqli_query($koneksi, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_data = $row_total['total'];
$total_halaman = ceil($total_data / $batas);

// Ambil Data Halaman Ini
$query = "SELECT d.*, u.nama_lengkap 
          FROM documents d 
          LEFT JOIN users u ON d.id_user = u.id_user 
          $where 
          ORDER BY d.created_at DESC 
          LIMIT $halaman_awal, $batas";
$result = mysqli_query($koneksi, $query);

// Cek error query
if(!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}
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
                                <input type="text" name="cari" class="form-control border-start-0 bg-light" placeholder="Cari Judul / Nomor Surat..." value="<?php echo htmlspecialchars($cari); ?>">
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
                                    <th class="text-center" width="5%">
                                        <input type="checkbox" class="form-check-input" id="checkAll">
                                    </th>
                                    <th>No</th>
                                    <th>Nomor & Judul</th>
                                    <th>Kategori</th>
                                    <th>Pengunggah</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(mysqli_num_rows($result) > 0){
                                    while($row = mysqli_fetch_assoc($result)){
                                        
                                        // Badge Visibility
                                        $vis = ($row['visibility']=='public') 
                                            ? '<i class="bi bi-globe text-success" title="Publik"></i>' 
                                            : '<i class="bi bi-lock-fill text-danger" title="Private"></i>';
                                        
                                        // Nama Uploader
                                        $uploader = !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : '<span class="text-muted fst-italic">User Dihapus</span>';
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="pilih[]" value="<?php echo $row['id_doc']; ?>" class="form-check-input check-item">
                                    </td>
                                    <td><?php echo $nomor++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['judul']); ?> <?php echo $vis; ?></div>
                                        <small class="text-muted bg-light px-2 py-1 rounded border"><?php echo htmlspecialchars($row['nomor_surat']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getBadgeColor($row['kategori']); ?> rounded-pill fw-normal px-3">
                                            <?php echo $row['kategori']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-dark"><?php echo $uploader; ?></div>
                                        <div class="small text-muted" style="font-size: 0.75rem;">
                                            <?php echo date('d M Y H:i', strtotime($row['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="uploads/doc_asli/<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat"><i class="bi bi-eye"></i></a>
                                            
                                            <?php if($role == 'admin' || $row['id_user'] == $id_user) { ?>
                                                <a href="edit_dokumen.php?id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="proses_dokumen.php?aksi=hapus&id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus dokumen ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Tidak ada dokumen ditemukan.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </form> </div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script Checkbox All
    document.getElementById('checkAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.check-item');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    // Script Submit Bulk Action
    function submitBulk(action) {
        var checkedCount = document.querySelectorAll('.check-item:checked').length;
        if(checkedCount === 0){
            alert('Pilih minimal satu dokumen!');
            return;
        }
        
        if(action === 'hapus_banyak'){
            if(!confirm('Yakin ingin menghapus ' + checkedCount + ' dokumen yang dipilih?')) return;
        }

        document.getElementById('bulkAksi').value = action;
        document.getElementById('formBulk').submit();
    }
</script>
</body>
</html>
