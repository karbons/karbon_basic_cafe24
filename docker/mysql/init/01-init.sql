-- MySQL 초기화 스크립트
-- 그누보드 데이터베이스 설정

-- 데이터베이스 생성 (이미 docker-compose에서 생성됨)
-- CREATE DATABASE IF NOT EXISTS gnuboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 사용자 권한 설정
GRANT ALL PRIVILEGES ON gnuboard.* TO 'gnuboard'@'%';
FLUSH PRIVILEGES;

-- 데이터베이스 선택
USE gnuboard;

-- 기본 테이블 생성 (그누보드 설치 시 자동 생성됨)
-- 여기에 필요한 초기 데이터나 설정을 추가할 수 있습니다.

-- 문자셋 설정
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET character_set_connection=utf8mb4; 