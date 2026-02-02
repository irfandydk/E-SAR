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
    <title>Upload Dokumen</title>
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
        <h4 class="fw-bold mb-4"><i class="bi bi-cloud-arrow-up-fill me-2 text-primary"></i>Upload Dokumen Baru</h4>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                
                <form action="proses_dokumen.php" method="POST" enctype="multipart/form-data">
                    
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Dokumen</label>
                        <select name="kategori" id="kategoriSelect" class="form-select" onchange="updateForm()" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php 
                            $cats = ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan', 
                                     'Arsip Keuangan', 'Arsip Operasi SAR', 'Arsip Sumberdaya', 'Arsip Lainnya'];
                            
                            foreach($cats as $cat){
                                if($allowed_cats === 'ALL' || in_array($cat, $allowed_cats)){
                                    $sel = ($selected_cat == $cat) ? 'selected' : '';
                                    echo "<option value='$cat' $sel>$cat</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" id="labelNomor">Nomor Dokumen</label>
                        <input type="text" name="nomor" class="form-control" placeholder="Contoh: 001/SAR/2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul / Perihal</label>
                        <input type="text" name="judul" class="form-control" placeholder="Isi judul dokumen..." required>
                    </div>

                    <div class="mb-3" id="boxAsalSurat" style="display:none;">
                        <label class="form-label fw-bold">Asal Surat (Pengirim)</label>
                        <input type="text" name="asal_surat" class="form-control" placeholder="Dari siapa surat ini?">
                    </div>
                    <div class="mb-3" id="boxTujuanSurat" style="display:none;">
                        <label class="form-label fw-bold">Tujuan Surat</label>
                        <input type="text" name="tujuan_surat" class="form-control" placeholder="Kepada siapa surat ini?">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hak Akses (Visibility)</label>
                        <select name="visibility" class="form-select">
                            <option value="public">Publik (Semua User Bisa Lihat)</option>
                            <option value="private">Private (Hanya Saya & Admin)</option>
                            <option value="divisi">Hanya Divisi Saya</option>
                        </select>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border border-success-subtle">
                        <label class="form-label fw-bold text-success"><i class="bi bi-hourglass-split me-1"></i> Jadwal Retensi Arsip</label>
                        <div class="row align-items-center g-2">
                            <div class="col-auto">Simpan selama:</div>
                            <div class="col-auto">
                                <select name="retensi" class="form-select form-select-sm border-success text-success fw-bold" required>
                                    <option value="1">1 Tahun</option>
                                    <option value="2">2 Tahun</option>
                                    <option value="3">3 Tahun</option>
                                    <option value="5" selected>5 Tahun (Standar)</option>
                                    <option value="10">10 Tahun</option>
                                    <option value="permanen">Permanen / Statis</option>
                                </select>
                            </div>
                            <div class="col-auto"><small class="text-muted ms-2">(Setelah ini status jadi Inaktif)</small></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">File Dokumen (PDF)</label>
                        <input type="file" name="file_dokumen" class="form-control" accept=".pdf" required>
                        <div class="form-text">Maksimal ukuran file disarankan di bawah 10MB.</div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updateForm() {
        var cat = document.getElementById("kategoriSelect").value;
        var boxAsal = document.getElementById("boxAsalSurat");
        var boxTujuan = document.getElementById("boxTujuanSurat");
        
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
    window.onload = updateForm;
</script>
</body>
</html>
