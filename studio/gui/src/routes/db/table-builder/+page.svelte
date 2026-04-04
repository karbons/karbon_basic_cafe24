<script>
	let tableName = $state('new_table');
	let columns = $state([
		{ name: 'id', type: 'INT', pk: true, ai: true, nullable: false },
		{ name: 'created_at', type: 'DATETIME', pk: false, ai: false, nullable: false },
		{ name: 'title', type: 'VARCHAR(255)', pk: false, ai: false, nullable: true }
	]);

	function addColumn() {
		columns.push({ name: '', type: 'VARCHAR(255)', pk: false, ai: false, nullable: true });
	}

	function createTable() {
		alert(`Table ${tableName} created in Database.`);
	}
</script>

<div class="space-y-6">
	<div class="flex justify-between items-center">
		<div>
			<h2 class="text-2xl font-bold">Table Builder</h2>
			<p class="text-gray-500">Design your database schema without writing SQL.</p>
		</div>
		<button onclick={createTable} class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
			Create Table
		</button>
	</div>

	<div class="bg-white rounded-xl shadow-sm border p-6">
		<div class="mb-6">
			<label for="table-name" class="block text-sm font-medium text-gray-700">Table Name</label>
			<input type="text" id="table-name" bind:value={tableName} class="mt-1 block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" />
		</div>

		<h4 class="text-sm font-semibold mb-4 text-gray-900 uppercase tracking-wider">Columns</h4>
		<div class="space-y-3">
			{#each columns as col, i}
				<div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border">
					<input type="text" bind:value={col.name} placeholder="Column Name" class="flex-1 border rounded px-2 py-1 text-sm" />
					<select bind:value={col.type} class="border rounded px-2 py-1 text-sm bg-white">
						<option>INT</option>
						<option>VARCHAR(255)</option>
						<option>TEXT</option>
						<option>DATETIME</option>
						<option>JSON</option>
						<option>BOOLEAN</option>
					</select>
					<label class="flex items-center space-x-1 text-xs">
						<input type="checkbox" bind:checked={col.pk} />
						<span>PK</span>
					</label>
					<label class="flex items-center space-x-1 text-xs">
						<input type="checkbox" bind:checked={col.ai} />
						<span>AI</span>
					</label>
					<label class="flex items-center space-x-1 text-xs">
						<input type="checkbox" bind:checked={col.nullable} />
						<span>Null</span>
					</label>
					<button onclick={() => columns.splice(i, 1)} class="text-gray-400 hover:text-red-500">✕</button>
				</div>
			{/each}
			<button onclick={addColumn} class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 text-sm hover:border-blue-500 hover:text-blue-500 transition-colors">
				+ Add Column
			</button>
		</div>
	</div>
</div>
