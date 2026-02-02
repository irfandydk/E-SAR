<?php
// File: sarsip/sidebar_menu.php

// 1. LOGIKA NAMA APLIKASI
if(!isset($koneksi)){
    if(file_exists('config/koneksi.php')) include 'config/koneksi.php';
}
$app_name_display = isset($app_name) ? $app_name : "SARSIP"; 
if(isset($koneksi) && (!isset($app_name) || empty($app_name))){
    $q_setting = @mysqli_query($koneksi, "SELECT nama_aplikasi FROM app_settings LIMIT 1");
    if($q_setting && mysqli_num_rows($q_setting) > 0){
        $d_setting = mysqli_fetch_assoc($q_setting);
        if(!empty($d_setting['nama_aplikasi'])) $app_name_display = $d_setting['nama_aplikasi'];
    }
}

// 2. FUNGSI HELPER
$current_page = basename($_SERVER['PHP_SELF']);
$current_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

function set_active($page_name, $cat_name = '') {
    global $current_page, $current_kategori;
    if ($cat_name != '') return ($page_name == $current_page && $current_kategori == $cat_name) ? 'active' : '';
    if ($current_kategori == '') return ($page_name == $current_page) ? 'active' : '';
    return '';
}
function set_show_collapse($page_check, $cats_check = []) {
    global $current_page, $current_kategori;
    if ($current_page == $page_check && in_array($current_kategori, $cats_check)) return 'show';
    return '';
}
function set_active_parent($page_check, $cats_check = []) {
    global $current_page, $current_kategori;
    if ($current_page == $page_check && in_array($current_kategori, $cats_check)) return 'text-orange fw-bold';
    return ''; // Biarkan default, nanti dihandle CSS dark mode
}
?>

<script>
    // Script ini ditaruh paling atas agar tidak ada "kedipan" putih saat reload
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme) {
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    } else {
        document.documentElement.setAttribute('data-bs-theme', 'light');
    }
</script>

<button class="btn btn-primary d-md-none rounded-circle shadow" id="btnToggleSidebar" style="position: fixed; top: 15px; left: 15px; z-index: 1060; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
    <i class="bi bi-list fs-4 text-white"></i>
</button>

<div id="sidebarOverlay" class="d-none" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040;"></div>

