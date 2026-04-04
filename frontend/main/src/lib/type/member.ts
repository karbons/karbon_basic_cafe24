export interface Member {
    mb_id: string;
    mb_name: string;
    mb_nick: string;
    mb_email?: string;
    mb_level: number;
    mb_point: number;
    mb_memo_cnt?: number;
    mb_scrap_cnt?: number;
}

export interface LoginRequest {
    mb_id?: string;
    mb_password?: string;
    mb_email?: string;
    mb_hp?: string;
    mb_otp?: string;
    login_type?: string;
    auto_login?: boolean;
}

export interface LoginResponse {
    mb: Member;
    firebase_token?: string | null;
    access_token?: string;
    csrf_token?: string;
    device_id?: string;
}