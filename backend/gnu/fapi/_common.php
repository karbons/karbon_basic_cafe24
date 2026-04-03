<?php
/**
 * API Legacy Bridge (_common.php)
 * 
 * This file is kept for backward compatibility with existing routes.
 */

/**
 * Legacy member fetch alias
 */
if (!function_exists('api_get_member')) {
    function api_get_member($mb_id, $fields = '*')
    {
        if (!function_exists('member_repo_find_by_id')) {
            require_once __DIR__ . '/domains/member/repository.php';
        }
        return member_repo_find_by_id($mb_id);
    }
}

/**
 * Legacy refresh token aliases
 */
if (!function_exists('set_refresh_token')) {
    function set_refresh_token($token, $mb_id, $uuid, $agent, $fcm_token = '', $device_model = '', $os_version = '')
    {
        if (!function_exists('auth_repo_save_refresh_token')) {
            require_once __DIR__ . '/domains/auth/repository.php';
        }
        return auth_repo_save_refresh_token($mb_id, $uuid, $agent, $token, $fcm_token, $device_model, $os_version);
    }
}

if (!function_exists('get_refresh_token')) {
    function get_refresh_token($uuid)
    {
        if (!function_exists('auth_repo_find_refresh_token')) {
            require_once __DIR__ . '/domains/auth/repository.php';
        }
        return auth_repo_find_refresh_token($uuid);
    }
}

if (!function_exists('delete_refresh_token')) {
    function delete_refresh_token($mb_id)
    {
        if (!function_exists('auth_repo_delete_refresh_token')) {
            require_once __DIR__ . '/domains/auth/repository.php';
        }
        return auth_repo_delete_refresh_token($mb_id);
    }
}
