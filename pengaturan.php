<?php
// File: sarsip/pengaturan.php
session_start();
include 'config/koneksi.php';

// Cek Akses Admin
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ header("location:login.php"); exit; }
if($_SESSION['role'] != 'admin'){ header("location:dashboard.php"); exit; }

// Ambil Data Pengaturan
$q_set = mysqli_query($koneksi, "SELECT * FROM app_settings LIMIT 1");
$set   = mysqli_fetch_assoc($q_set);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Sistem - SARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>
    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        .logo-preview { max-height: 100px; padding: 5px; border: 1px dashed #ccc; border-radius: 8px; background: #f8f9fa; }
    </style>
</head>
<body>

<?php include 'sidebar_menu.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <h4 class="fw-bold mb-4"><i class="bi bi-gear-fill me-2 text-primary"></i>Pengaturan Sistem</h4>

        <?php if(isset($_GET['pesan']) && $_GET['pesan']=='sukses'){ ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i> Pengaturan berhasil disimpan.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-building-gear me-2"></i>Identitas Instansi & Aplikasi</h6>
            </div>
            <div class="card-body p-4">
                
                <form action="proses_pengaturan.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="update_settings">
                    <input type="hidden" name="id_settings" value="<?php echo $set['id']; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Informasi Umum</h6>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nama Aplikasi</label>
                                <input type="text" name="nama_aplikasi" class="form-control" value="<?php echo htmlspecialchars($set['nama_aplikasi'] ?? 'SARSIP'); ?>" required>
                                <div class="form-text">Nama yang tampil di sidebar menu.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nama Instansi (Induk)</label>
                                <input type="text" name="nama_instansi" class="form-control" value="<?php echo htmlspecialchars($set['nama_instansi'] ?? ''); ?>" placeholder="Contoh: KEMENTERIAN PERHUBUNGAN">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nama Unit Kerja / Satuan Kerja</label>
                                <input type="text" name="nama_unit_kerja" class="form-control fw-bold" value="<?php echo htmlspecialchars($set['nama_unit_kerja'] ?? ''); ?>" placeholder="Contoh: KANTOR SAR KELAS A KENDARI">
                                <div class="form-text">Nama unit pelaksana teknis atau kantor daerah.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Logo Instansi</label>
                                <div class="d-flex align-items-center gap-3">
                                    <?php 
                                        $logo = !empty($set['logo_path']) ? "assets/".$set['logo_path'] : "assets/default_logo.png"; 
                                        if(file_exists($logo)) echo "<img src='$logo' class='logo-preview'>";
                                    ?>
                                    <div class="w-100">
                                        <input type="file" name="logo" class="form-control" accept="image/*">
                                        <div class="form-text">Format PNG/JPG (Transparan disarankan).</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <h6 class="text-success fw-bold border-bottom pb-2 mb-3">Kontak & Lokasi</h6>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Alamat Lengkap</label>
                                <textarea name="alamat_instansi" class="form-control" rows="4" placeholder="Jalan..."><?php echo htmlspecialchars($set['alamat_instansi'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Email Resmi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email_instansi" class="form-control" value="<?php echo htmlspecialchars($set['email_instansi'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nomor Telepon / Fax</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="telepon_instansi" class="form-control" value="<?php echo htmlspecialchars($set['telepon_instansi'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-save me-2"></i> Simpan Pengaturan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>