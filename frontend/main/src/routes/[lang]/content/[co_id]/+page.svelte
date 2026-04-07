<script lang="ts">
	import type { PageData } from "./$types";
	import { marked } from "marked";
	import { pageTitle } from "$lib/store/ui";

	interface Props {
		data: PageData;
	}

	let { data }: Props = $props();

	// PageLoad에서 이미 에러 처리를 하므로 data.content는 존재한다고 가정
	// data.content가 없을 경우를 대비한 안전한 접근
	let content = $derived(data.content);

	// 마크다운 형식인지 감지
	function isMarkdown(text: string): boolean {
		if (!text) return false;
		// 마크다운 패턴 감지: # 헤더, ``` 코드블록, **굵게**, [링크](url), - 리스트 등
		const markdownPatterns = [
			/^#{1,6}\s+/m, // # 헤더
			/```[\s\S]*?```/, // 코드 블록
			/\*\*[^*]+\*\*/, // **굵게**
			/\*[^*]+\*/, // *기울임*
			/\[[^\]]+\]\([^)]+\)/, // [링크](url)
			/^[-*+]\s+/m, // - 리스트
			/^\d+\.\s+/m, // 1. 숫자 리스트
			/^>\s+/m, // > 인용
		];
		return markdownPatterns.some((pattern) => pattern.test(text));
	}

	// 렌더링된 콘텐츠
	let renderedContent = $derived.by(() => {
		if (!content?.co_content) return "";
		const raw = content.co_content;

		// 마크다운 형식이면 마크다운으로 렌더링
		if (isMarkdown(raw)) {
			return marked.parse(raw) as string;
		}
		// 아니면 그대로 HTML 반환
		return raw;
	});

	$effect(() => {
		if (content) {
			$pageTitle = content.co_subject;
		}
		return () => {
			$pageTitle = "";
		};
	});
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-8">
	{#if content}
		<h1 class="hidden md:block text-3xl font-bold mb-8 pb-4 border-b">
			{content.co_subject}
		</h1>

		<div class="content-body">
			{@html renderedContent}
		</div>
	{:else}
		<p>내용이 없습니다.</p>
	{/if}
</div>

<style>
	/* Custom prose styles for markdown content */
	.content-body {
		line-height: 1.8;
		color: #374151;
	}

	.content-body :global(h1) {
		font-size: 2rem;
		font-weight: 700;
		margin-top: 2rem;
		margin-bottom: 1rem;
		border-bottom: 1px solid #e5e7eb;
		padding-bottom: 0.5rem;
	}

	.content-body :global(h2) {
		font-size: 1.5rem;
		font-weight: 600;
		margin-top: 1.5rem;
		margin-bottom: 0.75rem;
		color: #1f2937;
	}

	.content-body :global(h3) {
		font-size: 1.25rem;
		font-weight: 600;
		margin-top: 1.25rem;
		margin-bottom: 0.5rem;
		color: #1f2937;
	}

	.content-body :global(p) {
		margin-bottom: 1rem;
	}

	.content-body :global(ul),
	.content-body :global(ol) {
		margin-left: 1.5rem;
		margin-bottom: 1rem;
	}

	.content-body :global(ul) {
		list-style-type: disc;
	}

	.content-body :global(ol) {
		list-style-type: decimal;
	}

	.content-body :global(li) {
		margin-bottom: 0.5rem;
	}

	.content-body :global(a) {
		color: #2563eb;
		text-decoration: underline;
	}

	.content-body :global(a:hover) {
		color: #1d4ed8;
	}

	.content-body :global(code) {
		background-color: #f3f4f6;
		padding: 0.125rem 0.375rem;
		border-radius: 0.25rem;
		font-size: 0.875rem;
		font-family: ui-monospace, monospace;
	}

	.content-body :global(pre) {
		background-color: #1f2937;
		color: #f9fafb;
		padding: 1rem;
		border-radius: 0.5rem;
		overflow-x: auto;
		margin-bottom: 1rem;
	}

	.content-body :global(pre code) {
		background-color: transparent;
		padding: 0;
		color: inherit;
	}

	.content-body :global(blockquote) {
		border-left: 4px solid #3b82f6;
		padding-left: 1rem;
		margin-left: 0;
		margin-bottom: 1rem;
		color: #6b7280;
		font-style: italic;
	}

	.content-body :global(hr) {
		border: none;
		border-top: 1px solid #e5e7eb;
		margin: 2rem 0;
	}

	.content-body :global(table) {
		width: 100%;
		border-collapse: collapse;
		margin-bottom: 1rem;
	}

	.content-body :global(th),
	.content-body :global(td) {
		border: 1px solid #e5e7eb;
		padding: 0.5rem 0.75rem;
		text-align: left;
	}

	.content-body :global(th) {
		background-color: #f9fafb;
		font-weight: 600;
	}

	.content-body :global(img) {
		max-width: 100%;
		height: auto;
		border-radius: 0.5rem;
	}

	/* Dark mode support */
	:global(.dark) .content-body {
		color: #d1d5db;
	}

	:global(.dark) .content-body :global(h1),
	:global(.dark) .content-body :global(h2),
	:global(.dark) .content-body :global(h3) {
		color: #f9fafb;
		border-color: #374151;
	}

	:global(.dark) .content-body :global(code) {
		background-color: #374151;
	}

	:global(.dark) .content-body :global(blockquote) {
		color: #9ca3af;
	}

	:global(.dark) .content-body :global(hr) {
		border-color: #374151;
	}

	:global(.dark) .content-body :global(th),
	:global(.dark) .content-body :global(td) {
		border-color: #374151;
	}

	:global(.dark) .content-body :global(th) {
		background-color: #1f2937;
	}
</style>
