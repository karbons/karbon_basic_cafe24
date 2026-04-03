import { API_BASE_URL } from '$lib/config';
import type { ApiResponse } from '$lib/type/api';

// 토큰 갱신 중인지 여부와 대기 중인 요청들을 관리하는 상태
let isRefreshing = false;
let refreshPromise: Promise<void> | null = null;
const pendingRequests: Array<{
    endpoint: string;
    options: RequestInit;
    customFetch: typeof fetch;
    resolve: (value: ApiResponse<any>) => void;
    reject: (reason: any) => void;
}> = [];

// 대기 중인 모든 요청을 처리
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
                    1 // 재시도 횟수를 1로 설정하여 무한 루프 방지
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

    // FormData인 경우 Content-Type 헤더 제거 (브라우저가 자동으로 boundary 설정)
    if (options.body instanceof FormData) {
        // @ts-ignore
        delete headers['Content-Type'];
    }

    const response = await customFetch(url, {
        ...options,
        credentials: 'include', // HTTP Only Cookie를 위한 설정
        headers
    });

    const data: ApiResponse<T> = await response.json();

    // 토큰 만료 감지 (00004) - 자동 갱신 시도
    if (data.code === '00004' && retryCount === 0) {
        // 이미 갱신 중이면 대기 큐에 추가
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

        // 첫 번째 요청만 갱신 시도
        isRefreshing = true;
        refreshPromise = (async () => {
            try {
                const { refreshToken } = await import('$lib/api/auth');
                await refreshToken();
            } catch (e) {
                // Refresh Token도 만료됨
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
            // 갱신 성공 - 원래 요청 재시도
            refreshPromise = null;
            const result = await apiRequest<T>(endpoint, options, customFetch, 1);
            // 대기 중인 요청들도 처리
            await processPendingRequests(true);
            return result;
        } catch (e) {
            // 갱신 실패 - 대기 중인 요청들도 실패 처리
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
