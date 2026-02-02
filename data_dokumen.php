<?php
// File: sarsip/data_dokumen.php
include 'config/koneksi.php';
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ header("location:login.php"); exit; }

$role = $_SESSION['role'];
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$page_title = !empty($kategori_filter) ? "Arsip: " . htmlspecialchars($kategori_filter) : "Semua Dokumen Internal";

// Logika Hak Akses
function can_manage_category($role, $cat){
    if($role == 'admin') return true;
    $map = [
        'pic_admin'      => ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan'],
        'pic_keuangan'   => ['Arsip Keuangan'],
        'pic_ops'        => ['Arsip Operasi SAR'],
        'pic_sumberdaya' => ['Arsip Sumberdaya'],
        'user'           => ['Arsip Lainnya']
    ];
    return (isset($map[$role]) && in_array($cat, $map[$role]));
}

$show_add_button = false;
if(empty($kategori_filter)){
    $show_add_button = true; 
} else {
    if(can_manage_category($role, $kategori_filter)){
        $show_add_button = true;
    }
}

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
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        body { transition: background-color 0.3s; }
        .main-content { margin-left: 280px; padding: 30px; transition: margin 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0 !important; padding: 15px; padding-top: 80px; } }
        .modal-fullscreen-custom { max-width: 90%; margin: 1.75rem auto; }
        .pdf-frame { width: 100%; height: 85vh; border: none; background-color: #525659; }
    </style>
</head>
<body>

    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h4 class="fw-bold text-body mb-1"><?php echo $page_title; ?></h4>
                
                <?php if($show_add_button){ ?>
                <div class="mt-2 mt-md-0">
                    <a href="tambah_dokumen.php<?php echo !empty($kategori_filter) ? '?kategori='.urlencode($kategori_filter) : ''; ?>" class="btn btn-primary shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Tambah
                    </a>
                </div>
                <?php } ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="proses_dokumen.php" method="POST" id="formBulkAction">
                        <input type="hidden" name="aksi" id="inputAksi" value="">
                        
                        <div class="d-flex justify-content-end gap-2 mb-3">
                            <button type="button" id="btnBulkDownload" class="btn btn-outline-success btn-sm" disabled>
                                <i class="bi bi-file-earmark-zip"></i> Download ZIP
                            </button>
                            <button type="button" id="btnBulkDelete" class="btn btn-outline-danger btn-sm" disabled>
                                <i class="bi bi-trash"></i> Hapus Terpilih
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table id="tableDokumen" class="table table-hover align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%" class="text-center"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                                        <th width="5%">No</th>
                                        <?php if($kategori_filter == 'Surat Masuk'){ ?>
                                            <th width="15%">Asal Surat</th><th width="15%">No. Surat</th><th width="20%">Perihal</th>
                                        <?php } elseif($kategori_filter == 'Surat Keluar'){ ?>
                                            <th width="15%">Tujuan</th><th width="15%">No. Surat</th><th width="20%">Perihal</th>
                                        <?php } else { ?>
                                            <th width="20%">Nomor Dokumen</th><th width="25%">Perihal / Judul</th>
                                        <?php } ?>
                                        
                                        <?php if(empty($kategori_filter)){ ?><th width="10%">Kategori</th><?php } ?>
                                        <th width="5%">Sifat</th>
                                        <th width="10%">Tgl</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $query_sql = "SELECT documents.*, users.nama_lengkap FROM documents LEFT JOIN users ON documents.uploader_id = users.id_user WHERE 1=1"; 
                                    
                                    if($role != 'admin'){ 
                                        $my_id = $_SESSION['id_user']; 
                                        $query_sql .= " AND (visibility = 'public' OR uploader_id = '$my_id')"; 
                                    }

                                    if(!empty($kategori_filter)){ 
                                        $kategori_sql = mysqli_real_escape_string($koneksi, $kategori_filter); 
                                        $query_sql .= " AND documents.kategori = '$kategori_sql'"; 
                                    }
                                    $query_sql .= " ORDER BY documents.created_at DESC";
                                    $result = mysqli_query($koneksi, $query_sql);
                                    
                                    while($row = mysqli_fetch_assoc($result)){
                                        $is_owner = ($_SESSION['id_user'] == $row['uploader_id']);
                                        $is_pic   = can_manage_category($role, $row['kategori']);
                                        $can_edit = ($role == 'admin' || $is_owner || $is_pic);
                                        $can_sign = ($role == 'admin' || $row['kategori'] == 'Arsip Lainnya' || $is_pic);

                                        $path_asli   = "uploads/doc_asli/" . $row['file_path'];
                                        $path_signed = "uploads/doc_signed/SIGNED_" . $row['file_path'];
                                        $file_final  = file_exists($path_signed) ? $path_signed : $path_asli;
                                        $has_signed  = file_exists($path_signed);
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="pilih[]" value="<?php echo $row['id_doc']; ?>" class="check-item form-check-input">
                                        </td>
                                        <td><?php echo $no++; ?></td>

                                        <?php if($kategori_filter == 'Surat Masuk'){ ?>
                                            <td><div class="fw-bold"><?php echo htmlspecialchars($row['asal_surat']); ?></div></td>
                                            <td class="text-primary fw-bold"><?php echo htmlspecialchars($row['nomor_surat']); ?></td>
                                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                        <?php } elseif($kategori_filter == 'Surat Keluar'){ ?>
                                            <td><div class="fw-bold"><?php echo htmlspecialchars($row['tujuan_surat']); ?></div></td>
                                            <td class="text-primary fw-bold"><?php echo htmlspecialchars($row['nomor_surat']); ?></td>
                                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                        <?php } else { ?>
                                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['nomor_surat']); ?></td>
                                            <td><div class="fw-semibold"><?php echo htmlspecialchars($row['judul']); ?></div></td>
                                        <?php } ?>
                                        
                                        <?php if(empty($kategori_filter)){ ?>
                                        <td><span class="badge <?php echo getBadgeColor($row['kategori']); ?> badge-kategori"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
                                        <?php } ?>
                                        
                                        <td><?php echo ($row['visibility']=='private') ? '<i class="bi bi-lock-fill text-danger" title="Rahasia"></i>' : '<i class="bi bi-globe-americas text-success" title="Publik"></i>'; ?></td>
                                        <td class="small"><?php echo date('d/m/y', strtotime($row['created_at'])); ?></td>
                                        
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="previewFile('<?php echo $file_final; ?>', '<?php echo $row['nomor_surat']; ?>')" title="Lihat">
                                                    <i class="bi bi-eye"></i> <?php if($has_signed) echo "<i class='bi bi-qr-code small'></i>"; ?>
                                                </button>
                                                
                                                <a href="<?php echo $file_final; ?>" class="btn btn-sm btn-outline-primary" download title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>

                                                <?php if($can_sign){ ?>
                                                    <a href="form_ttd.php?id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-success" title="Tanda Tangan"><i class="bi bi-pen"></i></a>
                                                <?php } ?>
                                                
                                                <?php if($can_edit){ ?>
                                                <a href="edit_dokumen.php?id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a href="proses_dokumen.php?aksi=hapus&id=<?php echo $row['id_doc']; ?>" class="btn btn-sm btn-outline-danger btn-alert-hapus" title="Hapus"><i class="bi bi-trash"></i></a>
                                                <?php } ?>
                                            </div>
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

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tableDokumen').DataTable({ "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json" } });
            
            // Logika Checkbox Massal
            $('#checkAll').change(function(){ 
                $('.check-item').prop('checked', this.checked); 
                toggleBtn(); 
            });
            
            // Delegasi Event untuk Pagination DataTable
            $(document).on('change', '.check-item', function(){ 
                toggleBtn(); 
            });

            function toggleBtn(){
                if($('.check-item:checked').length > 0) {
                    $('#btnBulkDelete').removeAttr('disabled');
                    $('#btnBulkDownload').removeAttr('disabled');
                } else {
                    $('#btnBulkDelete').attr('disabled', 'disabled');
                    $('#btnBulkDownload').attr('disabled', 'disabled');
                }
            }

            // Tombol Hapus Banyak
            $('#btnBulkDelete').click(function(){ 
                if(confirm('Yakin ingin menghapus data terpilih?')) {
                    $('#inputAksi').val('hapus_banyak');
                    $('#formBulkAction').submit(); 
                }
            });

            // Tombol Download Banyak (ZIP)
            $('#btnBulkDownload').click(function(){
                $('#inputAksi').val('download_zip');
                $('#formBulkAction').submit();
            });
        });

        function previewFile(url, title) {
            document.getElementById('pdfFrame').src = url + "#toolbar=0";
            document.getElementById('previewTitle').innerText = "Preview: " + title;
            var myModal = new bootstrap.Modal(document.getElementById('previewModal'));
            myModal.show();
        }
    </script>
</body>
</html>