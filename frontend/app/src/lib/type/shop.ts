export interface ShopConfig {
    de_root_index_use: number;
    de_shop_mobile_use: number;
    de_type1_list_use: number;
    de_type2_list_use: number;
    de_type3_list_use: number;
    de_type4_list_use: number;
    de_type5_list_use: number;
    // 필요한 추가 설정들...
}

export interface Category {
    ca_id: string;
    ca_name: string;
    ca_use: number;
    children?: Category[];
}

export interface ShopItem {
    it_id: string;
    it_name: string;
    it_sc_type: number;
    it_sc_method: number;
    it_sc_price: number;
    it_sc_minimum: number;
    it_sc_qty: number;
    it_cust_price: number;
    it_price: number;
    it_point: number;
    it_stock_qty: number;
    it_use: number;
    it_img1?: string;
    it_img2?: string;
    it_img3?: string;
    it_basic?: string;
    it_explan?: string;
    it_mobile_explan?: string;
    ca_id: string;
    ca_id2?: string;
    ca_id3?: string;
    it_type1: number;
    it_type2: number;
    it_type3: number;
    it_type4: number;
    it_type5: number;
    it_sum_qty: number;
    it_hit: number;
    it_time: string;
    it_update_time: string;
    // 썸네일 정보
    it_img_url?: string;
}

export interface CartItem {
    ct_id: number;
    it_id: string;
    it_name: string;
    ct_option: string;
    ct_qty: number;
    ct_price: number;
    ct_point: number;
    ct_status: string;
    it_img_url?: string;
}

export interface OrderItem {
    od_id: string;
    it_id: string;
    it_name: string;
    ct_option: string;
    ct_qty: number;
    ct_price: number;
    cp_price: number;
    ct_status: string;
}

export interface Order {
    od_id: string;
    mb_id: string;
    od_name: string;
    od_email: string;
    od_tel: string;
    od_hp: string;
    od_zip1: string;
    od_zip2: string;
    od_addr1: string;
    od_addr2: string;
    od_addr3: string;
    od_addr_jibeon: string;
    od_deposit_name: string;
    od_pay_type: string;
    od_cart_price: number;
    od_send_cost: number;
    od_receipt_price: number;
    od_cancel_price: number;
    od_status: string;
    od_time: string;
    items?: OrderItem[];
}

export interface PaymentPrepareResponse {
    token: string;
    bridge_url: string;
}
