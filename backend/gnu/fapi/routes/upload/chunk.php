<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_API_PATH . '/shared/upload.php';

function POST() {
    $uploadId = $_POST['uploadId'] ?? '';
    $partNumber = (int)($_POST['partNumber'] ?? 0);
    $fileName = $_POST['fileName'] ?? '';
    
    if (!$uploadId || !$partNumber || !$fileName) {
        json_return(null, 400, '40002', '필수 파라미터가 누락되었습니다.');
    }

    if (!isset($_FILES['chunk'])) {
        json_return(null, 400, '40003', '청크 파일이 없습니다.');
    }

    try {
        $chunkData = file_get_contents($_FILES['chunk']['tmp_name']);
        $etag = upload_part($uploadId, $partNumber, $chunkData, $fileName);
        
        json_return(['etag' => $etag]);
    } catch (Exception $e) {
        json_return(null, 500, '50002', $e->getMessage());
    }
}
