<?php
// File: sarsip/proses_tools.php
session_start();
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){ http_response_code(403); exit; }

require_once('libs/fpdf/fpdf.php');
require_once('libs/fpdi/src/autoload.php');
use setasign\Fpdi\Fpdi;

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

if($aksi == 'merge_ajax'){
    error_reporting(0); // Supaya output binary bersih
    if(isset($_FILES['files']['name']) && count($_FILES['files']['name']) > 0){
        $pdf = new Fpdi();
        $files = $_FILES['files'];
        $count = count($files['name']);

        for($i=0; $i<$count; $i++){
            $tmp = $files['tmp_name'][$i];
            if(strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION)) != 'pdf') continue;
            try {
                $pageCount = $pdf->setSourceFile($tmp);
                for ($p=1; $p<=$pageCount; $p++) {
                    $tpl = $pdf->importPage($p);
                    $sz = $pdf->getTemplateSize($tpl);
                    $ori = ($sz['width'] > $sz['height']) ? 'L' : 'P';
                    $pdf->AddPage($ori, [$sz['width'], $sz['height']]);
                    $pdf->useTemplate($tpl);
                }
            } catch(Exception $e){ continue; }
        }
        $pdf->Output('D', 'merged.pdf'); 
        exit;
    }
}
elseif($aksi == 'split'){
    // (Logika split sama seperti sebelumnya)
    if(isset($_FILES['file_source']['tmp_name']) && !empty($_POST['range'])){
        $tmp = $_FILES['file_source']['tmp_name'];
        $range = $_POST['range'];
        $pdf = new Fpdi();
        try {
            $cnt = $pdf->setSourceFile($tmp);
            $pages = [];
            $parts = explode(',', $range);
            foreach($parts as $part){
                $part = trim($part);
                if(strpos($part, '-') !== false){
                    list($s, $e) = explode('-', $part);
                    for($j=$s; $j<=$e; $j++) $pages[] = (int)$j;
                } else { $pages[] = (int)$part; }
            }
            $pages = array_unique($pages);
            foreach($pages as $p){
                if($p>0 && $p<=$cnt){
                    $tpl = $pdf->importPage($p);
                    $sz = $pdf->getTemplateSize($tpl);
                    $ori = ($sz['width'] > $sz['height']) ? 'L' : 'P';
                    $pdf->AddPage($ori, [$sz['width'], $sz['height']]);
                    $pdf->useTemplate($tpl);
                }
            }
            $pdf->Output('D', 'split.pdf'); exit;
        } catch(Exception $e){ echo "<script>alert('Error'); window.history.back();</script>"; }
    }
}
?>