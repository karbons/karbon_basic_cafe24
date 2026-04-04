import { writable, get } from 'svelte/store';
import { browser } from '$app/environment';
import type { Member } from '$lib/type/member';

const MEMBER_STORAGE_KEY = 'main_member';

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

export const memberStore = writable<Member | null>(loadCachedMember());
export const isAuthenticated = writable<boolean>(loadCachedMember() !== null);

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

export function clearMember() {
    memberStore.set(null);
    isAuthenticated.set(false);
    if (browser) {
        localStorage.removeItem(MEMBER_STORAGE_KEY);
    }
}