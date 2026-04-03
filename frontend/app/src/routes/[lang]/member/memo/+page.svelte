<script lang="ts">
	import { apiGet } from '$lib/api';
	import { page } from '$app/stores';
	
	interface Memo {
		me_id: number;
		me_subject: string;
		me_content: string;
		me_send_mb_id: string;
		me_recv_mb_id: string;
		me_send_datetime: string;
		me_read_datetime: string;
	}
	
	let memos = $state<Memo[]>([]);
	let loading = $state(true);
	let error = $state('');
	let type = $state('recv');
	
	async function loadMemos() {
		try {
			loading = true;
			const data = await apiGet<{ memos: Memo[] }>(`/member/memo?type=${type}`);
			memos = data.memos;
		} catch (e: any) {
			error = e.message || '쪽지를 불러올 수 없습니다.';
		} finally {
			loading = false;
		}
	}
	
	$effect(() => {
		loadMemos();
	});
</script>

<div class="memo-container">
	<h1>쪽지</h1>
	
	<div class="memo-tabs">
		<button
			class:active={type === 'recv'}
			onclick={() => { type = 'recv'; loadMemos(); }}
		>
			받은 쪽지
		</button>
		<button
			class:active={type === 'send'}
			onclick={() => { type = 'send'; loadMemos(); }}
		>
			보낸 쪽지
		</button>
	</div>
	
	{#if loading}
		<p>로딩 중...</p>
	{:else if error}
		<div class="error">{error}</div>
	{:else}
		<div class="memo-list">
			{#each memos as memo}
				<div class="memo-item">
					<div class="memo-subject">{memo.me_subject}</div>
					<div class="memo-meta">
						<span>{type === 'recv' ? '보낸 사람' : '받은 사람'}: {type === 'recv' ? memo.me_send_mb_id : memo.me_recv_mb_id}</span>
						<span>작성일: {memo.me_send_datetime}</span>
					</div>
				</div>
			{/each}
		</div>
	{/if}
</div>

<style>
	.memo-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem;
	}
	
	.memo-tabs {
		margin-bottom: 1rem;
		display: flex;
		gap: 0.5rem;
	}
	
	.memo-tabs button {
		padding: 0.5rem 1rem;
		border: 1px solid #ddd;
		background-color: white;
		cursor: pointer;
		border-radius: 4px;
	}
	
	.memo-tabs button.active {
		background-color: #007bff;
		color: white;
		border-color: #007bff;
	}
	
	.memo-list {
		margin-top: 1rem;
	}
	
	.memo-item {
		padding: 1rem;
		border: 1px solid #ddd;
		border-radius: 4px;
		margin-bottom: 0.5rem;
	}
	
	.memo-subject {
		font-weight: bold;
		margin-bottom: 0.5rem;
	}
	
	.memo-meta {
		font-size: 0.9rem;
		color: #666;
		display: flex;
		gap: 1rem;
	}
	
	.error {
		color: red;
		padding: 1rem;
		background-color: #ffe6e6;
		border-radius: 4px;
	}
</style>

