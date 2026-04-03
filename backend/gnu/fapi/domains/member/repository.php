<?php

/**
 * Member Domain Repository
 * 
 * 회원 데이터에 대한 직접적인 데이터베이스 접근을 담당합니다.
 */

require_once G5_API_PATH . '/shared/db.php';

/**
 * 회원 ID로 회원 정보 조회
 * 
 * @param string $mb_id 회원 아이디 (UUID 형식 지원)
 * @return array 회원 정보 (없으면 빈 배열)
 */
function member_repo_find_by_id($mb_id)
{
    $mb_id = trim($mb_id);

    // UUID 형식 지원: 하이픈(-) 포함 허용 (api_get_member 로직 이식)
    if (preg_match("/[^0-9a-z_-]+/i", $mb_id)) {
        return [];
    }

    return sqlx::query("SELECT * FROM g5_member WHERE mb_id = ?")
        ->bind($mb_id)
        ->fetch_optional() ?? [];
}
