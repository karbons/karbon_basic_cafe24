-- Migration: mb_id 컬럼 크기 확장 (UUID 지원)
-- PostgreSQL 전환 시 UUID 타입으로 변환 가능하도록 VARCHAR(36) 사용

SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='ALLOW_INVALID_DATES';

ALTER TABLE g5_member MODIFY mb_id VARCHAR(36) NOT NULL;

SET SQL_MODE=@OLD_SQL_MODE;

-- PostgreSQL 전환 시 실행
-- ALTER TABLE g5_member ALTER COLUMN mb_id TYPE UUID USING mb_id::uuid;
