<?php 
// File: sarsip/proses_ttd.php
session_start();
include 'config/koneksi.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;

// Pastikan user login
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    header("location:login.php"); exit;
}

if(isset($_POST['proses_ttd'])){
    $id_doc   = $_POST['id_doc'];
    
    // 1. TENTUKAN SIGNER ID
    $id_user  = $_SESSION['id_user'];
    if(isset($_POST['signer_id']) && $_SESSION['role'] == 'admin'){
        $id_user = $_POST['signer_id'];
    }

    // 2. CEK DUPLIKASI (Mencegah Tanda Tangan Ganda)
    $cek_double = mysqli_query($koneksi, "SELECT * FROM doc_signers WHERE id_doc='$id_doc' AND id_user='$id_user'");
    if(mysqli_num_rows($cek_double) > 0){
        echo "<script>alert('GAGAL: Dokumen ini sudah Anda tanda tangani sebelumnya!'); window.location='tanda_tangan.php';</script>"; exit;
    }

    // Ambil Data Dokumen & Posisi TTD
    $qr_list_json = $_POST['qr_list']; 
    $qr_list      = json_decode($qr_list_json, true);
    if(empty($qr_list)) { 
        echo "<script>alert('Error: Data Posisi QR kosong. Silakan atur posisi TTD di halaman sebelumnya.'); window.location='tanda_tangan.php';</script>"; exit; 
    }

    $q = mysqli_query($koneksi, "SELECT * FROM documents WHERE id_doc='$id_doc'");
    $doc = mysqli_fetch_assoc($q);
    
    // ============================================================
    // 3. KEAMANAN AKSES
    // ============================================================
    if($_SESSION['role'] != 'admin' && $doc['kategori'] != 'Arsip Lainnya' && $doc['uploader_id'] != $_SESSION['id_user']){
        // Jika bukan admin, bukan uploader, dan bukan kategori bebas, tolak.
        // (Sesuaikan logika ini dengan kebutuhan instansi Anda)
        // echo "<script>alert('AKSES DITOLAK.'); window.location='data_dokumen.php';</script>"; exit;
    }

    // ============================================================
    // 4. PERSIAPAN FILE
    // ============================================================
    $path_asli   = 'uploads/doc_asli/' . $doc['file_path'];
    $path_signed = 'uploads/doc_signed/SIGNED_' . $doc['file_path'];
    
    // Buat folder jika belum ada
    if (!file_exists('uploads/doc_signed/')) { mkdir('uploads/doc_signed/', 0777, true); }

    // Prioritaskan file yang sudah ada tanda tangan sebelumnya (Multi-sign)
    // PENTING: Cek filesize > 0 untuk menghindari membaca file corrupt/kosong
    if(file_exists($path_signed) && filesize($path_signed) > 0){
        $source_file = $path_signed;
    } else {
        $source_file = $path_asli;
    }
    
    $output_file = $path_signed;

    if(!file_exists($source_file)){ die("Error: File fisik dokumen asli tidak ditemukan di server ($source_file)."); }

    // Generate Token & URL Validasi
    $token_unik = md5(uniqid(rand(), true));
    
    // Deteksi Base URL Otomatis
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $base_url_dinamis = $protocol . "://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
    $url_validasi = $base_url_dinamis . "validasi.php?token=" . $token_unik;
    
    $tempQR = 'temp_qr_' . $id_user . '_' . time() . '.png'; // Nama file temp unik

    // --- SETTING QR CODE ---
    $options = new QROptions([
        'version'      => QRCode::VERSION_AUTO,
        'scale'        => 10,
        'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'     => EccLevel::H, 
        'imageBase64'  => false,
        'imageTransparent' => false, 
    ]);
    
    try {
        // Render QR
        (new QRCode($options))->render($url_validasi, $tempQR);
    } catch (\Exception $e) {
        die("Gagal Membuat QR Code: " . $e->getMessage());
    }

    // ============================================================
    // CUSTOMISASI (WARNA & LOGO) DENGAN GD LIBRARY
    // ============================================================
    
    $im_qr = @imagecreatefrompng($tempQR);
    if($im_qr){
        $qr_width = imagesx($im_qr);
        $qr_height = imagesy($im_qr);

        // Path logo instansi
        $path_logo_instansi = isset($logo_db) ? "assets/" . $logo_db : "";
        
        if(!empty($path_logo_instansi) && file_exists($path_logo_instansi)){
            $ext = pathinfo($path_logo_instansi, PATHINFO_EXTENSION);
            $im_logo = null;

            if(strtolower($ext) == 'png'){
                $im_logo = @imagecreatefrompng($path_logo_instansi);
            } elseif(in_array(strtolower($ext), ['jpg','jpeg'])){
                $im_logo = @imagecreatefromjpeg($path_logo_instansi);
            }

            if($im_logo){
                $logo_w = imagesx($im_logo);
                $logo_h = imagesy($im_logo);
                
                $new_logo_w = $qr_width / 4; 
                $new_logo_h = ($logo_h / $logo_w) * $new_logo_w; 

                $x_pos = ($qr_width - $new_logo_w) / 2;
                $y_pos = ($qr_height - $new_logo_h) / 2;

                imagecopyresampled($im_qr, $im_logo, $x_pos, $y_pos, 0, 0, $new_logo_w, $new_logo_h, $logo_w, $logo_h);
                imagedestroy($im_logo);
            }
        }
        imagepng($im_qr, $tempQR);
        imagedestroy($im_qr);
    }

    // ============================================================
    // PROSES FPDI (PDF PARSING) DENGAN ERROR HANDLING
    // ============================================================

    $pdf = new Fpdi();
    
    try {
        // Coba baca file PDF sumber
        // INI ADALAH TITIK RAWAN ERROR "CrossReferenceException"
        $pageCount = $pdf->setSourceFile($source_file);

    } catch (\Exception $e) {
        // JIKA GAGAL BACA PDF
        if(file_exists($tempQR)) unlink($tempQR); // Hapus QR sampah
        
        // Hapus file signed yang mungkin corrupt jika itu sumber masalahnya
        // (Opsional, hati-hati jika ingin menghapus)
        // if($source_file == $path_signed) unlink($path_signed);

        $pesan_error = "Gagal memproses file PDF. Kemungkinan file rusak atau versinya tidak kompatibel (PDF 1.7+ Compressed). Solusi: Coba 'Print to PDF' dokumen Anda lalu upload ulang. Detail Error: " . $e->getMessage();
        
        echo "<script>alert('".addslashes($pesan_error)."'); window.location='tanda_tangan.php';</script>";
        exit;
    }

    // Jika berhasil baca, lanjut proses
    for ($i = 1; $i <= $pageCount; $i++) {
        try {
            $tplIdx = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplIdx);
            
            // Tambahkan halaman sesuai orientasi asli (Portrait/Landscape)
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, array($size['width'], $size['height']));
            $pdf->useTemplate($tplIdx);

            foreach($qr_list as $qr) {
                if ($qr['halaman'] == $i) {
                    $final_X = $qr['x_pct'] * $size['width'];
                    $final_Y = $qr['y_pct'] * $size['height'];
                    $final_W = isset($qr['w_pct']) ? ($qr['w_pct'] * $size['width']) : 25;
                    $final_H = $final_W; // Square

                    $pdf->Image($tempQR, $final_X, $final_Y, $final_W, $final_H);

                    // Simpan koordinat untuk database (hanya QR pertama yang dicatat posisinya sebagai referensi)
                    if(!isset($db_posX)){
                        $db_posX = $final_X; $db_posY = $final_Y; $db_hal = $i;
                    }
                }
            }
        } catch (\Exception $e) {
            // Error saat import halaman tertentu
            if(file_exists($tempQR)) unlink($tempQR);
            echo "<script>alert('Gagal mengimpor halaman PDF ke-$i. File mungkin diproteksi.'); window.location='tanda_tangan.php';</script>";
            exit;
        }
    }

    // Simpan File Akhir
    try {
        $pdf->Output($output_file, 'F'); 
        @unlink($tempQR); // Hapus QR sementara

        // Update Database
        if(file_exists($output_file)){
            $hash_baru = hash_file('sha256', $output_file);
            mysqli_query($koneksi, "UPDATE documents SET signed_file_hash = '$hash_baru' WHERE id_doc = '$id_doc'");
        
            $pos_x_db = isset($db_posX) ? $db_posX : 0;
            $pos_y_db = isset($db_posY) ? $db_posY : 0;
            $hal_db   = isset($db_hal)  ? $db_hal  : 1;

            $query_sign = "INSERT INTO doc_signers (id_doc, id_user, status, qr_token, posisi_x, posisi_y, halaman, signed_at)
                           VALUES ('$id_doc', '$id_user', 'signed', '$token_unik', '$pos_x_db', '$pos_y_db', '$hal_db', NOW())";
            
            if(mysqli_query($koneksi, $query_sign)){
                header("location:tanda_tangan.php?pesan=sukses");
            } else { 
                throw new Exception("Gagal menyimpan data ke database: " . mysqli_error($koneksi)); 
            }
        } else {
            throw new Exception("Gagal menyimpan file output ke server.");
        }

    } catch (\Exception $e) {
        @unlink($tempQR); 
        echo "<script>alert('TERJADI ERROR: ".addslashes($e->getMessage())."'); window.location='tanda_tangan.php';</script>";
        exit;
    }
} else {
    header("location:tanda_tangan.php");
}
?>
