// 채팅 서비스 - Firebase Firestore 기반
import {
    collection,
    doc,
    addDoc,
    updateDoc,
    query,
    where,
    orderBy,
    limit,
    onSnapshot,
    serverTimestamp,
    type Unsubscribe,
    type QuerySnapshot,
    type QueryDocumentSnapshot,
    Timestamp,
    getDoc,
    getDocs,
    setDoc,
    writeBatch,
    arrayUnion,
    increment
} from 'firebase/firestore';
import { getDb } from './firebase';

// 타입 정의
export interface ChatRoom {
    id: string;
    type: 'support' | 'member';
    participants: string[];
    participantNames: Record<string, string>;
    createdAt: Date;
    lastMessage: string;
    lastMessageAt: Date;
    lastSenderId: string;
    unreadCounts?: Record<string, number>;
}

export interface ChatMessage {
    id: string;
    senderId: string;
    senderName: string;
    content: string;
    createdAt: Date;
    readBy: string[];
    type?: 'text' | 'template';
    templateId?: string;
    templateData?: any;
}

// 고객센터 관리자 ID (환경변수로 설정 가능)
const SUPPORT_ADMIN_ID = 'support_admin';
const SUPPORT_ADMIN_NAME = '고객센터';

/**
 * 채팅방 생성 또는 기존 방 가져오기
 */
export async function getOrCreateChatRoom(
    userId: string,
    userName: string,
    type: 'support' | 'member',
    targetUserId?: string,
    targetUserName?: string
): Promise<string | null> {
    const db = getDb();
    if (!db) return null;

    const participants = type === 'support'
        ? [userId, SUPPORT_ADMIN_ID].sort()
        : [userId, targetUserId!].sort();

    // 기존 방 찾기
    const roomsRef = collection(db, 'chatRooms');
    const q = query(
        roomsRef,
        where('participants', '==', participants),
        where('type', '==', type),
        limit(1)
    );

    const snapshot = await getDocs(q);

    if (!snapshot.empty) {
        return snapshot.docs[0].id;
    }

    // 새 방 생성
    const participantNames: Record<string, string> = {
        [userId]: userName
    };

    if (type === 'support') {
        participantNames[SUPPORT_ADMIN_ID] = SUPPORT_ADMIN_NAME;
    } else if (targetUserId && targetUserName) {
        participantNames[targetUserId] = targetUserName;
    }

    const newRoom = await addDoc(roomsRef, {
        type,
        participants,
        participantNames,
        createdAt: serverTimestamp(),
        lastMessage: '',
        lastMessageAt: serverTimestamp(),
        lastSenderId: '',
        unreadCounts: {}
    });

    return newRoom.id;
}

/**
 * 메시지 전송
 */
export async function sendMessage(
    roomId: string,
    senderId: string,
    senderName: string,
    content: string,
    templateId?: string,
    templateData?: any
): Promise<boolean> {
    const db = getDb();
    if (!db) return false;

    try {
        const messagesRef = collection(db, 'chatRooms', roomId, 'messages');

        await addDoc(messagesRef, {
            senderId,
            senderName,
            content,
            createdAt: serverTimestamp(),
            readBy: [senderId],
            type: templateId ? 'template' : 'text',
            templateId: templateId || null,
            templateData: templateData || null
        });

        // 채팅방 lastMessage 업데이트 및 안 읽은 수 증가
        const roomRef = doc(db, 'chatRooms', roomId);

        // 방 정보 가져오기 (참가자 확인용)
        const roomSnap = await getDoc(roomRef);
        const updateData: any = {
            lastMessage: content.length > 50 ? content.substring(0, 50) + '...' : content,
            lastMessageAt: serverTimestamp(),
            lastSenderId: senderId
        };

        if (roomSnap.exists()) {
            const roomData = roomSnap.data();
            const participants = roomData.participants || [];
            participants.forEach((p: string) => {
                if (p !== senderId) {
                    updateData[`unreadCounts.${p}`] = increment(1);
                }
            });
        }

        await updateDoc(roomRef, updateData);

        return true;
    } catch (error) {
        console.error('메시지 전송 실패:', error);
        return false;
    }
}

/**
 * 채팅방 목록 구독
 */
export function subscribeToChatRooms(
    userId: string,
    callback: (rooms: ChatRoom[]) => void
): Unsubscribe | null {
    const db = getDb();
    if (!db) return null;

    const roomsRef = collection(db, 'chatRooms');
    const q = query(
        roomsRef,
        where('participants', 'array-contains', userId),
        orderBy('lastMessageAt', 'desc')
    );

    return onSnapshot(q, (snapshot: QuerySnapshot) => {
        const rooms: ChatRoom[] = snapshot.docs.map((doc: QueryDocumentSnapshot) => {
            const data = doc.data();
            return {
                id: doc.id,
                type: data.type,
                participants: data.participants,
                participantNames: data.participantNames || {},
                createdAt: data.createdAt?.toDate() || new Date(),
                lastMessage: data.lastMessage || '',
                lastMessageAt: data.lastMessageAt?.toDate() || new Date(),
                lastSenderId: data.lastSenderId || '',
                unreadCounts: data.unreadCounts || {}
            };
        });
        callback(rooms);
    });
}

/**
 * 메시지 목록 구독
 */
export function subscribeToMessages(
    roomId: string,
    callback: (messages: ChatMessage[]) => void
): Unsubscribe | null {
    const db = getDb();
    if (!db) return null;

    const messagesRef = collection(db, 'chatRooms', roomId, 'messages');
    const q = query(messagesRef, orderBy('createdAt', 'asc'));

    return onSnapshot(q, (snapshot) => {
        const messages: ChatMessage[] = snapshot.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                senderId: data.senderId,
                senderName: data.senderName,
                content: data.content,
                createdAt: data.createdAt?.toDate() || new Date(),
                readBy: data.readBy || [],
                type: data.type || 'text',
                templateId: data.templateId,
                templateData: data.templateData
            };
        });
        callback(messages);
    });
}

/**
 * 메시지 읽음 처리
 */
export async function markMessagesAsRead(
    roomId: string,
    userId: string
): Promise<void> {
    const db = getDb();
    if (!db) return;

    try {
        const messagesRef = collection(db, 'chatRooms', roomId, 'messages');
        // 최근 30개 메시지 중 내가 읽지 않은 것 확인 (Firestore 쿼리 제약으로 인해 클라이언트 필터링)
        const q = query(messagesRef, orderBy('createdAt', 'desc'), limit(30));
        const snapshot = await getDocs(q);

        const batch = writeBatch(db);
        let updateCount = 0;

        snapshot.docs.forEach(doc => {
            const data = doc.data();
            const readBy = data.readBy || [];

            if (!readBy.includes(userId)) {
                batch.update(doc.ref, {
                    readBy: arrayUnion(userId)
                });
                updateCount++;
            }
        });

        if (updateCount > 0) {
            // 채팅방의 내 안 읽은 카운트 초기화
            const roomRef = doc(db, 'chatRooms', roomId);
            batch.update(roomRef, {
                [`unreadCounts.${userId}`]: 0
            });

            await batch.commit();
        }
    } catch (error) {
        console.error('메시지 읽음 처리 실패:', error);
    }
}
