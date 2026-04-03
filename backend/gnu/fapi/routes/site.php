<?php
// GET /api/site
function GET()
{
    global $config, $g5;

    // 쇼핑몰 사업자정보 조회 (g5_shop_default 테이블)
    $shop_table = $g5['g5_shop_default_table'] ?? 'g5_shop_default';
    $shop_default = sqlx::query("SELECT * FROM {$shop_table} LIMIT 1")
        ->fetch_optional() ?: [];

    // 사이트 전반적인 설정 반환
    json_return([
        'cf_title' => $config['cf_title'],
        'cf_admin_name' => $config['cf_admin'],
        'cf_admin_email' => $config['cf_admin_email'],

        // 주소 및 연락처
        'cf_addr1' => $config['cf_addr1'] ?? '',
        'cf_addr2' => $config['cf_addr2'] ?? '',
        'cf_zip' => $config['cf_zip'] ?? '',
        'cf_tel' => $config['cf_tel'],
        'cf_fax' => $config['cf_1'] ?: '', // 여분필드 1을 팩스로 가정 (또는 직접 필드가 있다면 변경)

        // 추가 정보
        'cf_info1' => $config['cf_info1'], // 사업자등록번호
        'cf_info2' => $config['cf_info2'], // 통신판매업신고번호
        'cf_info3' => $config['cf_info3'], // 대표자명 등

        // 여분 필드
        'cf_privacy_officer' => $config['cf_2'] ?: $config['cf_admin'],
        'cf_extra_1' => $config['cf_3'] ?? '',
        'cf_extra_2' => $config['cf_4'] ?? '',

        // 회원가입 설정
        'cf_use_homepage' => (bool) $config['cf_use_homepage'],
        'cf_req_homepage' => (bool) $config['cf_req_homepage'],
        'cf_use_tel' => (bool) $config['cf_use_tel'],
        'cf_req_tel' => (bool) $config['cf_req_tel'],
        'cf_use_hp' => (bool) $config['cf_use_hp'],
        'cf_req_hp' => (bool) $config['cf_req_hp'],
        'cf_use_addr' => (bool) $config['cf_use_addr'],
        'cf_req_addr' => (bool) $config['cf_req_addr'],
        'cf_use_signature' => (bool) $config['cf_use_signature'],
        'cf_req_signature' => (bool) $config['cf_req_signature'],
        'cf_use_profile' => (bool) $config['cf_use_profile'],
        'cf_req_profile' => (bool) $config['cf_req_profile'],
        'cf_use_recommend' => (bool) $config['cf_use_recommend'],
        'cf_cert_use' => (int) $config['cf_cert_use'], // 0:미사용, 1:테스트, 2:실서비스

        // 쇼핑몰 사업자정보 (g5_shop_default 테이블)
        'shop_company_name' => $shop_default['de_admin_company_name'] ?? '',
        'shop_company_owner' => $shop_default['de_admin_company_owner'] ?? '',
        'shop_saupja_no' => $shop_default['de_admin_company_saupja_no'] ?? '',
        'shop_tongsin_no' => $shop_default['de_admin_tongsin_no'] ?? '',
        'shop_tel' => $shop_default['de_admin_company_tel'] ?? '',
        'shop_fax' => $shop_default['de_admin_company_fax'] ?? '',
        'shop_zip' => $shop_default['de_admin_company_zip'] ?? '',
        'shop_addr' => $shop_default['de_admin_company_addr'] ?? '',
        'shop_info_name' => $shop_default['de_admin_info_name'] ?? '',
        'shop_info_email' => $shop_default['de_admin_info_email'] ?? '',

        // 정책 관련
        'has_privacy_policy' => true,
        'has_service_policy' => true,

        // 앱 버전 관리
        'app_version' => getenv('APP_VERSION') ?: '0.0.1',
        'play_store_link' => getenv('PLAY_STORE_LINK') ?: '',
        'app_store_link' => getenv('APP_STORE_LINK') ?: '',
    ], 200, '00000');
}

