<?php
/**
 * @api {get} /chat/settings Get Chat Room Settings
 * @api {post} /chat/settings Update Chat Room Settings
 */
require_once __DIR__ . '/../_middleware.php';

function GET()
{
    global $member;

    // 인증 확인
    if (!$member) {
        return json_return(['error' => 'Unauthorized'], 401);
    }

    $mb_id = $member['mb_id'];

    // 특정 방의 설정 조회
    $room_id = $_GET['room_id'] ?? null;

    if ($room_id) {
        // 단일 방 설정
        $setting = sqlx::query("SELECT * FROM g5_chat_room_settings WHERE mb_id = ? AND room_id = ?")
            ->bind($mb_id)
            ->bind($room_id)
            ->fetch_optional();

        // 설정이 없으면 기본값 반환
        if (!$setting) {
            $setting = [
                'room_id' => $room_id,
                'mb_id' => $mb_id,
                'room_alias' => null,
                'room_image' => null,
                'bg_color' => null,
                'bg_image' => null,
                'is_pinned' => 0,
                'is_favorite' => 0,
                'is_alarm' => 1
            ];
        } else {
            // 숫자로 변환 (MySQL은 문자열로 반환하므로)
            $setting['is_pinned'] = (int) $setting['is_pinned'];
            $setting['is_favorite'] = (int) $setting['is_favorite'];
            $setting['is_alarm'] = (int) $setting['is_alarm'];
        }

        json_return($setting);
    } else {
        // 내 모든 방 설정 (목록 로딩시 필요할 수 있음 - 예: 핀 고정 목록)
        $rows = sqlx::query("SELECT * FROM g5_chat_room_settings WHERE mb_id = ?")
            ->bind($mb_id)
            ->fetch_all();

        $settingsMap = [];
        foreach ($rows as $row) {
            $row['is_pinned'] = (int) $row['is_pinned'];
            $row['is_favorite'] = (int) $row['is_favorite'];
            $row['is_alarm'] = (int) $row['is_alarm'];
            $settingsMap[$row['room_id']] = $row;
        }

        json_return($settingsMap);
    }
}

function POST()
{
    global $member;

    // 인증 확인
    if (!$member) {
        return json_return(['error' => 'Unauthorized'], 401);
    }

    $mb_id = $member['mb_id'];

    // 설정 업데이트
    $data = json_decode(file_get_contents('php://input'), true);
    $room_id = $data['room_id'] ?? null;

    if (!$room_id) {
        return json_return(['error' => 'Room ID is required'], 400);
    }

    // 허용된 필드만 업데이트
    $allowed_fields = ['room_alias', 'room_image', 'bg_color', 'bg_image', 'is_pinned', 'is_favorite', 'is_alarm'];
    $set_clauses = [];
    $params = [];

    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $set_clauses[] = "`$field` = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($set_clauses)) {
        return json_return(['success' => true, 'message' => 'No changes']);
    }

    // 설정이 존재하는지 확인
    $exists = sqlx::query("SELECT id FROM g5_chat_room_settings WHERE mb_id = ? AND room_id = ?")
        ->bind($mb_id)
        ->bind($room_id)
        ->fetch_optional();

    if ($exists) {
        // UPDATE
        $query = sqlx::query("UPDATE g5_chat_room_settings SET " . implode(', ', $set_clauses) . " WHERE mb_id = ? AND room_id = ?");
        foreach ($params as $param) {
            $query->bind($param);
        }
        $query->bind($mb_id)->bind($room_id)->execute();
    } else {
        // INSERT (upsert logic needed because we might be updating just one field)
        // 기본값을 채워서 INSERT
        $defaults = [
            'room_alias' => null,
            'room_image' => null,
            'bg_color' => null,
            'bg_image' => null,
            'is_pinned' => 0,
            'is_favorite' => 0,
            'is_alarm' => 1
        ];

        $insert_data = array_merge($defaults, $data);

        $fields = ['room_id', 'mb_id'];
        $placeholders = ['?', '?'];
        $insert_params = [$room_id, $mb_id];

        foreach ($allowed_fields as $field) {
            $fields[] = "`$field`";
            $placeholders[] = '?';
            $insert_params[] = isset($insert_data[$field]) ? $insert_data[$field] : (isset($defaults[$field]) ? $defaults[$field] : '');
        }

        $query = sqlx::query("INSERT INTO g5_chat_room_settings (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")");
        foreach ($insert_params as $param) {
            $query->bind($param);
        }
        $query->execute();
    }

    json_return(['success' => true]);
}
