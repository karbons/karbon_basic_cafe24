import { writable } from 'svelte/store';

export interface ConfirmOptions {
	title: string;
	message: string;
	confirmText?: string;
	cancelText?: string;
	hideCancel?: boolean;
	type?: 'danger' | 'warning' | 'info';
}

export interface ConfirmState extends ConfirmOptions {
	id: string;
	open: boolean;
	resolve?: (value: boolean) => void;
}

function createConfirmStore() {
	const { subscribe, update, set } = writable<ConfirmState | null>(null);

	return {
		subscribe,
		show: (options: ConfirmOptions): Promise<boolean> => {
			return new Promise((resolve) => {
				const id = crypto.randomUUID();
				set({
					...options,
					id,
					open: true,
					confirmText: options.confirmText ?? '확인',
					cancelText: options.cancelText ?? '취소',
					hideCancel: options.hideCancel ?? false,
					type: options.type ?? 'info',
					resolve
				});
			});
		},
		confirm: () => {
			update((state) => {
				if (state?.resolve) {
					state.resolve(true);
				}
				return null;
			});
		},
		cancel: () => {
			update((state) => {
				if (state?.resolve) {
					state.resolve(false);
				}
				return null;
			});
		}
	};
}

export const confirmStore = createConfirmStore();

