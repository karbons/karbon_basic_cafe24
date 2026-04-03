import { getShopItems } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ params, url }) => {
    const ca_id = params.ca_id;
    const page = Number(url.searchParams.get('page')) || 1;
    
    try {
        const data = await getShopItems({
            ca_id,
            page,
            page_rows: 20
        });
        
        return {
            items: data.items,
            category: data.category,
            pagination: {
                total_count: data.total_count,
                total_page: data.total_page,
                current_page: data.page
            },
            ca_id
        };
    } catch (error) {
        console.error('Failed to load shop items:', error);
        return {
            items: [],
            pagination: {
                total_count: 0,
                total_page: 0,
                current_page: 1
            },
            ca_id
        };
    }
};
