import { getOrderList } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ url }) => {
    const page = Number(url.searchParams.get('page')) || 1;
    
    try {
        const data = await getOrderList(page);
        return {
            list: data.list,
            pagination: {
                total_count: data.total_count,
                total_page: data.total_page,
                current_page: data.page
            }
        };
    } catch (error) {
        console.error('Failed to load order history:', error);
        return {
            list: [],
            pagination: {
                total_count: 0,
                total_page: 0,
                current_page: 1
            }
        };
    }
};
