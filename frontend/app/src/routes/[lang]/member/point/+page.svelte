<script lang="ts">
	import { apiGet } from '$lib/api';
	
	interface Point {
		po_id: number;
		po_content: string;
		po_point: number;
		po_use_point: number;
		po_datetime: string;
		po_expired: string;
		po_expire_date: string;
	}
	
	let totalPoint = $state(0);
	let points = $state<Point[]>([]);
	let loading = $state(true);
	let error = $state('');
	let page = $state(1);
	
	async function loadPoints() {
		try {
			loading = true;
			const data = await apiGet<{
				total_point: number;
				points: Point[];
				pagination: any;
			}>(`/member/point?page=${page}`);
			
			totalPoint = data.total_point;
			points = data.points;
		} catch (e: any) {
			error = e.message || '포인트 내역을 불러올 수 없습니다.';
		} finally {
			loading = false;
		}
	}
	
	$effect(() => {
		loadPoints();
	});
</script>

<div class="point-container">
	<h1>포인트 내역</h1>
	
	<div class="point-summary">
		<strong>보유 포인트: {totalPoint.toLocaleString()}P</strong>
	</div>
	
	{#if loading}
		<p>로딩 중...</p>
	{:else if error}
		<div class="error">{error}</div>
	{:else}
		<table class="point-table">
			<thead>
				<tr>
					<th>내용</th>
					<th>적립</th>
					<th>사용</th>
					<th>일시</th>
				</tr>
			</thead>
			<tbody>
				{#each points as point}
					<tr>
						<td>{point.po_content}</td>
						<td class="positive">{point.po_point > 0 ? '+' + point.po_point.toLocaleString() : ''}</td>
						<td class="negative">{point.po_use_point > 0 ? '-' + point.po_use_point.toLocaleString() : ''}</td>
						<td>{point.po_datetime}</td>
					</tr>
				{/each}
			</tbody>
		</table>
	{/if}
</div>

<style>
	.point-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem;
	}
	
	.point-summary {
		margin-bottom: 1rem;
		padding: 1rem;
		background-color: #f5f5f5;
		border-radius: 4px;
		font-size: 1.2rem;
	}
	
	.point-table {
		width: 100%;
		border-collapse: collapse;
		margin-top: 1rem;
	}
	
	.point-table th,
	.point-table td {
		padding: 0.75rem;
		border: 1px solid #ddd;
		text-align: left;
	}
	
	.point-table th {
		background-color: #f5f5f5;
		font-weight: bold;
	}
	
	.positive {
		color: #28a745;
		font-weight: bold;
	}
	
	.negative {
		color: #dc3545;
		font-weight: bold;
	}
	
	.error {
		color: red;
		padding: 1rem;
		background-color: #ffe6e6;
		border-radius: 4px;
	}
</style>

