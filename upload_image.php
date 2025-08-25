<?php
// TinyMCE image upload handler
require_once __DIR__.'/helpers.php';
require_admin(); // только из админки

header('Content-Type: application/json; charset=utf-8');

if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK){
    http_response_code(400); echo json_encode(['error'=>'upload']); exit;
}
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
$allowed = ['png','jpg','jpeg','gif','webp','svg'];
if(!in_array($ext,$allowed)){ http_response_code(415); echo json_encode(['error'=>'type']); exit; }

$base = 'uploads/images/img_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;
$dest = __DIR__.'/'.$base;
if(!move_uploaded_file($_FILES['file']['tmp_name'],$dest)){
    http_response_code(500); echo json_encode(['error'=>'move']); exit;
}
echo json_encode(['location'=>$base]);
