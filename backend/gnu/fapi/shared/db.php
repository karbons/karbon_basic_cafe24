<?php
require_once __DIR__ . '/sqlx.php';

/**
 * Database wrapper functions
 */

if (!function_exists('db_connect')) {
    function db_connect() {
        return sqlx::connect();
    }
}

if (!function_exists('db_query')) {
    function db_query($sql) {
        return sqlx::query($sql);
    }
}
