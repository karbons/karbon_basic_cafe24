<script>
	import '../app.css';
	import { page } from '$app/state';

	let { children } = $props();

	const menuItems = [
		{
			title: 'Home',
			path: '/',
			icon: '🏠'
		},
		{
			title: 'Infra & Installer',
			path: '/infra',
			icon: '🏗️',
			subItems: [
				{ title: 'Environment', path: '/infra/env' },
				{ title: 'Containers', path: '/infra/containers' },
				{ title: 'Deployment Sets', path: '/infra/deploy-sets' }
			]
		},
		{
			title: 'Project ERP',
			path: '/erp',
			icon: '📊',
			subItems: [
				{ title: 'Dashboard', path: '/erp/dashboard' },
				{ title: 'Milestones', path: '/erp/milestones' },
				{ title: 'Contracts', path: '/erp/contracts' },
				{ title: 'Revenue', path: '/erp/revenue' }
			]
		},
		{
			title: 'DB & Modeling',
			path: '/db',
			icon: '🗄️',
			subItems: [
				{ title: 'Table Builder', path: '/db/table-builder' },
				{ title: 'Data Browser', path: '/db/browser' },
				{ title: 'ERD View', path: '/db/erd' },
				{ title: 'Type Gen', path: '/db/types' }
			]
		},
		{
			title: 'Design System',
			path: '/design',
			icon: '🎨',
			subItems: [
				{ title: 'Storyboard', path: '/design/storyboard' },
				{ title: 'Prototype', path: '/design/prototype' },
				{ title: 'Design Guide', path: '/design/guide' },
				{ title: 'Shop', path: '/design/shop' }
			]
		},
		{
			title: 'AI & Ontology',
			path: '/ai',
			icon: '🤖',
			subItems: [
				{ title: 'GPU Sharing', path: '/ai/sharing' },
				{ title: 'Context', path: '/ai/context' },
				{ title: 'Ontology', path: '/ai/ontology' }
			]
		},
		{
			title: 'Ops & Monitoring',
			path: '/ops',
			icon: '📈',
			subItems: [
				{ title: 'Stats', path: '/ops/stats' },
				{ title: 'Errors', path: '/ops/errors' },
				{ title: 'Issues', path: '/ops/issues' },
				{ title: 'Changelog', path: '/ops/changelog' }
			]
		}
	];

	/** @param {string} path */
	function isActive(path) {
		return page.url.pathname === path || (path !== '/' && page.url.pathname.startsWith(path));
	}
</script>

<div class="flex h-screen bg-gray-100 font-sans text-gray-900">
	<aside class="flex w-64 flex-col border-r bg-white shadow-sm">
		<div class="flex h-16 items-center border-b px-6">
			<span class="text-xl font-bold text-blue-600">Karbon Builder</span>
		</div>
		<nav class="flex-1 overflow-y-auto p-4 space-y-2">
			{#each menuItems as item}
				<div>
					<a
						href={item.path}
						class="flex items-center space-x-3 rounded-lg px-3 py-2 transition-colors {isActive(item.path) ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'}"
					>
						<span class="text-xl">{item.icon}</span>
						<span>{item.title}</span>
					</a>
					{#if item.subItems && isActive(item.path)}
						<div class="mt-1 ml-9 space-y-1">
							{#each item.subItems as subItem}
								<a
									href={subItem.path}
									class="block rounded-md px-3 py-1.5 text-sm transition-colors {page.url.pathname === subItem.path ? 'text-blue-600 font-medium' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'}"
								>
									{subItem.title}
								</a>
							{/each}
						</div>
					{/if}
				</div>
			{/each}
		</nav>
		<div class="border-t p-4">
			<div class="flex items-center space-x-3 text-sm text-gray-500">
				<div class="h-2 w-2 rounded-full bg-green-500"></div>
				<span>System Online</span>
			</div>
		</div>
	</aside>

	<main class="flex flex-1 flex-col overflow-hidden">
		<header class="flex h-16 items-center justify-between border-b bg-white px-8">
			<h1 class="text-lg font-semibold capitalize">
				{page.url.pathname === '/' ? 'Welcome' : page.url.pathname.split('/').filter(Boolean).join(' > ')}
			</h1>
			<div class="flex items-center space-x-4">
				<button class="rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500">
					🔔
				</button>
				<div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
					A
				</div>
			</div>
		</header>

		<div class="flex-1 overflow-y-auto p-8">
			<div class="mx-auto max-w-5xl">
				{@render children()}
			</div>
		</div>
	</main>
</div>

<style>
	:global(body) {
		margin: 0;
	}
</style>
