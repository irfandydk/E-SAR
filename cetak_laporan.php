<?php
// File: sarsip/cetak_laporan.php
session_start();
include 'config/koneksi.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ header("location:login.php"); exit; }

// Tangkap Filter
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$status   = isset($_GET['status']) ? $_GET['status'] : ''; // Filter Baru
$tgl1     = $_GET['tgl_awal'];
$tgl2     = $_GET['tgl_akhir'];

// --- SETUP PDF (TETAP MENGGUNAKAN FPDI SEPERTI KODE ASLI) ---
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
    $pdf->Cell(0, 5, $periode, 0, 1, 'C');
    $pdf->Ln(5);
}

function cetakHeaderTabel($pdf, $header, $w){
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    for($i=0; $i<count($header); $i++){
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
}

// JUDUL LAPORAN
$judul_lap = "LAPORAN ARSIP DOKUMEN";
if(!empty($kategori)) $judul_lap .= " - " . strtoupper($kategori);
if($status == 'aktif') $judul_lap .= " (AKTIF)";
if($status == 'inaktif') $judul_lap .= " (INAKTIF / USUL MUSNAH)";

$periode_lap = "Periode: " . date('d/m/Y', strtotime($tgl1)) . " s/d " . date('d/m/Y', strtotime($tgl2));

// CONFIG KOLOM TABEL (Sesuai Kategori)
// Saya sesuaikan lebar kolom agar muat untuk 'Status'
if($kategori == 'Surat Masuk'){
    // No, Tgl, Asal, No Surat, Judul, Status
    $header = ['No', 'Tanggal', 'Asal Surat', 'No. Surat', 'Perihal / Judul', 'Status'];
    $w      = [10, 25, 40, 40, 50, 25]; 
} else {
    // No, Tgl, No Dokumen, Kategori, Judul, Status
    $header = ['No', 'Tanggal', 'No. Dokumen', 'Kategori', 'Judul', 'Status'];
    $w      = [10, 25, 40, 35, 55, 25];
}

// --- QUERY DATA ---
$q = "SELECT * FROM documents WHERE DATE(created_at) BETWEEN '$tgl1' AND '$tgl2'";
if(!empty($kategori)) $q .= " AND kategori='$kategori'";
if(!empty($status) && $status != 'semua') $q .= " AND status_retensi='$status'"; // Filter Status

$q .= " ORDER BY created_at ASC";
$exec = mysqli_query($koneksi, $q);

// --- MULAI CETAK ---
headerBaru($pdf, $judul_lap, $periode_lap);
cetakHeaderTabel($pdf, $header, $w);

$pdf->SetFont('Arial', '', 9);
$no = 1;

while($d = mysqli_fetch_assoc($exec)){
    
    // Cek Page Break Manual (Fitur Asli Anda)
    if($pdf->GetY() > 250){
        headerBaru($pdf, $judul_lap, $periode_lap);
        cetakHeaderTabel($pdf, $header, $w);
        $pdf->SetFont('Arial', '', 9);
    }

    // Siapkan Data Status
    $status_txt = strtoupper($d['status_retensi']);
    $tgl_upload = date('d/m/Y', strtotime($d['created_at']));

    if($kategori == 'Surat Masuk'){
        // Isi Surat Masuk
        $pdf->Cell($w[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($w[1], 7, $tgl_upload, 1, 0, 'C');
        $pdf->Cell($w[2], 7, substr($d['asal_surat'],0,20), 1, 0);
        $pdf->Cell($w[3], 7, substr($d['nomor_surat'],0,20), 1, 0);
        $pdf->Cell($w[4], 7, substr($d['judul'],0,30), 1, 0);
        $pdf->Cell($w[5], 7, $status_txt, 1, 0, 'C'); // Kolom Baru
    } else {
        // Isi Dokumen Lain
        $pdf->Cell($w[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($w[1], 7, $tgl_upload, 1, 0, 'C');
        $pdf->Cell($w[2], 7, substr($d['nomor_surat'],0,20), 1, 0);
        
        // Jika filter kategori kosong, tampilkan nama kategori. Jika tidak, kosongkan/custom
        $isi_kategori = empty($kategori) ? substr($d['kategori'],0,18) : substr($d['kategori'],0,18);
        $pdf->Cell($w[3], 7, $isi_kategori, 1, 0);
        
        $pdf->Cell($w[4], 7, substr($d['judul'],0,35), 1, 0);
        $pdf->Cell($w[5], 7, $status_txt, 1, 0, 'C'); // Kolom Baru
    }
    
    $pdf->Ln();
}

$pdf->Output('I', 'Laporan_Arsip.pdf');
?>
