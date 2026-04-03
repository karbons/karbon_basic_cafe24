import { writable } from 'svelte/store';
import type { Member } from '$lib/type/member';

export const memberStore = writable<Member | null>(null);
export const isAuthenticated = writable<boolean>(false);

export function setMember(member: Member | null) {
    memberStore.set(member);
    isAuthenticated.set(member !== null);
}

export function clearMember() {
    memberStore.set(null);
    isAuthenticated.set(false);
}
