<?php 
// File: sarsip/surat_arsip.php
session_start();
if($_SESSION['status'] != "login"){ header("location:login.php?pesan=belum_login"); }
include 'config/koneksi.php';

// ==========================================
// LOGIKA HAPUS (SATUAN & BULK) - TETAP ADA
// ==========================================

// 1. BULK DELETE SURAT MASUK
if(isset($_POST['bulk_delete_surat'])){
    if(!empty($_POST['ids'])){
        $ids = $_POST['ids'];
        $ids_string = implode(",", $ids);
        $q = mysqli_query($koneksi, "SELECT file_path FROM incoming_mail WHERE id_mail IN ($ids_string)");
        while($d = mysqli_fetch_assoc($q)){
            $file = "uploads/arsip_lain/" . $d['file_path'];
            if(file_exists($file)) unlink($file);
        }
        mysqli_query($koneksi, "DELETE FROM incoming_mail WHERE id_mail IN ($ids_string)");
        header("location:surat_arsip.php?pesan=bulk_surat_sukses&tab=surat"); exit;
    }
}

// 2. BULK DELETE ARSIP EKSTERNAL
if(isset($_POST['bulk_delete_ext'])){
    if(!empty($_POST['ids'])){
        $ids = $_POST['ids'];
        $ids_string = implode(",", $ids);
        $q = mysqli_query($koneksi, "SELECT file_path FROM external_archives WHERE id_ext IN ($ids_string)");
        while($d = mysqli_fetch_assoc($q)){
            $file = "uploads/arsip_lain/" . $d['file_path'];
            if(file_exists($file)) unlink($file);
        }
        mysqli_query($koneksi, "DELETE FROM external_archives WHERE id_ext IN ($ids_string)");
        header("location:surat_arsip.php?pesan=bulk_ext_sukses&tab=eksternal"); exit;
    }
}

