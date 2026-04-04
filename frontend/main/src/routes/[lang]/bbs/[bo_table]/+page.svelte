<script lang="ts">
	import type { PageData } from "./$types";
	import List from "$lib/skin/board/basic/List.svelte";

	interface Props {
		data: PageData;
	}

	let { data }: Props = $props();
	import { pageTitle } from "$lib/store/ui";

	$effect(() => {
		if (data.board) {
			$pageTitle = data.board.bo_subject;
		}
		return () => {
			$pageTitle = "";
		};
	});
</script>

<div class="pt-20 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
	{#if data.board}
		<div class="mb-8 hidden md:block">
			<h1 class="text-3xl font-bold">{data.board.bo_subject}</h1>
		</div>

		<List
			board={data.board}
			list={data.list}
			total_count={data.total_count}
			page_current={data.page_current}
		/>
	{:else}
		<div class="p-4 bg-red-50 text-red-500 rounded-md">
			게시판 정보를 불러올 수 없습니다.
		</div>
	{/if}
</div>
