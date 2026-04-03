<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_API_PATH . '/shared/upload.php';

function POST() {
    $uploadId = $_POST['uploadId'] ?? '';
    $fileName = $_POST['fileName'] ?? '';
    $partsJson = $_POST['parts'] ?? '[]';
    $parts = json_decode($partsJson, true);

    if (!$uploadId || !$fileName || empty($parts)) {
        json_return(null, 400, '40004', '필수 파라미터가 누락되었습니다.');
    }

    try {
        $url = upload_complete_multipart($uploadId, $parts, $fileName);
        
        json_return(['url' => $url]);
    } catch (Exception $e) {
        json_return(null, 500, '50003', $e->getMessage());
    }
}
