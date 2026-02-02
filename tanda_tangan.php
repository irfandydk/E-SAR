<?php 
// File: sarsip/tanda_tangan.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

$id_user_login = $_SESSION['id_user'];
$role_login    = $_SESSION['role'];

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Tangan Elektronik - <?php echo isset($app_name)?$app_name:'SARSIP'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; min-height: 100vh; background: #fff; position: fixed; top: 0; left: 0; z-index: 100; border-right: 1px solid #dee2e6; }
        .main-content { margin-left: 280px; padding: 30px; }
        .nav-pills .nav-link.active { background-color: #fd7e14; }
        .nav-pills .nav-link { color: #6c757d; }
        
        .signer-list { font-size: 0.75rem; margin-top: 4px; }
        .signer-badge { background-color: #e9ecef; color: #495057; border: 1px solid #dee2e6; padding: 1px 5px; border-radius: 4px; display: inline-block; margin-right: 2px; margin-bottom: 2px; }
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
                    <h4 class="fw-bold text-dark mb-1">✍️ Tanda Tangan Elektronik</h4>
                    <p class="text-muted small mb-0">Bubuhkan QR Code pada dokumen secara digital.</p>
                </div>
            </div>

            <?php if(isset($_GET['pesan']) && $_GET['pesan']=='sukses'){ ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Dokumen berhasil ditandatangani!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-3 border-bottom-0">
                    <ul class="nav nav-pills card-header-pills" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" id="pills-pending-tab" data-bs-toggle="pill" data-bs-target="#pills-pending" type="button">
                                <i class="bi bi-hourglass-split me-1"></i> Perlu Tanda Tangan
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="pills-done-tab" data-bs-toggle="pill" data-bs-target="#pills-done" type="button">
                                <i class="bi bi-check-all me-1"></i> Riwayat / Selesai
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <div class="tab-content">
                        
                        <div class="tab-pane fade show active" id="pills-pending">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle w-100 table-datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="40%">Dokumen</th>
                                            <th width="15%">Kategori</th>
                                            <th width="40%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no=1;
                                        $sql = "SELECT * FROM documents WHERE 1=1";
                                        // Jika bukan admin, hanya tampilkan dokumen miliknya sendiri (uploader)
                                        if($role_login != 'admin'){ $sql .= " AND uploader_id = '$id_user_login'"; }
                                        $sql .= " ORDER BY id_doc DESC";
                                        
                                        $q = mysqli_query($koneksi, $sql);
                                        while($d = mysqli_fetch_array($q)){
                                            $id_doc = $d['id_doc'];
                                            
                                            // Cek siapa saja yang sudah tanda tangan
                                            $q_signers = mysqli_query($koneksi, "SELECT u.nama_lengkap, u.id_user FROM doc_signers ds JOIN users u ON ds.id_user = u.id_user WHERE ds.id_doc='$id_doc'");
                                            $signed_users = [];
                                            $ids_signed = [];
                                            while($s = mysqli_fetch_array($q_signers)){
                                                $signed_users[] = $s['nama_lengkap'];
                                                $ids_signed[]   = $s['id_user'];
                                            }

                                            // Filter: Jika User biasa sudah tanda tangan, sembunyikan baris ini (pindah ke riwayat)
                                            $me_signed = in_array($id_user_login, $ids_signed);
                                            if($role_login != 'admin' && $me_signed) continue; 
                                            
                                            $file_url = "uploads/doc_asli/" . $d['file_path'];
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo $d['nomor_surat']; ?></div>
                                                <div class="text-secondary small"><?php echo $d['judul']; ?></div>
                                                
                                                <?php if(!empty($signed_users)){ ?>
                                                    <div class="signer-list">
                                                        <span class="text-muted fst-italic me-1">Sudah TTD:</span>
                                                        <?php foreach($signed_users as $nm){ echo "<span class='signer-badge'><i class='bi bi-check'></i> $nm</span>"; } ?>
                                                    </div>
                                                <?php } ?>
                                            </td>
                                            
                                            <td><span class="badge <?php echo getBadgeColor($d['kategori']); ?> rounded-pill"><?php echo $d['kategori']; ?></span></td>
                                            
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    <button type="button" class="btn btn-outline-info btn-sm btn-preview" data-file="<?php echo $file_url; ?>" title="Lihat Dokumen">
                                                        <i class="bi bi-eye"></i>
                                                    </button>

                                                    <a href="form_ttd.php?id=<?php echo $id_doc; ?>" class="btn btn-warning btn-sm text-white px-3 shadow-sm" title="Proses Tanda Tangan">
                                                        <i class="bi bi-pen-fill me-1"></i> Tanda Tangani
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pills-done">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle w-100 table-datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Dokumen</th>
                                            <th>Waktu TTD</th>
                                            <th>File Final</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no2 = 1;
                                        $sql2 = "SELECT ds.*, d.judul, d.nomor_surat, d.file_path, d.signed_file_hash, u.nama_lengkap 
                                                 FROM doc_signers ds 
                                                 JOIN documents d ON ds.id_doc = d.id_doc 
                                                 JOIN users u ON ds.id_user = u.id_user 
                                                 WHERE ds.status = 'signed'";
                                        
                                        if($role_login != 'admin'){
                                            $sql2 .= " AND ds.id_user = '$id_user_login'";
                                        }
                                        $sql2 .= " ORDER BY ds.signed_at DESC";

                                        $q2 = mysqli_query($koneksi, $sql2);
                                        while($r = mysqli_fetch_array($q2)){
                                            $file_final = !empty($r['signed_file_hash']) ? "SIGNED_".$r['file_path'] : $r['file_path'];
                                            $path_final = "uploads/doc_signed/" . $file_final;
                                        ?>
                                        <tr>
                                            <td><?php echo $no2++; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo $r['nomor_surat']; ?></div>
                                                <div class="small text-secondary mb-1"><?php echo $r['judul']; ?></div>
                                                <?php if($role_login == 'admin'){ ?>
                                                    <span class="badge bg-light text-dark border">Oleh: <?php echo $r['nama_lengkap']; ?></span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold small">
                                                    <i class="bi bi-calendar-check me-1"></i> <?php echo date('d/m/Y H:i', strtotime($r['signed_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo $path_final; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="alert('Token: <?php echo $r['qr_token']; ?>')">
                                                        <i class="bi bi-qr-code"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="modalPreview" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title small"><i class="bi bi-file-pdf me-2"></i>Preview Dokumen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh; background: #333;">
                    <iframe id="pdfFrame" src="" width="100%" height="100%" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('.table-datatable').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json" }
            });

            $(document).on('click', '.btn-preview', function(){
                var url = $(this).data('file');
                $('#pdfFrame').attr('src', url);
                $('#modalPreview').modal('show');
            });
            
            $('#modalPreview').on('hidden.bs.modal', function(){
                $('#pdfFrame').attr('src', '');
            });
        });
    </script>
</body>
</html>
