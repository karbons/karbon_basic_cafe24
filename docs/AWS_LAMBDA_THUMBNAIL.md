# AWS Lambda Rust Thumbnail Generator Guide

AWS S3에 이미지가 업로드될 때 자동으로 썸네일을 생성하는 Rust 기반 Lambda 함수 설정 가이드입니다.

## 1. 개발 환경 구성

- **Rust**: `curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh`
- **Cargo Lambda**: Lambda용 빌드 도구
  ```bash
  pip install cargo-lambda
  ```

## 2. 빌드 및 배포

1. **빌드**:
   ```bash
   cd scripts/lambda/thumbnail-generator
   cargo lambda build --release
   ```

2. **배포**:
   - AWS Console에서 Lambda 함수를 생성합니다 (Runtime: `provided.al2`).
   - `target/lambda/thumbnail-generator/bootstrap.zip` 파일을 업로드합니다.
   - **Architecture**: `x86_64` 또는 `arm64` (빌드 옵션에 따라 선택).

## 3. S3 트리거 설정

1. **Lambda 트리거 추가**: S3를 선택합니다.
2. **Event type**: `ObjectCreated`
3. **Prefix**: `uploads/`
4. **IAM Role**: Lambda 함수가 S3 버킷에 대해 `GetObject`, `PutObject` 권한을 가질 수 있도록 정책을 추가합니다.

## 4. 특징

- **고성능**: Rust의 `image` 크레이트를 사용하여 이미지 디코딩 및 리사이징 속도가 매우 빠릅니다.
- **안정성**: 강타입 언어의 장점을 살려 런타임 에러를 최소화합니다.
- **비용 절감**: Lambda 실행 시간이 짧아 비용 효율적입니다.
