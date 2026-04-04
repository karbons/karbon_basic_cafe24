<script>
	let envVars = $state([
		{ key: 'APP_ENV', value: 'development', group: 'Basic' },
		{ key: 'DB_HOST', value: 'localhost', group: 'Database' },
		{ key: 'DB_NAME', value: 'gnu5', group: 'Database' },
		{ key: 'JWT_SECRET', value: '••••••••', group: 'Security' }
	]);

	function addVar() {
		envVars.push({ key: '', value: '', group: 'Others' });
	}

	function saveEnv() {
		alert('Environment variables saved and .env regenerated.');
	}
</script>

<div class="space-y-6">
	<div class="flex justify-between items-center">
		<div>
			<h2 class="text-2xl font-bold">Environment Variables</h2>
			<p class="text-gray-500">Manage your .env configurations across different environments.</p>
		</div>
		<button onclick={saveEnv} class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
			Save Changes
		</button>
	</div>

	<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
		<table class="min-w-full divide-y divide-gray-200">
			<thead class="bg-gray-50">
				<tr>
					<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
					<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
					<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
					<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
				</tr>
			</thead>
			<tbody class="bg-white divide-y divide-gray-200">
				{#each envVars as v, i}
					<tr>
						<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{v.group}</td>
						<td class="px-6 py-4 whitespace-nowrap">
							<input type="text" bind:value={v.key} class="border rounded px-2 py-1 text-sm w-full font-mono" />
						</td>
						<td class="px-6 py-4 whitespace-nowrap">
							<input type="text" bind:value={v.value} class="border rounded px-2 py-1 text-sm w-full font-mono" />
						</td>
						<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
							<button onclick={() => envVars.splice(i, 1)} class="text-red-600 hover:text-red-900">Delete</button>
						</td>
					</tr>
				{/each}
			</tbody>
		</table>
		<div class="p-4 border-t bg-gray-50">
			<button onclick={addVar} class="text-blue-600 text-sm font-medium hover:text-blue-800">+ Add Variable</button>
		</div>
	</div>
</div>
