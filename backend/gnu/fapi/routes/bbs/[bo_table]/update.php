<?php
// PUT /api/bbs/{bo_table}/update
function PUT($bo_table)
{
    global $g5, $member;

    // 로그인 체크 (비회원 글쓰기 허용 시 생략 -> 권한 체크에서 처리)
    // if (!$member['mb_id']) {
    //    json_return(null, 401, '00002', '로그인이 필요합니다.');
    // }

    $board = get_board_db($bo_table, true);

    if (!$board || !$board['bo_table']) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['wr_id'])) {
        json_return(null, 200, '00001', '글 ID가 필요합니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;
    $write = get_write($write_table, $data['wr_id']);

    if (!$write) {
        json_return(null, 404, '00001', '글을 찾을 수 없습니다.');
    }

    // 수정 권한 체크 logic
    $is_admin = ($member['mb_id'] && $member['mb_level'] == 10) ? 'super' : '';
    // Group admin logic could be added here if needed, sticking to super admin for now or exact owner

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
        json_return(null, 403, '00003', '수정할 권한이 없습니다.');
    }

    // 비회원 글이고 관리자가 아니면 비밀번호 확인
    if (!$write['mb_id'] && !$is_admin) {
        if (!isset($data['wr_password']) || !$data['wr_password']) {
            json_return(null, 403, '00004', '비밀번호를 입력해주세요.');
        }
        // G5 Password check (sql_password function needed, or simple comparison if hashed)
        // Assuming standard G5 hash
        if (!check_password($data['wr_password'], $write['wr_password'])) {
            json_return(null, 403, '00005', '비밀번호가 일치하지 않습니다.');
        }
    }

    $sql = "update {$write_table}
            set wr_subject = ?,
                wr_content = ?,
                wr_last = ?,
                ca_name = ?
            where wr_id = ?";

    sqlx::query($sql)
        ->bind($data['wr_subject'] ?? $write['wr_subject'])
        ->bind($data['wr_content'] ?? $write['wr_content'])
        ->bind(G5_TIME_YMDHIS)
        ->bind($data['ca_name'] ?? $write['ca_name'])
        ->bind($data['wr_id'])
        ->execute();

    json_return(['wr_id' => $data['wr_id']], 200, '00000', '게시글이 수정되었습니다.');
}

