import type { ChatRoom, ChatMessage } from '$lib/firebase/chat';

export interface ChatRoomSettings {
    room_id: string;
    mb_id: string;
    room_alias?: string | null;
    room_image?: string | null;
    bg_color?: string | null;
    bg_image?: string | null;
    is_pinned: number; // 0 or 1
    is_favorite: number; // 0 or 1
    is_alarm: number; // 0 or 1
}

export type { ChatRoom, ChatMessage };
