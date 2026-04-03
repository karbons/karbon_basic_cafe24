<script>
	let milestones = $state([
		{ title: 'Project Kickoff', date: '2026-02-01', status: 'completed' },
		{ title: 'DB Schema Design', date: '2026-02-15', status: 'in-progress' },
		{ title: 'API Implementation', date: '2026-03-10', status: 'pending' }
	]);

	function addMilestone() {
		milestones.push({ title: '', date: '', status: 'pending' });
	}
</script>

<div class="space-y-6">
	<h2 class="text-2xl font-bold">Project Milestones</h2>

	<div class="bg-white rounded-xl shadow-sm border p-6">
		<div class="flow-root">
			<ul class="-mb-8">
				{#each milestones as m, i}
					<li>
						<div class="relative pb-8">
							{#if i !== milestones.length - 1}
								<span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
							{/if}
							<div class="relative flex space-x-3">
								<div>
									<span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white {m.status === 'completed' ? 'bg-green-500' : m.status === 'in-progress' ? 'bg-blue-500' : 'bg-gray-400'} text-white">
										{m.status === 'completed' ? '✓' : i + 1}
									</span>
								</div>
								<div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
									<div class="flex-1">
										<input type="text" bind:value={m.title} class="font-medium text-gray-900 border-none focus:ring-0 w-full p-0" placeholder="Milestone Title" />
									</div>
									<div class="whitespace-nowrap text-right text-sm text-gray-500">
										<input type="date" bind:value={m.date} class="border-none focus:ring-0 p-0" />
									</div>
								</div>
							</div>
						</div>
					</li>
				{/each}
			</ul>
		</div>
		<button onclick={addMilestone} class="mt-4 text-sm text-blue-600 font-medium hover:text-blue-800">+ Add Milestone</button>
	</div>
</div>
