import { API_BASE_URL } from '$lib/config';
import type { ApiResponse } from '$lib/type/api';
import { get } from 'svelte/store';
import { accessToken, csrfToken, getDeviceId } from '$lib/store/auth';

let isRefreshing = false;
let refreshPromise: Promise<void> | null = null;
const pendingRequests: Array<{
    endpoint: string;
    options: RequestInit;
    customFetch: typeof fetch;
    resolve: (value: ApiResponse<any>) => void;
    reject: (reason: any) => void;
}> = [];

async function processPendingRequests(success: boolean): Promise<void> {
    const requests = [...pendingRequests];
    pendingRequests.length = 0;

    for (const request of requests) {
        if (success) {
            try {
                const result = await apiRequest(
                    request.endpoint,
                    request.options,
                    request.customFetch,
                    1
                );
                request.resolve(result);
            } catch (error) {
                request.reject(error);
            }
        } else {
            request.reject(new Error('세션이 만료되었습니다. 다시 로그인해주세요.'));
        }
    }
}

export async function apiRequest<T>(
    endpoint: string,
    options: RequestInit = {},
    customFetch: typeof fetch = fetch,
    retryCount = 0
): Promise<ApiResponse<T>> {
    const url = `${API_BASE_URL}${endpoint}`;

    const headers: HeadersInit = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    const token = get(accessToken);
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const csrf = get(csrfToken);
    if (csrf) {
        headers['X-CSRF-Token'] = csrf;
    }

    const deviceId = getDeviceId();
    if (deviceId) {
        headers['X-Device-Id'] = deviceId;
    }

    if (options.body instanceof FormData) {
        delete headers['Content-Type'];
    }

    const response = await customFetch(url, {
        ...options,
        credentials: 'include',
        headers
    });

    const data: ApiResponse<T> = await response.json();

    if (data.code === '00004' && retryCount === 0) {
        if (isRefreshing) {
            return new Promise((resolve, reject) => {
                pendingRequests.push({
                    endpoint,
                    options,
                    customFetch,
                    resolve,
                    reject
                });
            });
        }

        isRefreshing = true;
        refreshPromise = (async () => {
            try {
                const { refreshToken } = await import('$lib/api/auth');
                await refreshToken();
            } catch (e) {
                if (typeof window !== 'undefined') {
                    window.location.href = '/login';
                }
                throw e;
            } finally {
                isRefreshing = false;
            }
        })();

        try {
            await refreshPromise;
            refreshPromise = null;
            const result = await apiRequest<T>(endpoint, options, customFetch, 1);
            await processPendingRequests(true);
            return result;
        } catch (e) {
            refreshPromise = null;
            await processPendingRequests(false);
            throw new Error('세션이 만료되었습니다. 다시 로그인해주세요.');
        }
    }

    if (data.code !== '00000') {
        throw new Error(data.msg || 'API 요청 실패');
    }

    return data;
}

export async function apiGet<T>(endpoint: string, customFetch: typeof fetch = fetch): Promise<T> {
    const response = await apiRequest<T>(endpoint, { method: 'GET' }, customFetch);
    return response.data;
}

export async function apiPost<T>(endpoint: string, body: any, customFetch: typeof fetch = fetch): Promise<T> {
    const isFormData = body instanceof FormData;
    const response = await apiRequest<T>(endpoint, {
        method: 'POST',
        body: isFormData ? body : JSON.stringify(body)
    }, customFetch);
    return response.data;
}

export async function apiPut<T>(endpoint: string, body: any, customFetch: typeof fetch = fetch): Promise<T> {
    const isFormData = body instanceof FormData;
    const response = await apiRequest<T>(endpoint, {
        method: 'PUT',
        body: isFormData ? body : JSON.stringify(body)
    }, customFetch);
    return response.data;
}

export async function apiDelete<T>(endpoint: string, customFetch: typeof fetch = fetch): Promise<T> {
    const response = await apiRequest<T>(endpoint, { method: 'DELETE' }, customFetch);
    return response.data;
}