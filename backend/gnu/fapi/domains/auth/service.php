<?php
require_once G5_API_PATH . '/domains/auth/repository.php';
require_once G5_API_PATH . '/domains/member/repository.php';
require_once G5_API_PATH . '/shared/jwt.php';
require_once G5_API_PATH . '/shared/config_class.php';

function auth_service_generate_access_token($member)
{
    $jwt = new JWT();
    $issuedAt = time();
    $accessMtime = Config::get('jwt.access_mtime', 15);
    $expirationTime = $issuedAt + ($accessMtime * 60);

    $payload = [
        "mb_id" => $member['mb_id'],
        "iss" => Config::get('app.url', G5_URL),
        "aud" => Config::get('jwt.audience'),
        "iat" => $issuedAt,
        "exp" => $expirationTime
    ];

    $jwtKey = Config::get('jwt.access_token_key', '');
    return $jwt->encode($payload, $jwtKey, "HS256");
}

function auth_service_generate_refresh_token($member, $fcm_token = '', $device_model = '', $os_version = '')
{
    $jwt = new JWT();
    $uuid = function_exists('gen_uuid_v4') ? gen_uuid_v4() : bin2hex(random_bytes(16));
    $issuedAt = time();
    $refreshDate = Config::get('jwt.refresh_date', 30);
    $expirationTime = $issuedAt + ($refreshDate * 86400);

    $payload = [
        "id" => $uuid,
        "mb_id" => $member['mb_id'],
        "iat" => $issuedAt,
        "exp" => $expirationTime
    ];

    $refreshKey = Config::get('jwt.refresh_token_key', '');
    $token = $jwt->encode($payload, $refreshKey, "HS256");

    auth_repo_save_refresh_token(
        $member['mb_id'],
        $uuid,
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $token,
        $fcm_token,
        $device_model,
        $os_version
    );

    return $token;
}

function auth_service_login($mb_id, $password, $fcm_token = '', $device_model = '', $os_version = '')
{
    $member = member_repo_find_by_id($mb_id);

    if (empty($member)) {
        return null;
    }

    if (!check_password($password, $member['mb_password'])) {
        return null;
    }

    if ($member['mb_intercept_date'] && $member['mb_intercept_date'] <= date("Ymd", G5_SERVER_TIME)) {
        return null;
    }

    if ($member['mb_leave_date'] && $member['mb_leave_date'] <= date("Ymd", G5_SERVER_TIME)) {
        return null;
    }

    $access_token = auth_service_generate_access_token($member);
    $refresh_token = auth_service_generate_refresh_token($member, $fcm_token, $device_model, $os_version);

    return [
        'member' => $member,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token
    ];
}
