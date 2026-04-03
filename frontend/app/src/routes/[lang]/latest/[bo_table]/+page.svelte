<script lang="ts">
	import { apiGet } from '$lib/api';
	import { page } from '$app/stores';
import { base } from '$app/paths';
	
	interface Post {
		wr_id: number;
		wr_subject: string;
		wr_name: string;
		wr_datetime: string;
		wr_hit: number;
	}
	
	let bo_table = $derived($page.params.bo_table);
	let bo_subject = $state('');
	let list = $state<Post[]>([]);
	let loading = $state(true);
	let error = $state('');
	
	async function loadLatest() {
		try {
			loading = true;
			const data = await apiGet<{
				bo_table: string;
				bo_subject: string;
				list: Post[];
			}>(`/latest/${bo_table}?rows=10`);
			
			bo_table = data.bo_table;
			bo_subject = data.bo_subject;
			list = data.list;
		} catch (e: any) {
			error = e.message || '최신글을 불러올 수 없습니다.';
		} finally {
			loading = false;
		}
	}
	
	$effect(() => {
		if (bo_table) {
			loadLatest();
		}
	});
</script>

<div class="latest-container">
	<h1>최신글 - {bo_subject}</h1>
	
	{#if loading}
		<p>로딩 중...</p>
	{:else if error}
		<div class="error">{error}</div>
	{:else}
		<div class="latest-list">
			{#each list as item}
				<div class="latest-item">
					<a href="{base}/bbs/{bo_table}/{item.wr_id}">
						{item.wr_subject}
					</a>
					<span class="latest-meta">
						{item.wr_name} | {item.wr_datetime} | 조회 {item.wr_hit}
					</span>
				</div>
			{/each}
		</div>
	{/if}
</div>

<style>
	.latest-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem;
	}
	
	.latest-list {
		margin-top: 1rem;
	}
	
	.latest-item {
		padding: 0.75rem;
		border-bottom: 1px solid #eee;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.latest-item a {
		color: #007bff;
		text-decoration: none;
		flex: 1;
	}
	
	.latest-item a:hover {
		text-decoration: underline;
	}
	
	.latest-meta {
		color: #666;
		font-size: 0.9rem;
		margin-left: 1rem;
	}
	
	.error {
		color: red;
		padding: 1rem;
		background-color: #ffe6e6;
		border-radius: 4px;
	}
</style>

