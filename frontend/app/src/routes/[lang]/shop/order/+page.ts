import { getCartList } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async () => {
    try {
        const data = await getCartList();
        if (data.list.length === 0) {
            // 장바구니가 비어있으면 리다이렉트 (실제 환경에서는 throw redirect(302, '/shop/cart') 사용)
        }
        return {
            list: data.list,
            totalPrice: data.total_price
        };
    } catch (error) {
        console.error('Failed to load order data:', error);
        return {
            list: [],
            totalPrice: 0
        };
    }
};
