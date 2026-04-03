<script lang="ts">
	import { _, isLoading } from 'svelte-i18n';
</script>

<h1 class="text-2xl font-bold mb-4">{$_('admin.nav.dashboard')}</h1>
{#if $isLoading}
	<div class="text-gray-500">Loading...</div>
{:else}
	<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
		<div class="rounded-xl border bg-card text-card-foreground shadow p-6 bg-white dark:bg-gray-950">
			<div class="flex flex-row items-center justify-between space-y-0 pb-2">
				<div class="tracking-tight text-sm font-medium text-gray-500">
					{$_('admin.dashboard.totalMembers')}
				</div>
			</div>
			<div class="text-2xl font-bold">1,234</div>
			<div class="text-xs text-gray-500">+20.1% {$_('admin.dashboard.fromLastMonth')}</div>
		</div>

		<div class="rounded-xl border bg-card text-card-foreground shadow p-6 bg-white dark:bg-gray-950">
			<div class="flex flex-row items-center justify-between space-y-0 pb-2">
				<div class="tracking-tight text-sm font-medium text-gray-500">
					{$_('admin.dashboard.totalPosts')}
				</div>
			</div>
			<div class="text-2xl font-bold">+573</div>
			<div class="text-xs text-gray-500">+201 {$_('admin.dashboard.sinceLastHour')}</div>
		</div>
	</div>
{/if}
