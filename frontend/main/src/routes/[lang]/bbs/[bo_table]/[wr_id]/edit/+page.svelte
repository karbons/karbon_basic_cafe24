<script lang="ts">
	import type { PageData } from "./$types";
	import Write from "$lib/skin/board/basic/Write.svelte";

	interface Props {
		data: PageData;
	}

	let { data }: Props = $props();
</script>

<div class="pt-20 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
	{#if data.board && data.write}
		<Write board={data.board} write={data.write} />
	{:else}
		<div class="p-4 bg-red-50 text-red-500 rounded-md">
			게시글 정보를 불러올 수 없습니다.
		</div>
	{/if}
</div>
