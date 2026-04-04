import { apiGet } from './api';
import type { Menu } from '$lib/store/menu';

interface MenuResponse {
    menus: Menu[];
}

export async function getMenus(device: 'pc' | 'mobile' = 'pc'): Promise<Menu[]> {
    const response = await apiGet<MenuResponse>(`/menu?device=${device}`);
    return response.menus;
}