// 3. HAPUS SATUAN
if(isset($_GET['hapus_surat'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_surat']);
    $q = mysqli_query($koneksi, "SELECT file_path FROM incoming_mail WHERE id_mail='$id'");
    if(mysqli_num_rows($q) > 0){
        $d = mysqli_fetch_assoc($q);
        $file = "uploads/arsip_lain/" . $d['file_path'];
        if(file_exists($file)) unlink($file);
        mysqli_query($koneksi, "DELETE FROM incoming_mail WHERE id_mail='$id'");
    }
    header("location:surat_arsip.php?pesan=hapus_surat_sukses"); exit;
}
if(isset($_GET['hapus_ext'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_ext']);
    $q = mysqli_query($koneksi, "SELECT file_path FROM external_archives WHERE id_ext='$id'");
    if(mysqli_num_rows($q) > 0){
        $d = mysqli_fetch_assoc($q);
        $file = "uploads/arsip_lain/" . $d['file_path'];
        if(file_exists($file)) unlink($file);
        mysqli_query($koneksi, "DELETE FROM external_archives WHERE id_ext='$id'");
    }
    header("location:surat_arsip.php?pesan=hapus_ext_sukses&tab=eksternal"); exit;
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'surat';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Arsip Terpadu - <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <style>
        :root { --primary-orange: #fd7e14; --dark-orange: #d35400; --sidebar-bg: #2d3436; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background-color: var(--sidebar-bg); color: white; }
        .sidebar a { color: #ffeaa7; text-decoration: none; display: block; padding: 12px 20px; transition: 0.3s; border-left: 5px solid transparent; }
        .sidebar a:hover { background-color: #3f4648; color: white; padding-left: 25px; border-left: 5px solid var(--primary-orange); }
        .sidebar .active { background-color: #3f4648; color: var(--primary-orange); border-left: 5px solid var(--primary-orange); font-weight: bold; }
        .nav-tabs .nav-link { color: #6c757d; font-weight: 600; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link:hover { color: var(--primary-orange); }
        .nav-tabs .nav-link.active { color: var(--primary-orange); border-bottom: 3px solid var(--primary-orange); background: transparent; }
        .btn-orange { background-color: var(--primary-orange); color: white; border: none; }
        .btn-orange:hover { background-color: var(--dark-orange); color: white; }
        .form-check-input { width: 1.3em; height: 1.3em; cursor: pointer; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0"><?php include 'sidebar_menu.php'; ?></div>

        <div class="col-md-10 p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-0">🗂️ Arsip Surat & Dokumen</h3>
                    <p class="text-muted small mb-0">Kelola Surat Masuk dan Arsip Fisik Eksternal.</p>
                </div>
            </div>

            <?php if(isset($_GET['pesan']) && strpos($_GET['pesan'], 'sukses') !== false){ echo '<div class="alert alert-success alert-dismissible fade show">Berhasil!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; } ?>

            <ul class="nav nav-tabs mb-4" id="arsipTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link <?php echo ($active_tab == 'surat') ? 'active' : ''; ?>" id="surat-tab" data-bs-toggle="tab" data-bs-target="#surat" type="button" role="tab"><i class="bi bi-envelope-paper-fill me-2"></i>Surat Masuk</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link <?php echo ($active_tab == 'eksternal') ? 'active' : ''; ?>" id="eksternal-tab" data-bs-toggle="tab" data-bs-target="#eksternal" type="button" role="tab"><i class="bi bi-archive-fill me-2"></i>Arsip Fisik</button></li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <div class="tab-pane fade <?php echo ($active_tab == 'surat') ? 'show active' : ''; ?>" id="surat" role="tabpanel">
                    
                    <?php if($_SESSION['role'] == 'admin') { ?>
                    <div class="mb-3 text-end">
                        <button type="button" class="btn btn-orange shadow-sm" data-bs-toggle="modal" data-bs-target="#modalInputSurat">
                            <i class="bi bi-plus-lg me-1"></i> Input Surat Masuk
                        </button>
                    </div>
                    <?php } ?>

                    <form action="" method="POST" id="formBulkSurat">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-table me-2"></i>Data Surat Masuk</h6>
                                <?php if($_SESSION['role'] == 'admin') { ?>
                                    <button type="button" class="btn btn-danger btn-sm" id="btnDelSurat" disabled onclick="confirmBulk('formBulkSurat')"><i class="bi bi-trash"></i> Hapus Terpilih</button>
                                <?php } ?>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="tableSurat" class="table table-hover mb-0 align-middle w-100">
                                        <thead class="table-light">
                                            <tr>
                                                <?php if($_SESSION['role'] == 'admin') { ?><th width="5%" class="text-center"><input type="checkbox" class="form-check-input" id="checkAllSurat"></th><?php } else { echo "<th>No</th>"; } ?>
                                                <th>Nomor & Tgl</th>
                                                <th>Asal & Perihal</th>
                                                <th>Sifat</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; $q_surat = mysqli_query($koneksi, "SELECT * FROM incoming_mail ORDER BY created_at DESC");
                                            while($d = mysqli_fetch_array($q_surat)){
                                                if($d['visibility'] == 'private' && $_SESSION['role'] != 'admin') continue; ?>
                                            <tr>
                                                <?php if($_SESSION['role'] == 'admin') { ?><td class="text-center"><input type="checkbox" name="ids[]" value="<?php echo $d['id_mail']; ?>" class="form-check-input check-surat"></td><?php } else { echo "<td>".$no++."</td>"; } ?>
                                                <td><span class="fw-bold"><?php echo htmlspecialchars($d['nomor_surat']); ?></span><br><small class="text-muted"><?php echo date('d/m/Y', strtotime($d['tgl_terima'])); ?></small></td>
                                                <td><span class="badge bg-info text-dark mb-1"><?php echo htmlspecialchars($d['asal_surat']); ?></span><br><?php echo htmlspecialchars($d['judul']); ?></td>
                                                <td><?php echo ($d['visibility'] == 'public') ? '<span class="badge bg-success">Publik</span>' : '<span class="badge bg-danger">Rahasia</span>'; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-preview" data-file="uploads/arsip_lain/<?php echo $d['file_path']; ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                                                    <?php if($_SESSION['role'] == 'admin') { ?>
                                                        <a href="surat_arsip.php?hapus_surat=<?php echo $d['id_mail']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="bulk_delete_surat" value="1">
                    </form>
                </div>

                <div class="tab-pane fade <?php echo ($active_tab == 'eksternal') ? 'show active' : ''; ?>" id="eksternal" role="tabpanel">
                    
                    <div class="mb-3 text-end">
                        <button type="button" class="btn btn-orange shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUploadExt">
                            <i class="bi bi-upload me-1"></i> Upload Dokumen Fisik
                        </button>
                    </div>

                    <form action="" method="POST" id="formBulkExt">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-table me-2"></i>Data Arsip Fisik</h6>
                                <button type="button" class="btn btn-danger btn-sm" id="btnDelExt" disabled onclick="confirmBulk('formBulkExt')"><i class="bi bi-trash"></i> Hapus Terpilih</button>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="tableEksternal" class="table table-hover mb-0 align-middle w-100">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%" class="text-center"><input type="checkbox" class="form-check-input" id="checkAllExt"></th>
                                                <th>Judul Dokumen</th>
                                                <th>Pengupload</th>
                                                <th>Waktu</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; $id_user = $_SESSION['id_user'];
                                            if($_SESSION['role'] == 'admin'){ $q_ext = mysqli_query($koneksi, "SELECT e.*, u.nama_lengkap FROM external_archives e JOIN users u ON e.uploaded_by = u.id_user ORDER BY e.created_at DESC"); } 
                                            else { $q_ext = mysqli_query($koneksi, "SELECT e.*, u.nama_lengkap FROM external_archives e JOIN users u ON e.uploaded_by = u.id_user WHERE e.uploaded_by='$id_user' ORDER BY e.created_at DESC"); }
                                            while($dx = mysqli_fetch_array($q_ext)){ ?>
                                            <tr>
                                                <td class="text-center"><input type="checkbox" name="ids[]" value="<?php echo $dx['id_ext']; ?>" class="form-check-input check-ext"></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($dx['judul']); ?></td>
                                                <td><?php echo htmlspecialchars($dx['nama_lengkap']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($dx['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-preview" data-file="uploads/arsip_lain/<?php echo $dx['file_path']; ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                                                    <a href="surat_arsip.php?hapus_ext=<?php echo $dx['id_ext']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                         <input type="hidden" name="bulk_delete_ext" value="1">
                    </form>
                </div>
            </div> 
        </div>
    </div>
</div>

<div class="modal fade" id="modalInputSurat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-orange text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-envelope-plus me-2"></i>Input Surat Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="proses_surat_masuk.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Surat</label>
                            <input type="text" name="nomor_surat" class="form-control" placeholder="Contoh: 005/UND/2026" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Asal Surat</label>
                            <input type="text" name="asal_surat" class="form-control" placeholder="Contoh: Dinas Pendidikan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Terima</label>
                            <input type="date" name="tgl_terima" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sifat Surat</label>
                            <select name="visibility" class="form-select" required>
                                <option value="public">Publik (Semua User)</option>
                                <option value="private">Rahasia (Hanya Admin)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Perihal / Judul</label>
                            <input type="text" name="judul" class="form-control" placeholder="Perihal surat..." required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">File PDF</label>
                            <input type="file" name="file_surat" class="form-control" accept=".pdf" required>
                            <div class="form-text">File otomatis diberi QR Code. Maksimal 5MB.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_surat" class="btn btn-orange px-4">Simpan Surat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUploadExt" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-orange text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-upload me-2"></i>Upload Arsip Fisik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="proses_eksternal.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Judul Dokumen</label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Ijazah, Sertifikat, Akta..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">File PDF</label>
                        <input type="file" name="file_eksternal" class="form-control" accept=".pdf" required>
                        <div class="form-text">Pastikan file PDF valid.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="upload_eksternal" class="btn btn-orange px-4">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-pdf me-2"></i>Preview Dokumen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 80vh; background: #525659;">
                <iframe id="pdfFrame" src="" width="100%" height="100%" style="border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        var idSetting = { "lengthMenu": "Tampilkan _MENU_", "search": "Cari:" };
        $('#tableSurat').DataTable({ "language": idSetting, "pageLength": 10 });
        $('#tableEksternal').DataTable({ "language": idSetting, "pageLength": 10 });

        // LOGIKA TOMBOL PREVIEW
        $(document).on('click', '.btn-preview', function(){
            var fileUrl = $(this).data('file');
            $('#pdfFrame').attr('src', fileUrl);
            $('#modalPreview').modal('show');
        });
        $('#modalPreview').on('hidden.bs.modal', function () { $('#pdfFrame').attr('src', ''); });

        // LOGIKA BULK DELETE
        $('#checkAllSurat').on('change', function() { $('.check-surat').prop('checked', this.checked); toggleBtnSurat(); });
        $(document).on('change', '.check-surat', function() { toggleBtnSurat(); });
        function toggleBtnSurat(){
            var count = $('.check-surat:checked').length;
            $('#btnDelSurat').prop('disabled', count === 0).html('<i class="bi bi-trash"></i> Hapus ('+count+')');
        }

        $('#checkAllExt').on('change', function() { $('.check-ext').prop('checked', this.checked); toggleBtnExt(); });
        $(document).on('change', '.check-ext', function() { toggleBtnExt(); });
        function toggleBtnExt(){
            var count = $('.check-ext:checked').length;
            $('#btnDelExt').prop('disabled', count === 0).html('<i class="bi bi-trash"></i> Hapus ('+count+')');
        }
    });

    function confirmBulk(formId) {
        Swal.fire({
            title: 'Yakin hapus data terpilih?',
            text: "Data tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) { $('#' + formId).submit(); }
        })
    }
</script>
</body>
</html>