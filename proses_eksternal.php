<?php 
// File: sarsip/proses_eksternal.php
session_start();
include 'config/koneksi.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

if(isset($_POST['upload_eksternal'])){
    $judul    = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $uploader = $_SESSION['id_user'];
    
    if(empty($_FILES['file_eksternal']['name'])){
        echo "<script>alert('Pilih file PDF terlebih dahulu!'); window.location='surat_arsip.php?tab=eksternal';</script>"; exit;
    }

    $filename = $_FILES['file_eksternal']['name'];
    $tmp_name = $_FILES['file_eksternal']['tmp_name'];
    $ext      = pathinfo($filename, PATHINFO_EXTENSION);

    if(strtolower($ext) != 'pdf'){
        echo "<script>alert('Hanya format PDF yang diperbolehkan!'); window.location='surat_arsip.php?tab=eksternal';</script>"; exit;
    }

    $target_dir = "uploads/arsip_lain/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $new_filename = "EXT_" . date('Ymd_His') . "_" . rand(100,999) . ".pdf";
    $path_temp    = $target_dir . "TEMP_" . $new_filename;
    $path_final   = $target_dir . $new_filename;

    if(move_uploaded_file($tmp_name, $path_temp)){

        $token_unik = "EXT-" . md5(uniqid(rand(), true)); 
        
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($path_temp);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplIdx);
                
                // Set ukuran halaman output SAMA dengan input
                $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));
                $pdf->useTemplate($tplIdx);

                // --- STAMPING LOGIC (LAST PAGE ONLY) ---
                if ($i == $pageCount) {
                    
                    // 1. Matikan AutoPageBreak agar tidak membuat halaman kosong baru
                    $pdf->SetAutoPageBreak(false);

                    // 2. Styling Text
                    $pdf->SetFont('Courier', 'B', 10);
                    $pdf->SetTextColor(80, 80, 80);

                    // 3. Posisi Dinamis (Tinggi Halaman - 15mm)
                    $posisi_X = 10; 
                    $posisi_Y = $size['height'] - 15;

                    $pdf->SetXY($posisi_X, $posisi_Y);
                    
                    // 4. Tulis
                    $text_stamp = "TOKEN: " . strtoupper($token_unik);
                    $pdf->Cell(0, 0, $text_stamp, 0, 0, 'L');
                    
                    $pdf->SetAutoPageBreak(true, 10);
                }
            }

            $pdf->Output($path_final, 'F');
            $file_hash = hash_file('sha256', $path_final);

            @unlink($path_temp); 

            $query = "INSERT INTO external_archives (judul, file_path, file_hash, qr_token, uploaded_by)
                      VALUES ('$judul', '$new_filename', '$file_hash', '$token_unik', '$uploader')";
            
            if(mysqli_query($koneksi, $query)){
                header("location:surat_arsip.php?pesan=sukses&tab=eksternal");
            } else {
                die("Error Database: " . mysqli_error($koneksi));
            }

        } catch (Exception $e) {
            die("Error PDF Processing: " . $e->getMessage());
        }

    } else {
        echo "<script>alert('Gagal mengupload file ke server.'); window.location='surat_arsip.php?tab=eksternal';</script>";
    }
}
?>