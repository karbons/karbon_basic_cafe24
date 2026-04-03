<?php
/**
 * Firebase Admin SDK Helper Functions
 */

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

/**
 * Firebase Auth 인스턴스 가져오기 (내부용)
 */
function _firebase_get_auth() {
    static $auth = null;
    if ($auth !== null) return $auth;

    $projectId = getenv('FIREBASE_PROJECT_ID');
    $privateKey = getenv('FIREBASE_PRIVATE_KEY');
    $clientEmail = getenv('FIREBASE_CLIENT_EMAIL');

    if (!$projectId || !$privateKey || !$clientEmail) {
        return null;
    }

    $privateKey = str_replace('\\n', "\n", $privateKey);

    $serviceAccount = [
        'type' => 'service_account',
        'project_id' => $projectId,
        'private_key_id' => getenv('FIREBASE_PRIVATE_KEY_ID') ?: '',
        'private_key' => $privateKey,
        'client_email' => $clientEmail,
        'client_id' => getenv('FIREBASE_CLIENT_ID') ?: '',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/' . urlencode($clientEmail),
    ];

    try {
        $factory = (new Factory)->withServiceAccount($serviceAccount);
        $auth = $factory->createAuth();
        return $auth;
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error('Firebase Auth 초기화 실패', ['error' => $e->getMessage()]);
        }
        return null;
    }
}

/**
 * Firebase 사용 가능 여부 확인
 */
function firebase_is_enabled() {
    return _firebase_get_auth() !== null;
}

/**
 * Firebase Custom Token 생성
 */
function firebase_create_custom_token(string $uid, array $claims = []) {
    $auth = _firebase_get_auth();
    if (!$auth) return null;

    try {
        $customToken = $auth->createCustomToken($uid, $claims);
        return $customToken->toString();
    } catch (Exception $e) {
        if (function_exists('log_error')) {
            log_error('Firebase Custom Token 생성 실패', ['error' => $e->getMessage()]);
        }
        return null;
    }
}

/**
 * FirebaseHelper 클래스
 */
class FirebaseHelper
{
    public static function isEnabled()
    {
        return firebase_is_enabled();
    }

    public static function createCustomToken($uid, $claims = [])
    {
        return firebase_create_custom_token($uid, $claims);
    }
}
