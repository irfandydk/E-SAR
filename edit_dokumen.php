<?php 
// File: sarsip/edit_dokumen.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ header("location:login.php"); exit; }
include 'config/koneksi.php';

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';
$query = mysqli_query($koneksi, "SELECT * FROM documents WHERE id_doc='$id'");

if(mysqli_num_rows($query) == 0){ echo "<script>alert('Data tidak ditemukan!'); window.location='data_dokumen.php';</script>"; exit; }
$d = mysqli_fetch_assoc($query);

// Cek Akses Edit
if($_SESSION['role'] != 'admin' && $_SESSION['id_user'] != $d['uploader_id']){
    echo "<script>alert('Akses Ditolak!'); window.location='data_dokumen.php';</script>"; exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Arsip - <?php echo isset($app_name) ? $app_name : 'SARSIP'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow rounded-4 border-0">
                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold text-warning mb-0"><i class="bi bi-pencil-square me-2"></i> Edit Dokumen</h4>
                        <a href="data_dokumen.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div class="card-body p-4">
                        <form action="proses_dokumen.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_doc" value="<?php echo $d['id_doc']; ?>">
                            <input type="hidden" name="file_lama" value="<?php echo $d['file_path']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Jenis / Kategori</label>
                                <select name="kategori" id="kategoriSelect" class="form-select bg-light" required onchange="updateForm()">
                                    <optgroup label="Administrasi Umum">
                                        <option value="Surat Masuk" <?php echo ($d['kategori']=='Surat Masuk')?'selected':''; ?>>Surat Masuk</option>
                                        <option value="Surat Keluar" <?php echo ($d['kategori']=='Surat Keluar')?'selected':''; ?>>Surat Keluar</option>
                                        <option value="SK" <?php echo ($d['kategori']=='SK')?'selected':''; ?>>SK</option>
                                        <option value="Surat Perintah" <?php echo ($d['kategori']=='Surat Perintah')?'selected':''; ?>>Surat Perintah</option>
                                        <option value="Surat Pernyataan" <?php echo ($d['kategori']=='Surat Pernyataan')?'selected':''; ?>>Surat Pernyataan</option>
                                    </optgroup>
                                    <optgroup label="Unit / Bidang">
                                        <option value="Arsip Keuangan" <?php echo ($d['kategori']=='Arsip Keuangan')?'selected':''; ?>>Arsip Keuangan</option>
                                        <option value="Arsip Operasi SAR" <?php echo ($d['kategori']=='Arsip Operasi SAR')?'selected':''; ?>>Arsip Operasi SAR</option>
                                        <option value="Arsip Sumberdaya" <?php echo ($d['kategori']=='Arsip Sumberdaya')?'selected':''; ?>>Arsip Sumberdaya</option>
                                    </optgroup>
                                    <option value="Arsip Lainnya" <?php echo ($d['kategori']=='Arsip Lainnya')?'selected':''; ?>>Arsip Lainnya</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Sifat Dokumen</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check card p-2 px-3 flex-fill <?php echo ($d['visibility']=='public')?'bg-success-subtle border-success-subtle':''; ?>">
                                        <input class="form-check-input" type="radio" name="visibility" id="visPublic" value="public" <?php echo ($d['visibility']=='public')?'checked':''; ?>>
                                        <label class="form-check-label w-100" for="visPublic"><i class="bi bi-globe-americas me-1"></i> Publik</label>
                                    </div>
                                    <div class="form-check card p-2 px-3 flex-fill <?php echo ($d['visibility']=='private')?'bg-danger-subtle border-danger-subtle':''; ?>">
                                        <input class="form-check-input" type="radio" name="visibility" id="visPrivate" value="private" <?php echo ($d['visibility']=='private')?'checked':''; ?>>
                                        <label class="form-check-label w-100" for="visPrivate"><i class="bi bi-lock-fill me-1"></i> Rahasia</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label class="form-label fw-bold" id="labelNomor">Nomor</label>
                                    <input type="text" name="nomor_surat" class="form-control" value="<?php echo $d['nomor_surat']; ?>" required>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-bold" id="labelTanggal">Tanggal</label>
                                    <input type="date" name="tgl_dokumen" class="form-control" value="<?php echo date('Y-m-d', strtotime($d['created_at'])); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3" id="boxAsalSurat" style="display: none;">
                                <label class="form-label fw-bold text-success">Asal Surat</label>
                                <input type="text" name="asal_surat" id="inputAsalSurat" class="form-control border-success" value="<?php echo $d['asal_surat']; ?>">
                            </div>
                            <div class="mb-3" id="boxTujuanSurat" style="display: none;">
                                <label class="form-label fw-bold text-primary">Tujuan Surat</label>
                                <input type="text" name="tujuan_surat" id="inputTujuanSurat" class="form-control border-primary" value="<?php echo $d['tujuan_surat']; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" id="labelJudul">Judul</label>
                                <textarea name="judul" class="form-control" rows="2" required><?php echo $d['judul']; ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">File PDF</label>
                                <div class="alert alert-secondary py-2 px-3 mb-2 small"><i class="bi bi-file-earmark-pdf me-1"></i> <?php echo $d['file_path']; ?></div>
                                <input type="file" name="file_dokumen" class="form-control" accept=".pdf">
                                <div class="form-text text-danger">* Kosongkan jika tidak mengganti file.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="data_dokumen.php" class="btn btn-light px-4">Batal</a>
                                <button type="submit" name="update_dokumen" class="btn btn-warning px-4 fw-bold">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateForm() {
            var cat = document.getElementById("kategoriSelect").value;
            var boxAsal = document.getElementById("boxAsalSurat");
            var boxTujuan = document.getElementById("boxTujuanSurat");
            
            boxAsal.style.display = "none";
            boxTujuan.style.display = "none";

            if(cat === "Surat Masuk"){ boxAsal.style.display = "block"; } 
            else if(cat === "Surat Keluar"){ boxTujuan.style.display = "block"; }
        }
        window.onload = updateForm;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>