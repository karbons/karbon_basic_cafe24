<script lang="ts">
	import { apiGet } from '$lib/api';
import { base } from '$app/paths';
	
	interface Scrap {
		ms_id: number;
		bo_table: string;
		wr_id: number;
		ms_datetime: string;
	}
	
	let scraps = $state<Scrap[]>([]);
	let loading = $state(true);
	let error = $state('');
	
	async function loadScraps() {
		try {
			loading = true;
			const data = await apiGet<{ scraps: Scrap[] }>('/member/scrap');
			scraps = data.scraps;
		} catch (e: any) {
			error = e.message || '스크랩을 불러올 수 없습니다.';
		} finally {
			loading = false;
		}
	}
	
	$effect(() => {
		loadScraps();
	});
</script>

<div class="scrap-container">
	<h1>스크랩</h1>
	
	{#if loading}
		<p>로딩 중...</p>
	{:else if error}
		<div class="error">{error}</div>
	{:else}
		<div class="scrap-list">
			{#each scraps as scrap}
				<div class="scrap-item">
					<a href="{base}/bbs/{scrap.bo_table}/{scrap.wr_id}">
						게시판: {scrap.bo_table} / 글번호: {scrap.wr_id}
					</a>
					<span class="scrap-date">{scrap.ms_datetime}</span>
				</div>
			{/each}
		</div>
	{/if}
</div>

<style>
	.scrap-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem;
	}
	
	.scrap-list {
		margin-top: 1rem;
	}
	
	.scrap-item {
		padding: 1rem;
		border: 1px solid #ddd;
		border-radius: 4px;
		margin-bottom: 0.5rem;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.scrap-item a {
		color: #007bff;
		text-decoration: none;
	}
	
	.scrap-item a:hover {
		text-decoration: underline;
	}
	
	.scrap-date {
		color: #666;
		font-size: 0.9rem;
	}
	
	.error {
		color: red;
		padding: 1rem;
		background-color: #ffe6e6;
		border-radius: 4px;
	}
</style>

