import { getOrderDetail } from '$lib/api/shop';
import type { PageLoad } from './$types';

export const load: PageLoad = async ({ params }) => {
    const od_id = params.od_id;
    
    try {
        const data = await getOrderDetail(od_id);
        return {
            order: data
        };
    } catch (error: any) {
        throw new Error(error.message || '주문 상세 정보를 불러올 수 없습니다.');
    }
};
