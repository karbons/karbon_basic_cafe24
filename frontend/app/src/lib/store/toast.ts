import { writable } from 'svelte/store';

export interface Toast {
	id: string;
	type: 'success' | 'error' | 'info' | 'warning';
	message: string;
	duration?: number; // 자동 닫기 시간 (ms), 0이면 자동 닫기 안함
}

function createToastStore() {
	const { subscribe, update } = writable<Toast[]>([]);

	const store = {
		subscribe,
		show: (toast: Omit<Toast, 'id'>) => {
			const id = crypto.randomUUID();
			update((toasts) => [
				...toasts,
				{ ...toast, id, duration: toast.duration ?? 3000 }
			]);
			return id;
		},
		remove: (id: string) => {
			update((toasts) => toasts.filter((t) => t.id !== id));
		},
		clear: () => {
			update(() => []);
		},
		// 편의 메서드
		success: (message: string, duration?: number) => {
			const id = crypto.randomUUID();
			update((toasts) => [
				...toasts,
				{ id, type: 'success', message, duration: duration ?? 3000 }
			]);
			return id;
		},
		error: (message: string, duration?: number) => {
			const id = crypto.randomUUID();
			update((toasts) => [
				...toasts,
				{ id, type: 'error', message, duration: duration ?? 3000 }
			]);
			return id;
		},
		info: (message: string, duration?: number) => {
			const id = crypto.randomUUID();
			update((toasts) => [
				...toasts,
				{ id, type: 'info', message, duration: duration ?? 3000 }
			]);
			return id;
		},
		warning: (message: string, duration?: number) => {
			const id = crypto.randomUUID();
			update((toasts) => [
				...toasts,
				{ id, type: 'warning', message, duration: duration ?? 3000 }
			]);
			return id;
		}
	};

	return store;
}

export const toastStore = createToastStore();

