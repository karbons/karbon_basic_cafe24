<script lang="ts">
	import "../../app.css";
	import { onMount } from "svelte";
	import { page } from "$app/stores";
	import { goto } from "$app/navigation";
	import { pageTitle, setMenus } from "$lib/store";
	import Header from "$lib/skin/layout/Header.svelte";
	import BottomNav from "$lib/skin/layout/BottomNav.svelte";
	import { ModeWatcher } from "mode-watcher";
	import { App } from "@capacitor/app";
	import { Capacitor } from "@capacitor/core";
	import { isLoading, locale, waitLocale } from 'svelte-i18n';
	import { initLocale } from '$lib/i18n';
	import { page as pageData } from '$app/state';
	import { getMenus } from '$lib/api';

	let { children } = $props();
	let i18nReady = $state(false);

	// 헤더를 숨킬 페이지 목록
	const hideHeaderPages = ["/auth/login", "/auth/register"];
	const isHideHeader = $derived(hideHeaderPages.includes(pageData.url.pathname));

	// 뒤로가기 대신 메뉴를 보여줄 메인 페이지들
	const mainPages = ["/", "/shop", "/member/mypage", "/chat"];
	const showBackButton = $derived(!mainPages.includes(pageData.url.pathname));

	// Extract lang from URL path
	const langFromPath = pageData.url.pathname.split('/')[2] || 'ko';

	onMount(async () => {
		if (Capacitor.isNativePlatform()) {
			App.addListener("appUrlOpen", (event: { url: string }) => {
				const slug = event.url.split(".kr").pop() || event.url.split("://").pop();
				if (slug) {
					const path = slug.startsWith("/") ? slug : "/" + slug;
					goto(path);
				}
			});
		}

		// Initialize i18n and wait for locale to be ready
		initLocale(langFromPath);
		await waitLocale();
		i18nReady = true;

		// Fetch menus from API
		try {
			const menus = await getMenus('pc');
			setMenus(menus);
		} catch (e) {
			console.warn('Failed to load menus:', e);
		}
	});
</script>

<ModeWatcher />

{#if $isLoading}
	<div class="min-h-screen flex items-center justify-center bg-gray-50">
		<div class="text-gray-500">Loading...</div>
	</div>
{:else}
	<div class="min-h-screen bg-gray-50 flex flex-col relative">
		{#if !isHideHeader}
			<Header {showBackButton} />
		{/if}

		<main class="flex-1 flex flex-col relative">
			{@render children()}
		</main>

		{#if !isHideHeader}
			<BottomNav />
		{/if}
	</div>
{/if}

<style>
	:global(body) {
		margin: 0;
		padding: 0;
		-webkit-tap-highlight-color: transparent;
	}
</style>
