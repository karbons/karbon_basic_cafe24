import { writable, get } from 'svelte/store';
import { browser } from '$app/environment';

// Access Token - 메모리 방식 (Bearer Header용)
export const accessToken = writable<string>('');

// CSRF Token - 메모리 방식
export const csrfToken = writable<string>('');

// Device ID - 일반 쿠키에서 읽기 (서브도메인 공유)
export function getDeviceId(): string {
    if (!browser) return '';
    // 쿠키에서 device_id 읽기
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'sapi_device_id') {
            return value;
        }
    }
    return '';
}

// 토큰 설정 (로그인 시 호출)
export function setAccessToken(token: string) {
    accessToken.set(token);
}

// 토큰 클리어 (로그아웃 시 호출)
export function clearAccessToken() {
    accessToken.set('');
}

// CSRF 토큰 설정
export function setCsrfToken(token: string) {
    csrfToken.set(token);
}

// CSRF 토큰 클리어
export function clearCsrfToken() {
    csrfToken.set('');
}