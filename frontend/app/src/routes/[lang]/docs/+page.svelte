<script lang="ts">
	import { apiGet } from '$lib/api';
	
	interface Route {
		method: string;
		path: string;
		file: string;
	}
	
	let routes = $state<Route[]>([]);
	let loading = $state(true);
	let error = $state('');
	
	async function loadDocs() {
		try {
			loading = true;
			const data = await apiGet<{ routes: Route[] }>('/docs');
			routes = data.routes;
		} catch (e: any) {
			error = e.message || 'API 문서를 불러올 수 없습니다.';
		} finally {
			loading = false;
		}
	}
	
	$effect(() => {
		loadDocs();
	});
</script>

<div class="docs-container">
	<h1>API 문서</h1>
	
	{#if loading}
		<p>로딩 중...</p>
	{:else if error}
		<div class="error">{error}</div>
	{:else}
		<table class="docs-table">
			<thead>
				<tr>
					<th>메서드</th>
					<th>경로</th>
					<th>파일</th>
				</tr>
			</thead>
			<tbody>
				{#each routes as route}
					<tr>
						<td class="method method-{route.method.toLowerCase()}">{route.method}</td>
						<td class="path">{route.path}</td>
						<td class="file">{route.file}</td>
					</tr>
				{/each}
			</tbody>
		</table>
	{/if}
</div>

<style>
	.docs-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem;
	}
	
	.docs-table {
		width: 100%;
		border-collapse: collapse;
		margin-top: 1rem;
	}
	
	.docs-table th,
	.docs-table td {
		padding: 0.75rem;
		border: 1px solid #ddd;
		text-align: left;
	}
	
	.docs-table th {
		background-color: #f5f5f5;
		font-weight: bold;
	}
	
	.method {
		font-weight: bold;
		text-align: center;
		width: 80px;
	}
	
	.method-get {
		color: #28a745;
	}
	
	.method-post {
		color: #007bff;
	}
	
	.method-put {
		color: #ffc107;
	}
	
	.method-delete {
		color: #dc3545;
	}
	
	.path {
		font-family: monospace;
		color: #007bff;
	}
	
	.file {
		font-family: monospace;
		font-size: 0.9rem;
		color: #666;
	}
	
	.error {
		color: red;
		padding: 1rem;
		background-color: #ffe6e6;
		border-radius: 4px;
	}
</style>

