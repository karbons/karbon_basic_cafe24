<script lang="ts">
	import "../../app.css";
	import { page } from "$app/stores";
import { base } from '$app/paths';
</script>

<div class="flex min-h-screen w-full bg-gray-50 dark:bg-gray-900">
	<!-- Sidebar -->
	<aside class="hidden w-64 border-r bg-white dark:bg-gray-950 md:block">
		<div class="flex h-16 items-center border-b px-6">
			<span class="text-lg font-bold">Gnuboard Manager</span>
		</div>
		<div class="py-4">
			<nav class="space-y-1 px-4">
				<a
					href="{base}/"
					class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-50 {$page
						.url.pathname === '/'
						? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50'
						: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-50'}"
				>
					Dashboard
				</a>
				<a
					href="{base}/members"
					class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-50 {$page.url.pathname.startsWith(
						'/members',
					)
						? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50'
						: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-50'}"
				>
					Members
				</a>
				<a
					href="{base}/boards"
					class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-gray-50 {$page.url.pathname.startsWith(
						'/boards',
					)
						? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50'
						: 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-50'}"
				>
					Boards
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
				{$page.url.pathname === "/"
					? "Dashboard"
					: $page.url.pathname.split("/")[1]}
			</div>
			<div class="flex items-center gap-4">
				<span class="text-sm text-gray-500">Admin</span>
			</div>
		</header>

		<main class="flex-1 p-6">
			<slot />
		</main>
	</div>
</div>
