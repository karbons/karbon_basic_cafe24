import { apiPost, apiGet } from './api';
import type { Member, LoginRequest, LoginResponse } from '$lib/type/member';
export type { Member, LoginRequest, LoginResponse };

import { get } from 'svelte/store';
import { fcmToken, deviceModel, osVersion } from '$lib/store/device';

export async function login(credentials: LoginRequest): Promise<LoginResponse> {
    const body = {
        ...credentials,
        fcm_token: get(fcmToken),
        device_model: get(deviceModel),
        os_version: get(osVersion)
    };
    return await apiPost<LoginResponse>('/auth/login', body);
}

export async function logout(): Promise<void> {
    await apiPost('/auth/logout', {});
}

export async function refreshToken(): Promise<Member> {
    const response = await apiPost<{ mb: Member }>('/auth/refresh', {});
    return response.mb;
}

export async function getProfile(): Promise<Member | null> {
    return await apiGet<Member | null>('/member/profile');
}
