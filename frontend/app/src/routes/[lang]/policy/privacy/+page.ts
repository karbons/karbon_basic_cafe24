import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { Content } from '$lib/type/content';

export const load: PageLoad = async ({ fetch }) => {
    try {
        // Gnuboard standard ID for Privacy Policy is 'privacy'
        const content = await apiGet<Content>(`/policy/privacy`, fetch);

        return {
            content
        };
    } catch (error: any) {
        // Content might not exist if not configured in admin
        console.error('Failed to load privacy policy:', error);
        return {
            content: null,
            error: error.message || '개인정보처리방침을 불러올 수 없습니다.'
        };
    }
};
