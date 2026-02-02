<?php 
// File: sarsip/surat_masuk.php
session_start();

// Cek Login
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit;
}

include 'config/koneksi.php';

// ==========================================
// LOGIKA HAPUS SURAT (HANYA ADMIN)
// ==========================================
if($_SESSION['role'] == 'admin'){
    
    // 1. HAPUS SATUAN
    if(isset($_GET['hapus_id'])){
        $id = mysqli_real_escape_string($koneksi, $_GET['hapus_id']);
        hapus_surat($koneksi, $id);
        header("location:surat_masuk.php?pesan=hapus_sukses");
        exit;
    }

    // 2. HAPUS MASSAL
    if(isset($_POST['aksi']) && $_POST['aksi'] == 'hapus_banyak'){
        if(!empty($_POST['ids'])){
            foreach($_POST['ids'] as $id){
                hapus_surat($koneksi, $id);
            }
            header("location:surat_masuk.php?pesan=hapus_banyak_sukses");
            exit;
        }
    }
}

// Fungsi Hapus Fisik & Database
function hapus_surat($koneksi, $id){
    $q = mysqli_query($koneksi, "SELECT file_path FROM incoming_mail WHERE id_mail='$id'");
    if(mysqli_num_rows($q) > 0){
        $d = mysqli_fetch_assoc($q);
        $file = "uploads/arsip_lain/" . $d['file_path'];
        if(file_exists($file)) unlink($file);
        mysqli_query($koneksi, "DELETE FROM incoming_mail WHERE id_mail='$id'");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Surat Masuk - <?php echo isset($app_name) ? $app_name : 'SARSIP'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; min-height: 100vh; background: #fff; position: fixed; top: 0; left: 0; z-index: 100; border-right: 1px solid #dee2e6; }
        .main-content { margin-left: 280px; padding: 30px; }
        
        .card-header { background: white; padding: 1.5rem; border-bottom: 1px solid #f0f0f0; }
        .btn-orange { background-color: #fd7e14; color: white; border: none; }
        .btn-orange:hover { background-color: #e36e10; color: white; }
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
                    <h4 class="fw-bold text-dark mb-1">Arsip Surat Masuk</h4>
                    <p class="text-muted small mb-0">Kelola surat-surat yang diterima instansi.</p>
                </div>
            </div>

            <?php if($_SESSION['role'] == 'admin') { ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-plus-circle me-2"></i>Registrasi Surat Masuk Baru</h6>
                </div>
                <div class="card-body p-4">
                    <form action="proses_surat_masuk.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted">Nomor Surat</label>
                                <input type="text" name="nomor_surat" class="form-control" placeholder="No. Surat Pengirim" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted">Asal Surat / Pengirim</label>
                                <input type="text" name="asal_surat" class="form-control" placeholder="Instansi Pengirim" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted">Tanggal Terima</label>
                                <input type="date" name="tgl_terima" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Perihal / Judul</label>
                                <input type="text" name="judul" class="form-control" placeholder="Inti surat..." required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label small fw-bold text-muted">Sifat Dokumen</label>
                                <select name="visibility" class="form-select">
                                    <option value="public">Publik (Semua User)</option>
                                    <option value="private">Rahasia (Admin Saja)</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label small fw-bold text-muted">File Scan (PDF)</label>
                                <input type="file" name="file_surat" class="form-control" accept=".pdf" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="simpan_surat" class="btn btn-orange px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Surat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php } ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    
                    <form action="" method="POST" id="formBulkDelete">
                        <input type="hidden" name="aksi" value="hapus_banyak">

                        <div class="d-flex justify-content-between mb-3">
                            <h5 class="card-title fw-bold text-dark">Data Surat Masuk</h5>
                            <?php if($_SESSION['role'] == 'admin') { ?>
                            <button type="button" id="btnBulkDelete" class="btn btn-outline-danger btn-sm" disabled>
                                <i class="bi bi-trash"></i> Hapus Terpilih
                            </button>
                            <?php } ?>
                        </div>

                        <div class="table-responsive">
                            <table id="tableSurat" class="table table-hover align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <?php if($_SESSION['role'] == 'admin') { ?>
                                            <th width="5%" class="text-center"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                                        <?php } ?>
                                        <th width="5%">No</th>
                                        <th width="15%">Tgl Terima</th>
                                        <th width="25%">Info Surat</th>
                                        <th width="30%">Perihal</th>
                                        <?php if($_SESSION['role'] == 'admin') echo "<th width='10%'>Akses</th>"; ?>
                                        <th width="10%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no=1;
                                    // Query sesuai Role
                                    if($_SESSION['role'] == 'admin'){
                                        $q = mysqli_query($koneksi, "SELECT * FROM incoming_mail ORDER BY tgl_terima DESC, id_mail DESC");
                                    } else {
                                        $q = mysqli_query($koneksi, "SELECT * FROM incoming_mail WHERE visibility='public' ORDER BY tgl_terima DESC");
                                    }

                                    while($d = mysqli_fetch_array($q)){
                                    ?>
                                    <tr>
                                        <?php if($_SESSION['role'] == 'admin') { ?>
                                            <td class="text-center">
                                                <input type="checkbox" name="ids[]" value="<?php echo $d['id_mail']; ?>" class="form-check-input check-item">
                                            </td>
                                        <?php } ?>
                                        
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <span class="text-dark fw-bold"><?php echo date('d M Y', strtotime($d['tgl_terima'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($d['nomor_surat']); ?></div>
                                            <div class="small text-muted"><i class="bi bi-building"></i> <?php echo htmlspecialchars($d['asal_surat']); ?></div>
                                        </td>
                                        <td class="text-secondary"><?php echo htmlspecialchars($d['judul']); ?></td>
                                        
                                        <?php if($_SESSION['role'] == 'admin') { ?>
                                            <td>
                                                <?php if($d['visibility'] == 'public') { ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Publik</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Rahasia</span>
                                                <?php } ?>
                                            </td>
                                        <?php } ?>

                                        <td class="text-center">
                                            <a href="uploads/arsip_lain/<?php echo $d['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat File">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <?php if($_SESSION['role'] == 'admin') { ?>
                                                <a href="surat_masuk.php?hapus_id=<?php echo $d['id_mail']; ?>" class="btn btn-sm btn-outline-danger btn-alert-hapus" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
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
            // Init DataTables
            $('#tableSurat').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json" }
            });

            // Logika Checkbox "Pilih Semua" (Untuk Admin)
            $('#checkAll').on('change', function() {
                $('.check-item').prop('checked', this.checked);
                toggleBulkBtn();
            });

            $('.check-item').on('change', function() {
                toggleBulkBtn();
            });

            function toggleBulkBtn() {
                if($('.check-item:checked').length > 0) {
                    $('#btnBulkDelete').prop('disabled', false).html('<i class="bi bi-trash"></i> Hapus (' + $('.check-item:checked').length + ')');
                } else {
                    $('#btnBulkDelete').prop('disabled', true).html('<i class="bi bi-trash"></i> Hapus Terpilih');
                }
            }
            
            // SweetAlert untuk Konfirmasi Bulk Delete
            $('#btnBulkDelete').on('click', function() {
                if(confirm('Yakin ingin menghapus data terpilih? File fisik juga akan dihapus.')){
                    $('#formBulkDelete').submit();
                }
            });

            // Konfirmasi Hapus Satuan (Backup jika SweetAlert di sidebar gagal load)
            $('.btn-alert-hapus').on('click', function(e) {
                if(!confirm('Yakin ingin menghapus surat ini?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>