// POST /api/bbs/{bo_table}/update
// 파일 업로드를 포함한 수정 (Write.svelte에서 FormData 사용 시 POST 요청)
function POST($bo_table)
{
    global $g5, $member;

    $board = get_board_db($bo_table, true);
    if (!$board || !$board['bo_table']) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }

    $data = $_POST;
    if (empty($data)) {
        $input = file_get_contents('php://input');
        if ($input) {
            $data = json_decode($input, true);
        }
    }

    if (empty($data['wr_id'])) {
        json_return(null, 200, '00001', '글 ID가 필요합니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;
    $write = get_write($write_table, $data['wr_id']);

    if (!$write) {
        json_return(null, 404, '00001', '글을 찾을 수 없습니다.');
    }

    // 수정 권한 체크
    $is_admin = ($member['mb_id'] && $member['mb_level'] == 10) ? 'super' : '';
    $is_owner = false;
    if ($write['mb_id']) {
        if ($member['mb_id'] && $member['mb_id'] === $write['mb_id']) {
            $is_owner = true;
        }
    } else {
        $is_owner = true;
    }

    if (!$is_admin && !$is_owner) {
        json_return(null, 403, '00003', '수정할 권한이 없습니다.');
    }

    // 비밀번호 체크
    if (!$write['mb_id'] && !$is_admin) {
        if (!isset($data['wr_password']) || !$data['wr_password']) {
            json_return(null, 403, '00004', '비밀번호를 입력해주세요.');
        }
        if (!check_password($data['wr_password'], $write['wr_password'])) {
            json_return(null, 403, '00005', '비밀번호가 일치하지 않습니다.');
        }
    }

    // 옵션 처리
    $wr_option = $data['wr_option'] ?? '';
    if (empty($wr_option)) {
        // 기존 옵션 유지 혹은 재설정? 보통 수정시는 재설정.
        // 하지만 기존 값이 없으면 유지하는 것이 안전
        if (isset($data['secret']) && $data['secret'])
            $wr_option .= 'secret';
        if (isset($data['html']) && $data['html'])
            $wr_option .= $wr_option ? ',html1' : 'html1';
        if (empty($wr_option))
            $wr_option = $write['wr_option']; // 입력 없으면 기존 유지
    }

    try {
        sqlx::transaction(function () use ($g5, $write_table, $bo_table, $data, $write, $wr_option) {
            $sql = "update {$write_table}
                    set wr_subject = ?,
                        wr_content = ?,
                        wr_last = ?,
                        wr_option = ?,
                        ca_name = ?
                    where wr_id = ?";

            sqlx::query($sql)
                ->bind($data['wr_subject'] ?? $write['wr_subject'])
                ->bind($data['wr_content'] ?? $write['wr_content'])
                ->bind(G5_TIME_YMDHIS)
                ->bind($wr_option)
                ->bind($data['ca_name'] ?? $write['ca_name'])
                ->bind($data['wr_id'])
                ->execute();

            // 파일 업로드 처리
            if (isset($_FILES['bf_file']) && is_array($_FILES['bf_file']['name'])) {
                $file_count = count($_FILES['bf_file']['name']);
                $dest_dir = G5_DATA_PATH . '/file/' . $bo_table;
                if (!is_dir($dest_dir)) {
                    @mkdir($dest_dir, G5_DIR_PERMISSION);
                    @chmod($dest_dir, G5_DIR_PERMISSION);
                }
                $chars_array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['bf_file']['name'][$i]) {
                        $filename = $_FILES['bf_file']['name'][$i];
                        if (preg_match("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", $filename))
                            continue;

                        shuffle($chars_array);
                        $shuffle = implode('', $chars_array);
                        $file_name = abs(ip2long($_SERVER['REMOTE_ADDR'])) . '_' . substr($shuffle, 0, 8) . '_' . str_replace('%', '', urlencode(str_replace(' ', '_', $filename)));
                        $dest_file = $dest_dir . '/' . $file_name;

                        move_uploaded_file($_FILES['bf_file']['tmp_name'][$i], $dest_file);
                        @chmod($dest_file, G5_FILE_PERMISSION);

                        $filesize = $_FILES['bf_file']['size'][$i];
                        $image_size = @getimagesize($dest_file);

                        // 기존 파일 삭제 logic 생략 (덮어쓰기 형태로 DB 업데이트)
                        // 실제로는 기존 파일 삭제해주는 것이 좋음:
                        $row = sqlx::query(" select bf_file from {$g5['board_file_table']} where bo_table = ? and wr_id = ? and bf_no = ? ")
                            ->bind($bo_table)
                            ->bind($data['wr_id'])
                            ->bind($i)
                            ->fetch_optional();

                        if ($row && $row['bf_file']) {
                            @unlink($dest_dir . '/' . $row['bf_file']);
                            // DB Record exists, update it
                            $sql = " update {$g5['board_file_table']}
                                set bf_source = ?,
                                    bf_file = ?,
                                    bf_filesize = ?,
                                    bf_width = ?,
                                    bf_height = ?,
                                    bf_type = ?,
                                    bf_datetime = ?
                                where bo_table = ? and wr_id = ? and bf_no = ? ";
                            
                            sqlx::query($sql)
                                ->bind($filename)
                                ->bind($file_name)
                                ->bind($filesize)
                                ->bind($image_size[0] ?? 0)
                                ->bind($image_size[1] ?? 0)
                                ->bind($image_size[2] ?? 0)
                                ->bind(G5_TIME_YMDHIS)
                                ->bind($bo_table)
                                ->bind($data['wr_id'])
                                ->bind($i)
                                ->execute();
                        } else {
                            // Insert
                            $sql = " insert into {$g5['board_file_table']}
                                set bo_table = ?,
                                    wr_id = ?,
                                    bf_no = ?,
                                    bf_source = ?,
                                    bf_file = ?,
                                    bf_download = 0,
                                    bf_content = '',
                                    bf_filesize = ?,
                                    bf_width = ?,
                                    bf_height = ?,
                                    bf_type = ?, 
                                    bf_datetime = ? ";
                            
                            sqlx::query($sql)
                                ->bind($bo_table)
                                ->bind($data['wr_id'])
                                ->bind($i)
                                ->bind($filename)
                                ->bind($file_name)
                                ->bind($filesize)
                                ->bind($image_size[0] ?? 0)
                                ->bind($image_size[1] ?? 0)
                                ->bind($image_size[2] ?? 0)
                                ->bind(G5_TIME_YMDHIS)
                                ->execute();
                        }
                    }
                }

                // Update wr_file count
                $cnt = sqlx::query(" select count(*) as cnt from {$g5['board_file_table']} where bo_table = ? and wr_id = ? ")
                    ->bind($bo_table)
                    ->bind($data['wr_id'])
                    ->fetch_one();
                
                sqlx::query(" update {$write_table} set wr_file = ? where wr_id = ? ")
                    ->bind($cnt['cnt'])
                    ->bind($data['wr_id'])
                    ->execute();
            }
        });
    } catch (Exception $e) {
        json_return(null, 500, '00005', 'DB Error: ' . $e->getMessage());
    }

    json_return(['wr_id' => $data['wr_id']], 200, '00000', '게시글이 수정되었습니다.');
}