<style>
    /* VARIABEL WARNA (Light vs Dark) */
    :root {
        --sidebar-bg: #ffffff;
        --sidebar-border: #dee2e6;
        --text-color: #333;
        --hover-bg: #ffe8cc;
        --active-bg: #fd7e14;
        --active-text: #fff;
        --header-bg: #ffffff;
    }

    [data-bs-theme="dark"] {
        --sidebar-bg: #212529;
        --sidebar-border: #373b3e;
        --text-color: #e0e0e0;
        --hover-bg: #343a40; /* Abu gelap saat hover */
        --active-bg: #fd7e14; /* Tetap Orange */
        --active-text: #fff;
        --header-bg: #1a1d20;
    }

    /* Override Body Background untuk Dark Mode */
    [data-bs-theme="dark"] body {
        background-color: #121416 !important;
        color: #e0e0e0;
    }

    /* SIDEBAR CONTAINER */
    .sidebar {
        position: fixed; top: 0; left: 0; bottom: 0; width: 280px; height: 100vh;
        overflow-y: auto; z-index: 1050; 
        background-color: var(--sidebar-bg); 
        border-right: 1px solid var(--sidebar-border);
        transition: transform 0.3s ease-in-out, background-color 0.3s;
        display: flex; flex-direction: column;
        box-shadow: 4px 0 10px rgba(0,0,0,0.01);
    }
    
    .sidebar-header {
        background: var(--header-bg);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--sidebar-border);
    }

    /* USER CARD */
    .user-card {
        background: var(--bs-body-bg); /* Ikuti tema bootstrap */
        border: 1px solid var(--sidebar-border);
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    
    /* MENU ITEM */
    .nav-link { color: var(--text-color); padding: 10px 15px; display: block; text-decoration: none; transition: 0.2s; border-radius: 8px; margin-bottom: 4px; font-weight: 500;}
    .nav-link:hover { background-color: var(--hover-bg); color: var(--active-bg); transform: translateX(3px); }
    .nav-link.active { background-color: var(--active-bg); color: var(--active-text) !important; font-weight: bold; box-shadow: 0 4px 10px rgba(253, 126, 20, 0.3); }
    .text-orange { color: #fd7e14 !important; }
    
    /* SUBMENU */
    .submenu-link { padding-left: 38px; font-size: 0.9rem; position: relative; }
    .submenu-link::before { 
        content: ''; position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
        width: 5px; height: 5px; background-color: var(--text-color); border-radius: 50%; opacity: 0.5;
    }

    /* SCROLLBAR */
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); border-radius: 10px; }

    /* Fix Table Colors in Dark Mode */
    [data-bs-theme="dark"] .table { color: #e0e0e0; border-color: #373b3e; }
    [data-bs-theme="dark"] .table-light th { background-color: #2c3034; color: #fff; border-color: #373b3e; }
    [data-bs-theme="dark"] .card { background-color: #212529; border-color: #373b3e; }
    [data-bs-theme="dark"] .card-header { background-color: #2c3034 !important; border-color: #373b3e; color: #fff !important; }
    
    /* Modal Dark Mode Fix */
    [data-bs-theme="dark"] .modal-content { background-color: #212529; border-color: #495057; }
    [data-bs-theme="dark"] .modal-header { border-bottom-color: #495057; }
    [data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.show { transform: translateX(0); }
    }
</style>

<div class="sidebar" id="sidebarMenu">
    
    <div class="sidebar-header p-4 text-center position-relative">
        <button type="button" class="btn-close d-md-none position-absolute top-0 end-0 m-3" id="btnCloseSidebar"></button>

        <div class="mb-2">
            <?php if(file_exists('assets/logo_instansi.png')): ?>
                <img src="assets/logo_instansi.png" alt="Logo" class="img-fluid" style="max-height: 65px; filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1));">
            <?php else: ?>
                <div class="d-inline-block p-3 rounded-circle bg-body-secondary text-primary mb-2">
                    <i class="bi bi-building fs-1"></i>
                </div>
            <?php endif; ?>
        </div>

        <h5 class="fw-bold mb-3 sidebar-title" style="letter-spacing: 0.5px; color: var(--text-color);">
            <?php echo $app_name_display; ?>
        </h5>

        <div class="user-card p-3 rounded-4 mx-1">
            <div class="d-flex flex-column align-items-center">
                <div class="position-relative mb-2">
                    <div class="rounded-circle bg-body border border-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 55px; height: 55px; border-color: #fd7e14 !important;">
                        <i class="bi bi-person-fill fs-2 text-secondary"></i>
                    </div>
                    <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle"></span>
                </div>
                <div class="fw-bold lh-sm mb-1 text-body" style="font-size: 0.95rem; word-wrap: break-word; overflow-wrap: break-word;">
                    <?php echo $_SESSION['nama']; ?>
                </div>
                <span class="badge <?php echo ($_SESSION['role']=='admin') ? 'text-bg-danger' : 'text-bg-success'; ?> rounded-pill badge-role px-3 py-1 mt-1">
                    <?php echo strtoupper($_SESSION['role']); ?>
                </span>
            </div>
        </div>
    </div>

    <nav class="flex-grow-1 px-3 pb-5 mt-2">
        <div class="text-secondary fw-bold mb-2 ps-2" style="font-size: 0.7rem; letter-spacing: 1px;">MENU UTAMA</div>

        <a href="dashboard.php" class="nav-link <?php echo set_active('dashboard.php'); ?>">
            <i class="bi bi-grid-fill me-2"></i> Dashboard
        </a>
		
		<div class="my-3 border-top border-secondary-subtle"></div>
        
        <a href="tools_pdf.php" class="nav-link <?php echo set_active('tools_pdf.php'); ?>">
            <i class="bi bi-tools me-2"></i> PDF Tools
        </a>

        <?php if($_SESSION['role'] == 'admin'){ ?> 
            <a href="data_user.php" class="nav-link <?php echo set_active('data_user.php'); ?>">
                <i class="bi bi-people-fill me-2"></i> Kelola User
            </a> 
            <a href="admin_persetujuan.php" class="nav-link <?php echo set_active('admin_persetujuan.php'); ?>">
                <i class="bi bi-patch-check-fill me-2"></i> Persetujuan Profil
            </a>
            <div class="my-3 border-top border-secondary-subtle"></div>
        <?php } ?>

        <div class="text-secondary fw-bold mt-3 mb-2 ps-2" style="font-size: 0.7rem; letter-spacing: 1px;">ARSIP DOKUMEN</div>

        <a href="data_dokumen.php" class="nav-link <?php echo set_active('data_dokumen.php'); ?>">
            <i class="bi bi-collection-fill me-2"></i> Semua Dokumen
        </a>

        <?php 
            $admin_umum_cats = ['Surat Masuk', 'Surat Keluar', 'SK', 'Surat Perintah', 'Surat Pernyataan'];
            $collapse_state = set_show_collapse('data_dokumen.php', $admin_umum_cats);
            $parent_style   = ($collapse_state == 'show') ? 'text-orange fw-bold' : '';
        ?>
        <a class="nav-link d-flex justify-content-between align-items-center <?php echo $parent_style; ?>" data-bs-toggle="collapse" href="#collapseAdminUmum">
            <span><i class="bi bi-folder-fill me-2"></i> Admin Umum</span>
            <i class="bi bi-chevron-down small" style="font-size: 0.8em;"></i>
        </a>
        <div class="collapse <?php echo $collapse_state; ?>" id="collapseAdminUmum">
            <div class="ms-1 ps-1 border-start border-2 my-1" style="border-color: var(--sidebar-border) !important;">
                <a href="data_dokumen.php?kategori=Surat Masuk" class="nav-link submenu-link <?php echo set_active('data_dokumen.php', 'Surat Masuk'); ?>">Surat Masuk</a>
                <a href="data_dokumen.php?kategori=Surat Keluar" class="nav-link submenu-link <?php echo set_active('data_dokumen.php', 'Surat Keluar'); ?>">Surat Keluar</a>
                <a href="data_dokumen.php?kategori=SK" class="nav-link submenu-link <?php echo set_active('data_dokumen.php', 'SK'); ?>">SK (Keputusan)</a>
                <a href="data_dokumen.php?kategori=Surat Perintah" class="nav-link submenu-link <?php echo set_active('data_dokumen.php', 'Surat Perintah'); ?>">Surat Perintah</a>
                <a href="data_dokumen.php?kategori=Surat Pernyataan" class="nav-link submenu-link <?php echo set_active('data_dokumen.php', 'Surat Pernyataan'); ?>">Surat Pernyataan</a>
            </div>
        </div>

        <a href="data_dokumen.php?kategori=Arsip Keuangan" class="nav-link <?php echo set_active('data_dokumen.php', 'Arsip Keuangan'); ?>">
            <i class="bi bi-wallet-fill me-2"></i> Arsip Keuangan
        </a>
        <a href="data_dokumen.php?kategori=Arsip Operasi SAR" class="nav-link <?php echo set_active('data_dokumen.php', 'Arsip Operasi SAR'); ?>">
            <i class="bi bi-life-preserver me-2"></i> Arsip Operasi SAR
        </a>
        <a href="data_dokumen.php?kategori=Arsip Sumberdaya" class="nav-link <?php echo set_active('data_dokumen.php', 'Arsip Sumberdaya'); ?>">
            <i class="bi bi-tools me-2"></i> Arsip Sumberdaya
        </a>
        <a href="data_dokumen.php?kategori=Arsip Lainnya" class="nav-link <?php echo set_active('data_dokumen.php', 'Arsip Lainnya'); ?>">
            <i class="bi bi-archive-fill me-2"></i> Arsip Lainnya
        </a>

        <div class="text-secondary fw-bold mt-4 mb-2 ps-2" style="font-size: 0.7rem; letter-spacing: 1px;">PENGATURAN</div>

        <a href="tanda_tangan.php" class="nav-link <?php echo set_active('tanda_tangan.php'); ?>">
            <i class="bi bi-pen-fill me-2"></i> Tanda Tangan
        </a>
        <a href="laporan.php" class="nav-link <?php echo set_active('laporan.php'); ?>">
            <i class="bi bi-file-earmark-bar-graph-fill me-2"></i> Laporan
        </a>
        <a href="profil.php" class="nav-link <?php echo set_active('profil.php'); ?>">
            <i class="bi bi-person-lines-fill me-2"></i> Profil Saya
        </a>
        <?php if($_SESSION['role'] == 'admin'){ ?> 
            <a href="pengaturan.php" class="nav-link <?php echo set_active('pengaturan.php'); ?>">
                <i class="bi bi-gear-fill me-2"></i> Pengaturan
            </a> 
        <?php } ?>
        
        <div class="mt-4 px-1">
            <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between mb-3" id="btnThemeSwitch">
                <span id="themeText">Mode Terang</span>
                <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
            </button>

            <a href="logout.php" class="btn btn-danger-subtle text-danger w-100 btn-alert-logout d-flex align-items-center justify-content-center fw-bold py-2">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. LOGIKA DARK MODE ---
    const btnTheme = document.getElementById('btnThemeSwitch');
    const iconTheme = document.getElementById('themeIcon');
    const textTheme = document.getElementById('themeText');
    const html = document.documentElement;

    function applyTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (theme === 'dark') {
            iconTheme.className = 'bi bi-sun-fill text-warning';
            textTheme.innerText = 'Mode Gelap';
            btnTheme.className = 'btn btn-outline-light w-100 d-flex align-items-center justify-content-between mb-3';
        } else {
            iconTheme.className = 'bi bi-moon-stars-fill text-primary';
            textTheme.innerText = 'Mode Terang';
            btnTheme.className = 'btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between mb-3';
        }
    }

    // Cek theme saat load
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Event Klik
    btnTheme.addEventListener('click', function() {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    });


    // --- 2. LOGIKA SIDEBAR ---
    const btnToggle = document.getElementById('btnToggleSidebar');
    const btnClose  = document.getElementById('btnCloseSidebar');
    const sidebar   = document.getElementById('sidebarMenu');
    const overlay   = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        if(sidebar.classList.contains('show')) overlay.classList.remove('d-none');
        else overlay.classList.add('d-none');
    }

    if(btnToggle) btnToggle.addEventListener('click', toggleSidebar);
    if(btnClose) btnClose.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);

    // --- 3. LOGIKA LOGOUT ---
    document.querySelectorAll('.btn-alert-logout').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Konfirmasi Logout', text: "Anda yakin ingin mengakhiri sesi?", icon: 'question',
                showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Keluar'
            }).then((result) => { if (result.isConfirmed) window.location.href = href; });
        });
    });
    
    document.querySelectorAll('.btn-alert-hapus').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({ title: 'Hapus Data?', text: 'Data tidak dapat dikembalikan!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33333', confirmButtonText: 'Ya, Hapus!' })
            .then((result) => { if (result.isConfirmed) window.location.href = href; });
        });
    });
});
</script>