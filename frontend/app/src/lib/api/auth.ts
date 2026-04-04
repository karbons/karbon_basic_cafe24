import { apiPost, apiGet } from './api';
import type { Member, LoginRequest, LoginResponse } from '$lib/type/member';
export type { Member, LoginRequest, LoginResponse };

import { get } from 'svelte/store';
import { fcmToken, deviceModel, osVersion } from '$lib/store/device';
import { accessToken, setAccessToken, clearAccessToken, setCsrfToken, clearCsrfToken } from '$lib/store/auth';
import { setMember } from '$lib/store/member';

export async function login(credentials: LoginRequest): Promise<LoginResponse> {
    const body = {
        ...credentials,
        fcm_token: get(fcmToken),
        device_model: get(deviceModel),
        os_version: get(osVersion)
    };
    const response = await apiPost<LoginResponse>('/auth/login', body);

    if (response.access_token) {
        setAccessToken(response.access_token);
    }
    if (response.csrf_token) {
        setCsrfToken(response.csrf_token);
    }
    if (response.mb) {
        setMember(response.mb);
    }

    return response;
}

export async function logout(): Promise<void> {
    await apiPost('/auth/logout', {});
    clearAccessToken();
    clearCsrfToken();
}

export async function refreshToken(): Promise<Member> {
    const response = await apiPost<{ mb: Member; access_token?: string; csrf_token?: string }>('/auth/refresh', {});

    if (response.access_token) {
        setAccessToken(response.access_token);
    }
    if (response.csrf_token) {
        setCsrfToken(response.csrf_token);
    }
    if (response.mb) {
        setMember(response.mb);
    }

    return response.mb;
}

export async function getProfile(): Promise<Member | null> {
    return await apiGet<Member | null>('/member/profile');
}