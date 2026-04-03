# AWS S3 Chunked Upload (Multipart Upload) Guide

본 프로젝트는 대용량 파일 업로드를 위해 AWS S3 Multipart Upload API를 활용합니다. 

## S3 설정

1. **Bucket 생성**: 파일이 저장될 S3 버킷을 생성합니다.
2. **CORS 설정**: 프론트엔드에서 S3로 직접 업로드하는 경우(현재는 백엔드를 경유하지만, 향후 확장을 위해) CORS 설정이 필요할 수 있습니다.
   ```json
   [
       {
           "AllowedHeaders": ["*"],
           "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
           "AllowedOrigins": ["*"],
           "ExposeHeaders": ["ETag"]
       }
   ]
   ```
3. **IAM 권한**: API 서버가 사용하는 IAM 사용자에게 `s3:PutObject`, `s3:GetObject`, `s3:AbortMultipartUpload`, `s3:ListMultipartUploadParts` 등의 권한을 부여해야 합니다.

## 청크 업로드 프로세스

1. **Init (`POST /upload/init`)**: `s3Client->createMultipartUpload`를 호출하여 `UploadId`를 발급받습니다.
2. **Chunk (`POST /upload/chunk`)**: `s3Client->uploadPart`를 호출하여 개별 파트를 업로드합니다. 각 파트는 최소 5MB 이상이어야 합니다(마지막 파트 제외). 업로드 후 반환된 `ETag`를 프론트엔드에 전달합니다.
3. **Complete (`POST /upload/complete`)**: 모든 파트의 `PartNumber`와 `ETag` 목록을 `s3Client->completeMultipartUpload`에 전달하여 파일을 결합합니다.

## 장점
- **안정성**: 네트워크 오류 시 전체 파일을 다시 올릴 필요 없이 실패한 청크만 재시도할 수 있습니다.
- **성능**: 여러 청크를 병렬로 업로드하여 전체 업로드 시간을 단축할 수 있습니다.
