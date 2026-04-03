<script lang="ts">
	import { alertStore } from "$lib/store/alert";
	import { onMount } from "svelte";

	function handleClose(id: string) {
		alertStore.remove(id);
	}

	function handleAutoClose(id: string, duration: number) {
		if (duration > 0) {
			setTimeout(() => {
				alertStore.remove(id);
			}, duration);
		}
	}
</script>

<div class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-full max-w-md">
	{#each $alertStore as alert (alert.id)}
		{@const duration = alert.duration ?? 5000}
		{#if duration > 0}
			{onMount(() => handleAutoClose(alert.id, duration))}
		{/if}
		<div
			class="bg-white rounded-lg shadow-lg border p-4 flex items-start gap-3 animate-slide-in-right"
			role="alert"
		>
			<!-- 아이콘 -->
			<div
				class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center {alert.type ===
				'success'
					? 'bg-green-100 text-green-600'
					: alert.type === 'error'
						? 'bg-red-100 text-red-600'
						: alert.type === 'warning'
							? 'bg-yellow-100 text-yellow-600'
							: 'bg-blue-100 text-blue-600'}"
			>
				{#if alert.type === "success"}
					<svg
						class="w-4 h-4"
						fill="none"
						stroke="currentColor"
						viewBox="0 0 24 24"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M5 13l4 4L19 7"
						/>
					</svg>
				{:else if alert.type === "error"}
					<svg
						class="w-4 h-4"
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
				{:else if alert.type === "warning"}
					<svg
						class="w-4 h-4"
						fill="none"
						stroke="currentColor"
						viewBox="0 0 24 24"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
						/>
					</svg>
				{:else}
					<svg
						class="w-4 h-4"
						fill="none"
						stroke="currentColor"
						viewBox="0 0 24 24"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				{/if}
			</div>

			<!-- 내용 -->
			<div class="flex-1 min-w-0">
				<h4 class="font-semibold text-gray-900 mb-1">{alert.title}</h4>
				<p class="text-sm text-gray-600">{alert.message}</p>
			</div>

			<!-- 닫기 버튼 -->
			<button
				type="button"
				onclick={() => handleClose(alert.id)}
				class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
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
	{/each}
</div>

<style>
	@keyframes slide-in-right {
		from {
			transform: translateX(100%);
			opacity: 0;
		}
		to {
			transform: translateX(0);
			opacity: 1;
		}
	}

	.animate-slide-in-right {
		animation: slide-in-right 0.3s ease-out;
	}
</style>
