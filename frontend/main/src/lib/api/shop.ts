import { apiGet, apiPost, apiPut, apiDelete } from './api';
import type { ShopItem, Category, CartItem, Order, PaymentPrepareResponse } from '../type/shop';

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

export async function getShopItem(it_id: string) {
    return apiGet<{
        item: ShopItem;
        options: any[];
        relations: ShopItem[];
    }>(`/shop/item/${it_id}`);
}

export async function getCartList() {
    return apiGet<{
        list: CartItem[];
        total_price: number;
        total_qty: number;
    }>('/shop/cart');
}

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

export async function getOrderList(page = 1) {
    return apiGet<{
        list: Order[];
        total_count: number;
        total_page: number;
    }>(`/shop/order/list?page=${page}`);
}

export async function getOrderDetail(od_id: string) {
    return apiGet<Order>(`/shop/order/${od_id}`);
}

export async function preparePayment(orderData: any) {
    return apiPost<PaymentPrepareResponse>('/shop/payment/prepare', orderData);
}