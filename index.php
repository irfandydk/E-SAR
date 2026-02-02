<?php 
// File: sarsip/index.php
session_start();
include 'config/koneksi.php';

// Jika status sudah login, langsung lempar ke dashboard
if(isset($_SESSION['status']) && $_SESSION['status'] == "login"){
    header("location:dashboard.php"); exit;
}

// Ambil Data Aplikasi
$app_name = "E-ARSIP";
$app_logo = "logo_instansi.png"; 
$q_setting = @mysqli_query($koneksi, "SELECT nama_aplikasi, logo_path FROM app_settings LIMIT 1");
if($q_setting && mysqli_num_rows($q_setting) > 0){
    $d = mysqli_fetch_assoc($q_setting);
    if(!empty($d['nama_aplikasi'])) $app_name = $d['nama_aplikasi'];
    if(!empty($d['logo_path'])) $app_logo = $d['logo_path'];
}
$logo_url = "assets/" . $app_logo;

// Cek trigger error login
$auto_open_login = isset($_GET['pesan']) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Dokumen - <?php echo $app_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        /* --- 1. GLOBAL & BACKGROUND ANIMATION --- */
        body {
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            /* Animated Gradient Background */
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: #333;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Dark Mode Background Override */
        [data-bs-theme="dark"] body {
            background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: #f0f0f0;
        }

        /* --- 2. GLASSMORPHISM NAVBAR --- */
        .navbar {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
            z-index: 100;
        }
        
        [data-bs-theme="dark"] .navbar {
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand img { height: 40px; margin-right: 10px; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.2)); }
        .navbar-brand span { font-weight: 700; letter-spacing: 1px; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }

        .btn-login-nav {
            background: rgba(255, 255, 255, 0.9); color: #333; border: none;
            border-radius: 50px; padding: 8px 25px; font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s;
        }
        .btn-login-nav:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.2); background: #fff; }

        /* --- 3. MAIN CARD (GLASS EFFECT) --- */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        [data-bs-theme="dark"] .glass-card {
            background: rgba(30, 30, 40, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* --- 4. TABS STYLING --- */
        .custom-tabs {
            background: rgba(0,0,0,0.05);
            border-radius: 50px;
            padding: 5px;
            display: inline-flex;
        }
        [data-bs-theme="dark"] .custom-tabs { background: rgba(255,255,255,0.1); }

        .nav-pills .nav-link {
            border-radius: 50px; color: #666; font-weight: 600; padding: 10px 20px; transition: 0.3s;
        }
        [data-bs-theme="dark"] .nav-pills .nav-link { color: #ccc; }

        .nav-pills .nav-link.active {
            background-color: #fff; color: #333; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        [data-bs-theme="dark"] .nav-pills .nav-link.active {
            background-color: #4e54c8; color: #fff;
        }

        /* --- 5. ELEMENTS --- */
        .hero-title { font-weight: 800; color: #fff; text-shadow: 0 4px 10px rgba(0,0,0,0.3); letter-spacing: -1px; }
        .hero-subtitle { color: rgba(255,255,255,0.9); font-size: 1.1rem; }
        
        .btn-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none; color: white; border-radius: 12px; padding: 12px;
            font-weight: 600; width: 100%; transition: 0.3s;
        }
        .btn-action:hover { filter: brightness(1.1); transform: translateY(-2px); }

        #reader { width: 100%; border-radius: 16px; border: 2px dashed #ccc; overflow: hidden; }

        /* --- 6. LOGIN OVERLAY & ANIMATION --- */
        .main-wrapper { transition: filter 0.5s ease, transform 0.5s ease; }
        .main-wrapper.blur-bg { filter: blur(10px) brightness(0.7); transform: scale(0.95); pointer-events: none; }

        .login-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            z-index: 1050; visibility: hidden; opacity: 0;
            transition: all 0.4s ease;
        }
        .login-overlay.active { visibility: visible; opacity: 1; }

        .login-card-modal {
            width: 100%; max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            /* Animasi Masuk: Dari Pojok Kanan Atas */
            transform: translate(40vw, -40vh) scale(0.1);
            opacity: 0;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .login-overlay.active .login-card-modal { transform: translate(0, 0) scale(1); opacity: 1; }
        
        [data-bs-theme="dark"] .login-card-modal {
            background: rgba(30, 30, 40, 0.95); border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-close-custom {
            position: absolute; top: 20px; right: 20px; background: transparent; border: none;
            font-size: 1.5rem; color: #999; transition: 0.3s;
        }
        .btn-close-custom:hover { color: #dc3545; transform: rotate(90deg); }
    </style>
</head>
<body>

    <div class="main-wrapper" id="landingContent">
        
        <nav class="navbar navbar-expand-lg position-absolute w-100" style="top:0;">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <?php if(file_exists($logo_url)): ?>
                        <img src="<?php echo $logo_url; ?>" alt="Logo">
                    <?php else: ?>
                        <i class="bi bi-building fs-2 text-white me-2"></i>
                    <?php endif; ?>
                    <span><?php echo $app_name; ?></span>
                </a>
                <div class="ms-auto">
                    <button class="btn-login-nav" onclick="toggleLogin(true)">
                        Masuk <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </div>
        </nav>

        <div class="d-flex align-items-center justify-content-center min-vh-100 pt-5 pb-4">
            <div class="container">
                <div class="row justify-content-center text-center mb-4 mt-5">
                    <div class="col-lg-8">
                        <h1 class="display-4 hero-title mb-3">Validasi Dokumen Digital</h1>
                        <p class="hero-subtitle mb-0">
                            Verifikasi keaslian dokumen resmi instansi secara cepat, akurat, dan real-time.
                        </p>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        
                        <div class="glass-card p-4 p-md-5">
                            
                            <div class="text-center mb-4">
                                <div class="custom-tabs" id="pills-tab" role="tablist">
                                    <button class="nav-link active rounded-pill px-4" id="pills-scan-tab" data-bs-toggle="pill" data-bs-target="#pills-scan" type="button" onclick="startScanner()">
                                        <i class="bi bi-qr-code-scan me-2"></i>Scan
                                    </button>
                                    <button class="nav-link rounded-pill px-4" id="pills-upload-tab" data-bs-toggle="pill" data-bs-target="#pills-upload" type="button" onclick="stopScanner()">
                                        <i class="bi bi-cloud-upload me-2"></i>Upload
                                    </button>
                                    <button class="nav-link rounded-pill px-4" id="pills-manual-tab" data-bs-toggle="pill" data-bs-target="#pills-manual" type="button" onclick="stopScanner()">
                                        <i class="bi bi-keyboard me-2"></i>Input
                                    </button>
                                </div>
                            </div>

                            <div class="tab-content" id="pills-tabContent">
                                
                                <div class="tab-pane fade show active text-center" id="pills-scan">
                                    <div class="position-relative mb-3">
                                        <div id="reader"></div>
                                        <div class="mt-2 text-muted small" id="scan-status">
                                            <i class="bi bi-camera-video me-1"></i> Izinkan akses kamera.
                                        </div>
                                    </div>
                                    <button id="btn-stop-scan" class="btn btn-sm btn-outline-danger d-none rounded-pill px-3" onclick="stopScanner()">
                                        Matikan Kamera
                                    </button>
                                </div>
                                
                                <div class="tab-pane fade" id="pills-upload">
                                    <form action="validasi_file.php" method="POST" enctype="multipart/form-data">
                                        <div class="mb-4 text-center">
                                            <div class="p-4 border-2 border-dashed rounded-4 bg-body-tertiary">
                                                <i class="bi bi-file-earmark-pdf fs-1 text-primary mb-2"></i>
                                                <div class="small text-muted">Upload file PDF dokumen asli di sini</div>
                                            </div>
                                        </div>
                                        <input type="file" name="file_dokumen" class="form-control mb-3" accept=".pdf" required>
                                        <button type="submit" name="cek_validasi" class="btn-action">
                                            <i class="bi bi-search me-2"></i> Cek Validitas File
                                        </button>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="pills-manual">
                                    <form action="validasi_manual.php" method="GET">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold small text-uppercase text-muted">Token / Nomor Surat</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent"><i class="bi bi-hash"></i></span>
                                                <input type="text" name="kode" class="form-control form-control-lg" placeholder="Contoh: EXT-A1B2..." required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn-action">
                                            <i class="bi bi-arrow-right-circle me-2"></i> Cari Dokumen
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-white opacity-75">&copy; <?php echo date('Y'); ?> <b><?php echo $app_name; ?></b>. All Rights Reserved.</small>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="login-overlay" id="loginOverlay">
        <div class="position-absolute w-100 h-100" onclick="toggleLogin(false)"></div> <div class="login-card-modal">
            <button class="btn-close-custom" onclick="toggleLogin(false)"><i class="bi bi-x-lg"></i></button>

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-person-lock fs-2"></i>
                </div>
                <h4 class="fw-bold">Login Area</h4>
                <p class="text-muted small">Masuk untuk mengelola arsip.</p>
            </div>

            <?php if(isset($_GET['pesan'])){ ?>
                <div class="alert alert-danger py-2 small mb-3 text-center rounded-3 shadow-sm border-0">
                    <?php 
                        if($_GET['pesan'] == "gagal") echo "<i class='bi bi-x-circle me-1'></i> Akun tidak ditemukan / Password salah.";
                        else if($_GET['pesan'] == "logout") echo "<i class='bi bi-check-circle me-1'></i> Anda berhasil logout.";
                        else if($_GET['pesan'] == "belum_login") echo "<i class='bi bi-exclamation-circle me-1'></i> Sesi berakhir, silakan login.";
                    ?>
                </div>
            <?php } ?>

            <form action="cek_login.php" method="POST" autocomplete="off">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" onclick="showPass()" style="cursor:pointer; z-index:10">
                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm" style="background: linear-gradient(to right, #4e54c8, #8f94fb); border:none;">
                    MASUK SISTEM <i class="bi bi-box-arrow-in-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- LOGIKA SCANNER (Fungsi Asli Tetap Sama) ---
        var html5QrcodeScanner = null;

        function onScanSuccess(decodedText, decodedResult) {
            stopScanner();
            if (decodedText.includes("validasi.php?token=")) {
                window.location.href = decodedText;
            } else {
                window.location.href = "validasi.php?token=" + decodedText; 
            }
        }

        function onScanFailure(error) {}

        function startScanner() {
            if (html5QrcodeScanner === null) {
                document.getElementById('scan-status').innerHTML = "Memulai kamera...";
                html5QrcodeScanner = new Html5Qrcode("reader");
                const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
                .then(() => {
                    document.getElementById('scan-status').innerHTML = "Arahkan QR Code ke kotak.";
                    document.getElementById('btn-stop-scan').classList.remove('d-none');
                })
                .catch(err => {
                    document.getElementById('scan-status').innerHTML = "<span class='text-danger'>Gagal akses kamera: " + err + "</span>";
                });
            }
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                    document.getElementById('btn-stop-scan').classList.add('d-none');
                    document.getElementById('scan-status').innerHTML = "<i class='bi bi-camera-video me-1'></i> Kamera dimatikan.";
                }).catch((err) => {});
            }
        }
        
        // Auto start scan jika tab scan diklik
        document.getElementById('pills-scan-tab').addEventListener('click', startScanner);

        // --- LOGIKA LOGIN (ANIMASI) ---
        function toggleLogin(show) {
            const landing = document.getElementById('landingContent');
            const overlay = document.getElementById('loginOverlay');
            
            if(show) {
                landing.classList.add('blur-bg');
                overlay.classList.add('active');
                setTimeout(() => { document.getElementById('username').focus(); }, 300);
            } else {
                landing.classList.remove('blur-bg');
                overlay.classList.remove('active');
            }
        }

        // Auto Open Login jika error
        const autoOpen = "<?php echo $auto_open_login; ?>";
        if(autoOpen === 'true'){ toggleLogin(true); }

        function showPass() {
            var x = document.getElementById("password");
            var icon = document.getElementById("eyeIcon");
            if (x.type === "password") { x.type = "text"; icon.className = "bi bi-eye"; } 
            else { x.type = "password"; icon.className = "bi bi-eye-slash"; }
        }
    </script>
</body>
</html>