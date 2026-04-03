export interface Member {
    mb_id: string;
    mb_name: string;
    mb_nick: string;
    mb_level: number;
    mb_point: number;
    mb_memo_cnt?: number;
    mb_scrap_cnt?: number;
}

export interface LoginRequest {
    mb_id: string;
    mb_password: string;
    auto_login?: boolean;
}

export interface LoginResponse {
    mb: Member;
    firebase_token?: string | null;
}
