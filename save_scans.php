<?php
ini_set('display_errors',1); error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

$res = isset($_GET['res']) ? trim($_GET['res']) : '';
$i   = isset($_GET['i'])   ? (int)$_GET['i']   : 1;

if ($res === '') { echo json_encode(['ok'=>false,'error'=>'Reservation number missing']); exit; }
if ($i < 1) $i = 1;

$cleanRes = preg_replace('/[^A-Za-z0-9_\-]/','', $res);
$uploads  = __DIR__ . '/uploads';
if (!is_dir($uploads)) { @mkdir($uploads,0777,true); }

function save_dataurl($dataurl, $pathJpg){
  if (!preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $dataurl)) return false;
  $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i','',$dataurl));
  if ($data===false) return false;
  return file_put_contents($pathJpg, $data) !== false;
}

$front = $_POST['front'] ?? '';
$back  = $_POST['back']  ?? '';

$frontPath = $uploads . "/res_{$cleanRes}_{$i}_front.jpg";
$backPath  = $uploads . "/res_{$cleanRes}_{$i}_back.jpg";

$okF = $front ? save_dataurl($front, $frontPath) : false;
$okB = $back  ? save_dataurl($back , $backPath ) : false;

if ($okF && $okB) {
  echo json_encode(['ok'=>true,'front'=>"uploads/".basename($frontPath),'back'=>"uploads/".basename($backPath)]);
} else {
  echo json_encode(['ok'=>false,'error'=>'Failed to save images']);
}
