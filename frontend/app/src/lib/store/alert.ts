import { writable } from 'svelte/store';

export interface Alert {
	id: string;
	type: 'success' | 'error' | 'info' | 'warning';
	title: string;
	message: string;
	duration?: number; // 자동 닫기 시간 (ms), 0이면 자동 닫기 안함
}

function createAlertStore() {
	const { subscribe, update } = writable<Alert[]>([]);

	return {
		subscribe,
		show: (alert: Omit<Alert, 'id'>) => {
			const id = crypto.randomUUID();
			update((alerts) => [
				...alerts,
				{ ...alert, id, duration: alert.duration ?? 5000 }
			]);
			return id;
		},
		remove: (id: string) => {
			update((alerts) => alerts.filter((a) => a.id !== id));
		},
		clear: () => {
			update(() => []);
		}
	};
}

export const alertStore = createAlertStore();

