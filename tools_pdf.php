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
        // Pastikan path ini sesuai dengan struktur folder Anda
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
        
        .pdf-card { 
            position: relative; 
            width: 150px; 
            margin: 10px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            overflow: hidden; 
            background: #fff;
            cursor: move;
            transition: transform 0.2s;
        }
        .pdf-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .pdf-thumb { height: 180px; width: 100%; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .pdf-thumb canvas { width: 100%; height: auto; object-fit: contain; }
        .pdf-info { padding: 8px; font-size: 12px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: #fff; border-top: 1px solid #eee; }
        .btn-remove { 
            position: absolute; top: 5px; right: 5px; 
            background: rgba(220, 53, 69, 0.9); color: white; 
            border: none; border-radius: 50%; width: 24px; height: 24px; 
            font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .drag-area { 
            border: 2px dashed #0d6efd; 
            border-radius: 10px; 
            background-color: rgba(13, 110, 253, 0.05); 
            transition: all 0.3s;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .drag-area.active { background-color: rgba(13, 110, 253, 0.15); border-color: #0a58ca; }
        
        /* Grid Container untuk Sortable */
        #pdfGrid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            min-height: 100px;
            padding: 10px;
            align-items: flex-start;
        }
    </style>
</head>
<body>

    <?php include 'sidebar_menu.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h4 class="fw-bold mb-4"><i class="bi bi-file-earmark-pdf-fill me-2 text-primary"></i>Gabungkan PDF (Merge)</h4>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                        <div>
                            <strong>Cara Penggunaan:</strong>
                            <ul class="mb-0 ps-3">
                                <li>Upload file PDF yang ingin digabungkan.</li>
                                <li>Geser (Drag & Drop) kotak file untuk mengatur urutan halaman.</li>
                                <li>Maksimal upload <strong>20 File</strong> sekaligus (Keterbatasan Server).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="drag-area p-5 text-center mb-4" id="dropArea">
                        <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 50px;"></i>
                        <h5 class="mt-3 fw-bold">Drag & Drop File PDF Di Sini</h5>
                        <p class="text-muted">atau</p>
                        <button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="document.getElementById('fileInput').click()">Pilih File</button>
                        <input type="file" id="fileInput" multiple accept=".pdf" hidden>
                    </div>

                    <div id="pdfGrid" class="border rounded bg-light mb-4">
                        <div class="text-muted w-100 text-center py-5" id="emptyText">Belum ada file yang dipilih.</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button class="btn btn-outline-danger px-4 rounded-pill" onclick="resetFiles()">
                            <i class="bi bi-trash me-1"></i> Reset
                        </button>
                        <button id="btnMerge" class="btn btn-success px-4 rounded-pill fw-bold" onclick="mergeFiles()">
                            <i class="bi bi-files me-1"></i> Gabungkan PDF
                        </button>
                    </div>

                    <div id="loadingText" class="text-center mt-3" style="display:none;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 fw-bold text-primary">Sedang memproses, mohon tunggu...</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Penyimpanan File Object Global
        let fileStorage = []; 
        const dropArea = document.getElementById("dropArea");
        const fileInput = document.getElementById("fileInput");
        const pdfGrid = document.getElementById("pdfGrid");
        const emptyText = document.getElementById("emptyText");

        // Inisialisasi SortableJS
        new Sortable(pdfGrid, {
            animation: 150,
            ghostClass: 'bg-primary-subtle'
        });

        // Event Listeners Drag & Drop
        dropArea.addEventListener("dragover", (event) => {
            event.preventDefault();
            dropArea.classList.add("active");
        });

        dropArea.addEventListener("dragleave", () => {
            dropArea.classList.remove("active");
        });

        dropArea.addEventListener("drop", (event) => {
            event.preventDefault();
            dropArea.classList.remove("active");
            handleFiles(event.dataTransfer.files);
        });

        fileInput.addEventListener("change", () => {
            handleFiles(fileInput.files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                emptyText.style.display = "none";
                Array.from(files).forEach(file => {
                    if (file.type === "application/pdf") {
                        addFileToGrid(file);
                    } else {
                        alert("File " + file.name + " bukan PDF!");
                    }
                });
            }
        }

        // Fungsi Menambah File ke Grid & Storage
        function addFileToGrid(file) {
            // Buat ID Unik
            const id = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            
            // Simpan ke array global
            fileStorage.push({ id: id, file: file });

            // Buat Elemen HTML
            const div = document.createElement("div");
            div.className = "pdf-card";
            div.setAttribute("data-id", id);
            div.innerHTML = `
                <button class="btn-remove" onclick="removeFile('${id}')">&times;</button>
                <div class="pdf-thumb" id="thumb-${id}">
                    <div class="spinner-border spinner-border-sm text-secondary"></div>
                </div>
                <div class="pdf-info" title="${file.name}">${file.name}</div>
            `;
            pdfGrid.appendChild(div);

            // Generate Thumbnail
            generateThumbnail(file, id);
        }

        // Fungsi Hapus File
        window.removeFile = function(id) {
            // Hapus dari DOM
            const el = document.querySelector(`div[data-id="${id}"]`);
            if(el) el.remove();

            // Hapus dari Array Storage
            fileStorage = fileStorage.filter(f => f.id !== id);

            // Cek jika kosong
            if(fileStorage.length === 0) emptyText.style.display = "block";
        }

        // Fungsi Reset Semua
        window.resetFiles = function() {
            pdfGrid.innerHTML = '<div class="text-muted w-100 text-center py-5" id="emptyText">Belum ada file yang dipilih.</div>';
            emptyText.style.display = "block";
            fileStorage = [];
            fileInput.value = "";
        }

        // Generate Thumbnail PDF dengan PDF.js
        function generateThumbnail(file, id) {
            const fileReader = new FileReader();
            fileReader.onload = function() {
                const typedarray = new Uint8Array(this.result);
                
                pdfjsLib.getDocument(typedarray).promise.then(pdf => {
                    return pdf.getPage(1); // Ambil halaman 1
                }).then(page => {
                    const scale = 0.5;
                    const viewport = page.getViewport({ scale: scale });
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    
                    page.render(renderContext).promise.then(() => {
                        const thumbContainer = document.getElementById(`thumb-${id}`);
                        if(thumbContainer) {
                            thumbContainer.innerHTML = '';
                            thumbContainer.appendChild(canvas);
                        }
                    });
                }).catch(err => {
                    console.error("Error render PDF:", err);
                    const thumbContainer = document.getElementById(`thumb-${id}`);
                    if(thumbContainer) thumbContainer.innerHTML = '<small class="text-danger">Error Preview</small>';
                });
            };
            fileReader.readAsArrayBuffer(file);
        }

        // --- PROSES MERGE (DIPERBAIKI) ---
        window.mergeFiles = function() {
            // 1. Validasi Jumlah File
            const gridItems = document.querySelectorAll('#pdfGrid > div[data-id]');
            if(gridItems.length < 2) {
                alert("Pilih minimal 2 file PDF untuk digabungkan!");
                return;
            }

            // 2. Peringatan Batas Server (Standard PHP limit is 20)
            if(gridItems.length > 20) {
                if(!confirm("Peringatan: Anda mencoba menggabungkan " + gridItems.length + " file.\nBatas upload server biasanya 20 file. File berlebih mungkin akan hilang.\n\nLanjutkan?")) {
                    return;
                }
            }

            const btn = document.getElementById('btnMerge');
            const loading = document.getElementById('loadingText');
            btn.disabled = true;
            loading.style.display = 'block';

            const formData = new FormData();
            formData.append('aksi', 'merge_ajax');

            // 3. Loop berdasarkan urutan visual di GRID (DOM)
            // Ini penting agar urutan file sesuai dengan yang disusun user
            let fileCount = 0;
            gridItems.forEach(item => {
                const id = item.getAttribute('data-id');
                // Cari file asli di array penyimpanan
                const fileObj = fileStorage.find(f => f.id === id);
                
                if(fileObj && fileObj.file) {
                    formData.append('files[]', fileObj.file);
                    fileCount++;
                }
            });

            console.log("Mengirim " + fileCount + " file ke server...");

            // 4. Kirim ke Server
            fetch('proses_tools.php', { method: 'POST', body: formData })
            .then(response => {
                if(!response.ok) {
                    throw new Error("Gagal terhubung ke server (Code: " + response.status + "). Mungkin ukuran file terlalu besar.");
                }
                return response.blob();
            })
            .then(blob => {
                // Cek jika blob terlalu kecil (kemungkinan pesan error teks, bukan PDF)
                if(blob.size < 100) {
                     // Opsional: Baca blob sebagai text untuk melihat error PHP jika ada
                     return blob.text().then(text => { throw new Error("Respon Server: " + text); });
                }

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
                alert("Terjadi Kesalahan:\n" + err.message + "\n\nTips: Coba kurangi jumlah file atau ukuran file.");
                btn.disabled = false;
                loading.style.display = 'none';
                console.error(err);
            });
        }
    </script>
</body>
</html>
