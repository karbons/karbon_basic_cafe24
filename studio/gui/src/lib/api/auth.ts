const API_BASE = import.meta.env.VITE_API_BASE_URL || '/v1/fapi';

interface LoginPayload {
  mb_id?: string;
  mb_email?: string;
  mb_password?: string;
  mb_hp?: string;
  mb_otp?: string;
  login_type?: 'email' | 'phone';
  auto_login?: boolean;
}

interface RegisterPayload {
  mb_id: string;
  mb_email: string;
  mb_password: string;
  mb_name: string;
  mb_hp?: string;
}

interface FindPasswordPayload {
  mb_email: string;
  mb_name: string;
}

interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  code?: string;
  msg?: string;
  data?: T;
  mb?: any;
  firebase_token?: string;
  [key: string]: any;
}

async function fetchApi<T = ApiResponse>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE}${endpoint}`;
  
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      credentials: 'include',
    });

    const contentType = response.headers.get('content-type');
    let data: any;
    
    if (contentType && contentType.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }

    if (!response.ok) {
      if (data.msg || data.message) {
        throw new Error(data.msg || data.message);
      }
      throw new Error(`API request failed: ${response.status} ${response.statusText}`);
    }

    if (data.code && data.code !== '00000') {
      throw new Error(data.msg || data.message || 'API request failed');
    }

    return data;
  } catch (e: any) {
    if (e instanceof TypeError && e.message.includes('fetch')) {
      throw new Error('서버에 연결할 수 없습니다.');
    }
    throw e;
  }
}

export async function login(payload: LoginPayload): Promise<ApiResponse> {
  return fetchApi('/auth/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function logout(): Promise<ApiResponse> {
  return fetchApi('/auth/logout', {
    method: 'POST',
  });
}

export async function refreshToken(): Promise<ApiResponse> {
  return fetchApi('/auth/refresh', {
    method: 'POST',
  });
}

export async function register(payload: RegisterPayload): Promise<ApiResponse> {
  return fetchApi('/auth/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function findPassword(
  payload: FindPasswordPayload
): Promise<ApiResponse> {
  return fetchApi('/auth/find-password', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function checkAuth(): Promise<ApiResponse> {
  return fetchApi('/auth/me', {
    method: 'GET',
  });
}