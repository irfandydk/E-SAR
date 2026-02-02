<?php 
session_start();
include 'config/koneksi.php';

$id_doc = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM documents WHERE id_doc='$id_doc'");
$data = mysqli_fetch_assoc($query);

// 1. TENTUKAN SIAPA YANG AKAN TANDA TANGAN
$signer_id   = $_SESSION['id_user'];      
$signer_name = $_SESSION['nama'];         

// Jika Admin memilih User Lain (Impersonate)
if(isset($_GET['ttd_as']) && $_SESSION['role'] == 'admin'){
    $signer_id = $_GET['ttd_as'];
    $q_user = mysqli_query($koneksi, "SELECT nama_lengkap, jabatan FROM users WHERE id_user='$signer_id'");
    $d_user = mysqli_fetch_assoc($q_user);
    if($d_user){
        $signer_name = $d_user['nama_lengkap'] . " <br><small class='text-muted'>(" . $d_user['jabatan'] . ")</small>";
    }
}

// 2. CEK APAKAH ORANG INI ($signer_id) SUDAH TANDA TANGAN DI DOKUMEN INI?
$cek_duplikat = mysqli_query($koneksi, "SELECT * FROM doc_signers WHERE id_doc='$id_doc' AND id_user='$signer_id'");

if(mysqli_num_rows($cek_duplikat) > 0){
    // JIKA SUDAH ADA, TOLAK AKSES!
    $pemberitahuan = "User ini sudah menandatangani dokumen tersebut sebelumnya.";
    if($_SESSION['role'] == 'admin' && isset($_GET['ttd_as'])){
        $pemberitahuan = "User yang Anda pilih sudah memiliki tanda tangan di dokumen ini. Silakan pilih user lain.";
    }
    
    echo "<script>
            alert('$pemberitahuan');
            // Jika admin, kembalikan ke tanda_tangan.php agar bisa pilih ulang atau dokumen lain
            window.location='tanda_tangan.php';
          </script>";
    exit;
}

