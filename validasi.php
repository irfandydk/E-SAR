<?php
// File: sarsip/validasi.php
include 'config/koneksi.php';

// Fungsi Format Tanggal Indonesia
function tgl_indo($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

$isValid = false;
$data = []; 
$history = []; 
$jenis_dokumen = ""; 
$id_doc_found = 0; 
$token = "";

// ------------------------------------------------------------------
// LOGIKA PHP VALIDASI (TETAP SAMA)
// ------------------------------------------------------------------

// A. JIKA VIA SCAN QR (TOKEN)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = mysqli_real_escape_string($koneksi, $_GET['token']);

    // CEK 1: DOKUMEN INTERNAL
    if (!$isValid) {
        $q_internal = "SELECT doc_signers.*, users.nama_lengkap as nama_penandatangan, 
                           users.jabatan, users.nip, users.instansi, 
                           documents.id_doc, documents.judul, documents.nomor_surat, documents.kategori, documents.file_path, documents.created_at as tgl_upload
                  FROM doc_signers
                  JOIN users ON doc_signers.id_user = users.id_user
                  JOIN documents ON doc_signers.id_doc = documents.id_doc
                  WHERE doc_signers.qr_token = '$token'";
        $res_internal = mysqli_query($koneksi, $q_internal);
        if (mysqli_num_rows($res_internal) > 0) {
            $isValid = true;
            $data = mysqli_fetch_assoc($res_internal);
            $jenis_dokumen = "INTERNAL";
            $id_doc_found = $data['id_doc']; 
        }
    }

}

// B. JIKA VIA UPLOAD FILE -> DOKUMEN INTERNAL
if (isset($_GET['doc_id'])) {
    $id_doc = mysqli_real_escape_string($koneksi, $_GET['doc_id']);
    $query = "SELECT documents.*, users.nama_lengkap, users.nip, users.jabatan, users.unit_kerja, users.instansi 
              FROM documents JOIN users ON documents.uploader_id = users.id_user WHERE id_doc = '$id_doc'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        $isValid = true;
        $row = mysqli_fetch_assoc($result);
        $data = $row;
        $data['has_signed_version'] = !empty($row['signed_file_hash']);
        $id_doc_found = $row['id_doc'];
        $data['nama_penandatangan'] = $row['nama_lengkap']; 
        $data['signed_at']          = $row['created_at'];
        $jenis_dokumen = "HASH";
    }
}



// AMBIL HISTORY
if ($isValid && ($jenis_dokumen == "INTERNAL" || $jenis_dokumen == "HASH")) {
    $q_history = "SELECT doc_signers.*, users.nama_lengkap, users.jabatan, users.nip 
                  FROM doc_signers JOIN users ON doc_signers.id_user = users.id_user 
                  WHERE id_doc = '$id_doc_found' ORDER BY signed_at ASC"; 
    $res_history = mysqli_query($koneksi, $q_history);
    while($h = mysqli_fetch_assoc($res_history)){ $history[] = $h; }

    if($jenis_dokumen == "HASH" && count($history) > 0){
        $last_signer = end($history); 
        $data['nama_penandatangan'] = $last_signer['nama_lengkap'];
        $data['nip']                = $last_signer['nip'];
        $data['jabatan']            = $last_signer['jabatan'];
        $data['signed_at']          = $last_signer['signed_at'];
    }
}

