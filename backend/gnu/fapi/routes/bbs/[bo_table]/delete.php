<?php
// DELETE /api/bbs/{bo_table}/delete
function DELETE($bo_table)
{
    global $g5, $member;

    // 로그인 체크 (비회원 허용 시 생략)
    // if (!$member['mb_id']) {
    //     json_return(null, 401, '00002', '로그인이 필요합니다.');
    // }

    $board = sqlx::query("select * from {$g5['board_table']} where bo_table = ?")
        ->bind($bo_table)
        ->fetch_optional();

    if (!$board) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }

    $wr_id = isset($_GET['wr_id']) ? (int) $_GET['wr_id'] : 0;

    if (!$wr_id) {
        json_return(null, 200, '00001', '글 ID가 필요합니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;
    $write = sqlx::query("select * from {$write_table} where wr_id = ?")
        ->bind($wr_id)
        ->fetch_optional();

    if (!$write) {
        json_return(null, 404, '00001', '글을 찾을 수 없습니다.');
    }

    // 삭제 권한 체크 logic
    $is_admin = ($member['mb_id'] && $member['mb_level'] == 10) ? 'super' : '';

    $is_owner = false;
    if ($write['mb_id']) {
        // 회원글
        if ($member['mb_id'] && $member['mb_id'] === $write['mb_id']) {
            $is_owner = true;
        }
    } else {
        // 비회원글
        $is_owner = true; // 비밀번호 체크로 검증
    }

    if (!$is_admin && !$is_owner) {
        json_return(null, 403, '00003', '삭제할 권한이 없습니다.');
    }

    // 비회원 글이고 관리자가 아니면 비밀번호 확인
    if (!$write['mb_id'] && !$is_admin) {
        $wr_password = isset($_GET['wr_password']) ? $_GET['wr_password'] : ''; // DELETE json body or query param? DELETE usually query or header. Using Query for now.
        if (!$wr_password) {
            // Body fallback if possible, usually DELETE allows body but server config varies.
            $data = json_decode(file_get_contents('php://input'), true);
            $wr_password = $data['wr_password'] ?? '';
        }

        if (!$wr_password) {
            json_return(null, 403, '00004', '비밀번호를 입력해주세요.');
        }
        if (!check_password($wr_password, $write['wr_password'])) {
            json_return(null, 403, '00005', '비밀번호가 일치하지 않습니다.');
        }
    }

    // 코멘트 개수 체크 (관리자는 제외)
    if (!$is_admin && $board['bo_count_delete'] > 0 && $write['wr_comment'] >= $board['bo_count_delete']) {
        json_return(null, 403, '00006', '댓글이 ' . $board['bo_count_delete'] . '개 이상 달린 글은 삭제할 수 없습니다.');
    }

    // 게시글 삭제
    // (Note: In real G5, delete_file, delete_comment, update parent/lists needed. Simple delete for now)
    sqlx::transaction(function () use ($write_table, $wr_id) {
        sqlx::query("delete from {$write_table} where wr_id = ?")
            ->bind($wr_id)
            ->execute();
    });

    json_return(null, 200, '00000', '게시글이 삭제되었습니다.');
}

