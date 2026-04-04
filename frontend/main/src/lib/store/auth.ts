import { writable, get } from 'svelte/store';

export const accessToken = writable<string>('');
export const csrfToken = writable<string>('');

export function getDeviceId(): string {
    if (typeof document === 'undefined') return '';
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'sapi_device_id') {
            return value;
        }
    }
    return '';
}

export function setAccessToken(token: string) {
    accessToken.set(token);
}

export function clearAccessToken() {
    accessToken.set('');
}

export function setCsrfToken(token: string) {
    csrfToken.set(token);
}

export function clearCsrfToken() {
    csrfToken.set('');
}