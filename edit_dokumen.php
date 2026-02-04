<?php
// File: sarsip/edit_dokumen.php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}

// Ambil ID dari URL
$id_doc = mysqli_real_escape_string($koneksi, $_GET['id']);
$id_user = $_SESSION['id_user'];
$role    = $_SESSION['role'];

// Query Data Lama
$query = "SELECT * FROM documents WHERE id_doc = '$id_doc'";
$result = mysqli_query($koneksi, $query);
$d = mysqli_fetch_assoc($result);

// Validasi Keberadaan Data
if(!$d){
    echo "<script>alert('Dokumen tidak ditemukan!'); window.location='data_dokumen.php';</script>"; exit;
}

// Validasi Hak Akses (Hanya Admin atau Pemilik Dokumen yang boleh edit)
if($role != 'admin' && $d['id_user'] != $id_user){
    echo "<script>alert('Anda tidak berhak mengedit dokumen ini!'); window.location='data_dokumen.php';</script>"; exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Dokumen</title>
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
        <h4 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Dokumen</h4>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                
                <form action="proses_dokumen.php" method="POST" enctype="multipart/form-data">
                    
                    <input type="hidden" name="aksi" value="update">
                    <input type="hidden" name="id_doc" value="<?php echo $d['id_doc']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Dokumen</label>
                        <select name="kategori" id="kategoriSelect" class="form-select" onchange="updateForm()" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php 
                            $cats = ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan', 
                                     'Arsip Keuangan', 'Arsip Operasi SAR', 'Arsip Sumberdaya', 'Arsip Lainnya'];
                            
                            foreach($cats as $cat){
                                $sel = ($d['kategori'] == $cat) ? 'selected' : '';
                                echo "<option value='$cat' $sel>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" id="labelNomor">Nomor Dokumen</label>
                        <input type="text" name="nomor" class="form-control" value="<?php echo htmlspecialchars($d['nomor_surat']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul / Perihal</label>
                        <input type="text" name="judul" class="form-control" value="<?php echo htmlspecialchars($d['judul']); ?>" required>
                    </div>

                    <div class="mb-3" id="boxAsalSurat" style="display:none;">
                        <label class="form-label fw-bold">Asal Surat (Pengirim)</label>
                        <input type="text" name="asal_surat" class="form-control" value="<?php echo htmlspecialchars($d['asal_surat']); ?>" placeholder="Dari siapa surat ini?">
                    </div>
                    <div class="mb-3" id="boxTujuanSurat" style="display:none;">
                        <label class="form-label fw-bold">Tujuan Surat</label>
                        <input type="text" name="tujuan_surat" class="form-control" value="<?php echo htmlspecialchars($d['tujuan_surat']); ?>" placeholder="Kepada siapa surat ini?">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hak Akses (Visibility)</label>
                        <select name="visibility" class="form-select">
                            <option value="public" <?php echo ($d['visibility']=='public')?'selected':''; ?>>Publik (Semua User Bisa Lihat)</option>
                            <option value="private" <?php echo ($d['visibility']=='private')?'selected':''; ?>>Private (Hanya Saya & Admin)</option>
                            <option value="divisi" <?php echo ($d['visibility']=='divisi')?'selected':''; ?>>Hanya Divisi Saya</option>
                        </select>
                    </div>

                    <div class="mb-4 p-3 bg-light rounded border border-warning-subtle">
                        <label class="form-label fw-bold text-dark"><i class="bi bi-clock-history me-1"></i> Status Retensi Saat Ini</label>
                        
                        <div class="d-flex align-items-center mb-2 gap-2">
                            <?php 
                                $status_badge = ($d['status_retensi'] == 'aktif') 
                                    ? '<span class="badge bg-success">Aktif</span>' 
                                    : '<span class="badge bg-danger">Inaktif</span>';
                                
                                $tgl_exp = ($d['tgl_retensi'] == '9999-12-31') ? 'Permanen' : date('d M Y', strtotime($d['tgl_retensi']));
                            ?>
                            <div><?php echo $status_badge; ?></div>
                            <div class="text-muted small">Berakhir pada: <strong><?php echo $tgl_exp; ?></strong></div>
                        </div>

                        <label class="form-label fw-bold small text-primary mt-2">Ubah Jadwal Retensi (Opsional)</label>
                        <select name="retensi_update" class="form-select form-select-sm border-primary">
                            <option value="tetap" selected>-- Tetap (Jangan Ubah) --</option>
                            <option value="1">Reset jadi 1 Tahun (dari Tgl Upload)</option>
                            <option value="2">Reset jadi 2 Tahun (dari Tgl Upload)</option>
                            <option value="5">Reset jadi 5 Tahun (dari Tgl Upload)</option>
                            <option value="permanen">Ubah jadi Permanen</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">File Dokumen</label>
                        <div class="mb-2">
                            <small class="text-muted">File saat ini: </small>
                            <a href="uploads/doc_asli/<?php echo $d['file_path']; ?>" target="_blank" class="text-decoration-none fw-bold">
                                <i class="bi bi-file-earmark-pdf text-danger"></i> <?php echo $d['file_path']; ?>
                            </a>
                        </div>
                        <input type="file" name="file_dokumen" class="form-control" accept=".pdf">
                        <div class="form-text text-danger">Biarkan kosong jika tidak ingin mengganti file.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="data_dokumen.php" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" name="update" class="btn btn-warning px-4 fw-bold shadow-sm">Update Perubahan</button>
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
        var labelNomor = document.getElementById("labelNomor");
        
        // Reset State
        boxAsal.style.display = "none";
        boxTujuan.style.display = "none";
        labelNomor.innerText = "Nomor Dokumen";

        // Logic
        if(cat === "Surat Masuk"){ 
            boxAsal.style.display = "block"; 
            labelNomor.innerText = "Nomor Surat";
        } 
        else if(cat === "Surat Keluar"){ 
            boxTujuan.style.display = "block";
            labelNomor.innerText = "Nomor Surat";
        }
    }
    
    // Jalankan saat halaman dimuat agar data lama tampil benar
    window.onload = updateForm;
</script>
</body>
</html>
