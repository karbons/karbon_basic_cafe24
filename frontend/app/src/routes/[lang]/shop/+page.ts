import { getShopItems } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ url }) => {
    const page = Number(url.searchParams.get('page')) || 1;
    const it_type = Number(url.searchParams.get('it_type')) || 0;
    
    try {
        const data = await getShopItems({
            it_type,
            page,
            page_rows: 20
        });
        
        return {
            items: data.items,
            pagination: {
                total_count: data.total_count,
                total_page: data.total_page,
                current_page: data.page
            }
        };
    } catch (error) {
        console.error('Failed to load shop items:', error);
        return {
            items: [],
            pagination: {
                total_count: 0,
                total_page: 0,
                current_page: 1
            }
        };
    }
};
