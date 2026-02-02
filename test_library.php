<?php
// Panggil file autoload dari Composer
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// 1. KONFIGURASI & GENERATE QR CODE
$text = "Halo SARSIP - Tes Library Berhasil";

// Kita simpan file sementara bernama 'temp_qr.png' di folder yang sama
$tempQR = 'temp_qr.png';

$options = new QROptions([
    'version'    => 5,
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'   => QRCode::ECC_L,
    'scale'      => 5,
    'imageBase64'=> false, // Penting: Matikan base64 agar jadi binary data
]);

// Render dan simpan langsung ke file
(new QRCode($options))->render($text, $tempQR);


// 2. BUAT PDF BARU
$pdf = new Fpdi();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Tulis Judul
$pdf->Cell(0, 10, 'Tes Integrasi Library SARSIP', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Jika Anda melihat QR Code di bawah, berarti instalasi SUKSES.', 0, 1, 'C');

// Tempel QR Code dari file fisik 'temp_qr.png'
// Format: Image(path_file, x, y, width, height)
$pdf->Image($tempQR, 85, 50, 40, 40);

// 3. OUTPUT PDF
$pdf->Output('I', 'tes_sarsip.pdf');

// 4. BERSIH-BERSIH (Hapus file temp setelah selesai)
// Kita gunakan @ agar tidak error jika file gagal dihapus
@unlink($tempQR); 
?>