// 채팅 상태 관리 스토어
import { writable, derived, get } from 'svelte/store';
import type { ChatRoom, ChatMessage } from '$lib/firebase/chat';
import {
    subscribeToChatRooms,
    subscribeToMessages,
    getOrCreateChatRoom,
    sendMessage
} from '$lib/firebase/chat';
import { isFirebaseEnabled, waitForAuthReady, getCurrentUser } from '$lib/firebase/firebase';
import type { Unsubscribe } from 'firebase/firestore';
import { memberStore } from './member';

// 채팅 위젯 상태
export const isChatOpen = writable(false);
export const currentView = writable<'rooms' | 'chat'>('rooms');
export const currentRoomId = writable<string | null>(null);

// 채팅 데이터
export const chatRooms = writable<ChatRoom[]>([]);
export const currentMessages = writable<ChatMessage[]>([]);
export const isLoading = writable(false);

// 읽지 않은 메시지 수
export const unreadCount = derived(
    [chatRooms, memberStore],
    ([$rooms, $member]) => {
        if (!$member || !$rooms) return 0;
        return $rooms.reduce((total, room) => {
            const count = (room.unreadCounts && room.unreadCounts[$member.mb_id]) || 0;
            return total + count;
        }, 0);
    }
);

// Firebase 활성화 여부
export const chatEnabled = writable(false);

// 구독 관리
let roomsUnsubscribe: Unsubscribe | null = null;
let messagesUnsubscribe: Unsubscribe | null = null;

/**
 * 채팅 시스템 초기화 (Firebase Auth 대기 후 실행)
 */
export async function initChat(userId: string): Promise<void> {
    if (!isFirebaseEnabled()) {
        chatEnabled.set(false);
        return;
    }

    // Firebase Auth 상태 복원 대기
    const isAuthenticated = await waitForAuthReady();
    const currentUser = getCurrentUser();

    if (!isAuthenticated || !currentUser) {
        chatEnabled.set(false);
        return;
    }

    chatEnabled.set(true);

    // 기존 구독 정리
    cleanupSubscriptions();

    // 채팅방 목록 구독
    roomsUnsubscribe = subscribeToChatRooms(userId, (rooms) => {
        chatRooms.set(rooms);
    });
}

/**
 * 채팅방 열기
 */
export async function openChatRoom(roomId: string): Promise<void> {
    currentRoomId.set(roomId);
    currentView.set('chat');
    isLoading.set(true);

    // 기존 메시지 구독 정리
    if (messagesUnsubscribe) {
        messagesUnsubscribe();
        messagesUnsubscribe = null;
    }

    // 메시지 구독
    messagesUnsubscribe = subscribeToMessages(roomId, (messages) => {
        currentMessages.set(messages);
        isLoading.set(false);
    });
}

/**
 * 고객센터 채팅 시작
 */
export async function startSupportChat(
    userId: string,
    userName: string
): Promise<void> {
    isLoading.set(true);

    const roomId = await getOrCreateChatRoom(userId, userName, 'support');

    if (roomId) {
        await openChatRoom(roomId);
    }

    isLoading.set(false);
}

/**
 * 회원간 채팅 시작
 */
export async function startMemberChat(
    userId: string,
    userName: string,
    targetUserId: string,
    targetUserName: string,
    initialMessage?: string,
    templateId?: string,
    templateData?: any
): Promise<void> {
    isLoading.set(true);

    const roomId = await getOrCreateChatRoom(
        userId,
        userName,
        'member',
        targetUserId,
        targetUserName
    );

    if (roomId) {
        await openChatRoom(roomId);
        isChatOpen.set(true);

        if (initialMessage) {
            await sendMessage(roomId, userId, userName, initialMessage, templateId, templateData);
        }
    }

    isLoading.set(false);
}

/**
 * 메시지 전송
 */
export async function sendChatMessage(
    senderId: string,
    senderName: string,
    content: string,
    templateId?: string,
    templateData?: any
): Promise<boolean> {
    const roomId = get(currentRoomId);
    if (!roomId || !content.trim()) return false;

    return await sendMessage(roomId, senderId, senderName, content.trim(), templateId, templateData);
}

/**
 * 채팅방 목록으로 돌아가기
 */
export function backToRooms(): void {
    currentView.set('rooms');
    currentRoomId.set(null);
    currentMessages.set([]);

    if (messagesUnsubscribe) {
        messagesUnsubscribe();
        messagesUnsubscribe = null;
    }
}

/**
 * 채팅 위젯 토글
 */
export function toggleChat(): void {
    isChatOpen.update(v => !v);
}

/**
 * 채팅 위젯 닫기
 */
export function closeChat(): void {
    isChatOpen.set(false);
}

/**
 * 구독 정리
 */
export function cleanupSubscriptions(): void {
    if (roomsUnsubscribe) {
        roomsUnsubscribe();
        roomsUnsubscribe = null;
    }
    if (messagesUnsubscribe) {
        messagesUnsubscribe();
        messagesUnsubscribe = null;
    }
}

// 외부에서 채팅 입력창에 텍스트 삽입
export const textToInsert = writable<string | null>(null);

export function insertTextToChat(text: string): void {
    textToInsert.set(text);
    isChatOpen.set(true);
}

// 채팅방 설정 관리
import type { ChatRoomSettings } from '$lib/type/chat';
import { apiGet } from '$lib/api';

export const roomSettings = writable<Record<string, ChatRoomSettings>>({});

export async function loadChatRoomSettings(roomId?: string) {
    try {
        const url = roomId ? `/chat/settings?room_id=${roomId}` : '/chat/settings';
        const res = await apiGet(url);

        if (roomId) {
            roomSettings.update(settings => ({
                ...settings,
                [roomId]: res
            }));
        } else {
            roomSettings.set(res);
        }
    } catch (e) {
        console.error('Failed to load chat settings:', e);
    }
}

export async function updateChatRoomSettings(roomId: string, data: Partial<ChatRoomSettings>) {
    // Optimistic update
    roomSettings.update(settings => {
        const current = settings[roomId] || {};
        return {
            ...settings,
            [roomId]: { ...current, ...data } as ChatRoomSettings
        };
    });

    // API call is handled in the component for now, but state update is needed here
    // If we wanted to move API call here:
    // await apiPost('/chat/settings', { room_id: roomId, ...data });
}
