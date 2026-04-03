export interface Menu {
    me_id: number;
    me_name: string;
    me_link: string;
    me_target: string;
    sub?: Menu[];
}
