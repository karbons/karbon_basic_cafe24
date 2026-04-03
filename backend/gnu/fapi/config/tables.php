<?php
/**
 * 테이블명 설정
 * 
 * $g5 전역 변수 대체용 테이블 설정 파일입니다.
 * 그누보드 common.php 없이 독립적으로 테이블명을 관리합니다.
 */

class Tables
{
    /** @var string 테이블 접두사 */
    private static string $prefix = 'g5_';

    /** @var string 쇼핑몰 테이블 접두사 */
    private static string $shopPrefix = 'g5_shop_';

    /** @var string 게시판 글 테이블 접두사 */
    private static string $writePrefix = 'g5_write_';

    /** @var array 테이블명 정의 */
    private static array $tables = [
        // 기본 테이블
        'config_table' => 'g5_config',
        'member_table' => 'g5_member',
        'member_refresh' => 'g5_member_refresh',
        'group_table' => 'g5_group',
        'group_member_table' => 'g5_group_member',
        'board_table' => 'g5_board',
        'board_file_table' => 'g5_board_file',
        'board_new_table' => 'g5_board_new',
        'board_good_table' => 'g5_board_good',
        'menu_table' => 'g5_menu',
        'point_table' => 'g5_point',
        'visit_table' => 'g5_visit',
        'visit_sum_table' => 'g5_visit_sum',
        'login_table' => 'g5_login',
        'auth_table' => 'g5_auth',
        'poll_table' => 'g5_poll',
        'poll_etc_table' => 'g5_poll_etc',
        'content_table' => 'g5_content',
        'scrap_table' => 'g5_scrap',
        'memo_table' => 'g5_memo',
        'popular_table' => 'g5_popular',
        'faq_table' => 'g5_faq',
        'faq_master_table' => 'g5_faq_master',
        'qa_config_table' => 'g5_qa_config',
        'qa_content_table' => 'g5_qa_content',
        'uniq_id_table' => 'g5_uniq_id',
        'autosave_table' => 'g5_autosave',
        'cert_history_table' => 'g5_cert_history',
        'mail_table' => 'g5_mail',
        'new_win_table' => 'g5_new_win',
        'social_profile_table' => 'g5_social_profile',

        // 쇼핑몰 테이블
        'g5_shop_item_table' => 'g5_shop_item',
        'g5_shop_category_table' => 'g5_shop_category',
        'g5_shop_cart_table' => 'g5_shop_cart',
        'g5_shop_order_table' => 'g5_shop_order',
        'g5_shop_order_item_table' => 'g5_shop_order_item',
        'g5_shop_order_data_table' => 'g5_shop_order_data',
        'g5_shop_wish_table' => 'g5_shop_wish',
        'g5_shop_item_option_table' => 'g5_shop_item_option',
        'g5_shop_item_stock_table' => 'g5_shop_item_stock',
        'g5_shop_item_use_table' => 'g5_shop_item_use',
        'g5_shop_item_qa_table' => 'g5_shop_item_qa',
        'g5_shop_item_relation_table' => 'g5_shop_item_relation',
        'g5_shop_coupon_table' => 'g5_shop_coupon',
        'g5_shop_coupon_log_table' => 'g5_shop_coupon_log',
        'g5_shop_coupon_zone_table' => 'g5_shop_coupon_zone',
        'g5_shop_default_table' => 'g5_shop_default',
        'g5_shop_event_table' => 'g5_shop_event',
        'g5_shop_event_item_table' => 'g5_shop_event_item',
        'g5_shop_sendcost_table' => 'g5_shop_sendcost',
        'g5_shop_banner_table' => 'g5_shop_banner',
        'g5_shop_personalpay_table' => 'g5_shop_personalpay',
    ];

    /**
     * 테이블명 조회
     * 
     * @param string $key 테이블 키 (예: 'member_table')
     * @return string 테이블명
     */
    public static function get(string $key): string
    {
        return self::$tables[$key] ?? '';
    }

    /**
     * 게시판 글 테이블명 생성
     * 
     * @param string $bo_table 게시판 테이블명
     * @return string 글 테이블명 (예: g5_write_free)
     */
    public static function write(string $bo_table): string
    {
        return self::$writePrefix . $bo_table;
    }

    /**
     * 모든 테이블명 반환 ($g5 호환용)
     * 
     * @return array 테이블 배열
     */
    public static function all(): array
    {
        return array_merge(self::$tables, [
            'write_prefix' => self::$writePrefix,
        ]);
    }

    /**
     * 접두사 반환
     */
    public static function prefix(): string
    {
        return self::$prefix;
    }
}

// $g5 전역 변수 호환성 지원
$g5 = Tables::all();
