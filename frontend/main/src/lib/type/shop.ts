export interface ShopItem {
    it_id: string;
    it_name: string;
    it_price: number;
    it_price_org?: number;
    it_price_type?: number;
    it_stock_qty: number;
    it_use: number;
    it_soldout: number;
    it_img1: string;
    it_img2?: string;
    it_detail?: string;
    ca_id: string;
    mb_id?: string;
    it_time?: string;
    it_update_time?: string;
}

export interface Category {
    ca_id: string;
    ca_name: string;
    ca_order: number;
    ca_use: number;
    ca_mobile_use?: number;
}

export interface CartItem {
    ct_id: string;
    it_id: string;
    it_name: string;
    it_price: number;
    ct_qty: number;
    io_id?: string;
    io_type?: number;
    io_value?: string;
    io_price?: number;
}

export interface Order {
    od_id: string;
    od_time: string;
    od_status: string;
    od_name: string;
    od_tel: string;
    od_hp: string;
    od_email: string;
    od_b_name?: string;
    od_b_tel?: string;
    od_b_hp?: string;
    od_b_addr1?: string;
    od_b_addr2?: string;
    od_deposit_name?: string;
    od_receipt_price: number;
    od_cancel_price?: number;
    od_taxable_use?: number;
    mb_id: string;
    it_total_price: number;
    it_total_qty: number;
}

export interface PaymentPrepareResponse {
    token: string;
    amount: number;
    currency: string;
}