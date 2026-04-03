import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { BoardConfig, Write } from '$lib/type/board';

export const load: PageLoad = async ({ params, depends }) => {
	// SvelteKit의 invalidation을 위한 의존성 등록
	depends('app:post');

	const bo_table = params.bo_table;
	const wr_id = params.wr_id;

	if (!bo_table || !wr_id) {
		throw new Error('게시판 ID 또는 글 ID가 없습니다.');
	}

	try {
		const data = await apiGet<{
			board: BoardConfig;
			write: Write;
			prev: {
				wr_id: number;
				wr_subject: string;
			} | null;
			next: {
				wr_id: number;
				wr_subject: string;
			} | null;
			can_edit: boolean;
			can_delete: boolean;
		}>(`/bbs/${bo_table}/${wr_id}`);

		return {
			board: data.board,
			write: data.write,
			prev: data.prev,
			next: data.next,
			can_edit: data.can_edit,
			can_delete: data.can_delete,
			bo_table,
			wr_id
		};
	} catch (error: any) {
		throw new Error(error.message || '게시글을 불러올 수 없습니다.');
	}
};

