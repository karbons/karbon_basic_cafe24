<script lang="ts">
	import { confirmStore } from "$lib/store/confirm";
	import { onMount } from "svelte";

	let dialogEl = $state<HTMLDialogElement | null>(null);

	$effect(() => {
		if ($confirmStore?.open && dialogEl) {
			dialogEl.showModal();
		} else if (!$confirmStore && dialogEl) {
			dialogEl.close();
		}
	});

	function handleConfirm() {
		confirmStore.confirm();
	}

	function handleCancel() {
		confirmStore.cancel();
	}

	function handleBackdropClick(e: MouseEvent) {
		if (e.target === dialogEl && !$confirmStore?.hideCancel) {
			handleCancel();
		}
	}

	function handleDialogCancel(e: Event) {
		if ($confirmStore?.hideCancel) {
			e.preventDefault();
		}
	}
</script>

{#if $confirmStore}
	<dialog
		bind:this={dialogEl}
		onclick={handleBackdropClick}
		oncancel={handleDialogCancel}
		class="backdrop:bg-black/50 backdrop:backdrop-blur-sm rounded-lg shadow-xl p-0 max-w-md w-[calc(100%-2rem)] m-auto border-0"
	>
		<div class="p-6">
			<!-- 아이콘 -->
			<div
				class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 {$confirmStore.type ===
				'danger'
					? 'bg-red-100 text-red-600'
					: $confirmStore.type === 'warning'
						? 'bg-yellow-100 text-yellow-600'
						: 'bg-blue-100 text-blue-600'}"
			>
				{#if $confirmStore.type === "danger"}
					<svg
						class="w-6 h-6"
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
				{:else if $confirmStore.type === "warning"}
					<svg
						class="w-6 h-6"
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
						class="w-6 h-6"
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

			<!-- 제목 -->
			<h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
				{$confirmStore.title}
			</h3>

			<!-- 메시지 -->
			<p
				class="text-sm text-gray-600 text-center mb-6 whitespace-pre-line"
			>
				{$confirmStore.message}
			</p>

			<!-- 버튼 -->
			<div class="flex gap-3">
				{#if !$confirmStore.hideCancel}
					<button
						type="button"
						onclick={handleCancel}
						class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
					>
						{$confirmStore.cancelText}
					</button>
				{/if}
				<button
					type="button"
					onclick={handleConfirm}
					class="flex-1 px-4 py-2 text-sm font-medium text-white {$confirmStore.type ===
					'danger'
						? 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
						: $confirmStore.type === 'warning'
							? 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500'
							: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'} rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
				>
					{$confirmStore.confirmText}
				</button>
			</div>
		</div>
	</dialog>
{/if}
