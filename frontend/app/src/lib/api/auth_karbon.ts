import { apiPost, apiGet } from './api';
import type { LoginRequest, LoginResponse } from '$lib/type/member';
import { get } from 'svelte/store';
import { fcmToken, deviceModel, osVersion } from '$lib/store/device';

// Karbon 전용 로그인 API
export async function loginKarbon(credentials: LoginRequest): Promise<LoginResponse> {
    const body = {
        ...credentials,
        fcm_token: get(fcmToken),
        device_model: get(deviceModel),
        os_version: get(osVersion)
    };
    // Karbon 전용 엔드포인트 사용
    return await apiPost<LoginResponse>('/auth/karbon/login', body);
}

// Karbon 전용 회원가입 API
export async function registerKarbon(data: any): Promise<void> {
    return await apiPost('/auth/karbon/register', data);
}

// OTP 전송 API (Karbon)
export async function sendOtp(hp: string): Promise<void> {
    return await apiPost('/auth/karbon/otp', { hp });
}

// 2FA APIs
export async function send2FACode(method: 'sms' | 'email' | 'password'): Promise<void> {
    // Check if password method, nothing to send
    if (method === 'password') return;

    // Simulate API call
    return new Promise((resolve) => setTimeout(() => {
        console.log(`Sending 2FA code via ${method}`);
        resolve();
    }, 500));
}

export async function verify2FA(data: { code: string; type: string }): Promise<{ verified: boolean }> {
    // Simulate API call
    return new Promise((resolve, reject) => setTimeout(() => {
        if (data.code === '000000' || data.code.length > 3) {
            resolve({ verified: true });
        } else {
            reject(new Error("잘못된 인증코드입니다."));
        }
    }, 500));
}

export async function check2FAStatus(): Promise<{ required: boolean; authenticated: boolean }> {
    // This function should be called by pages that require 2FA to check status
    // Returns if 2FA is required for current action/session and if it is already authenticated.
    return Promise.resolve({ required: true, authenticated: false });
}

// 비밀번호 재설정 API
export async function resetPassword(method: 'email' | 'phone', value: string): Promise<void> {
    const endpoint = method === 'email'
        ? '/auth/karbon/reset-password/email'
        : '/auth/karbon/reset-password/phone';

    const body = method === 'email'
        ? { email: value }
        : { phone: value };

    return await apiPost(endpoint, body);
}
