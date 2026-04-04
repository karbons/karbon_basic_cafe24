import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { Content } from '$lib/type/content';

export const load: PageLoad = async ({ params, fetch }) => {
    const co_id = params.co_id;

    if (!co_id) {
        throw new Error('컨텐츠 ID가 없습니다.');
    }

    try {
        const content = await apiGet<Content>(`/content/${co_id}`, fetch);
        return {
            content,
            co_id
        };
    } catch (error: any) {
        throw new Error(error.message || '컨텐츠를 불러올 수 없습니다.');
    }
};
