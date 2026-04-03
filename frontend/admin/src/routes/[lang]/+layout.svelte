<script lang="ts">
	import "../../app.css";
	import { onMount } from "svelte";
	import { page as pageData } from '$app/state';
	import { _, isLoading, waitLocale } from 'svelte-i18n';
	import { base } from '$app/paths';
	import { initLocale } from '$lib/i18n';

	let i18nReady = $state(false);
	const langFromPath = pageData.url.pathname.split('/')[2] || 'ko';

	let { children } = $props();

	onMount(async () => {
		initLocale(langFromPath);
		await waitLocale();
		i18nReady = true;
	});
</script>

{#if $isLoading || !i18nReady}
	<div class="min-h-screen flex items-center justify-center bg-gray-50">
		<div class="text-gray-500">Loading...</div>
	</div>
{:else}
	<div class="flex min-h-screen w-full bg-gray-50 dark:bg-gray-900">
		<!-- Sidebar -->
		<aside class="hidden w-64 border-r bg-white dark:bg-gray-950 md:block">
			<div class="flex h-16 items-center border-b px-6">
				<span class="text-lg font-bold">{$_('admin.nav.dashboard')}</span>
			</div>
			<div class="py-4">
				<nav class="space-y-1 px-4">
					<a
						href="{base}/"
						class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-50 {pageData.url.pathname === '/'
						? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50'
						: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-50'}"
					>
						{$_('admin.nav.dashboard')}
					</a>
					<a
						href="{base}/members"
						class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-50 {pageData.url.pathname.startsWith(
							'/members',
						)
							? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50'
							: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-50'}"
					>
						{$_('admin.nav.members')}
					</a>
					<a
						href="{base}/boards"
						class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-50 {pageData.url.pathname.startsWith(
							'/boards',
						)
							? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50'
							: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-50'}"
					>
						{$_('admin.nav.boards')}
					</a>
				</nav>
			</div>
		</aside>

		<!-- Main Content -->
		<div class="flex flex-1 flex-col">
			<!-- Header -->
			<header
				class="flex h-16 items-center justify-between border-b bg-white px-6 shadow-sm dark:bg-gray-950"
			>
				<div class="md:hidden">
					<!-- Mobile Toggle (Implement later with Sheet) -->
					<button class="text-sm font-medium">Menu</button>
				</div>
				<div class="text-xl font-semibold capitalize">
					{pageData.url.pathname === "/"
						? $_('admin.nav.dashboard')
						: pageData.url.pathname.split("/")[1]}
				</div>
				<div class="flex items-center gap-4">
					<span class="text-sm text-gray-500">{$_('admin.nav.dashboard')}</span>
				</div>
			</header>

			<main class="flex-1 p-6">
				{@render children()}
			</main>
		</div>
	</div>
{/if}
