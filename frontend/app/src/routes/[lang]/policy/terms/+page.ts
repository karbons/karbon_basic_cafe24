import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { Content } from '$lib/type/content';

export const load: PageLoad = async ({ fetch }) => {
    try {
        // Gnuboard standard ID for Terms of Service is 'provision'
        const content = await apiGet<Content>(`/policy/terms`, fetch);

        return {
            content
        };
    } catch (error: any) {
        // Content might not exist if not configured in admin
        console.error('Failed to load terms of service:', error);
        return {
            content: null,
            error: error.message || '이용약관을 불러올 수 없습니다.'
        };
    }
};
