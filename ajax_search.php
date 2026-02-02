<?php
// File: sarsip/ajax_search.php
include 'config/koneksi.php';

if(isset($_POST['keyword'])){
    $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword']);
    
    // Cari data
    $query = "SELECT * FROM documents 
              WHERE nomor_surat LIKE '%$keyword%' OR judul LIKE '%$keyword%' OR asal_surat LIKE '%$keyword%'
              LIMIT 5";
              
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) > 0){
        echo '<ul class="list-unstyled mb-0">';
        while($row = mysqli_fetch_assoc($result)){
            // PENTING: Kita simpan nomor surat di atribut 'data-search'
            ?>
            <li data-search="<?php echo $row['nomor_surat']; ?>">
                <div class="fw-bold text-dark"><?php echo $row['nomor_surat']; ?></div>
                <small class="text-muted"><?php echo substr($row['judul'], 0, 50); ?>...</small>
            </li>
            <?php
        }
        echo '</ul>';
    } else {
        echo '<ul class="list-unstyled mb-0"><li class="text-muted text-center py-2">Tidak ditemukan</li></ul>';
    }
}
?>