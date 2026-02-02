<?php 
// File: sarsip/tambah_dokumen.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}
include 'config/koneksi.php';

// --- LOGIKA HAK AKSES KATEGORI ---
$role = $_SESSION['role'];
$allowed_cats = [];

// Definisi Hak Akses
if($role == 'admin'){
    $allowed_cats = 'ALL';
} elseif($role == 'pic_admin'){
    $allowed_cats = ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan'];
} elseif($role == 'pic_keuangan'){
    $allowed_cats = ['Arsip Keuangan'];
} elseif($role == 'pic_ops'){
    $allowed_cats = ['Arsip Operasi SAR'];
} elseif($role == 'pic_sumberdaya'){
    $allowed_cats = ['Arsip Sumberdaya'];
} else {
    // User Biasa
    $allowed_cats = ['Arsip Lainnya'];
}

$selected_cat = isset($_GET['kategori']) ? $_GET['kategori'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Arsip - SARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        body { transition: background-color 0.3s, color 0.3s; }
        .main-content { margin-left: 280px; padding: 30px; transition: margin 0.3s; }
        
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select {
            background-color: #2b3035; border-color: #495057; color: #e0e0e0;
        }
        [data-bs-theme="dark"] .form-control:focus, [data-bs-theme="dark"] .form-select:focus {
            border-color: #fd7e14; color: #fff;
        }
        @media (max-width: 768px) { .main-content { margin-left: 0 !important; padding: 15px; padding-top: 80px; } }
    </style>
</head>
<body>
    
    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-9">
                    
                    <div class="card shadow rounded-4 border-0 mb-5">
                        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-body"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>Input Arsip Baru</h5>
                            <a href="data_dokumen.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                        </div>
                        
                        <div class="card-body p-4">
                            <form action="proses_dokumen.php" method="POST" enctype="multipart/form-data">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jenis / Kategori</label>
                                    <select name="kategori" id="kategoriSelect" class="form-select form-select-lg" required onchange="updateForm()">
                                        <option value="">-- Pilih Jenis --</option>
                                        
                                        <?php 
                                        // Helper function untuk cek opsi
                                        function renderOption($val, $label, $selected, $allowed){
                                            if($allowed === 'ALL' || in_array($val, $allowed)){
                                                $sel = ($selected == $val) ? 'selected' : '';
                                                echo "<option value='$val' $sel>$label</option>";
                                            }
                                        }

                                        // GRUP 1: ADMIN UMUM
                                        if($allowed_cats === 'ALL' || in_array('Surat Masuk', $allowed_cats)){
                                            echo '<optgroup label="Administrasi Umum">';
                                            renderOption('Surat Masuk', 'Surat Masuk', $selected_cat, $allowed_cats);
                                            renderOption('Surat Keluar', 'Surat Keluar', $selected_cat, $allowed_cats);
                                            renderOption('SK', 'SK (Keputusan)', $selected_cat, $allowed_cats);
                                            renderOption('Surat Perintah', 'Surat Perintah', $selected_cat, $allowed_cats);
                                            renderOption('Surat Pernyataan', 'Surat Pernyataan', $selected_cat, $allowed_cats);
                                            echo '</optgroup>';
                                        }

                                        // GRUP 2: UNIT / BIDANG
                                        if($allowed_cats === 'ALL' || in_array('Arsip Keuangan', $allowed_cats) || in_array('Arsip Operasi SAR', $allowed_cats) || in_array('Arsip Sumberdaya', $allowed_cats)){
                                            echo '<optgroup label="Unit / Bidang">';
                                            renderOption('Arsip Keuangan', 'Arsip Keuangan', $selected_cat, $allowed_cats);
                                            renderOption('Arsip Operasi SAR', 'Arsip Operasi SAR', $selected_cat, $allowed_cats);
                                            renderOption('Arsip Sumberdaya', 'Arsip Sumberdaya', $selected_cat, $allowed_cats);
                                            echo '</optgroup>';
                                        }

                                        // GRUP 3: LAINNYA
                                        renderOption('Arsip Lainnya', 'Arsip Lainnya', $selected_cat, $allowed_cats);
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Sifat Dokumen</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check card p-2 px-3 border-success-subtle bg-success-subtle flex-fill">
                                            <input class="form-check-input" type="radio" name="visibility" value="public" checked>
                                            <label class="form-check-label w-100 text-dark"><i class="bi bi-globe-americas me-1 text-success"></i> Publik</label>
                                        </div>
                                        <div class="form-check card p-2 px-3 border-danger-subtle bg-danger-subtle flex-fill">
                                            <input class="form-check-input" type="radio" name="visibility" value="private">
                                            <label class="form-check-label w-100 text-dark"><i class="bi bi-lock-fill me-1 text-danger"></i> Rahasia</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-7 mb-3">
                                        <label class="form-label fw-bold" id="labelNomor">Nomor Dokumen</label>
                                        <input type="text" name="nomor_surat" class="form-control" required placeholder="Contoh: 001/SAR/2026">
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label fw-bold" id="labelTanggal">Tanggal</label>
                                        <input type="date" name="tgl_dokumen" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>

                                <div class="mb-3" id="boxAsalSurat" style="display: none;">
                                    <label class="form-label fw-bold text-success">Asal Surat (Pengirim)</label>
                                    <input type="text" name="asal_surat" id="inputAsalSurat" class="form-control border-success">
                                </div>
                                <div class="mb-3" id="boxTujuanSurat" style="display: none;">
                                    <label class="form-label fw-bold text-primary">Tujuan Surat</label>
                                    <input type="text" name="tujuan_surat" id="inputTujuanSurat" class="form-control border-primary">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold" id="labelJudul">Judul / Perihal</label>
                                    <textarea name="judul" class="form-control" rows="2" required placeholder="Ketik perihal dokumen..."></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">File Fisik (PDF)</label>
                                    <input type="file" name="file_dokumen" class="form-control" accept=".pdf" required>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="data_dokumen.php" class="btn btn-light border px-4">Batal</a>
                                    <button type="submit" name="simpan" class="btn btn-primary px-4 fw-bold shadow-sm">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateForm() {
            var cat = document.getElementById("kategoriSelect").value;
            var boxAsal = document.getElementById("boxAsalSurat");
            var boxTujuan = document.getElementById("boxTujuanSurat");
            
            // Reset
            boxAsal.style.display = "none";
            boxTujuan.style.display = "none";
            document.getElementById("labelNomor").innerText = "Nomor Dokumen";

            if(cat === "Surat Masuk"){ 
                boxAsal.style.display = "block"; 
                document.getElementById("labelNomor").innerText = "Nomor Surat";
            } 
            else if(cat === "Surat Keluar"){ 
                boxTujuan.style.display = "block";
                document.getElementById("labelNomor").innerText = "Nomor Surat";
            }
        }
        // Jalankan saat load (jika ada selected category dari GET)
        window.onload = updateForm;
    </script>
</body>

</html>
