import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import type { Member } from '$lib/type/member';

const MEMBER_STORAGE_KEY = 'app_member';

// localStorage에서 캐시된 멤버 로드
function loadCachedMember(): Member | null {
    if (!browser) return null;
    try {
        const cached = localStorage.getItem(MEMBER_STORAGE_KEY);
        if (cached) {
            return JSON.parse(cached);
        }
    } catch (e) {
        console.warn('Failed to load cached member:', e);
    }
    return null;
}

// 초기값으로 캐시된 데이터 사용
const cachedMember = loadCachedMember();
export const memberStore = writable<Member | null>(cachedMember);
export const isAuthenticated = writable<boolean>(cachedMember !== null);

// 멤버 설정 시 localStorage에도 저장
export function setMember(member: Member | null) {
    memberStore.set(member);
    isAuthenticated.set(member !== null);
    if (browser) {
        try {
            if (member) {
                localStorage.setItem(MEMBER_STORAGE_KEY, JSON.stringify(member));
            } else {
                localStorage.removeItem(MEMBER_STORAGE_KEY);
            }
        } catch (e) {
            console.warn('Failed to cache member:', e);
        }
    }
}

// 멤버 클리어
export function clearMember() {
    memberStore.set(null);
    isAuthenticated.set(false);
    if (browser) {
        localStorage.removeItem(MEMBER_STORAGE_KEY);
    }
}
