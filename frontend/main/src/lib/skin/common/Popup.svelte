<script lang="ts">
	import { onMount } from "svelte";
	import { apiGet } from "$lib/api";
	import { browser } from "$app/environment";

	interface Popup {
		nw_id: number;
		nw_subject: string;
		nw_content: string;
		nw_width: number;
		nw_height: number;
		nw_left: number;
		nw_top: number;
	}

	let popups = $state<Popup[]>([]);
	let closedPopups = $state<Set<number>>(new Set());

	function getCookieName(nw_id: number): string {
		return `popup_close_${nw_id}`;
	}

	function isPopupClosed(nw_id: number): boolean {
		if (!browser) return false;
		const cookie = document.cookie
			.split("; ")
			.find((row) => row.startsWith(getCookieName(nw_id) + "="));
		return !!cookie;
	}

	function closePopup(nw_id: number, dontShowToday: boolean = false) {
		if (dontShowToday && browser) {
			// 오늘 하루 동안 보지 않기 쿠키 설정
			const expires = new Date();
			expires.setHours(23, 59, 59, 999);
			document.cookie = `${getCookieName(nw_id)}=1; expires=${expires.toUTCString()}; path=/`;
		}
		closedPopups = new Set([...closedPopups, nw_id]);
	}

	async function loadPopups() {
		try {
			const isMobile = browser && window.innerWidth < 768;
			const device = isMobile ? "mobile" : "pc";
			const data = await apiGet<{ popups: Popup[] }>(
				`/popup?device=${device}`,
			);
			// 쿠키로 닫힌 팝업 필터링
			popups = (data.popups || []).filter((p) => !isPopupClosed(p.nw_id));
		} catch (e) {
			console.error("팝업 로드 실패:", e);
		}
	}

	onMount(() => {
		loadPopups();
	});
</script>

{#each popups.filter((p) => !closedPopups.has(p.nw_id)) as popup (popup.nw_id)}
	<div
		class="fixed bg-white rounded-lg shadow-2xl z-[100] overflow-hidden border border-gray-200"
		style="
			width: {popup.nw_width}px;
			max-width: calc(100vw - 32px);
			left: {popup.nw_left}px;
			top: {popup.nw_top}px;
		"
	>
		<!-- 팝업 헤더 -->
		<div
			class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200"
		>
			<h3 class="text-sm font-semibold text-gray-900 truncate">
				{popup.nw_subject}
			</h3>
			<button
				type="button"
				onclick={() => closePopup(popup.nw_id)}
				class="p-1 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-200 transition-colors"
				aria-label="닫기"
			>
				<svg
					class="w-5 h-5"
					fill="none"
					stroke="currentColor"
					viewBox="0 0 24 24"
				>
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M6 18L18 6M6 6l12 12"
					/>
				</svg>
			</button>
		</div>

		<!-- 팝업 내용 -->
		<div
			class="p-4 overflow-auto popup-content"
			style="max-height: {popup.nw_height}px;"
		>
			{@html popup.nw_content}
		</div>

		<!-- 팝업 푸터 -->
		<div
			class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200"
		>
			<button
				type="button"
				onclick={() => closePopup(popup.nw_id, true)}
				class="text-sm text-gray-600 hover:text-gray-900 transition-colors"
			>
				오늘 하루 보지 않기
			</button>
			<button
				type="button"
				onclick={() => closePopup(popup.nw_id)}
				class="px-4 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors"
			>
				닫기
			</button>
		</div>
	</div>
{/each}

<style>
	.popup-content :global(img) {
		max-width: 100%;
		height: auto;
	}

	.popup-content :global(a) {
		color: #2563eb;
		text-decoration: underline;
	}

	.popup-content :global(a:hover) {
		color: #1d4ed8;
	}
</style>
