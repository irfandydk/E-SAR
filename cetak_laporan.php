<?php
// File: sarsip/cetak_laporan.php
session_start();
include 'config/koneksi.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

if($_SESSION['status'] != "login"){ header("location:login.php"); exit; }

// Tangkap Filter
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$tgl1     = $_GET['tgl_awal'];
$tgl2     = $_GET['tgl_akhir'];

// --- SETUP PDF ---
$pdf = new Fpdi();
$pdf->SetAutoPageBreak(true, 15);

function headerBaru($pdf, $judul, $periode){
    $pdf->AddPage('P', 'A4');
    
    // Kop Surat (Jika ada)
    $path_kop = 'assets/kop_surat.pdf';
    if(file_exists($path_kop)){
        $pdf->setSourceFile($path_kop);
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 210);
    }
    
    $pdf->SetY(55); // Mulai tulis di bawah kop
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 5, strtoupper($judul), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, "Periode: $periode", 0, 1, 'C');
    $pdf->Ln(5);
}

// Menentukan Judul Laporan
if($kategori == 'Surat Masuk'){
    $judul_lap = "LAPORAN AGENDA SURAT MASUK";
} elseif(!empty($kategori)){
    $judul_lap = "LAPORAN ARSIP " . strtoupper($kategori);
} else {
    $judul_lap = "LAPORAN REKAPITULASI DOKUMEN ARSIP";
}

$periode_lap = date('d/m/Y', strtotime($tgl1)) . " s/d " . date('d/m/Y', strtotime($tgl2));

// Inisialisasi Halaman 1
headerBaru($pdf, $judul_lap, $periode_lap);

// --- CONFIG KOLOM TABEL ---
if($kategori == 'Surat Masuk'){
    // Kolom Khusus Surat Masuk
    $header = ['No', 'Tgl Terima', 'Asal Surat / Pengirim', 'No. Surat', 'Perihal'];
    $w      = [10, 25, 50, 40, 65]; // Total 190
} else {
    // Kolom Standar Dokumen Lain
    $header = ['No', 'Tanggal', 'Nomor Dokumen', 'Kategori', 'Perihal / Judul'];
    $w      = [10, 25, 45, 35, 75]; // Total 190
}

// Query Database (Single Table: documents)
$query = "SELECT * FROM documents WHERE DATE(created_at) BETWEEN '$tgl1' AND '$tgl2'";
if(!empty($kategori)){
    $query .= " AND kategori = '$kategori'";
}
$query .= " ORDER BY created_at ASC";


// Cetak Header Tabel Function
function cetakHeaderTabel($pdf, $header, $w){
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    foreach($header as $k => $h){
        $pdf->Cell($w[$k], 8, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();
}

// Panggil Header Tabel Pertama Kali
cetakHeaderTabel($pdf, $header, $w);

// Cetak Isi
$pdf->SetFont('Arial', '', 9);
$q = mysqli_query($koneksi, $query);
$no = 1;

while($d = mysqli_fetch_assoc($q)){
    // Cek Page Break Manual
    if($pdf->GetY() > 250){
        headerBaru($pdf, $judul_lap, $periode_lap);
        cetakHeaderTabel($pdf, $header, $w);
        $pdf->SetFont('Arial', '', 9);
    }

    if($kategori == 'Surat Masuk'){
        // Isi Surat Masuk
        $pdf->Cell($w[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($w[1], 7, date('d/m/Y', strtotime($d['created_at'])), 1, 0, 'C');
        $pdf->Cell($w[2], 7, substr($d['asal_surat'],0,25), 1, 0);
        $pdf->Cell($w[3], 7, substr($d['nomor_surat'],0,20), 1, 0);
        $pdf->Cell($w[4], 7, substr($d['judul'],0,35), 1, 0);
    } else {
        // Isi Dokumen Lain
        $pdf->Cell($w[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($w[1], 7, date('d/m/Y', strtotime($d['created_at'])), 1, 0, 'C');
        $pdf->Cell($w[2], 7, substr($d['nomor_surat'],0,22), 1, 0);
        $pdf->Cell($w[3], 7, substr($d['kategori'],0,18), 1, 0); // Jika "Semua Kategori", kolom ini berguna
        $pdf->Cell($w[4], 7, substr($d['judul'],0,40), 1, 0);
    }
    $pdf->Ln();
}

// Area Tanda Tangan
if($pdf->GetY() > 220) $pdf->AddPage();
$pdf->Ln(15);

// Posisi Kanan
$pdf->SetX(130);
$pdf->Cell(60, 5, "Kendari, ".date('d F Y'), 0, 1, 'C');
$pdf->SetX(130);
$pdf->Cell(60, 5, "Mengetahui / Kepala Kantor,", 0, 1, 'C');
$pdf->Ln(25);
$pdf->SetX(130);
$pdf->SetFont('Arial', 'BU', 9);
$pdf->Cell(60, 5, "( .................................... )", 0, 1, 'C');

$pdf->Output('I', 'Laporan_Arsip.pdf');
?>