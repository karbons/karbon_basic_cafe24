<?php
/**
 * 그누보드 호환성 레이어
 * 그누보드 함수와 중복되지 않도록 function_exists로 보호
 */

// 테이블 설정 로드
if (file_exists(__DIR__ . '/../config/tables.php')) {
    require_once __DIR__ . '/../config/tables.php';
}

if (!function_exists('sql_query')) {
    function sql_query($sql, $error = true)
    {
        try {
            if (class_exists('sqlx')) {
                $pdo = sqlx::pool();
                $stmt = $pdo->query($sql);
                return $stmt;
            }
            return false;
        } catch (PDOException $e) {
            if ($error && function_exists('log_error')) {
                log_error('sql_query error: ' . $e->getMessage(), ['sql' => $sql]);
            }
            return false;
        }
    }
}

if (!function_exists('sql_fetch')) {
    function sql_fetch($sql, $error = true)
    {
        try {
            if (class_exists('sqlx')) {
                $pdo = sqlx::pool();
                $stmt = $pdo->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: [];
            }
            return [];
        } catch (PDOException $e) {
            if ($error && function_exists('log_error')) {
                log_error('sql_fetch error: ' . $e->getMessage(), ['sql' => $sql]);
            }
            return [];
        }
    }
}

if (!function_exists('sql_fetch_array')) {
    function sql_fetch_array($result)
    {
        if (!$result || !($result instanceof PDOStatement)) {
            return false;
        }
        return $result->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('sql_real_escape_string')) {
    function sql_real_escape_string($str)
    {
        try {
            if (class_exists('sqlx')) {
                $pdo = sqlx::pool();
                $quoted = $pdo->quote($str);
                return substr($quoted, 1, -1);
            }
            return addslashes($str);
        } catch (PDOException $e) {
            return addslashes($str);
        }
    }
}

if (!function_exists('get_microtime')) {
    function get_microtime()
    {
        return microtime(true);
    }
}

if (!function_exists('sql_insert_id')) {
    function sql_insert_id()
    {
        try {
            if (class_exists('sqlx')) {
                return sqlx::pool()->lastInsertId();
            }
            return '0';
        } catch (PDOException $e) {
            return '0';
        }
    }
}

if (!function_exists('sql_affected_rows')) {
    function sql_affected_rows($result = null)
    {
        if ($result instanceof PDOStatement) {
            return $result->rowCount();
        }
        return 0;
    }
}

if (!function_exists('sql_num_rows')) {
    function sql_num_rows($result)
    {
        if (!$result || !($result instanceof PDOStatement)) {
            return 0;
        }
        return $result->rowCount();
    }
}

if (!function_exists('sql_free_result')) {
    function sql_free_result($result)
    {
        if ($result instanceof PDOStatement) {
            $result->closeCursor();
            return true;
        }
        return false;
    }
}

if (!function_exists('sql_connect')) {
    function sql_connect($host = '', $user = '', $pass = '', $db = '')
    {
        if (class_exists('sqlx')) {
            return sqlx::pool();
        }
        return null;
    }
}

if (!function_exists('sql_select_db')) {
    function sql_select_db($db = '', $connection = null)
    {
        return true;
    }
}

if (!function_exists('sql_error')) {
    function sql_error()
    {
        try {
            if (class_exists('sqlx')) {
                $pdo = sqlx::pool();
                $errorInfo = $pdo->errorInfo();
                return $errorInfo[2] ?? '';
            }
            return '';
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}

if (!function_exists('sql_errno')) {
    function sql_errno()
    {
        try {
            if (class_exists('sqlx')) {
                $pdo = sqlx::pool();
                $errorInfo = $pdo->errorInfo();
                return $errorInfo[1] ?? 0;
            }
            return 0;
        } catch (PDOException $e) {
            return $e->getCode();
        }
    }
}

if (!function_exists('get_board_db')) {
    function get_board_db($bo_table, $get_admin = false)
    {
        global $g5;

        static $board_cache = [];
        $cache_key = $bo_table . '_' . ($get_admin ? '1' : '0');

        if (isset($board_cache[$cache_key])) {
            return $board_cache[$cache_key];
        }

        $sql = "SELECT * FROM {$g5['board_table']} WHERE bo_table = ? LIMIT 1";
        $board = [];

        try {
            if (class_exists('sqlx')) {
                $result = sqlx::query($sql)->bind($bo_table)->fetch_optional();
                if ($result) {
                    $board = $result;
                }
            }
        } catch (Exception $e) {
            if (function_exists('log_error')) {
                log_error('get_board_db error', ['error' => $e->getMessage()]);
            }
        }

        $board_cache[$cache_key] = $board;
        return $board;
    }
}

if (!function_exists('get_sql_search')) {
    function get_sql_search($sca, $sfl, $stx, $sop = 'and')
    {
        $search_sql = '';

        if ($sfl && $stx) {
            $op = $sop === 'or' ? ' OR ' : ' AND ';
            $search_sql .= "{$op} {$sfl} LIKE '%" . sql_real_escape_string($stx) . "%' ";
        }

        return $search_sql;
    }
}

if (!function_exists('get_list_thumbnail')) {
    function get_list_thumbnail($bo_table, $wr_id, $thumb_width = 0, $thumb_height = 0, $is_cache = true, $create_thumb = true, $thumb_fallback = 'center')
    {
        global $g5;

        $write_table = $g5['write_prefix'] . $bo_table;

        $sql = "SELECT wr_content FROM {$write_table} WHERE wr_id = ?";
        $row = [];

        try {
            if (class_exists('sqlx')) {
                $row = sqlx::query($sql)->bind($wr_id)->fetch_optional();
            }
        } catch (Exception $e) {}

        $src = null;
        $ori = null;
        $alt = '';

        if ($row && !empty($row['wr_content'])) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/is', $row['wr_content'], $matches);

            if (!empty($matches[1][0])) {
                $src = $matches[1][0];
                $ori = $matches[1][0];
            }
        }

        return [
            'src' => $src,
            'ori' => $ori,
            'alt' => $alt,
            'width' => $thumb_width,
            'height' => $thumb_height
        ];
    }
}

if (!function_exists('login_password_check')) {
    function login_password_check($member, $input_password, $stored_password)
    {
        if (empty($input_password) || empty($stored_password)) {
            return false;
        }
        return password_verify($input_password, $stored_password) || md5($input_password) === $stored_password;
    }
}

if (!function_exists('is_use_email_certify')) {
    function is_use_email_certify()
    {
        global $config;
        return isset($config['cf_use_email_certify']) && $config['cf_use_email_certify'];
    }
}

if (!function_exists('run_event')) {
    function run_event($event_name, ...$args)
    {
        return true;
    }
}

if (!function_exists('check_password')) {
    function check_password($password, $hash)
    {
        if (empty($password) || empty($hash)) {
            return false;
        }
        if (password_verify($password, $hash)) {
            return true;
        }
        if (md5($password) === $hash) {
            return true;
        }
        return false;
    }
}

if (!function_exists('get_encrypt_string')) {
    function get_encrypt_string($str)
    {
        return password_hash($str, PASSWORD_DEFAULT);
    }
}
