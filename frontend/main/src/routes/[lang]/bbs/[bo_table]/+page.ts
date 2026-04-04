import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { BoardConfig, Write } from '$lib/type/board';

export const load: PageLoad = async ({ params, depends }) => {
	// SvelteKit의 invalidation을 위한 의존성 등록
	depends('app:board');

	const bo_table = params.bo_table;

	if (!bo_table) {
		throw new Error('게시판 ID가 없습니다.');
	}

	try {
		const data = await apiGet<{
			board: BoardConfig;
			notice_list: Write[];
			list: Write[];
			pagination: {
				current_page: number;
				total_page: number;
				total_count: number;
				page_rows: number;
			};
		}>(`/bbs/${bo_table}?page=1`);

		return {
			board: data.board,
			noticeList: data.notice_list || [],
			list: data.list || [],
			total_count: data.pagination.total_count,
			page_current: data.pagination.current_page,
			pagination: data.pagination,
			bo_table
		};
	} catch (error: any) {
		throw new Error(error.message || '게시판을 불러올 수 없습니다.');
	}
};

