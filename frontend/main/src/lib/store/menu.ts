import { writable, get } from 'svelte/store';
import { browser } from '$app/environment';

export interface Menu {
    me_id: number;
    me_name: string;
    me_link: string;
    me_target: string;
    sub?: Menu[];
}

const MENU_STORAGE_KEY = 'main_menus';

/**
 * 그누보드 메뉴 링크를 현재 프로젝트 URL로 변환
 * - 컨텐츠: bbs/content.php?co_id=company → /[lang]/content/company
 * - 게시판: bbs/board.php?bo_table=free → /[lang]/bbs/free
 * - 외부 링크: 그대로 반환
 */
export function transformMenuLink(link: string, lang: string = 'ko'): string {
    if (!link) return '/';
    
    // 이미 변환된 경로인 경우 그대로 반환
    if (link.startsWith(`/${lang}/`)) {
        return link;
    }
    
    let path = link;
    
    // 전체 URL인 경우 경로만 추출
    if (link.startsWith('http')) {
        try {
            const url = new URL(link);
            path = url.pathname;
            if (url.search) {
                path += '?' + url.searchParams.toString();
            }
        } catch (e) {
            return link;
        }
    }
    
    // /v1/ prefix 제거
    path = path.replace(/^\/v1\//, '/');
    
    // 이미 변환된 경로인 경우 (다른 언어)
    if (path.match(/^\/(ko|en)\//)) {
        return path.replace(/^\/(ko|en)\//, `/${lang}/`);
    }
    
    // bbs/content.php 또는 bbs/board.php 패턴 변환
    const contentMatch = path.match(/bbs\/content\.php\?co_id=(\w+)/);
    if (contentMatch) {
        return `/${lang}/content/${contentMatch[1]}`;
    }

    const boardMatch = path.match(/bbs\/board\.php\?bo_table=(\w+)/);
    if (boardMatch) {
        return `/${lang}/bbs/${boardMatch[1]}`;
    }

    return path;
}

function transformMenus(menus: Menu[], lang: string = 'ko'): Menu[] {
    return menus.map(menu => ({
        ...menu,
        me_link: transformMenuLink(menu.me_link, lang),
        sub: menu.sub ? transformMenus(menu.sub, lang) : []
    }));
}

function loadCachedMenus(): Menu[] {
    return [];
}

export const menuStore = writable<Menu[]>(loadCachedMenus());
export const menuLoaded = writable<boolean>(false);

export function setMenus(menus: Menu[], lang: string = 'ko') {
    // 메뉴 변환
    const transformed = transformMenus(menus, lang);
    menuStore.set(transformed);
    menuLoaded.set(true);
    // localStorage에는 원본(변환 전) 메뉴 저장
    if (browser) {
        try {
            localStorage.setItem(MENU_STORAGE_KEY, JSON.stringify(menus));
        } catch (e) {
            console.warn('Failed to cache menus:', e);
        }
    }
}

export function clearMenus() {
    menuStore.set([]);
    menuLoaded.set(false);
    if (browser) {
        localStorage.removeItem(MENU_STORAGE_KEY);
    }
}