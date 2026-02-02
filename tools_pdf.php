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
    <script>
        // Set Worker ke File Lokal
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/js/pdf.worker.min.js';
    </script>
    
    <script src="assets/js/Sortable.min.js"></script>

    <script>
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <style>
        .main-content { margin-left: 280px; padding: 30px; transition: 0.3s; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 80px; } }
        
        /* Style untuk Kartu Drag & Drop */
        .pdf-card {
            cursor: grab; transition: transform 0.2s, box-shadow 0.2s; position: relative;
            background: var(--bs-body-bg); border: 1px solid var(--bs-border-color);
        }
        .pdf-card:active { cursor: grabbing; transform: scale(1.02); }
        .pdf-thumbnail {
            width: 100%; height: 180px; object-fit: contain; background-color: #525659;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .btn-remove-card {
            position: absolute; top: 5px; right: 5px; z-index: 10;
            background: rgba(220, 53, 69, 0.9); color: white; border: none;
            width: 25px; height: 25px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-size: 12px;
        }
        .btn-remove-card:hover { background: #dc3545; }
        .ghost-class { opacity: 0.5; background: #c8ebfb; border: 2px dashed #0d6efd; }
    </style>
</head>
<body>

<?php include 'sidebar_menu.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        
        <h4 class="fw-bold mb-4"><i class="bi bi-tools me-2 text-primary"></i>PDF Tools Internal</h4>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <ul class="nav nav-pills nav-fill gap-2 p-1 bg-body-secondary rounded-pill" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill fw-bold" id="pills-merge-tab" data-bs-toggle="pill" data-bs-target="#pills-merge">
                            <i class="bi bi-files me-2"></i> Gabung PDF (Merge)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill fw-bold" id="pills-split-tab" data-bs-toggle="pill" data-bs-target="#pills-split">
                            <i class="bi bi-scissors me-2"></i> Pisah PDF (Split)
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content" id="pills-tabContent">
                    
                    <div class="tab-pane fade show active" id="pills-merge">
                        
                        <div class="text-center mb-4">
                            <input type="file" id="pdfInput" class="d-none" accept=".pdf" multiple onchange="handleFiles(this.files)">
                            
                            <button type="button" class="btn btn-outline-primary btn-lg rounded-pill px-5 border-2 fw-bold" onclick="document.getElementById('pdfInput').click()">
                                <i class="bi bi-plus-circle me-2"></i> Pilih File PDF
                            </button>
                            <div class="form-text mt-2">Klik tombol untuk menambah file. Geser kartu (drag) untuk mengubah urutan.</div>
                        </div>

                        <div id="pdfGrid" class="row g-3 mb-4">
                            <div class="col-12 text-center py-5" id="emptyState">
                                <i class="bi bi-grid-3x3-gap fs-1 text-muted opacity-25"></i>
                                <p class="text-muted mt-2">Belum ada file dipilih.</p>
                            </div>
                        </div>

                        <div class="d-grid justify-content-center" id="actionArea" style="display:none;">
                            <button type="button" onclick="processMerge()" id="btnMerge" class="btn btn-primary btn-lg rounded-pill shadow-sm px-5">
                                <i class="bi bi-download me-2"></i> Gabung & Download PDF
                            </button>
                            <div id="loadingText" class="text-center mt-2 text-primary" style="display:none;">
                                <div class="spinner-border spinner-border-sm me-2"></div> Sedang memproses & menggabungkan...
                            </div>
                        </div>

                    </div>

                    <div class="tab-pane fade" id="pills-split">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <form action="proses_tools.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="aksi" value="split">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">1. Upload File PDF</label>
                                        <input type="file" name="file_source" class="form-control" accept=".pdf" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">2. Halaman yang Diambil</label>
                                        <input type="text" name="range" class="form-control" placeholder="Contoh: 1-5, 8, 10" required>
                                        <div class="form-text">Contoh: <b>1-3</b> (Ambil hal 1 s.d 3), atau <b>1,5</b> (Ambil hal 1 dan 5 saja).</div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg rounded-pill">
                                            <i class="bi bi-scissors me-2"></i> Potong PDF
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

    // Init Sortable (Library Lokal)
    var el = document.getElementById('pdfGrid');
    var sortable = Sortable.create(el, {
        animation: 150,
        ghostClass: 'ghost-class'
    });

    // 1. HANDLE FILES
    function handleFiles(files) {
        const emptyState = document.getElementById('emptyState');
        const actionArea = document.getElementById('actionArea');
        const grid = document.getElementById('pdfGrid');

        if(files.length > 0) {
            emptyState.style.display = 'none';
            actionArea.style.display = 'grid';
        }

        Array.from(files).forEach(file => {
            if(file.type !== 'application/pdf') {
                alert("File " + file.name + " bukan PDF."); return;
            }

            const uniqueId = 'pdf_' + Math.random().toString(36).substr(2, 9);
            fileStorage.push({ id: uniqueId, file: file });

            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3';
            col.setAttribute('data-id', uniqueId);
            col.innerHTML = `
                <div class="card pdf-card h-100 overflow-hidden shadow-sm">
                    <button class="btn-remove-card" onclick="removeCard('${uniqueId}')" title="Hapus">&times;</button>
                    <div class="pdf-thumbnail d-flex align-items-center justify-content-center text-white">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                    <div class="card-body p-2 text-center bg-light">
                        <small class="fw-bold d-block text-truncate" title="${file.name}">${file.name}</small>
                        <span class="badge bg-secondary" style="font-size: 0.65rem;">${(file.size/1024/1024).toFixed(2)} MB</span>
                    </div>
                </div>
            `;
            grid.appendChild(col);
            generateThumbnail(file, col.querySelector('.pdf-thumbnail'));
        });
        document.getElementById('pdfInput').value = '';
    }

    // 2. GENERATE THUMBNAIL (PDF.js Lokal)
    function generateThumbnail(file, container) {
        const fileReader = new FileReader();
        fileReader.onload = function() {
            const typedarray = new Uint8Array(this.result);

            pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                pdf.getPage(1).then(function(page) {
                    const viewport = page.getViewport({ scale: 0.5 });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                    canvas.style.objectFit = 'contain';

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    
                    page.render(renderContext).promise.then(function() {
                        container.innerHTML = '';
                        container.appendChild(canvas);
                    });
                });
            }).catch(err => {
                container.innerHTML = '<span class="small text-danger">Error Preview</span>';
            });
        };
        fileReader.readAsArrayBuffer(file);
    }

    // 3. HAPUS KARTU
    function removeCard(id) {
        const element = document.querySelector(`[data-id="${id}"]`);
        if(element) element.remove();
        fileStorage = fileStorage.filter(item => item.id !== id);
        if(fileStorage.length === 0) {
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('actionArea').style.display = 'none';
        }
    }

    // 4. PROSES MERGE
    function processMerge() {
        if(fileStorage.length < 2) {
            alert("Pilih minimal 2 file!"); return;
        }

        const btn = document.getElementById('btnMerge');
        const loading = document.getElementById('loadingText');
        btn.disabled = true;
        loading.style.display = 'block';

        const formData = new FormData();
        formData.append('aksi', 'merge_ajax');

        const gridItems = document.querySelectorAll('#pdfGrid > div[data-id]');
        gridItems.forEach(item => {
            const id = item.getAttribute('data-id');
            const fileObj = fileStorage.find(f => f.id === id);
            if(fileObj) formData.append('files[]', fileObj.file);
        });

        fetch('proses_tools.php', { method: 'POST', body: formData })
        .then(response => {
            if(response.ok) return response.blob();
            throw new Error('Gagal memproses');
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = "Merged_" + new Date().getTime() + ".pdf";
            document.body.appendChild(a);
            a.click();
            a.remove();
            
            btn.disabled = false;
            loading.style.display = 'none';
        })
        .catch(err => {
            alert("Error: " + err.message);
            btn.disabled = false;
            loading.style.display = 'none';
        });
    }
</script>

</body>
</html>