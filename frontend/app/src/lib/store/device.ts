import { writable } from 'svelte/store';

export const fcmToken = writable<string>('');
export const deviceModel = writable<string>('');
export const osVersion = writable<string>('');
