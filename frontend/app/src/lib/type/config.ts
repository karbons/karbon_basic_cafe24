export interface SiteConfig {
    cf_title: string;
    cf_admin_name: string; // 관리자명 (대표자)
    cf_admin_email: string;

    // 주소 / 연락처
    cf_addr1?: string;
    cf_addr2?: string;
    cf_zip?: string;
    cf_tel?: string;
    cf_fax?: string; // cf_1 (가정)

    // 정보
    cf_info1?: string; // 사업자등록번호
    cf_info2?: string; // 통신판매업신고
    cf_info3?: string; // 추가정보 (대표자명 등 중복될 수 있음)

    // 담당자
    cf_privacy_officer?: string; // 개인정보관리책임자

    // 쇼핑몰 사업자정보 (g5_shop_default 테이블)
    shop_company_name?: string;
    shop_company_owner?: string;
    shop_saupja_no?: string;
    shop_tongsin_no?: string;
    shop_tel?: string;
    shop_fax?: string;
    shop_zip?: string;
    shop_addr?: string;
    shop_info_name?: string;
    shop_info_email?: string;

    // 정책 유무 확인용
    has_privacy_policy?: boolean;
    has_service_policy?: boolean;

    // 앱 버전 및 스토어 링크
    app_version?: string;
    play_store_link?: string;
    app_store_link?: string;
}

