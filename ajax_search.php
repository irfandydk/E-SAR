<?php
// File: sarsip/ajax_search.php
session_start();
include 'config/koneksi.php';

// Cek Login
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    exit;
}

if(!empty($_POST["keyword"])) {
    
    $key     = mysqli_real_escape_string($koneksi, $_POST["keyword"]);
    $role    = $_SESSION['role'];
    $id_user = $_SESSION['id_user'];

    // 1. CONFIG FILTER PRIVASI (PENTING)
    // Gunakan alias 'd.' untuk documents
    $filter_user = ($role != 'admin') ? " AND (d.visibility='public' OR d.id_user='$id_user') " : "";

    // 2. QUERY PENCARIAN (Dengan Alias d. dan u.)
    // Kita mencari berdasarkan Judul, Nomor Surat, atau Kategori
    $query = "SELECT d.*, u.nama_lengkap 
              FROM documents d
              LEFT JOIN users u ON d.id_user = u.id_user
              WHERE (d.judul LIKE '%$key%' OR d.nomor_surat LIKE '%$key%' OR d.kategori LIKE '%$key%') 
              $filter_user
              ORDER BY d.created_at DESC LIMIT 5";
              
    $result = mysqli_query($koneksi, $query);

    // 3. TAMPILKAN HASIL
    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            // Tentukan warna badge kategori
            $kategori = $row['kategori'];
            $badge_color = 'bg-secondary';
            if($kategori == 'Surat Masuk') $badge_color = 'bg-primary';
            if($kategori == 'Surat Keluar') $badge_color = 'bg-success';
            if($kategori == 'SK') $badge_color = 'bg-warning text-dark';
            
            // Output HTML untuk list sugesti
            ?>
            <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-start" 
                data-search="<?php echo $row['judul']; ?>" 
                onclick="selectDoc('<?php echo $row['judul']; ?>')">
                
                <div>
                    <div class="fw-bold text-dark"><?php echo $row['judul']; ?></div>
                    <small class="text-muted"><i class="bi bi-hash"></i> <?php echo $row['nomor_surat']; ?></small>
                </div>
                <span class="badge <?php echo $badge_color; ?> rounded-pill small"><?php echo $row['kategori']; ?></span>
            </li>
            <?php
        }
    } else {
        echo "<li class='list-group-item text-muted small text-center py-3'>Dokumen tidak ditemukan.</li>";
    }
}
?>
