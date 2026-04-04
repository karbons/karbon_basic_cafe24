<?php
// 댓글 API
// GET /api/bbs/{bo_table}/comment?wr_id={wr_id}
// POST /api/bbs/{bo_table}/comment
// PUT /api/bbs/{bo_table}/comment?comment_id={comment_id}
// DELETE /api/bbs/{bo_table}/comment?comment_id={comment_id}

function GET($bo_table)
{
    global $g5, $member;

    $wr_id = isset($_GET['wr_id']) ? (int) $_GET['wr_id'] : 0;
    if (!$wr_id) {
        json_return(null, 400, '00001', '게시글 ID가 필요합니다.');
    }

    $board = get_board_db($bo_table, true);
    if (!$board['bo_table']) {
        json_return(null, 404, '00001', '존재하지 않는 게시판입니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;

    // 댓글 조회 (wr_is_comment = 1)
    $sql = "SELECT * FROM {$write_table} 
            WHERE wr_parent = ? 
            AND wr_is_comment = 1 
            ORDER BY wr_comment, wr_comment_reply";
            
    $result = sqlx::query($sql)
        ->bind($wr_id)
        ->fetch_all();

    $comments = [];
    foreach ($result as $row) {
        $is_secret = strpos($row['wr_option'], 'secret') !== false;
        $can_view = false;
        $can_edit = false;
        $can_delete = false;

        // 비밀댓글 열람 권한 체크
        if (!$is_secret) {
            $can_view = true;
        } else {
            // 본인, 원글 작성자, 관리자만 열람 가능
            $parent_write = sqlx::query("SELECT wr_name, mb_id FROM {$write_table} WHERE wr_id = ?")
                ->bind($wr_id)
                ->fetch_optional();
                
            if ($member['mb_id'] && ($member['mb_id'] === $row['mb_id'] || $member['mb_id'] === $parent_write['mb_id'])) {
                $can_view = true;
            }
            if ($member['mb_level'] >= $board['bo_admin_level']) {
                $can_view = true;
            }
        }

        // 수정/삭제 권한 체크
        if ($row['mb_id']) {
            // 회원 댓글
            if ($member['mb_id'] === $row['mb_id'] || $member['mb_level'] >= $board['bo_admin_level']) {
                $can_edit = true;
                $can_delete = true;
            }
        } else {
            // 비회원 댓글 - 비밀번호로 확인
            $can_edit = true;
            $can_delete = true;
        }

        $comments[] = [
            'wr_id' => $row['wr_id'],
            'wr_parent' => $row['wr_parent'],
            'mb_id' => $row['mb_id'],
            'wr_name' => $row['wr_name'],
            'wr_content' => $can_view ? replace_localhost_urls($row['wr_content'], G5_URL) : '비밀 댓글입니다.',
            'wr_datetime' => $row['wr_datetime'],
            'wr_option' => $row['wr_option'],
            'wr_comment' => $row['wr_comment'],
            'wr_comment_reply' => $row['wr_comment_reply'],
            'is_secret' => $is_secret,
            'can_view' => $can_view,
            'can_edit' => $can_edit,
            'can_delete' => $can_delete
        ];
    }

    json_return(['comments' => $comments], 200, '00000');
}

function POST($bo_table)
{
    global $g5, $member;

    $board = get_board_db($bo_table, true);
    if (!$board['bo_table']) {
        json_return(null, 404, '00001', '존재하지 않는 게시판입니다.');
    }

    // 댓글 쓰기 레벨 체크
    if ($member['mb_level'] < $board['bo_comment_level']) {
        json_return(null, 403, '00003', '댓글을 작성할 권한이 없습니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $wr_parent = isset($data['wr_parent']) ? (int) $data['wr_parent'] : 0;
    $wr_content = isset($data['wr_content']) ? trim($data['wr_content']) : '';
    $wr_name = isset($data['wr_name']) ? trim($data['wr_name']) : '';
    $wr_password = isset($data['wr_password']) ? trim($data['wr_password']) : '';
    $wr_secret = isset($data['wr_secret']) ? (bool) $data['wr_secret'] : false;
    $comment_id = isset($data['comment_id']) ? (int) $data['comment_id'] : 0; // 대댓글용

    if (!$wr_parent) {
        json_return(null, 400, '00001', '원글 ID가 필요합니다.');
    }

    if (!$wr_content) {
        json_return(null, 400, '00001', '댓글 내용을 입력해주세요.');
    }

    // 비회원 체크
    if (!$member['mb_id']) {
        if (!$wr_name || !$wr_password) {
            json_return(null, 400, '00001', '이름과 비밀번호를 입력해주세요.');
        }

        require_once __DIR__ . '/../../../../shared/captcha.php';
        if (!captcha_verify($data['captcha_key'] ?? '')) {
            json_return(null, 400, '00001', '자동등록방지 숫자가 틀렸습니다.');
        }
    } else {
        $wr_name = $member['mb_nick'] ?: $member['mb_name'];
    }

    $write_table = $g5['write_prefix'] . $bo_table;

    // 원글 존재 확인
    $parent = sqlx::query("SELECT * FROM {$write_table} WHERE wr_id = ?")
        ->bind($wr_parent)
        ->fetch_optional();
        
    if (!$parent || !$parent['wr_id']) {
        json_return(null, 404, '00001', '원글을 찾을 수 없습니다.');
    }

    try {
        $tx = sqlx::transaction();

        // 대댓글인 경우 wr_comment, wr_comment_reply 계산
        $wr_comment = 0;
        $wr_comment_reply = '';

        if ($comment_id) {
            // 대댓글
            $reply_to = $tx->query("SELECT * FROM {$write_table} WHERE wr_id = ? AND wr_is_comment = 1")
                ->bind($comment_id)
                ->fetch_optional();
                
            if ($reply_to && $reply_to['wr_id']) {
                $wr_comment = $reply_to['wr_comment'];

                // wr_comment_reply 계산 (A, B, C, ... AA, AB, ...)
                $sql = "SELECT MAX(wr_comment_reply) as max_reply FROM {$write_table} 
                        WHERE wr_parent = ? 
                        AND wr_comment = ? 
                        AND wr_comment_reply LIKE ?
                        AND LENGTH(wr_comment_reply) = ?";
                $like_pattern = $reply_to['wr_comment_reply'] . '%';
                $reply_len = strlen($reply_to['wr_comment_reply']) + 1;
                
                $max = $tx->query($sql)
                    ->bind($wr_parent)
                    ->bind($wr_comment)
                    ->bind($like_pattern)
                    ->bind($reply_len)
                    ->fetch_optional();

                if ($max && $max['max_reply']) {
                    $last_char = substr($max['max_reply'], -1);
                    $next_char = chr(ord($last_char) + 1);
                    $wr_comment_reply = $reply_to['wr_comment_reply'] . $next_char;
                } else {
                    $wr_comment_reply = $reply_to['wr_comment_reply'] . 'A';
                }
            }
        } else {
            // 새 댓글
            $sql = "SELECT MAX(wr_comment) as max_comment FROM {$write_table} WHERE wr_parent = ?";
            $max = $tx->query($sql)
                ->bind($wr_parent)
                ->fetch_optional();
            $wr_comment = ($max['max_comment'] ?? 0) + 1;
        }

        $wr_option = $wr_secret ? 'secret' : '';
        $wr_password_hash = $wr_password ? get_encrypt_string($wr_password) : '';
        $remote_addr = $_SERVER['REMOTE_ADDR'];
        $current_time = G5_TIME_YMDHIS;
        $mb_id_val = $member['mb_id'] ?: '';
        $mb_email_val = $member['mb_email'] ?: '';

        $sql = "INSERT INTO {$write_table} SET 
                wr_num = 0,
                wr_reply = '',
                wr_parent = ?,
                wr_is_comment = 1,
                wr_comment = ?,
                wr_comment_reply = ?,
                ca_name = '',
                wr_option = ?,
                wr_subject = '',
                wr_content = ?,
                wr_link1 = '',
                wr_link2 = '',
                wr_link1_hit = 0,
                wr_link2_hit = 0,
                wr_hit = 0,
                wr_good = 0,
                wr_nogood = 0,
                mb_id = ?,
                wr_password = ?,
                wr_name = ?,
                wr_email = ?,
                wr_homepage = '',
                wr_datetime = ?,
                wr_last = ?,
                wr_ip = ?,
                wr_file = 0,
                wr_1 = '', wr_2 = '', wr_3 = '', wr_4 = '', wr_5 = '',
                wr_6 = '', wr_7 = '', wr_8 = '', wr_9 = '', wr_10 = ''";
                
        $tx->query($sql)
            ->bind($wr_parent)
            ->bind($wr_comment)
            ->bind($wr_comment_reply)
            ->bind($wr_option)
            ->bind($wr_content)
            ->bind($mb_id_val)
            ->bind($wr_password_hash)
            ->bind($wr_name)
            ->bind($mb_email_val)
            ->bind($current_time)
            ->bind($current_time)
            ->bind($remote_addr)
            ->execute();

        $comment_id = sqlx::last_insert_id();

        // 원글의 댓글 수 증가
        $tx->query("UPDATE {$write_table} SET wr_comment = wr_comment + 1 WHERE wr_id = ?")
            ->bind($wr_parent)
            ->execute();

        $tx->commit();

        json_return(['comment_id' => $comment_id], 200, '00000', '댓글이 등록되었습니다.');

    } catch (Exception $e) {
        json_return(null, 500, '00001', '댓글 등록 중 오류가 발생했습니다: ' . $e->getMessage());
    }
}

function PUT($bo_table)
{
    global $g5, $member;

    $comment_id = isset($_GET['comment_id']) ? (int) $_GET['comment_id'] : 0;
    if (!$comment_id) {
        json_return(null, 400, '00001', '댓글 ID가 필요합니다.');
    }

    $board = get_board_db($bo_table, true);
    if (!$board['bo_table']) {
        json_return(null, 404, '00001', '존재하지 않는 게시판입니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;

    $comment = sqlx::query("SELECT * FROM {$write_table} WHERE wr_id = ? AND wr_is_comment = 1")
        ->bind($comment_id)
        ->fetch_optional();
        
    if (!$comment || !$comment['wr_id']) {
        json_return(null, 404, '00001', '댓글을 찾을 수 없습니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $wr_content = isset($data['wr_content']) ? trim($data['wr_content']) : '';
    $wr_password = isset($data['wr_password']) ? trim($data['wr_password']) : '';
    $wr_secret = isset($data['wr_secret']) ? (bool) $data['wr_secret'] : false;

    if (!$wr_content) {
        json_return(null, 400, '00001', '댓글 내용을 입력해주세요.');
    }

    // 권한 체크
    $can_edit = false;
    if ($comment['mb_id']) {
        // 회원 댓글
        if ($member['mb_id'] === $comment['mb_id'] || $member['mb_level'] >= $board['bo_admin_level']) {
            $can_edit = true;
        }
    } else {
        // 비회원 댓글 - 비밀번호 확인
        if (!$wr_password) {
            json_return(null, 400, '00001', '비밀번호를 입력해주세요.');
        }
        if (!check_password($wr_password, $comment['wr_password'])) {
            json_return(null, 403, '00003', '비밀번호가 일치하지 않습니다.');
        }
        $can_edit = true;
    }

    if (!$can_edit) {
        json_return(null, 403, '00003', '수정 권한이 없습니다.');
    }

    $wr_option = $wr_secret ? 'secret' : '';
    $current_time = G5_TIME_YMDHIS;

    sqlx::query("UPDATE {$write_table} SET 
               wr_content = ?,
               wr_option = ?,
               wr_last = ?
               WHERE wr_id = ?")
        ->bind($wr_content)
        ->bind($wr_option)
        ->bind($current_time)
        ->bind($comment_id)
        ->execute();

    json_return(null, 200, '00000', '댓글이 수정되었습니다.');
}

function DELETE($bo_table)
{
    global $g5, $member;

    $comment_id = isset($_GET['comment_id']) ? (int) $_GET['comment_id'] : 0;
    $wr_password = isset($_GET['wr_password']) ? trim($_GET['wr_password']) : '';

    if (!$comment_id) {
        json_return(null, 400, '00001', '댓글 ID가 필요합니다.');
    }

    $board = get_board_db($bo_table, true);
    if (!$board['bo_table']) {
        json_return(null, 404, '00001', '존재하지 않는 게시판입니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;

    $comment = sqlx::query("SELECT * FROM {$write_table} WHERE wr_id = ? AND wr_is_comment = 1")
        ->bind($comment_id)
        ->fetch_optional();
        
    if (!$comment || !$comment['wr_id']) {
        json_return(null, 404, '00001', '댓글을 찾을 수 없습니다.');
    }

    // 권한 체크
    $can_delete = false;
    if ($comment['mb_id']) {
        // 회원 댓글
        if ($member['mb_id'] === $comment['mb_id'] || $member['mb_level'] >= $board['bo_admin_level']) {
            $can_delete = true;
        }
    } else {
        // 비회원 댓글 - 비밀번호 확인
        if (!$wr_password) {
            json_return(null, 400, '00001', '비밀번호를 입력해주세요.');
        }
        if (!check_password($wr_password, $comment['wr_password'])) {
            json_return(null, 403, '00003', '비밀번호가 일치하지 않습니다.');
        }
        $can_delete = true;
    }

    if (!$can_delete) {
        json_return(null, 403, '00003', '삭제 권한이 없습니다.');
    }

    $wr_parent = $comment['wr_parent'];

    try {
        $tx = sqlx::transaction();

        // 댓글 삭제
        $tx->query("DELETE FROM {$write_table} WHERE wr_id = ?")
            ->bind($comment_id)
            ->execute();

        // 원글의 댓글 수 감소
        $tx->query("UPDATE {$write_table} SET wr_comment = wr_comment - 1 WHERE wr_id = ? AND wr_comment > 0")
            ->bind($wr_parent)
            ->execute();

        $tx->commit();
        
        json_return(null, 200, '00000', '댓글이 삭제되었습니다.');

    } catch (Exception $e) {
        json_return(null, 500, '00001', '댓글 삭제 중 오류가 발생했습니다.');
    }
}
