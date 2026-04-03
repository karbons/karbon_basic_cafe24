<script lang="ts">
	import { apiGet } from "$lib/api";

	interface Banner {
		bn_id: number;
		bn_alt: string;
		bn_url: string;
		bn_target: string;
		bn_image: string;
		bn_bgcolor: string;
	}

	let banners = $state<Banner[]>([]);
	let loading = $state(true);

	let { position, device = "pc" } = $props<{
		position: string;
		device?: "pc" | "mobile";
	}>();

	async function loadBanners() {
		try {
			const data = await apiGet<{ banners: Banner[] }>(
				`/banner/${position}?device=${device}`,
			);
			banners = data.banners;
		} catch (e) {
			console.error("배너 로드 실패:", e);
		} finally {
			loading = false;
		}
	}

	$effect(() => {
		if (position) {
			loadBanners();
		}
	});
</script>

<div class="banner-container">
	{#if loading}
		<p>로딩 중...</p>
	{:else}
		{#each banners as banner}
			<a
				href={banner.bn_url}
				target={banner.bn_target}
				class="banner-item"
				style="background-color: {banner.bn_bgcolor || 'transparent'}"
			>
				<img src={banner.bn_image} alt={banner.bn_alt} />
			</a>
		{/each}
	{/if}
</div>

<style>
	.banner-container {
		display: flex;
		flex-wrap: wrap;
		gap: 1rem;
	}

	.banner-item {
		display: block;
		text-decoration: none;
		border-radius: 4px;
		overflow: hidden;
	}

	.banner-item img {
		display: block;
		max-width: 100%;
		height: auto;
	}
</style>
