import { apiPost, apiGet } from './api';
import type { Member, LoginRequest, LoginResponse } from '$lib/type/member';
export type { Member, LoginRequest, LoginResponse };

import { setMember } from '$lib/store/member';
import { accessToken, setAccessToken, clearAccessToken, setCsrfToken, clearCsrfToken } from '$lib/store/auth';

export async function login(credentials: LoginRequest): Promise<LoginResponse> {
    const body = {
        ...credentials
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

export interface RegisterRequest {
    mb_id: string;
    mb_password: string;
    mb_name: string;
    mb_nick: string;
    mb_email: string;
    mb_mailling?: boolean;
    mb_open?: boolean;
    mb_homepage?: string;
    mb_tel?: string;
    mb_hp?: string;
    mb_zip?: string;
    mb_addr1?: string;
    mb_addr2?: string;
}

export async function register(credentials: RegisterRequest): Promise<LoginResponse> {
    const response = await apiPost<LoginResponse>('/auth/register', credentials);
    return response;
}