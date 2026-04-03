<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_API_PATH . '/shared/upload.php';

function POST() {
    $fileName = $_POST['fileName'] ?? '';
    $mimeType = $_POST['mimeType'] ?? 'application/octet-stream';

    if (!$fileName) {
        json_return(null, 400, '40001', '파일 이름이 없습니다.');
    }

    try {
        $uploadId = upload_init_multipart($fileName, $mimeType);
        
        json_return(['uploadId' => $uploadId]);
    } catch (Exception $e) {
        json_return(null, 500, '50001', $e->getMessage());
    }
}
