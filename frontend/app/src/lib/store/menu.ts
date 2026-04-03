import { writable, get } from 'svelte/store';
import { browser } from '$app/environment';

export interface Menu {
    me_id: number;
    me_name: string;
    me_link: string;
    me_target: string;
    sub?: Menu[];
}

const MENU_STORAGE_KEY = 'app_menus';

// localStorage에서 캐시된 메뉴 로드
function loadCachedMenus(): Menu[] {
    if (!browser) return [];
    try {
        const cached = localStorage.getItem(MENU_STORAGE_KEY);
        if (cached) {
            return JSON.parse(cached);
        }
    } catch (e) {
        console.warn('Failed to load cached menus:', e);
    }
    return [];
}

// 초기값으로 캐시된 데이터 사용
export const menuStore = writable<Menu[]>(loadCachedMenus());
export const menuLoaded = writable<boolean>(false);

// 메뉴 저장 시 localStorage에도 저장
export function setMenus(menus: Menu[]) {
    menuStore.set(menus);
    menuLoaded.set(true);
    if (browser) {
        try {
            localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(menus));
        } catch (e) {
            console.warn('Failed to cache menus:', e);
        }
    }
}

// 메뉴 클리어
export function clearMenus() {
    menuStore.set([]);
    menuLoaded.set(false);
    if (browser) {
        localStorage.removeItem(MENU_STORAGE_KEY);
    }
}
