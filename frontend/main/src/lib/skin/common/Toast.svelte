<script lang="ts">
	import { toastStore } from "$lib/store/toast";
	import { onMount } from "svelte";

	function handleClose(id: string) {
		toastStore.remove(id);
	}

	function handleAutoClose(id: string, duration: number) {
		if (duration > 0) {
			setTimeout(() => {
				toastStore.remove(id);
			}, duration);
		}
	}
</script>

<div
	class="fixed bottom-20 left-4 right-4 sm:left-auto sm:right-4 z-[70] flex flex-col gap-2 sm:w-full sm:max-w-sm"
>
	{#each $toastStore as toast (toast.id)}
		{@const duration = toast.duration ?? 3000}
		{#if duration > 0}
			{onMount(() => handleAutoClose(toast.id, duration))}
		{/if}
		<div
			class="bg-white rounded-lg shadow-lg border p-3 flex items-center gap-3 animate-slide-up {toast.type ===
			'success'
				? 'border-green-200'
				: toast.type === 'error'
					? 'border-red-200'
					: toast.type === 'warning'
						? 'border-yellow-200'
						: 'border-blue-200'}"
			role="alert"
		>
			<!-- 아이콘 -->
			<div
				class="flex-shrink-0 w-5 h-5 {toast.type === 'success'
					? 'text-green-600'
					: toast.type === 'error'
						? 'text-red-600'
						: toast.type === 'warning'
							? 'text-yellow-600'
							: 'text-blue-600'}"
			>
				{#if toast.type === "success"}
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
							d="M5 13l4 4L19 7"
						/>
					</svg>
				{:else if toast.type === "error"}
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
				{:else if toast.type === "warning"}
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
							d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
						/>
					</svg>
				{:else}
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
							d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				{/if}
			</div>

			<!-- 메시지 -->
			<p class="flex-1 text-sm text-gray-700">{toast.message}</p>

			<!-- 닫기 버튼 -->
			<button
				type="button"
				onclick={() => handleClose(toast.id)}
				class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
				aria-label="닫기"
			>
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
			</button>
		</div>
	{/each}
</div>

<style>
	@keyframes slide-up {
		from {
			transform: translateY(100%);
			opacity: 0;
		}
		to {
			transform: translateY(0);
			opacity: 1;
		}
	}

	.animate-slide-up {
		animation: slide-up 0.3s ease-out;
	}
</style>
