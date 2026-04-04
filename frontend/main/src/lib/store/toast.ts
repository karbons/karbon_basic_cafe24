import { writable } from 'svelte/store';

export interface Toast {
    id: string;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
    duration?: number;
}

function createToastStore() {
    const { subscribe, update } = writable<Toast[]>([]);

    function add(toast: Omit<Toast, 'id'>) {
        const id = Math.random().toString(36).substring(2, 9);
        const newToast = { ...toast, id };
        
        update((toasts) => [...toasts, newToast]);

        const duration = toast.duration ?? 3000;
        setTimeout(() => {
            remove(id);
        }, duration);

        return id;
    }

    function remove(id: string) {
        update((toasts) => toasts.filter((t) => t.id !== id));
    }

    return {
        subscribe,
        success: (message: string, duration?: number) =>
            add({ message, type: 'success', duration }),
        error: (message: string, duration?: number) =>
            add({ message, type: 'error', duration }),
        info: (message: string, duration?: number) =>
            add({ message, type: 'info', duration }),
        warning: (message: string, duration?: number) =>
            add({ message, type: 'warning', duration }),
        remove,
        clear: () => update(() => [])
    };
}

export const toastStore = {
    success: (message: string, duration?: number) => {
        if (typeof window !== 'undefined') {
            console.log('[Toast Success]', message);
        }
    },
    error: (message: string, duration?: number) => {
        if (typeof window !== 'undefined') {
            console.error('[Toast Error]', message);
        }
    },
    info: (message: string, duration?: number) => {
        if (typeof window !== 'undefined') {
            console.info('[Toast Info]', message);
        }
    },
    warning: (message: string, duration?: number) => {
        if (typeof window !== 'undefined') {
            console.warn('[Toast Warning]', message);
        }
    }
};