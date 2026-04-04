<script lang="ts">
	import {
		Home,
		Construction,
		LayoutDashboard,
		Database,
		Palette,
		BrainCircuit,
		Activity,
		ChevronDown,
		ChevronRight,
		Server,
		Table2,
		FileSearch,
		Shapes,
		Type,
		PenTool,
		Play,
		BookOpen,
		ShoppingBag,
		Cpu,
		BookMarked,
		Network,
		BarChart3,
		AlertCircle,
		AlertTriangle,
		FileText
	} from 'lucide-svelte';
	import { page } from '$app/state';

	const menuItems = [
		{
			title: 'Home',
			path: '/',
			icon: Home
		},
		{
			title: 'Infra & Installer',
			path: '/infra',
			icon: Construction,
			subItems: [
				{ title: 'Environment', path: '/infra/env', icon: Server },
				{ title: 'Containers', path: '/infra/containers', icon: Server },
				{ title: 'Deployment Sets', path: '/infra/deploy-sets', icon: Server }
			]
		},
		{
			title: 'Project ERP',
			path: '/erp',
			icon: LayoutDashboard,
			subItems: [
				{ title: 'Dashboard', path: '/erp/dashboard', icon: BarChart3 },
				{ title: 'Milestones', path: '/erp/milestones', icon: FileText },
				{ title: 'Contracts', path: '/erp/contracts', icon: FileText },
				{ title: 'Revenue', path: '/erp/revenue', icon: BarChart3 }
			]
		},
		{
			title: 'DB & Modeling',
			path: '/db',
			icon: Database,
			subItems: [
				{ title: 'Table Builder', path: '/db/table-builder', icon: Table2 },
				{ title: 'Data Browser', path: '/db/browser', icon: FileSearch },
				{ title: 'ERD View', path: '/db/erd', icon: Shapes },
				{ title: 'Type Gen', path: '/db/types', icon: Type }
			]
		},
		{
			title: 'Design System',
			path: '/design',
			icon: Palette,
			subItems: [
				{ title: 'Storyboard', path: '/design/storyboard', icon: PenTool },
				{ title: 'Prototype', path: '/design/prototype', icon: Play },
				{ title: 'Design Guide', path: '/design/guide', icon: BookOpen },
				{ title: 'Shop', path: '/design/shop', icon: ShoppingBag }
			]
		},
		{
			title: 'AI & Ontology',
			path: '/ai',
			icon: BrainCircuit,
			subItems: [
				{ title: 'GPU Sharing', path: '/ai/sharing', icon: Cpu },
				{ title: 'Context', path: '/ai/context', icon: BookMarked },
				{ title: 'Ontology', path: '/ai/ontology', icon: Network }
			]
		},
		{
			title: 'Ops & Monitoring',
			path: '/ops',
			icon: Activity,
			subItems: [
				{ title: 'Stats', path: '/ops/stats', icon: BarChart3 },
				{ title: 'Errors', path: '/ops/errors', icon: AlertCircle },
				{ title: 'Issues', path: '/ops/issues', icon: AlertTriangle },
				{ title: 'Changelog', path: '/ops/changelog', icon: FileText }
			]
		}
	];

	function isActive(path: string): boolean {
		return (
			page.url.pathname === path ||
			(path !== '/' && page.url.pathname.startsWith(path))
		);
	}
</script>

<aside class="flex w-64 flex-col border-r bg-white shadow-sm">
	<div class="flex h-16 items-center border-b px-6">
		<span class="text-xl font-bold text-blue-600">Karbon Studio</span>
	</div>
	<nav class="flex-1 overflow-y-auto p-4 space-y-1">
		{#each menuItems as item}
			{@const isItemActive = isActive(item.path)}
			<div>
				<a
					href={item.path}
					class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors {isItemActive
						? 'bg-blue-50 text-blue-700 font-medium'
						: 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'}"
				>
					<svelte:component this={item.icon} size={20} />
					<span>{item.title}</span>
					{#if item.subItems}
						{#if isItemActive}
							<ChevronDown size={16} class="ml-auto" />
						{:else}
							<ChevronRight size={16} class="ml-auto" />
						{/if}
					{/if}
				</a>
				{#if item.subItems && isItemActive}
					<div class="mt-1 ml-4 space-y-1 border-l border-gray-200 pl-2">
						{#each item.subItems as subItem}
							<a
								href={subItem.path}
								class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm transition-colors {page
									.url.pathname === subItem.path
									? 'text-blue-600 font-medium bg-blue-50'
									: 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'}"
							>
								{#if subItem.icon}
									<svelte:component this={subItem.icon} size={14} />
								{/if}
								{subItem.title}
							</a>
						{/each}
					</div>
				{/if}
			</div>
		{/each}
	</nav>
	<div class="border-t p-4">
		<div class="flex items-center gap-3 text-sm text-gray-500">
			<div class="h-2 w-2 rounded-full bg-green-500"></div>
			<span>System Online</span>
		</div>
	</div>
</aside>