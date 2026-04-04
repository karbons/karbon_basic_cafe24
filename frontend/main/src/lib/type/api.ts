export interface ApiResponse<T> {
    data: T;
    msg: string;
    code: string;
}