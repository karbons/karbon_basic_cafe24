<?php
// POST /api/visit
function POST()
{
    global $g5;

    $remote_addr = $_SERVER['REMOTE_ADDR'];

    // 쿠키를 API에서는 받기 어려우므로 DB로 오늘 이 IP가 방문했는지 체크
    $today = G5_TIME_YMD;
    $exists = sqlx::query("SELECT vi_id FROM {$g5['visit_table']} WHERE vi_ip = ? AND vi_date = ?")
        ->bind($remote_addr)
        ->bind($today)
        ->fetch_optional();

    if ($exists && $exists['vi_id']) {
        // 이미 오늘 방문 기록 있음
        json_return(['already_logged' => true], 200, '00000');
    }

    // 방문 정보 수집
    $referer = '';
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = substr($_SERVER['HTTP_REFERER'], 0, 255);
    }
    $user_agent = '';
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
    }

    // 간단한 디바이스 감지
    $vi_device = 'desktop';
    if (preg_match('/Mobile|Android|iPhone/i', $user_agent)) {
        $vi_device = 'mobile';
    } elseif (preg_match('/Tablet|iPad/i', $user_agent)) {
        $vi_device = 'tablet';
    }

    // 방문 기록 INSERT
    try {
        sqlx::query("INSERT INTO {$g5['visit_table']} 
            (vi_ip, vi_date, vi_time, vi_referer, vi_agent, vi_browser, vi_os, vi_device) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->bind($remote_addr)
            ->bind($today)
            ->bind(G5_TIME_HIS)
            ->bind($referer)
            ->bind($user_agent)
            ->bind('')
            ->bind('')
            ->bind($vi_device)
            ->execute();
            
        // 방문자 합계 업데이트
        try {
            sqlx::query("INSERT INTO {$g5['visit_sum_table']} (vs_count, vs_date) VALUES (1, ?)")
                ->bind($today)
                ->execute();
        } catch (Exception $e) {
            // 이미 오늘 날짜 행이 있으면 UPDATE
            sqlx::query("UPDATE {$g5['visit_sum_table']} SET vs_count = vs_count + 1 WHERE vs_date = ?")
                ->bind($today)
                ->execute();
        }

        // 방문자 통계 업데이트 (cf_visit)
        $today_cnt = sqlx::query("SELECT vs_count as cnt FROM {$g5['visit_sum_table']} WHERE vs_date = ?")
            ->bind($today)
            ->fetch_one();
        
        $yesterday_cnt = sqlx::query("SELECT vs_count as cnt FROM {$g5['visit_sum_table']} WHERE vs_date = DATE_SUB(?, INTERVAL 1 DAY)")
            ->bind($today)
            ->fetch_optional();
            
        $max_cnt = sqlx::query("SELECT MAX(vs_count) as cnt FROM {$g5['visit_sum_table']}")
            ->fetch_one();
            
        $total_cnt = sqlx::query("SELECT SUM(vs_count) as total FROM {$g5['visit_sum_table']}")
            ->fetch_one();

        $visit = '오늘:' . ($today_cnt['cnt'] ?? 0) .
            ',어제:' . ($yesterday_cnt['cnt'] ?? 0) .
            ',최대:' . ($max_cnt['cnt'] ?? 0) .
            ',전체:' . ($total_cnt['total'] ?? 0);
            
        sqlx::query("UPDATE {$g5['config_table']} SET cf_visit = ?")
            ->bind($visit)
            ->execute();
            
    } catch (Exception $e) {
        // INSERT 실패 시 (예: 동시 접속으로 인한 중복 등) 무시하고 성공 응답
    }

    json_return(['logged' => true], 200, '00000');
}

// GET /api/visit - 방문자 통계 조회
function GET()
{
    global $g5;

    $today = G5_TIME_YMD;

    $today_cnt = sqlx::query("SELECT vs_count as cnt FROM {$g5['visit_sum_table']} WHERE vs_date = ?")
        ->bind($today)
        ->fetch_one();
        
    $yesterday_cnt = sqlx::query("SELECT vs_count as cnt FROM {$g5['visit_sum_table']} WHERE vs_date = DATE_SUB(?, INTERVAL 1 DAY)")
        ->bind($today)
        ->fetch_optional();
        
    $max_cnt = sqlx::query("SELECT MAX(vs_count) as cnt FROM {$g5['visit_sum_table']}")
        ->fetch_one();
        
    $total_cnt = sqlx::query("SELECT SUM(vs_count) as total FROM {$g5['visit_sum_table']}")
        ->fetch_one();

    json_return([
        'today' => (int) ($today_cnt['cnt'] ?? 0),
        'yesterday' => (int) ($yesterday_cnt['cnt'] ?? 0),
        'max' => (int) ($max_cnt['cnt'] ?? 0),
        'total' => (int) ($total_cnt['total'] ?? 0)
    ], 200, '00000');
}
