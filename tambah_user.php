<?php 
// File: sarsip/tambah_user.php
session_start();

// 1. CEK LOGIN & AKSES ADMIN
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login"); exit;
}
if($_SESSION['role'] != 'admin'){
    echo "<script>alert('Akses Ditolak! Hanya Admin yang boleh menambah user.'); window.location='dashboard.php';</script>"; exit;
}

include 'config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - SARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        body { transition: background-color 0.3s; }
        .main-content { margin-left: 280px; padding: 30px; transition: margin 0.3s; }
        
        /* Dark Mode Fixes */
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select {
            background-color: #2b3035; border-color: #495057; color: #e0e0e0;
        }
        [data-bs-theme="dark"] .form-control:focus, [data-bs-theme="dark"] .form-select:focus {
            border-color: #fd7e14; color: #fff;
        }
        [data-bs-theme="dark"] .text-muted { color: #adb5bd !important; }

        @media (max-width: 768px) { .main-content { margin-left: 0 !important; padding: 15px; padding-top: 80px; } }
    </style>
</head>
<body>
    
    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    
                    <div class="card shadow rounded-4 border-0 mb-5">
                        <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-body"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Tambah Pengguna Baru</h5>
                            <a href="data_user.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                        </div>
                        
                        <div class="card-body p-4">
                            <form action="proses_user.php" method="POST" autocomplete="off">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control" required placeholder="Tanpa spasi">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" required placeholder="Min. 6 karakter">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_lengkap" class="form-control" required placeholder="Contoh: Budi Santoso, S.Kom">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">NIP / NRP</label>
                                        <input type="text" name="nip" class="form-control" placeholder="Nomor Induk Pegawai">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jabatan</label>
                                        <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Staf Administrasi">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-primary">Hak Akses (Role) <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select form-select-lg" required>
                                        <option value="">-- Pilih Role --</option>
                                        <option value="user">User Biasa (Pegawai)</option>
                                        
                                        <optgroup label="Administrator & PIC">
                                            <option value="admin">Administrator (Super User)</option>
                                            <option value="pic_admin">PIC Admin Umum</option>
                                            <option value="pic_keuangan">PIC Keuangan</option>
                                            <option value="pic_ops">PIC Operasi SAR</option>
                                            <option value="pic_sumberdaya">PIC Sumber Daya</option>
                                        </optgroup>
                                    </select>
                                    <div class="form-text mt-2">
                                        <i class="bi bi-info-circle me-1"></i> <strong>PIC</strong> memiliki hak CRUD (Input/Edit/Hapus) pada kategori arsip spesifik sesuai bidangnya.
                                    </div>
                                </div>

                                <div class="row border-top pt-3 mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Unit Kerja / Bidang</label>
                                        <input type="text" name="unit_kerja" class="form-control form-control-sm" placeholder="Opsional">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted">Instansi</label>
                                        <input type="text" name="instansi" class="form-control form-control-sm" value="BASARNAS" placeholder="Nama Instansi">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="data_user.php" class="btn btn-light border px-4">Batal</a>
                                    <button type="submit" name="simpan_user" class="btn btn-primary px-4 fw-bold shadow-sm">
                                        <i class="bi bi-save me-1"></i> Simpan User
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>