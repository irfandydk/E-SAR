<?php
// File: sarsip/tools_pdf.php
session_start();
include 'config/koneksi.php';
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ header("location:login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Tools - SARSIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script src="assets/js/pdf.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/js/pdf.worker.min.js';</script>
    <script src="assets/js/Sortable.min.js"></script>

    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        
        /* Style untuk Merge Tools */
        .pdf-card { 
            position: relative; width: 150px; margin: 10px; border: 1px solid #ddd; border-radius: 8px; 
            overflow: hidden; background: #fff; cursor: move; transition: transform 0.2s;
        }
        .pdf-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .pdf-thumb { height: 180px; width: 100%; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .pdf-thumb canvas { width: 100%; height: auto; object-fit: contain; }
        .pdf-info { padding: 8px; font-size: 12px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: #fff; border-top: 1px solid #eee; }
        .btn-remove { 
            position: absolute; top: 5px; right: 5px; background: rgba(220, 53, 69, 0.9); color: white; 
            border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 12px; cursor: pointer;
        }
        .drag-area { 
            border: 2px dashed #0d6efd; border-radius: 10px; background-color: rgba(13, 110, 253, 0.05); 
            transition: all 0.3s; min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center;
        }
        .drag-area.active { background-color: rgba(13, 110, 253, 0.15); border-color: #0a58ca; }
        #pdfGrid { display: flex; flex-wrap: wrap; gap: 10px; min-height: 100px; padding: 10px; align-items: flex-start; }
    </style>
</head>
<body>

    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h4 class="fw-bold mb-4"><i class="bi bi-tools me-2 text-primary"></i>PDF Tools</h4>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <ul class="nav nav-tabs card-header-tabs" id="toolsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="merge-tab" data-bs-toggle="tab" data-bs-target="#merge-pane" type="button" role="tab">
                                <i class="bi bi-files me-2"></i>Gabungkan PDF (Merge)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="split-tab" data-bs-toggle="tab" data-bs-target="#split-pane" type="button" role="tab">
                                <i class="bi bi-file-earmark-break me-2"></i>Pisahkan PDF (Split)
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="toolsTabContent">
                        
                        <div class="tab-pane fade show active" id="merge-pane" role="tabpanel">
                            <div class="alert alert-info d-flex align-items-center mb-4">
                                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                                <div>
                                    <strong>Cara Merge:</strong> Upload file, geser kotak untuk mengurutkan, lalu klik Gabungkan.
                                    <br><small>Batas maksimal upload server: ±20 File.</small>
                                </div>
                            </div>

                            <div class="drag-area p-5 text-center mb-4" id="dropArea">
                                <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 50px;"></i>
                                <h5 class="mt-3 fw-bold">Drag & Drop PDF Di Sini</h5>
                                <button class="btn btn-primary rounded-pill px-4 mt-2" onclick="document.getElementById('fileInput').click()">Pilih File</button>
                                <input type="file" id="fileInput" multiple accept=".pdf" hidden>
                            </div>

                            <div id="pdfGrid" class="border rounded bg-light mb-4">
                                <div class="text-muted w-100 text-center py-5" id="emptyText">Belum ada file.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-outline-danger rounded-pill px-4" onclick="resetFiles()">Reset</button>
                                <button id="btnMerge" class="btn btn-success rounded-pill px-4 fw-bold" onclick="mergeFiles()">
                                    <i class="bi bi-check-circle me-2"></i>Proses Gabung
                                </button>
                            </div>
                            
                            <div id="loadingText" class="text-center mt-3" style="display:none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 fw-bold text-primary">Memproses penggabungan...</p>
                            </div>
                        </div>


                        <div class="tab-pane fade" id="split-pane" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="alert alert-warning d-flex align-items-center">
                                        <i class="bi bi-exclamation-circle-fill me-3 fs-3"></i>
                                        <div>
                                            <strong>Fitur Split PDF</strong><br>
                                            Pisahkan file PDF menjadi per halaman, atau ambil halaman tertentu saja.
                                        </div>
                                    </div>

                                    <form action="proses_tools.php" method="POST" enctype="multipart/form-data" target="_blank" class="border p-4 rounded-4 bg-light">
                                        <input type="hidden" name="aksi" value="split">

                                        <div class="mb-4">
                                            <label class="form-label fw-bold">1. Upload File PDF</label>
                                            <input type="file" name="file_pdf" class="form-control" accept=".pdf" required>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-bold">2. Metode Pemisahan</label>
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="tipe_split" id="splitAll" value="all" checked onchange="toggleRange(false)">
                                                <label class="form-check-label" for="splitAll">
                                                    Pisahkan Semua Halaman (Menjadi banyak file)
                                                </label>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="tipe_split" id="splitRange" value="range" onchange="toggleRange(true)">
                                                <label class="form-check-label" for="splitRange">
                                                    Ambil Halaman Tertentu (Extract)
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-4" id="rangeBox" style="display:none;">
                                            <label class="form-label fw-bold text-primary">Masukkan Nomor Halaman</label>
                                            <input type="text" name="range_hal" class="form-control border-primary" placeholder="Contoh: 1,3,5-10">
                                            <div class="form-text">Gunakan koma (,) untuk halaman acak, atau strip (-) untuk rentang.</div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-warning fw-bold py-2 rounded-pill text-dark">
                                                <i class="bi bi-scissors me-2"></i> Proses Split PDF
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let fileStorage = []; 
        const dropArea = document.getElementById("dropArea");
        const fileInput = document.getElementById("fileInput");
        const pdfGrid = document.getElementById("pdfGrid");
        const emptyText = document.getElementById("emptyText");

        // Init Sortable
        new Sortable(pdfGrid, { animation: 150, ghostClass: 'bg-primary-subtle' });

        // Toggle Range Input Split
        function toggleRange(show) {
            document.getElementById('rangeBox').style.display = show ? 'block' : 'none';
        }

        // --- LOGIC DRAG & DROP MERGE ---
        dropArea.addEventListener("dragover", (e) => { e.preventDefault(); dropArea.classList.add("active"); });
        dropArea.addEventListener("dragleave", () => { dropArea.classList.remove("active"); });
        dropArea.addEventListener("drop", (e) => { e.preventDefault(); dropArea.classList.remove("active"); handleFiles(e.dataTransfer.files); });
        fileInput.addEventListener("change", () => { handleFiles(fileInput.files); });

        function handleFiles(files) {
            if (files.length > 0) {
                emptyText.style.display = "none";
                Array.from(files).forEach(file => {
                    if (file.type === "application/pdf") addFileToGrid(file);
                    else alert("File " + file.name + " bukan PDF!");
                });
            }
        }

        function addFileToGrid(file) {
            const id = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            fileStorage.push({ id: id, file: file });
            const div = document.createElement("div");
            div.className = "pdf-card";
            div.setAttribute("data-id", id);
            div.innerHTML = `<button class="btn-remove" onclick="removeFile('${id}')">&times;</button>
                             <div class="pdf-thumb" id="thumb-${id}"><div class="spinner-border spinner-border-sm text-secondary"></div></div>
                             <div class="pdf-info" title="${file.name}">${file.name}</div>`;
            pdfGrid.appendChild(div);
            generateThumbnail(file, id);
        }

        window.removeFile = function(id) {
            document.querySelector(`div[data-id="${id}"]`)?.remove();
            fileStorage = fileStorage.filter(f => f.id !== id);
            if(fileStorage.length === 0) emptyText.style.display = "block";
        }

        window.resetFiles = function() {
            pdfGrid.innerHTML = '<div class="text-muted w-100 text-center py-5" id="emptyText">Belum ada file.</div>';
            emptyText.style.display = "block";
            fileStorage = [];
            fileInput.value = "";
        }

        function generateThumbnail(file, id) {
            const fileReader = new FileReader();
            fileReader.onload = function() {
                const typedarray = new Uint8Array(this.result);
                pdfjsLib.getDocument(typedarray).promise.then(pdf => pdf.getPage(1)).then(page => {
                    const viewport = page.getViewport({ scale: 0.5 });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    page.render({ canvasContext: context, viewport: viewport }).promise.then(() => {
                        document.getElementById(`thumb-${id}`).innerHTML = '';
                        document.getElementById(`thumb-${id}`).appendChild(canvas);
                    });
                }).catch(err => {
                    document.getElementById(`thumb-${id}`).innerHTML = '<small class="text-danger">Error</small>';
                });
            };
            fileReader.readAsArrayBuffer(file);
        }

        window.mergeFiles = function() {
            const gridItems = document.querySelectorAll('#pdfGrid > div[data-id]');
            if(gridItems.length < 2) { alert("Pilih minimal 2 file!"); return; }
            if(gridItems.length > 20 && !confirm("Anda menggabungkan >20 file. Lanjutkan?")) return;

            const btn = document.getElementById('btnMerge');
            const loading = document.getElementById('loadingText');
            btn.disabled = true; loading.style.display = 'block';

            const formData = new FormData();
            formData.append('aksi', 'merge_ajax');
            gridItems.forEach(item => {
                const id = item.getAttribute('data-id');
                const fileObj = fileStorage.find(f => f.id === id);
                if(fileObj) formData.append('files[]', fileObj.file);
            });

            fetch('proses_tools.php', { method: 'POST', body: formData })
            .then(res => { if(!res.ok) throw new Error("Gagal Server"); return res.blob(); })
            .then(blob => {
                if(blob.size < 100) return blob.text().then(t => { throw new Error(t); });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = "Merged_" + Date.now() + ".pdf";
                document.body.appendChild(a); a.click(); a.remove();
                btn.disabled = false; loading.style.display = 'none';
            })
            .catch(err => {
                alert("Error: " + err.message);
                btn.disabled = false; loading.style.display = 'none';
            });
        }
    </script>
</body>
</html>
