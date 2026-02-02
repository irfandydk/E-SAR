<?php
// File: sarsip/proses_tools.php
session_start();
include 'config/koneksi.php';
require 'vendor/autoload.php'; // Pastikan library PDF terload

use setasign\Fpdi\Fpdi;

// Matikan error reporting agar tidak merusak output PDF (Blob)
error_reporting(0);
ini_set('display_errors', 0);

// Cek Login
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ exit; }

// Tangkap Aksi
$aksi = isset($_REQUEST['aksi']) ? $_REQUEST['aksi'] : '';

// =======================================================================
// 1. FITUR MERGE (GABUNG PDF) - AJAX
// =======================================================================
if($aksi == 'merge_ajax') {
    
    if(empty($_FILES['files']['name'][0])) {
        http_response_code(400);
        echo "Tidak ada file yang diupload.";
        exit;
    }

    $pdf = new Fpdi();
    $files = $_FILES['files'];
    $count = count($files['name']);

    try {
        // Loop setiap file yang diupload
        for($i = 0; $i < $count; $i++) {
            $tmp = $files['tmp_name'][$i];
            
            if(file_exists($tmp)) {
                // Hitung jumlah halaman file ini
                $pageCount = $pdf->setSourceFile($tmp);
                
                // Import setiap halaman ke PDF Utama
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    
                    // Ambil ukuran halaman asli
                    $size = $pdf->getTemplateSize($templateId);
                    
                    // Tambah halaman baru sesuai ukuran asli (P/L)
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }
        }

        // Output ke Browser sebagai File Download
        $pdf->Output('D', 'Merged_Document.pdf');

    } catch (Exception $e) {
        http_response_code(500);
        echo "Error PDF Processing: " . $e->getMessage();
    }
}

// =======================================================================
// 2. FITUR SPLIT (PISAH PDF) - FORM POST
// =======================================================================
elseif($aksi == 'split') {
    
    if(empty($_FILES['file_pdf']['name'])){
        echo "<script>alert('File PDF belum dipilih!'); window.close();</script>"; exit;
    }

    $tmp_file   = $_FILES['file_pdf']['tmp_name'];
    $tipe_split = $_POST['tipe_split']; // 'all' atau 'range'
    $range_hal  = $_POST['range_hal'];  // Contoh: "1,3,5-7"

    // Simpan sementara file upload agar bisa dibaca berulang
    $temp_path = "uploads/temp_split_" . rand() . ".pdf";
    move_uploaded_file($tmp_file, $temp_path);

    try {
        $sourcePdf = new Fpdi();
        $totalPage = $sourcePdf->setSourceFile($temp_path);

        // A. JIKA SPLIT ALL (PISAH SEMUA HALAMAN JADI ZIP)
        if($tipe_split == 'all'){
            
            $zip = new ZipArchive();
            $zipName = "Split_Result_" . date('YmdHis') . ".zip";
            $zipPath = "uploads/" . $zipName;

            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                die("Gagal membuat ZIP");
            }

            for ($i = 1; $i <= $totalPage; $i++) {
                $newPdf = new Fpdi();
                $newPdf->setSourceFile($temp_path);
                $tpl = $newPdf->importPage($i);
                
                $size = $newPdf->getTemplateSize($tpl);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                
                $newPdf->AddPage($orientation, [$size['width'], $size['height']]);
                $newPdf->useTemplate($tpl);
                
                // Masukkan PDF per halaman ke dalam ZIP
                $pdfContent = $newPdf->Output('S'); // S = String
                $zip->addFromString("Halaman_$i.pdf", $pdfContent);
            }
            
            $zip->close();
            
            // Download ZIP
            if(file_exists($zipPath)){
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.$zipName.'"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);
                unlink($zipPath); // Hapus ZIP
                unlink($temp_path); // Hapus file temp
                exit;
            }

        } 
        
        // B. JIKA SPLIT RANGE (AMBIL HALAMAN TERTENTU JADI 1 FILE)
        else {
            
            $pagesToExtract = [];
            
            // Parsing Range (Contoh: "1,3, 5-7")
            $parts = explode(',', $range_hal);
            foreach($parts as $part){
                $part = trim($part);
                if(strpos($part, '-') !== false){
                    // Range (e.g., 5-7)
                    list($start, $end) = explode('-', $part);
                    for($i=$start; $i<=$end; $i++) $pagesToExtract[] = (int)$i;
                } else {
                    // Single (e.g., 1)
                    $pagesToExtract[] = (int)$part;
                }
            }

            // Buat PDF Baru
            $newPdf = new Fpdi();
            $newPdf->setSourceFile($temp_path);

            foreach($pagesToExtract as $p){
                if($p > 0 && $p <= $totalPage){
                    $tpl = $newPdf->importPage($p);
                    $size = $newPdf->getTemplateSize($tpl);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                    $newPdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $newPdf->useTemplate($tpl);
                }
            }

            unlink($temp_path); // Hapus file temp
            $newPdf->Output('D', 'Extracted_Pages.pdf');
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        if(file_exists($temp_path)) unlink($temp_path);
    }
}
?>
