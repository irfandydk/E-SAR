<?php 
session_start();
if($_SESSION['status'] != "login"){ header("location:login.php?pesan=belum_login"); }
include 'config/koneksi.php';

// ==========================================
// LOGIKA HAPUS (SATUAN & MASSAL)
// ==========================================

// 1. HAPUS SATUAN (GET)
if(isset($_GET['hapus_id'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_id']);
    hapus_arsip_eksternal($koneksi, $id);
    header("location:arsip_eksternal.php?pesan=hapus_sukses");
    exit;
}

// 2. HAPUS MASSAL (POST)
if(isset($_POST['hapus_banyak'])){
    if(!empty($_POST['ids'])){
        $ids = $_POST['ids'];
        foreach($ids as $id){
            hapus_arsip_eksternal($koneksi, $id);
        }
        header("location:arsip_eksternal.php?pesan=bulk_sukses");
        exit;
    }
}

// FUNGSI BANTUAN HAPUS
function hapus_arsip_eksternal($koneksi, $id){
    $q = mysqli_query($koneksi, "SELECT file_path FROM external_archives WHERE id_ext='$id'");
    if(mysqli_num_rows($q) > 0){
        $d = mysqli_fetch_assoc($q);
        $file = "uploads/arsip_lain/" . $d['file_path'];
        if(file_exists($file)) unlink($file);
        mysqli_query($koneksi, "DELETE FROM external_archives WHERE id_ext='$id'");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Arsip Eksternal - <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-orange: #fd7e14; --dark-orange: #d35400; --sidebar-bg: #2d3436; }
        body { background-color: #fff7e6; }
        .sidebar { min-height: 100vh; background-color: var(--sidebar-bg); color: white; }
        .sidebar a { color: #ffeaa7; text-decoration: none; display: block; padding: 12px 20px; transition: 0.3s; border-left: 5px solid transparent; }
        .sidebar a:hover { background-color: #3f4648; color: white; padding-left: 25px; border-left: 5px solid var(--primary-orange); }
        .sidebar .active { background-color: #3f4648; color: var(--primary-orange); border-left: 5px solid var(--primary-orange); font-weight: bold; }
        .btn-primary { background-color: var(--primary-orange); border-color: var(--primary-orange); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0"><?php include 'sidebar_menu.php'; ?></div>

        <div class="col-md-10 p-4">
            <h3 class="mb-4">Arsip Eksternal</h3>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-secondary">+ Upload Arsip Fisik</h5>
                </div>
                <div class="card-body">
                    <form action="proses_eksternal.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Judul Dokumen</label>
                                <input type="text" name="judul" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>File PDF</label>
                                <input type="file" name="file_eksternal" class="form-control" accept=".pdf" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label>&nbsp;</label>
                                <button type="submit" name="upload_eksternal" class="btn btn-primary w-100">Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <form action="" method="POST" id="formBulkDelete">
                <input type="hidden" name="hapus_banyak" value="true">

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Arsip</h5>
                        
                        <button type="button" id="btnBulkDelete" class="btn btn-danger btn-sm" disabled>
                            <i class="bi bi-trash"></i> Hapus Terpilih
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%" class="text-center">
                                            <input type="checkbox" id="checkAll" class="form-check-input">
                                        </th>
                                        <th>No</th>
                                        <th>Judul Dokumen</th>
                                        <th>Pengupload</th>
                                        <th>Waktu</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no=1;
                                    $id_user = $_SESSION['id_user'];
                                    if($_SESSION['role'] == 'admin'){
                                        $q = mysqli_query($koneksi, "SELECT e.*, u.nama_lengkap FROM external_archives e JOIN users u ON e.uploaded_by = u.id_user ORDER BY e.created_at DESC");
                                    } else {
                                        $q = mysqli_query($koneksi, "SELECT e.*, u.nama_lengkap FROM external_archives e JOIN users u ON e.uploaded_by = u.id_user WHERE e.uploaded_by='$id_user' ORDER BY e.created_at DESC");
                                    }

                                    if(mysqli_num_rows($q) > 0){
                                        while($d = mysqli_fetch_array($q)){
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="ids[]" value="<?php echo $d['id_ext']; ?>" class="form-check-input check-item">
                                        </td>
                                        <td><?php echo $no++; ?></td>
                                        <td class="fw-bold"><?php echo $d['judul']; ?></td>
                                        <td><?php echo $d['nama_lengkap']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($d['created_at'])); ?></td>
                                        <td>
                                            <a href="uploads/arsip_lain/<?php echo $d['file_path']; ?>" target="_blank" class="btn btn-sm btn-info text-white">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="arsip_eksternal.php?hapus_id=<?php echo $d['id_ext']; ?>" class="btn btn-sm btn-danger btn-alert-hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center py-4'>Tidak ada data.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
</body>
</html>