<?php
// POST /api/bbs/{bo_table}/write
function POST($bo_table)
{
    global $g5, $member;

    $board = get_board_db($bo_table, true);

    if (!$board || !$board['bo_table']) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }

    // 글쓰기 권한 체크
    $is_guest = !$member['mb_id'];
    if ($is_guest && $board['bo_write_level'] > 1) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    if (!$is_guest && $member['mb_level'] < $board['bo_write_level']) {
        json_return(null, 403, '00003', '글을 쓸 권한이 없습니다.');
    }

    // 데이터 처리 (JSON or POST)
    $data = $_POST;
    if (empty($data)) {
        $input = file_get_contents('php://input');
        if ($input) {
            $data = json_decode($input, true);
        }
    }

    if (empty($data['wr_subject']) || empty($data['wr_content'])) {
        json_return(null, 200, '00001', '제목과 내용을 입력해주세요.');
    }

    // 비회원 입력 체크
    if ($is_guest) {
        if (empty($data['wr_name']) || empty($data['wr_password'])) {
            json_return(null, 200, '00004', '이름과 비밀번호를 입력해주세요.');
        }

        require_once __DIR__ . '/../../../../shared/captcha.php';
        if (!captcha_verify($data['captcha_key'] ?? '')) {
            json_return(null, 200, '00006', '자동등록방지 숫자가 틀렸습니다.');
        }
    }

    $write_table = $g5['write_prefix'] . $bo_table;

    // Transaction for atomicity
    try {
        $wr_id = sqlx::transaction(function () use ($g5, $write_table, $bo_table, $data, $member, $is_guest) {
            // 그누보드 write_update.php 로직 활용
            // 간단한 버전 구현
            $sql = "select max(wr_num) as max_wr_num from {$write_table}";
            $max_wr_num = sqlx::query($sql)->fetch_scalar();
            $wr_num = (int) $max_wr_num + 1;
            $wr_reply = '';

            $wr_name = $is_guest ? $data['wr_name'] : $member['mb_name'];
            $mb_id = $is_guest ? '' : $member['mb_id'];

            $wr_password = '';
            if ($is_guest && !empty($data['wr_password'])) {
                $wr_password = get_encrypt_string($data['wr_password']); // G5 standard hash
            }

            $wr_email = $is_guest ? ($data['wr_email'] ?? '') : $member['mb_email'];
            $wr_homepage = $is_guest ? ($data['wr_homepage'] ?? '') : $member['mb_homepage'];

            // 옵션 처리
            $wr_option = $data['wr_option'] ?? '';
            if (empty($wr_option)) {
                if (isset($data['secret']) && $data['secret'])
                    $wr_option .= 'secret';
                if (isset($data['html']) && $data['html'])
                    $wr_option .= $wr_option ? ',html1' : 'html1';
            }

            $sql = "insert into {$write_table}
                    set wr_num = ?,
                        wr_reply = '',
                        wr_parent = ?,
                        wr_is_comment = 0,
                        wr_subject = ?,
                        wr_content = ?,
                        wr_name = ?,
                        mb_id = ?,
                        wr_password = ?,
                        wr_email = ?,
                        wr_homepage = ?,
                        wr_datetime = ?,
                        wr_last = ?,
                        wr_ip = ?,
                        wr_option = ?,
                        ca_name = ?";

            $wr_id = sqlx::query($sql)
                ->bind($wr_num)
                ->bind($wr_num)
                ->bind($data['wr_subject'])
                ->bind($data['wr_content'])
                ->bind($wr_name)
                ->bind($mb_id)
                ->bind($wr_password)
                ->bind($wr_email)
                ->bind($wr_homepage)
                ->bind(G5_TIME_YMDHIS)
                ->bind(G5_TIME_YMDHIS)
                ->bind($_SERVER['REMOTE_ADDR'])
                ->bind($wr_option)
                ->bind($data['ca_name'] ?? '')
                ->execute_insert_id();

            // Parent update required for G5 structure
            sqlx::query("update {$write_table} set wr_parent = ? where wr_id = ?")
                ->bind($wr_id)
                ->bind($wr_id)
                ->execute();

            // 파일 업로드 처리
            $wr_file = 0;
            if (isset($_FILES['bf_file']) && is_array($_FILES['bf_file']['name'])) {
                $file_count = count($_FILES['bf_file']['name']);
                $upload = array();

                $dest_dir = G5_DATA_PATH . '/file/' . $bo_table;
                if (!is_dir($dest_dir)) {
                    @mkdir($dest_dir, G5_DIR_PERMISSION);
                    @chmod($dest_dir, G5_DIR_PERMISSION);
                }

                $chars_array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['bf_file']['name'][$i]) {
                        $filename = $_FILES['bf_file']['name'][$i];
                        // 확장자 필터링
                        if (preg_match("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", $filename)) {
                            continue;
                        }

                        // 파일명 생성
                        shuffle($chars_array);
                        $shuffle = implode('', $chars_array);
                        $file_name = abs(ip2long($_SERVER['REMOTE_ADDR'])) . '_' . substr($shuffle, 0, 8) . '_' . str_replace('%', '', urlencode(str_replace(' ', '_', $filename)));

                        $dest_file = $dest_dir . '/' . $file_name;
                        move_uploaded_file($_FILES['bf_file']['tmp_name'][$i], $dest_file);
                        @chmod($dest_file, G5_FILE_PERMISSION);

                        $upload[$i]['file'] = $file_name;
                        $upload[$i]['source'] = $filename;
                        $upload[$i]['filesize'] = $_FILES['bf_file']['size'][$i];
                        $image_size = @getimagesize($dest_file);
                        $upload[$i]['image'] = $image_size;

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
                            ->bind($wr_id)
                            ->bind($i)
                            ->bind($filename)
                            ->bind($file_name)
                            ->bind($upload[$i]['filesize'])
                            ->bind($image_size[0] ?? 0)
                            ->bind($image_size[1] ?? 0)
                            ->bind($image_size[2] ?? 0)
                            ->bind(G5_TIME_YMDHIS)
                            ->execute();
                            
                        $wr_file++;
                    }
                }

                if ($wr_file > 0) {
                    sqlx::query(" update {$write_table} set wr_file = ? where wr_id = ? ")
                        ->bind($wr_file)
                        ->bind($wr_id)
                        ->execute();
                }
            }
            
            return $wr_id;
        });
    } catch (Exception $e) {
        json_return(null, 500, '00005', 'DB Error: ' . $e->getMessage());
    }

    json_return(['wr_id' => $wr_id], 200, '00000', '게시글이 작성되었습니다.');
}

