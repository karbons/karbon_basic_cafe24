<?php
/**
 * 파일 업로드 핸들러 함수
 */

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * 업로드 스토리지 타입 가져오기
 */
function upload_get_storage() {
    return getenv('UPLOAD_STORAGE') ?: 'local';
}

/**
 * 멀티파트 업로드 시작
 */
function upload_init_multipart($fileName, $mimeType) {
    $storage = upload_get_storage();
    if ($storage === 's3') {
        $s3 = _upload_get_s3_client();
        try {
            $result = $s3->createMultipartUpload([
                'Bucket'      => getenv('AWS_S3_BUCKET'),
                'Key'         => 'uploads/' . $fileName,
                'ContentType' => $mimeType,
            ]);
            return $result['UploadId'];
        } catch (AwsException $e) {
            throw new Exception("S3 Init Error: " . $e->getMessage());
        }
    } else {
        return bin2hex(random_bytes(16));
    }
}

/**
 * 멀티파트 업로드 파트 업로드
 */
function upload_part($uploadId, $partNumber, $data, $fileName) {
    $storage = upload_get_storage();
    if ($storage === 's3') {
        $s3 = _upload_get_s3_client();
        try {
            $result = $s3->uploadPart([
                'Bucket'     => getenv('AWS_S3_BUCKET'),
                'Key'        => 'uploads/' . $fileName,
                'UploadId'   => $uploadId,
                'PartNumber' => $partNumber,
                'Body'       => $data,
            ]);
            return $result['ETag'];
        } catch (AwsException $e) {
            throw new Exception("S3 Upload Part Error: " . $e->getMessage());
        }
    } else {
        $tempDir = G5_DATA_PATH . '/upload_temp/' . $uploadId;
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/part_' . $partNumber, $data);
        return "part_" . $partNumber;
    }
}

/**
 * 멀티파트 업로드 완료
 */
function upload_complete_multipart($uploadId, $parts, $fileName) {
    $storage = upload_get_storage();
    if ($storage === 's3') {
        $s3 = _upload_get_s3_client();
        try {
            $formattedParts = [];
            foreach ($parts as $p) {
                $formattedParts[] = [
                    'PartNumber' => $p['partNumber'],
                    'ETag'       => $p['etag'],
                ];
            }
            $result = $s3->completeMultipartUpload([
                'Bucket'   => getenv('AWS_S3_BUCKET'),
                'Key'      => 'uploads/' . $fileName,
                'UploadId' => $uploadId,
                'MultipartUpload' => ['Parts' => $formattedParts],
            ]);
            return $result['ObjectURL'];
        } catch (AwsException $e) {
            throw new Exception("S3 Complete Error: " . $e->getMessage());
        }
    } else {
        $tempDir = G5_DATA_PATH . '/upload_temp/' . $uploadId;
        $finalPath = G5_DATA_PATH . '/upload/' . $fileName;
        
        if (!is_dir(dirname($finalPath))) mkdir(dirname($finalPath), 0755, true);

        $out = fopen($finalPath, 'wb');
        usort($parts, function($a, $b) {
            return $a['partNumber'] <=> $b['partNumber'];
        });

        foreach ($parts as $p) {
            $partPath = $tempDir . '/part_' . $p['partNumber'];
            $in = fopen($partPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($partPath);
        }
        fclose($out);
        rmdir($tempDir);

        _upload_handle_thumbnail($finalPath, $fileName);

        return G5_DATA_URL . '/upload/' . $fileName;
    }
}

/**
 * S3 클라이언트 가져오기 (내부용)
 */
function _upload_get_s3_client() {
    static $s3 = null;
    if ($s3 !== null) return $s3;

    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => getenv('AWS_REGION') ?: 'ap-northeast-2',
        'credentials' => [
            'key'    => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);
    return $s3;
}

/**
 * 썸네일 생성 처리 (내부용)
 */
function _upload_handle_thumbnail($filePath, $fileName) {
    $mime = mime_content_type($filePath);
    if (strpos($mime, 'image/') === 0) {
        $thumbDir = G5_DATA_PATH . '/upload/thumbnails';
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->read($filePath);
        $width = (int)(getenv('UPLOAD_THUMBNAIL_WIDTH') ?: 300);
        $height = (int)(getenv('UPLOAD_THUMBNAIL_HEIGHT') ?: 300);

        $image->scale(width: $width, height: $height);
        $image->save($thumbDir . '/' . $fileName);
    }
}
