<?php
// GET /api/menu
function GET()
{
    global $g5;

    $device = isset($_GET['device']) ? $_GET['device'] : 'pc'; // pc or mobile

    // 메뉴 조회 (그누보드5는 g5_menu 테이블 사용)
    // me_use: PC 사용 여부, me_mobile_use: 모바일 사용 여부
    $device_condition = ($device === 'mobile')
        ? "me_mobile_use = 1"
        : "me_use = 1";

    $rows = sqlx::query("select * from {$g5['menu_table']} 
            where {$device_condition}
            order by me_code asc")
        ->fetch_all();

    $menus = [];
    $menu_map = [];

    // 모든 메뉴를 먼저 배열로 저장
    foreach ($rows as $row) {
        $me_code = $row['me_code'];
        $menu_map[$me_code] = [
            'me_id' => (int) $row['me_id'],
            'me_name' => $row['me_name'],
            'me_link' => $row['me_link'],
            'me_target' => $row['me_target'] ?: '_self',
            'me_code' => $me_code,
            'me_order' => (int) $row['me_order'],
            'sub' => []
        ];
    }

    // 메뉴 구조화: me_code 자릿수로 계층 판단 (2자리: 1단, 4자리: 2단...)
    foreach ($menu_map as $me_code => $menu) {
        $len = strlen($me_code);
        if ($len == 2) {
            // 최상위 메뉴 (1단)
            $menus[] = &$menu_map[$me_code];
        } else {
            // 하위 메뉴 (2단 이상): 상위 코드(앞의 n-2자리)를 가진 메뉴의 sub에 추가
            $parent_code = substr($me_code, 0, $len - 2);
            if (isset($menu_map[$parent_code])) {
                $menu_map[$parent_code]['sub'][] = &$menu_map[$me_code];
            }
        }
    }

    // 클라이언트에 전달할 최종 형식으로 변환 (참조 끊기 및 필요한 필드만 추출)
    $make_output = function ($list) use (&$make_output) {
        $out = [];
        foreach ($list as $m) {
            $item = [
                'me_id' => $m['me_id'],
                'me_name' => $m['me_name'],
                'me_link' => $m['me_link'],
                'me_target' => $m['me_target']
            ];
            if (!empty($m['sub'])) {
                $item['sub'] = $make_output($m['sub']);
            }
            $out[] = $item;
        }
        return $out;
    };

    $final_menus = $make_output($menus);

    json_return(['menus' => $final_menus], 200, '00000');
}
