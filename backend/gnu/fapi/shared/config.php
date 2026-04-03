<?php
/**
 * Shared Config Utilities
 * Wrapper for gnu5_api/gnuboard/api/lib/Config.php
 */

/**
 * 설정 값 가져오기
 */
function config($key, $default = null) {
    if (class_exists('Config')) {
        return Config::get($key, $default);
    }
    return $default;
}

/**
 * 테이블 이름 가져오기
 */
function get_table_name($key) {
    global $g5;
    return isset($g5[$key . '_table']) ? $g5[$key . '_table'] : '';
}
