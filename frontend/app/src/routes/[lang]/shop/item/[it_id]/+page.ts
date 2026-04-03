import { getShopItem } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ params }) => {
    const it_id = params.it_id;
    
    try {
        const data = await getShopItem(it_id);
        return {
            item: data.item,
            options: data.options,
            relations: data.relations
        };
    } catch (error: any) {
        throw new Error(error.message || '상품 정보를 불러올 수 없습니다.');
    }
};
