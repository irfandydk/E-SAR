<?php 
session_start();
include 'config/koneksi.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;

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
        echo "<script>alert('GAGAL: Dokumen ini sudah Anda tanda tangani sebelumnya!'); window.location='data_dokumen.php';</script>"; exit;
    }

    // Ambil Data Dokumen & Posisi TTD
    $qr_list_json = $_POST['qr_list']; 
    $qr_list      = json_decode($qr_list_json, true);
    if(empty($qr_list)) { die("Error: Data Posisi QR kosong. Silakan atur posisi TTD di halaman sebelumnya."); }

    $q = mysqli_query($koneksi, "SELECT * FROM documents WHERE id_doc='$id_doc'");
    $doc = mysqli_fetch_assoc($q);
    
    // ============================================================
    // 3. KEAMANAN AKSES (LOGIKA BARU)
    // ============================================================
    // Aturan: Admin boleh semua. User Biasa HANYA boleh 'Arsip Lainnya'.
    if($_SESSION['role'] != 'admin' && $doc['kategori'] != 'Arsip Lainnya'){
        echo "<script>alert('AKSES DITOLAK: User biasa hanya diizinkan menandatangani dokumen kategori Arsip Lainnya.'); window.location='data_dokumen.php';</script>"; 
        exit;
    }
    // (Verifikasi Password dihapus sesuai permintaan agar tidak error)

    // ============================================================
    // 4. PERSIAPAN FILE
    // ============================================================
    $path_asli   = 'uploads/doc_asli/' . $doc['file_path'];
    $path_signed = 'uploads/doc_signed/SIGNED_' . $doc['file_path'];
    
    // Buat folder jika belum ada
    if (!file_exists('uploads/doc_signed/')) { mkdir('uploads/doc_signed/', 0777, true); }

    $source_file = file_exists($path_signed) ? $path_signed : $path_asli;
    $output_file = $path_signed;

    if(!file_exists($source_file)){ die("Error: File fisik dokumen asli tidak ditemukan di server."); }

    // Generate Token & URL Validasi
    $token_unik = md5(uniqid(rand(), true));
    
    // Deteksi Base URL Otomatis
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $base_url_dinamis = $protocol . "://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
    $url_validasi = $base_url_dinamis . "validasi.php?token=" . $token_unik;
    
    $tempQR = 'temp_qr_' . $id_user . '.png';

    // ... (LANJUTKAN DENGAN KODE QR GENERATOR LAMA ANDA DI BAWAH INI) ...

    // --- (LANJUTKAN KE KODE 'SETTING QR CODE' ANDA DI BAWAH INI) ---
    // ... Biarkan kode Generate QR & FPDI Anda tetap sama di bawah ini ...

   // --- SETTING QR CODE ---
    $options = new QROptions([
        'version'      => QRCode::VERSION_AUTO, // GANTI DARI 5 MENJADI AUTO
        'scale'        => 10,
        'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'     => EccLevel::H, // Tetap High agar logo aman
        'imageBase64'  => false,
        'imageTransparent' => false, 
    ]);
    
    // Render QR Polos dulu
    (new QRCode($options))->render($url_validasi, $tempQR);


    // ============================================================
    // MULAI CUSTOMISASI (WARNA & LOGO) DENGAN GD LIBRARY
    // ============================================================
    
    // 1. Load QR yang barusan dibuat
    $im_qr = imagecreatefrompng($tempQR);
    $qr_width = imagesx($im_qr);
    $qr_height = imagesy($im_qr);

    // 2. UBAH WARNA (Opsional: Misal jadi Biru Tua / Orange Gelap)
    // QR Code harus kontras tinggi. Jangan pakai warna muda (kuning/hijau muda).
    // Kita pakai Biru Tua Gelap agar profesional: RGB(0, 51, 102)
    // Atau Orange Gelap sesuai tema: RGB(200, 80, 0)
    
    // Aktifkan filter warna jika diinginkan (Uncomment baris di bawah ini)
    // imagefilter($im_qr, IMG_FILTER_COLORIZE, 0, 0, 0); // Hitam (Default)
    // imagefilter($im_qr, IMG_FILTER_COLORIZE, -50, -100, -200); // Nuansa Orange/Coklat

    // 3. SISIPKAN LOGO INSTANSI DI TENGAH
    // Ambil path logo dari database (lewat config/koneksi.php yang sudah include)
    // Pastikan variabel $logo_db tersedia dari include koneksi.php
    $path_logo_instansi = "assets/" . $logo_db;
    
    if(file_exists($path_logo_instansi)){
        $ext = pathinfo($path_logo_instansi, PATHINFO_EXTENSION);
        
        // Load Logo sesuai format
        if(strtolower($ext) == 'png'){
            $im_logo = imagecreatefrompng($path_logo_instansi);
        } elseif(in_array(strtolower($ext), ['jpg','jpeg'])){
            $im_logo = imagecreatefromjpeg($path_logo_instansi);
        }

        if(isset($im_logo)){
            // Hitung ukuran logo (maksimal 20% dari luas QR agar tetap terbaca)
            $logo_w = imagesx($im_logo);
            $logo_h = imagesy($im_logo);
            
            $new_logo_w = $qr_width / 4; // Ukuran logo 1/4 dari lebar QR
            $new_logo_h = ($logo_h / $logo_w) * $new_logo_w; // Jaga aspek rasio

            // Hitung posisi tengah
            $x_pos = ($qr_width - $new_logo_w) / 2;
            $y_pos = ($qr_height - $new_logo_h) / 2;

            // Tempel Logo ke QR (Logo, Tujuan, dst...)
            // Kita gunakan imagecopyresampled agar hasil resize bagus
            imagecopyresampled($im_qr, $im_logo, $x_pos, $y_pos, 0, 0, $new_logo_w, $new_logo_h, $logo_w, $logo_h);
        }
    }

    // Simpan hasil Custom QR menimpa file temp lama
    imagepng($im_qr, $tempQR);
    imagedestroy($im_qr);
    if(isset($im_logo)) imagedestroy($im_logo);

    // ============================================================
    // SELESAI CUSTOMISASI - LANJUT TEMPEL KE PDF
    // ============================================================

    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($source_file);
    $db_posX = 0; $db_posY = 0; $db_hal = 1;

    for ($i = 1; $i <= $pageCount; $i++) {
        $tplIdx = $pdf->importPage($i);
        $size = $pdf->getTemplateSize($tplIdx);
        $w_page = $size['width'];
        $h_page = $size['height'];
        
        $pdf->AddPage($size['orientation'], array($w_page, $h_page));
        $pdf->useTemplate($tplIdx);

        foreach($qr_list as $qr) {
            if ($qr['halaman'] == $i) {
                $final_X = $qr['x_pct'] * $w_page;
                $final_Y = $qr['y_pct'] * $h_page;
                $final_W = isset($qr['w_pct']) ? ($qr['w_pct'] * $w_page) : 25;
                $final_H = $final_W;

                // Tempel Custom QR
                $pdf->Image($tempQR, $final_X, $final_Y, $final_W, $final_H);

                if($db_posX == 0){
                    $db_posX = $final_X; $db_posY = $final_Y; $db_hal = $i;
                }
            }
        }
    }

    // Simpan PDF dengan Try-Catch
    try {
        if (file_exists($output_file)) {
            $fp = @fopen($output_file, 'r+');
            if ($fp === false) { throw new Exception("File Locked"); }
            fclose($fp);
        }
        $pdf->Output($output_file, 'F'); 
        @unlink($tempQR);

        if(file_exists($output_file)){
            $hash_baru = hash_file('sha256', $output_file);
            mysqli_query($koneksi, "UPDATE documents SET signed_file_hash = '$hash_baru' WHERE id_doc = '$id_doc'");
        }

        $pos_x_db = isset($db_posX) ? $db_posX : 0;
        $pos_y_db = isset($db_posY) ? $db_posY : 0;
        $hal_db   = isset($db_hal)  ? $db_hal  : 1;

        $query_sign = "INSERT INTO doc_signers (id_doc, id_user, status, qr_token, posisi_x, posisi_y, halaman, signed_at)
                       VALUES ('$id_doc', '$id_user', 'signed', '$token_unik', '$pos_x_db', '$pos_y_db', '$hal_db', NOW())";
        
        if(mysqli_query($koneksi, $query_sign)){
            header("location:tanda_tangan.php?pesan=sukses");
        } else { throw new Exception("DB Error"); }

    } catch (Exception $e) {
        @unlink($tempQR); 
        header("location:tanda_tangan.php?pesan=file_terbuka");
        exit;
    }
}
?>