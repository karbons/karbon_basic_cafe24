import { getCartList } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async () => {
    try {
        const data = await getCartList();
        return {
            list: data.list,
            totalPrice: data.total_price,
            totalQty: data.total_qty
        };
    } catch (error) {
        console.error('Failed to load cart:', error);
        return {
            list: [],
            totalPrice: 0,
            totalQty: 0
        };
    }
};
