import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { BoardConfig } from '$lib/type/board';

export const load: PageLoad = async ({ params, fetch }) => {
	const bo_table = params.bo_table;

	if (!bo_table) {
		throw new Error('게시판 ID가 없습니다.');
	}

	try {
		// board 정보만 가져오기 위해 list api 호출 (page=1, limit=1 등 최소 데이터 요청 권장)
		const data = await apiGet<{
			board: BoardConfig;
		}>(`/bbs/${bo_table}?page=1`, fetch);

		return {
			board: data.board,
			bo_table
		};
	} catch (error: any) {
		throw new Error(error.message || '게시판 정보를 불러올 수 없습니다.');
	}
};

