-- Migration: 마이그레이션 버전 관리 테이블 생성
-- 모든 마이그레이션의 실행 이력을 추적

CREATE TABLE IF NOT EXISTS {PREFIX}migrations (
    version VARCHAR(14) NOT NULL COMMENT '마이그레이션 버전 (YYYYMMDDHHIISS)',
    name VARCHAR(255) NOT NULL COMMENT '마이그레이션 이름',
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '실행 시간',
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- PostgreSQL용 (환경에 따라 주석 해제)
-- CREATE TABLE IF NOT EXISTS {PREFIX}migrations (
--     version VARCHAR(14) NOT NULL,
--     name VARCHAR(255) NOT NULL,
--     executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     PRIMARY KEY (version)
-- );
