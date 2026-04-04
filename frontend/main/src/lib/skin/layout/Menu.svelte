<script lang="ts">
	import { apiGet } from "$lib/api";

	interface Menu {
		me_id: number;
		me_name: string;
		me_link: string;
		me_target: string;
		sub?: Menu[];
	}

	let menus = $state<Menu[]>([]);
	let loading = $state(true);
	let device = $state<"pc" | "mobile">("pc");

	async function loadMenus() {
		try {
			const data = await apiGet<{ menus: Menu[] }>(
				`/menu?device=${device}`,
			);
			menus = data.menus;
			console.log(menus);
		} catch (e) {
			console.error("메뉴 로드 실패:", e);
		} finally {
			loading = false;
		}
	}

	$effect(() => {
		loadMenus();
	});
</script>

<nav class="menu">
	{#if loading}
		<p>로딩 중...</p>
	{:else}
		<ul class="menu-list">
			{#each menus as menu}
				<li class="menu-item">
					<a href={menu.me_link} target={menu.me_target || "_self"}>
						{menu.me_name}
					</a>
					{#if menu.sub && menu.sub.length > 0}
						<ul class="sub-menu">
							{#each menu.sub as subItem}
								<li>
									ㅈㄷㅈㄷ <a
										href={subItem.me_link}
										target={subItem.me_target || "_self"}
									>
										{subItem.me_name}
									</a>
								</li>
							{/each}
						</ul>
					{/if}
				</li>
			{/each}
		</ul>
	{/if}
</nav>

<style>
	.menu {
		background-color: #f5f5f5;
		padding: 1rem;
	}

	.menu-list {
		list-style: none;
		display: flex;
		gap: 1rem;
		margin: 0;
		padding: 0;
	}

	.menu-item {
		position: relative;
	}

	.menu-item > a {
		display: block;
		padding: 0.5rem 1rem;
		color: #333;
		text-decoration: none;
		border-radius: 4px;
	}

	.menu-item > a:hover {
		background-color: #007bff;
		color: white;
	}

	.sub-menu {
		position: absolute;
		top: 100%;
		left: 0;
		background-color: white;
		border: 1px solid #ddd;
		border-radius: 4px;
		list-style: none;
		margin: 0;
		padding: 0;
		min-width: 150px;
		display: none;
	}

	.menu-item:hover .sub-menu {
		display: block;
	}

	.sub-menu li {
		padding: 0;
	}

	.sub-menu a {
		display: block;
		padding: 0.5rem 1rem;
		color: #333;
		text-decoration: none;
	}

	.sub-menu a:hover {
		background-color: #f5f5f5;
	}
</style>
