import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { BoardConfig, Write } from '$lib/type/board';

export const load: PageLoad = async ({ params, depends, fetch, url }) => {
	// SvelteKit의 invalidation을 위한 의존성 등록
	depends('app:post');

	const bo_table = params.bo_table;
	const wr_id = params.wr_id;
	const wr_password = url.searchParams.get('wr_password');

	if (!bo_table || !wr_id) {
		throw new Error('게시판 ID 또는 글 ID가 없습니다.');
	}

	try {
		let apiUrl = `/bbs/${bo_table}/${wr_id}`;
		if (wr_password) {
			apiUrl += `?wr_password=${encodeURIComponent(wr_password)}`;
		}

		const data = await apiGet<{
			board: BoardConfig;
			write: Write;
			can_edit: boolean;
		}>(apiUrl, fetch);

		if (!data.can_edit) {
			throw new Error('수정할 권한이 없습니다.');
		}

		return {
			board: data.board,
			write: data.write,
			bo_table,
			wr_id
		};
	} catch (error: any) {
		throw new Error(error.message || '게시글을 불러올 수 없습니다.');
	}
};

