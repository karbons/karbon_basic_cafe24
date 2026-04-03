import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import type { SiteConfig } from '$lib/type/config';

const CONFIG_STORAGE_KEY = 'app_config';

// localStorage에서 캐시된 설정 로드
function loadCachedConfig(): SiteConfig | null {
    if (!browser) return null;
    try {
        const cached = localStorage.getItem(CONFIG_STORAGE_KEY);
        if (cached) {
            return JSON.parse(cached);
        }
    } catch (e) {
        console.warn('Failed to load cached config:', e);
    }
    return null;
}

// 초기값으로 캐시된 데이터 사용
export const configStore = writable<SiteConfig | null>(loadCachedConfig());

// 설정 저장 시 localStorage에도 저장
export function setConfig(config: SiteConfig) {
    configStore.set(config);
    if (browser) {
        try {
            localStorage.setItem(CONFIG_STORAGE_KEY, JSON.stringify(config));
        } catch (e) {
            console.warn('Failed to cache config:', e);
        }
    }
}

// 설정 클리어
export function clearConfig() {
    configStore.set(null);
    if (browser) {
        localStorage.removeItem(CONFIG_STORAGE_KEY);
    }
}