$path_signed = "uploads/doc_signed/SIGNED_" . $data['file_path'];
$file_url = (file_exists($path_signed) ? $path_signed : "uploads/doc_asli/" . $data['file_path']) . "?t=" . time();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Multi & Resize TTD - SARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    
    <style>
        #pdf-wrapper { background-color: #525659; padding: 20px; height: 100vh; overflow: auto; display: flex; justify-content: center; }
        #pdf-container { position: relative; width: fit-content; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
        
        /* Marker Style */
        .qr-marker {
            /* Hapus fixed width/height */
            background-color: rgba(255, 235, 59, 0.6); border: 2px dashed red;
            position: absolute; cursor: move;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 10px; color: black;
            z-index: 100; user-select: none;
            min-width: 30px; min-height: 30px; /* Batas minimal */
        }
        /* Handle Resize di Pojok Kanan Bawah */
        .resize-handle {
            width: 15px; height: 15px;
            background-color: red;
            position: absolute; right: 0; bottom: 0;
            cursor: se-resize; /* Kursor panah diagonal */
            z-index: 101;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 bg-light border-end p-3 d-flex flex-column" style="height: 100vh; overflow-y: auto;">
                
                <?php if($_SESSION['role'] == 'admin'): ?>
                <div class="card mb-3 border-warning shadow-sm">
                    <div class="card-body bg-warning bg-opacity-10 p-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-badge-fill text-warning fs-4 me-2"></i>
                            <div class="lh-1">
                                <span class="fw-bold small text-dark d-block">Mode Administrator</span>
                                <small class="text-muted" style="font-size: 11px;">Pilih penandatangan:</small>
                            </div>
                        </div>
                        <form>
                            <select class="form-select form-select-sm border-warning text-dark" 
                                    onchange="window.location.href='?id=<?php echo $id_doc; ?>&ttd_as='+this.value">
                                
                                <option value="<?php echo $_SESSION['id_user']; ?>" <?php if($signer_id == $_SESSION['id_user']) echo 'selected'; ?>>
                                    -- Saya Sendiri (<?php echo $_SESSION['nama']; ?>) --
                                </option>

                                <?php
                                // Ambil semua user aktif
                                $q_users = mysqli_query($koneksi, "SELECT * FROM users WHERE status_aktif='1' ORDER BY nama_lengkap ASC");
                                
                                while($u = mysqli_fetch_array($q_users)){
                                    // Skip admin login agar tidak duplikat dengan opsi "Saya Sendiri"
                                    if($u['id_user'] == $_SESSION['id_user']) continue;

                                    // Cek apakah user ini SUDAH tanda tangan di dokumen ini?
                                    $cek_sudah = mysqli_query($koneksi, "SELECT * FROM doc_signers WHERE id_doc='$id_doc' AND id_user='".$u['id_user']."'");
                                    $is_signed = mysqli_num_rows($cek_sudah) > 0;
                                    
                                    $selected = ($u['id_user'] == $signer_id) ? 'selected' : '';
                                    
                                    // Jika user sudah tanda tangan, disable pilihannya agar tidak dipilih lagi
                                    $disabled = ($is_signed) ? 'disabled' : ''; 
                                    $label_status = ($is_signed) ? ' (Sudah TTD)' : '';
                                    
                                    echo "<option value='".$u['id_user']."' $selected $disabled>".$u['nama_lengkap']." $label_status</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>
                <hr class="my-2">
                <?php endif; ?>

                <h5 class="fw-bold" style="color: var(--primary-orange);">Posisi TTD</h5>
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted">Judul Dokumen:</label>
                    <p class="lh-sm mb-0 small"><?php echo $data['judul']; ?></p>
                </div>

                <div class="alert alert-primary py-2 mb-3 shadow-sm">
                    <small class="fw-bold text-dark">Penandatangan Aktif:</small><br>
                    <span class="text-primary fw-bold"><?php echo $signer_name; ?></span>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small">Navigasi & Zoom</label>
                    <div class="d-flex justify-content-between align-items-center mt-1 mb-2">
                        <button class="btn btn-sm btn-outline-dark" id="prev-page">← Prev</button>
                        <span id="page-info" class="fw-bold small">Page 1</span>
                        <button class="btn btn-sm btn-outline-dark" id="next-page">Next →</button>
                    </div>
                    <div class="btn-group w-100">
                        <button class="btn btn-sm btn-secondary" onclick="zoomOut()">Zoom Out</button>
                        <button class="btn btn-sm btn-secondary" onclick="zoomIn()">Zoom In</button>
                    </div>
                </div>

                <div class="mb-3 p-2 bg-white border rounded">
                    <label class="fw-bold text-success small">+ Tambah QR Code</label>
                    <button class="btn btn-success btn-sm w-100 mt-1" onclick="addMarker()">
                        <i class="bi bi-qr-code"></i> Letakkan QR Baru
                    </button>
                    <small class="text-muted d-block mt-2 lh-sm" style="font-size: 0.75em;">
                        * Geser kotak kuning untuk memindah.<br>
                        * Tarik pojok kanan bawah merah untuk resize.<br>
                        * Klik kanan untuk menghapus.
                    </small>
                </div>

                <div class="mt-auto pb-3">
                    <form action="proses_ttd.php" method="POST" id="form-ttd">
                        <input type="hidden" name="signer_id" value="<?php echo $signer_id; ?>">
                        
                        <input type="hidden" name="id_doc" value="<?php echo $data['id_doc']; ?>">
                        <input type="hidden" name="proses_ttd" value="1">
                        <input type="hidden" name="qr_list" id="input-qr-list">
                        
                        <button type="button" onclick="simpanSemua()" class="btn btn-primary w-100 py-3 fw-bold shadow">
                            <i class="bi bi-floppy-fill me-2"></i> SIMPAN POSISI
                        </button>
                        <a href="tanda_tangan.php" class="btn btn-link text-danger w-100 text-center mt-2">Batal / Kembali</a>
                    </form>
                </div>
            </div>

            <div class="col-md-9 p-0">
                <div id="pdf-wrapper">
                    <div id="pdf-container">
                        <canvas id="the-canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var url = '<?php echo $file_url; ?>';
        var pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        var pdfDoc = null, pageNum = 1, scale = 1.0,
            canvas = document.getElementById('the-canvas'),
            ctx = canvas.getContext('2d'),
            container = document.getElementById('pdf-container');

        // DATA GLOBAL: Menyimpan posisi (x,y) dan ukuran (w,h) dalam PERSEN
        var markers = []; 
        var uniqueIdCounter = 0;

        // --- RENDER PDF ---
        function renderPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                var viewport = page.getViewport({scale: scale});
                canvas.height = viewport.height; canvas.width = viewport.width;
                page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function(){
                    renderMarkersForPage(num); // Render ulang marker setelah zoom/pindah halaman
                });
                document.getElementById('page-info').textContent = 'Page ' + num + ' of ' + pdfDoc.numPages;
            });
        }

        // --- MANAJEMEN MARKER ---
        function addMarker() {
            var id = uniqueIdCounter++;
            markers.push({
                id: id, page: pageNum,
                x_percent: 0.1, y_percent: 0.1, // Posisi awal 10% dari kiri atas
                w_percent: 0.15, h_percent: 0.0 // Ukuran awal (h_percent 0 dulu, nanti disamakan dgn w)
            });
            renderMarkersForPage(pageNum);
        }

        function removeMarker(id) {
            if(confirm("Hapus QR ini?")) {
                markers = markers.filter(m => m.id !== id);
                renderMarkersForPage(pageNum);
            }
        }

        function renderMarkersForPage(pNum) {
            document.querySelectorAll('.qr-marker').forEach(e => e.remove());
            var pageMarkers = markers.filter(m => m.page === pNum);

            pageMarkers.forEach(m => {
                var el = document.createElement('div');
                el.className = 'qr-marker';
                el.innerHTML = '<i class="bi bi-qr-code"></i>';
                el.id = 'marker-' + m.id;
                
                // Hitung Posisi & Ukuran Pixel berdasarkan Canvas saat ini
                var leftPos = m.x_percent * canvas.width;
                var topPos  = m.y_percent * canvas.height;
                // Aspect Ratio Square: Tinggi mengikuti Lebar
                var widthPx = m.w_percent * canvas.width;
                var heightPx = widthPx; // Paksa persegi

                el.style.left = leftPos + 'px'; el.style.top  = topPos + 'px';
                el.style.width = widthPx + 'px'; el.style.height = heightPx + 'px';
                el.style.fontSize = (widthPx * 0.5) + 'px'; // Sesuaikan ukuran ikon

                // Tambah Handle Resize
                var handle = document.createElement('div');
                handle.className = 'resize-handle';
                el.appendChild(handle);

                // Setup Event Listener (Move & Resize)
                setupInteractions(el, handle, m);

                el.addEventListener('contextmenu', function(e){ e.preventDefault(); removeMarker(m.id); });
                container.appendChild(el);
            });
        }

        // --- LOGIC INTERAKSI (MOVE & RESIZE) ---
        function setupInteractions(markerEl, handleEl, markerData) {
            var isMoving = false; var isResizing = false;
            var startX, startY, startLeft, startTop, startWidth;

            // 1. LOGIC MOVE (Klik pada badan marker)
            markerEl.addEventListener('mousedown', function(e) {
                if(e.target === handleEl) return; // Abaikan jika yang diklik handle resize
                isMoving = true;
                startX = e.clientX; startY = e.clientY;
                startLeft = parseFloat(markerEl.style.left); startTop = parseFloat(markerEl.style.top);
                markerEl.style.cursor = 'grabbing';
                e.stopPropagation();
            });

            // 2. LOGIC RESIZE (Klik pada handle merah)
            handleEl.addEventListener('mousedown', function(e) {
                isResizing = true;
                startX = e.clientX;
                startWidth = parseFloat(markerEl.style.width);
                e.stopPropagation(); // Stop agar tidak memicu logic move
                e.preventDefault();
            });

            // GLOBAL MOUSEMOVE & MOUSEUP
            window.addEventListener('mousemove', function(e) {
                if (isMoving) {
                    var dx = e.clientX - startX; var dy = e.clientY - startY;
                    var newLeft = startLeft + dx; var newTop  = startTop + dy;
                    // Batas container
                    var maxW = canvas.width - markerEl.offsetWidth; var maxH = canvas.height - markerEl.offsetHeight;
                    newLeft = Math.max(0, Math.min(newLeft, maxW)); newTop  = Math.max(0, Math.min(newTop, maxH));

                    markerEl.style.left = newLeft + 'px'; markerEl.style.top  = newTop + 'px';
                    // Update Data
                    markerData.x_percent = newLeft / canvas.width; markerData.y_percent = newTop / canvas.height;
                }
                
                if (isResizing) {
                    var dx = e.clientX - startX;
                    var newWidth = startWidth + dx;
                    // Batas minimal 30px
                    newWidth = Math.max(30, newWidth);
                    // Batas maksimal agar tidak keluar kanan bawah
                    var maxWidthAvailable = canvas.width - parseFloat(markerEl.style.left);
                    var maxHeightAvailable = canvas.height - parseFloat(markerEl.style.top);
                    var maxAllowed = Math.min(maxWidthAvailable, maxHeightAvailable);
                    newWidth = Math.min(newWidth, maxAllowed);

                    // Terapkan Width & Height (Square)
                    markerEl.style.width = newWidth + 'px'; markerEl.style.height = newWidth + 'px';
                    markerEl.style.fontSize = (newWidth * 0.5) + 'px'; // Update icon size

                    // Update Data
                    markerData.w_percent = newWidth / canvas.width; 
                    markerData.h_percent = newWidth / canvas.height; // Height percent mengikuti width pixel yang sama
                }
            });

            window.addEventListener('mouseup', function() {
                isMoving = false; isResizing = false;
                markerEl.style.cursor = 'move';
            });
        }

        // --- PDF INIT & ZOOM ---
        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) { pdfDoc = pdfDoc_; renderPage(pageNum); });
        document.getElementById('prev-page').addEventListener('click', () => { if(pageNum>1){ pageNum--; renderPage(pageNum);} });
        document.getElementById('next-page').addEventListener('click', () => { if(pageNum<pdfDoc.numPages){ pageNum++; renderPage(pageNum);} });
        function zoomIn(){ scale += 0.2; renderPage(pageNum); }
        function zoomOut(){ if(scale > 0.4) { scale -= 0.2; renderPage(pageNum); } }

        // --- SIMPAN KE BACKEND ---
        // --- REVISI FUNGSI SIMPAN (Kirim Persentase, Bukan MM) ---
        function simpanSemua() {
            if(markers.length === 0) {
                alert("Belum ada QR Code yang ditambahkan!");
                return;
            }

            // Kita kirim data PERSENTASE (0.0 sampai 1.0)
            // Biarkan Backend (PHP) yang menghitung mm sesuai ukuran kertas asli (A4/F4/Legal)
            var finalData = markers.map(m => {
                return {
                    halaman: m.page,
                    x_pct: m.x_percent, // Kirim persen X
                    y_pct: m.y_percent, // Kirim persen Y
                    w_pct: m.w_percent  // Kirim persen Lebar
                    // h_pct tidak perlu, karena akan dibuat persegi (ratio 1:1) di backend
                };
            });

            // Masukkan JSON ke Input Hidden
            document.getElementById('input-qr-list').value = JSON.stringify(finalData);

            if(confirm("Simpan " + markers.length + " posisi tanda tangan?")) {
                document.getElementById('form-ttd').submit();
            }
        }
    </script>
</body>
</html>
