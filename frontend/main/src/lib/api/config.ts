import { apiGet } from './api';
import type { SiteConfig } from '$lib/type/config';
export type { SiteConfig };

export async function getConfig(): Promise<SiteConfig> {
    // 실제 API 연동 전까지 Mock 데이터 반환 (필요시 주석 해제하여 테스트)
    /*
    return {
        cf_title: 'Gnuboard Karbon',
        cf_admin_email: 'admin@gnu.com',
        cf_addr: '서울시 강남구 테헤란로 123',
        cf_tel: '02-1234-5678',
        cf_info1: '사업자등록번호: 123-45-67890',
        cf_info2: '통신판매업신고: 제2024-서울강남-0000호',
        cf_info3: '대표: 홍길동'
    };
    */

    // 실제 API 호출
    return await apiGet<SiteConfig>('/site');
}
