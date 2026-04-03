<script lang="ts">
	import type { PageData } from "./$types";
	import View from "$lib/skin/board/basic/View.svelte";
	import { MetaTags } from "svelte-meta-tags";
	import { seoConfig, truncate, stripHtml } from "$lib/config/seo";

	interface Props {
		data: PageData;
	}

	let { data }: Props = $props();

	// 동적 SEO 데이터
	const pageTitle = data.write
		? `${data.write.wr_subject} | ${data.board.bo_subject}`
		: "게시글";
	const pageDescription = data.write
		? truncate(stripHtml(data.write.wr_content), 160)
		: "";
</script>

{#if data.write}
	<MetaTags
		title={pageTitle}
		description={pageDescription}
		openGraph={{
			type: "article",
			title: pageTitle,
			description: pageDescription,
			siteName: seoConfig.siteName,
		}}
	/>
{/if}

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
	{#if data.write}
		<View
			board={data.board}
			write={data.write}
			can_edit={data.can_edit}
			can_delete={data.can_delete}
			can_reply={data.can_reply ?? false}
			is_scraped={data.is_scraped ?? false}
		/>
	{:else}
		<div class="p-4 bg-red-50 text-red-500 rounded-md">
			게시글을 불러올 수 없습니다.
		</div>
	{/if}
</div>