// Ambil Nama Aplikasi
$app_name_display = isset($app_name) ? $app_name : "SARSIP";
$app_logo_display = "assets/logo_instansi.png";
$q_set = @mysqli_query($koneksi, "SELECT nama_aplikasi, logo_path FROM app_settings LIMIT 1");
if($q_set && mysqli_num_rows($q_set) > 0){
    $d_set = mysqli_fetch_assoc($q_set);
    if(!empty($d_set['nama_aplikasi'])) $app_name_display = $d_set['nama_aplikasi'];
    if(!empty($d_set['logo_path'])) $app_logo_display = "assets/" . $d_set['logo_path'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Validasi - <?php echo $app_name_display; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        /* --- 1. ANIMASI BACKGROUND --- */
        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            padding-bottom: 50px;
        }
        [data-bs-theme="dark"] body {
            background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: #f0f0f0;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; }
        }

        /* --- 2. GLASS CARD --- */
        .result-card {
            max-width: 750px; margin: 30px auto; border-radius: 24px; overflow: hidden;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.6);
        }
        [data-bs-theme="dark"] .result-card {
            background: rgba(30, 30, 40, 0.9); border: 1px solid rgba(255,255,255,0.1);
        }

        /* --- 3. HEADER STATUS --- */
        .header-valid { background: linear-gradient(135deg, #00b09b, #96c93d); color: white; padding: 40px 20px; text-align: center; }
        .header-invalid { background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white; padding: 40px 20px; text-align: center; }
        .icon-status { font-size: 80px; margin-bottom: 10px; display: block; text-shadow: 0 5px 10px rgba(0,0,0,0.2); }

        /* --- 4. CONTENT STYLE --- */
        .label-field { color: #6c757d; font-size: 0.9em; width: 35%; padding-bottom: 10px; }
        .value-field { font-weight: 600; padding-bottom: 10px; font-size: 1.05em; }
        [data-bs-theme="dark"] .label-field { color: #aaa; }
        
        .info-box {
            background: rgba(0,0,0,0.03); border-radius: 12px; padding: 20px; border: 1px solid rgba(0,0,0,0.05);
        }
        [data-bs-theme="dark"] .info-box { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }

        /* --- 5. TIMELINE --- */
        .timeline { border-left: 3px solid #dee2e6; margin-left: 10px; padding-left: 25px; list-style: none; margin-top: 15px; }
        [data-bs-theme="dark"] .timeline { border-color: #495057; }
        
        .timeline-item { position: relative; margin-bottom: 25px; }
        .timeline-item::before {
            content: ""; position: absolute; left: -34px; top: 5px;
            width: 16px; height: 16px; border-radius: 50%;
            background: #0d6efd; border: 3px solid #fff; box-shadow: 0 0 0 2px #0d6efd;
        }
        [data-bs-theme="dark"] .timeline-item::before { border-color: #2b3035; }
        
        .timeline-content { background: rgba(255,255,255,0.5); padding: 12px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); }
        [data-bs-theme="dark"] .timeline-content { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
        
        .current-token { border-left: 4px solid #198754; background-color: rgba(25, 135, 84, 0.05); }

        /* BUTTON GLASS */
        .btn-glass {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none; color: white; padding: 12px 25px; border-radius: 50px;
            font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: 0.3s;
            text-decoration: none; display: inline-block; width: 100%; text-align: center;
            cursor: pointer;
        }
        .btn-glass:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.3); color: white; }

        /* MODAL PREVIEW STYLE */
        .modal-preview-container { height: 85vh; background-color: #525659; }
        .pdf-frame { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="text-center pt-5 pb-3">
        <?php if(file_exists($app_logo_display)): ?>
            <img src="<?php echo $app_logo_display; ?>" alt="Logo" style="height: 90px; margin-bottom: 10px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));">
        <?php else: ?>
            <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 80px; height: 80px;">
                <i class="bi bi-building fs-1 text-primary"></i>
            </div>
        <?php endif; ?>
        <h3 class="fw-bold text-white text-shadow"><?php echo $app_name_display; ?></h3>
    </div>

    <div class="card result-card">
        
        <?php if ($isValid): ?>
            
            <div class="header-valid">
                <i class="bi bi-patch-check-fill icon-status"></i>
                <h2 class="fw-bold">DOKUMEN VALID</h2>
                <p class="mb-0 opacity-90">Dokumen ini terdaftar resmi dan telah diverifikasi.</p>
            </div>
            
            <div class="card-body p-4 p-md-5">
                
                <h6 class="text-uppercase text-muted fw-bold mb-3 small border-bottom pb-2">Detail Dokumen</h6>
                <table class="table table-borderless table-sm mb-4 text-body">
                    <tr>
                        <td class="label-field">Kategori</td>
                        <td class="value-field"><span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($data['kategori']); ?></span></td>
                    </tr>
                    <tr>
                        <td class="label-field">Nomor Surat</td>
                        <td class="value-field text-break"><?php echo htmlspecialchars($data['nomor_surat']); ?></td>
                    </tr>
                    <tr>
                        <td class="label-field">Perihal</td>
                        <td class="value-field text-break"><?php echo htmlspecialchars($data['judul']); ?></td>
                    </tr>
                    <?php if($jenis_dokumen == 'SURAT_MASUK'){ ?>
                    <tr>
                        <td class="label-field">Info Tambahan</td>
                        <td class="value-field"><?php echo htmlspecialchars($data['instansi']); ?></td>
                    </tr>
                    <?php } ?>
                </table>

                <h6 class="text-uppercase text-muted fw-bold mb-3 small border-bottom pb-2">
                    <?php echo ($jenis_dokumen == 'SURAT_MASUK') ? 'Asal Surat / Pengirim' : 'Penandatangan Dokumen'; ?>
                </h6>
                
                <div class="info-box mb-4 d-flex align-items-center">
                    <div class="me-3 text-success">
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-person-badge-fill fs-2 text-success"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="text-success fw-bold mb-1"><?php echo htmlspecialchars($data['nama_penandatangan']); ?></h5>
                        <p class="mb-0 text-muted small lh-sm">
                            <?php if(!empty($data['nip'])) echo "NIP. " . htmlspecialchars($data['nip']) . "<br>"; ?>
                            <?php echo htmlspecialchars($data['jabatan']); ?>
                        </p>
                        <?php if($jenis_dokumen != 'SURAT_MASUK' && isset($data['instansi']) && !empty($data['instansi'])) { ?>
                        <p class="mb-0 text-muted small mt-1">
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($data['instansi']); ?>
                        </p>
                        <?php } ?>
                        <p class="mb-0 text-muted small mt-2">
                            <i class="bi bi-clock-history"></i> Ditandatangani: <?php echo tgl_indo($data['signed_at']) . ", " . date('H:i', strtotime($data['signed_at'])); ?> WIB
                        </p>
                    </div>
                </div>

                <?php if(count($history) > 0) { ?>
                    <h6 class="text-uppercase text-muted fw-bold mb-3 small border-bottom pb-2">Riwayat Validasi (<?php echo count($history); ?>)</h6>
                    
                    <ul class="timeline">
                        <?php 
                        $urutan = 1;
                        foreach($history as $h) { 
                            $is_current = (isset($_GET['token']) && $_GET['token'] == $h['qr_token']) ? "current-token" : "";
                        ?>
                        <li class="timeline-item">
                            <span class="small text-muted d-block mb-1"><?php echo tgl_indo($h['signed_at']) . ", " . date('H:i', strtotime($h['signed_at'])); ?> WIB</span>
                            <div class="timeline-content <?php echo $is_current; ?>">
                                <div class="fw-bold text-success mb-1">
                                    <i class="bi bi-vector-pen"></i> Tanda Tangan Ke-<?php echo $urutan++; ?>
                                    <?php if($is_current) echo '<span class="badge bg-success float-end" style="font-size:0.6em">Scan Saat Ini</span>'; ?>
                                </div>
                                <div class="lh-sm">
                                    <span class="fw-bold text-body"><?php echo htmlspecialchars($h['nama_lengkap']); ?></span>
                                    <br>
                                    <span class="small text-muted"><?php echo htmlspecialchars($h['jabatan']); ?></span>
                                </div>
                            </div>
                        </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <div class="mt-5">
                    <?php 
                        $download_link = "#";
                        $nama_file_asli = isset($data['file_path']) ? basename($data['file_path']) : '';

                        if ($jenis_dokumen == "EKSTERNAL" || $jenis_dokumen == "SURAT_MASUK") {
                            $download_link = "uploads/arsip_lain/" . $nama_file_asli;
                        } elseif ($jenis_dokumen == "INTERNAL" || $jenis_dokumen == "HASH") {
                            if($jenis_dokumen == "INTERNAL" || ($jenis_dokumen == "HASH" && !empty($data['has_signed_version']))) {
                                $download_link = "uploads/doc_signed/SIGNED_" . $nama_file_asli;
                            } else {
                                $download_link = "uploads/doc_asli/" . $nama_file_asli;
                            }
                        }
                    ?>
                    
                    <button onclick="showPreview('<?php echo $download_link; ?>', '<?php echo $data['nomor_surat']; ?>')" class="btn-glass mb-3">
                        <i class="bi bi-eye-fill me-2"></i> Live Preview Dokumen
                    </button>
                    
                    <div class="text-center">
                        <?php if(isset($_SESSION['status']) && $_SESSION['status'] == 'login') { ?>
                             <a href="dashboard.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                        <?php } else { ?>
                             <a href="index.php" class="text-decoration-none text-muted small"><i class="bi bi-qr-code-scan"></i> Scan Dokumen Lain</a>
                        <?php } ?>
                    </div>
                </div>

            </div>

        <?php else: ?>
            
            <div class="header-invalid">
                <i class="bi bi-x-circle-fill icon-status"></i>
                <h2 class="fw-bold">DATA TIDAK DITEMUKAN</h2>
                <p class="mb-0 opacity-90">Maaf, Token atau Hash file tidak terdaftar di sistem.</p>
                <?php if(!empty($token)) { ?>
                    <div class="mt-3">
                        <span class="badge bg-black bg-opacity-25 fw-normal px-3 py-2">Token: <?php echo htmlspecialchars($token); ?></span>
                    </div>
                <?php } ?>
            </div>
            
            <div class="card-body p-5 text-center">
                <div class="mb-4">
                    <i class="bi bi-file-earmark-x text-secondary" style="font-size: 5rem; opacity: 0.5;"></i>
                </div>
                <p class="text-muted mb-4">
                    Dokumen ini mungkin belum terdaftar, telah dihapus, atau file yang Anda unggah berbeda (telah dimodifikasi) dari aslinya.
                </p>
                <a href="index.php" class="btn btn-secondary rounded-pill px-4 py-2">
                    <i class="bi bi-arrow-left me-2"></i> Kembali ke Beranda
                </a>
            </div>

        <?php endif; ?>

    </div>
    
    <div class="text-center text-white-50 small mb-4">
        &copy; <?php echo date('Y'); ?> <?php echo $app_name_display; ?>. All Rights Reserved.
    </div>

</div>

<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"> 
        <div class="modal-content rounded-4 shadow overflow-hidden">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title" id="previewTitle"><i class="bi bi-file-pdf me-2"></i>Preview Dokumen</h6>
                <div class="ms-auto">
                    <a id="btnDownload" href="#" target="_blank" class="btn btn-sm btn-outline-light me-2" title="Download / Buka di Tab Baru">
                        <i class="bi bi-download"></i>
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 modal-preview-container">
                <iframe id="pdfFrame" class="pdf-frame" src=""></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // FUNGSI PREVIEW MODAL
    function showPreview(url, title) {
        document.getElementById('pdfFrame').src = url + "#toolbar=0"; 
        document.getElementById('previewTitle').innerText = "Preview: " + title;
        document.getElementById('btnDownload').href = url; // Set link download backup
        
        var myModal = new bootstrap.Modal(document.getElementById('previewModal'));
        myModal.show();
    }

    // Reset iframe saat modal ditutup (Hemat memori)
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('pdfFrame').src = "";
    });
</script>
</body>
</html>