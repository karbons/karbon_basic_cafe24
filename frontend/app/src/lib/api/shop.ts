import { apiGet, apiPost, apiPut, apiDelete } from './api';
import type { ShopItem, Category, CartItem, Order, PaymentPrepareResponse } from '../type/shop';

// 상품 목록 조회
export async function getShopItems(params: {
    ca_id?: string;
    it_type?: number;
    stx?: string;
    sort?: string;
    page?: number;
    page_rows?: number;
}) {
    const query = new URLSearchParams(params as any).toString();
    return apiGet<{
        items: ShopItem[];
        category?: Category;
        total_count: number;
        total_page: number;
    }>(`/shop/list?${query}`);
}

// 상품 상세 조회
export async function getShopItem(it_id: string) {
    return apiGet<{
        item: ShopItem;
        options: any[];
        relations: ShopItem[];
    }>(`/shop/item/${it_id}`);
}

// 장바구니 목록
export async function getCartList() {
    return apiGet<{
        list: CartItem[];
        total_price: number;
        total_qty: number;
    }>('/shop/cart');
}

// 장바구니 추가
export async function addToCart(data: {
    it_id: string;
    it_name: string;
    it_price: number;
    ct_qty: number;
    io_id: string;
    io_type: number;
    io_value: string;
}) {
    return apiPost('/shop/cart/update', data);
}

// 주문 내역
export async function getOrderList(page = 1) {
    return apiGet<{
        list: Order[];
        total_count: number;
        total_page: number;
    }>(`/shop/order/list?page=${page}`);
}

// 주문 상세
export async function getOrderDetail(od_id: string) {
    return apiGet<Order>(`/shop/order/${od_id}`);
}

// 결제 준비 (암호화 토큰 발급)
export async function preparePayment(orderData: any) {
    return apiPost<PaymentPrepareResponse>('/shop/payment/prepare', orderData);
}
