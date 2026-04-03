// Firebase 초기화 및 Firestore/Auth 인스턴스
import { initializeApp, type FirebaseApp } from 'firebase/app';
import { getFirestore, type Firestore } from 'firebase/firestore';
import { getAuth, signInWithCustomToken, signOut, onAuthStateChanged, type Auth, type User } from 'firebase/auth';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID
};

let app: FirebaseApp | null = null;
let db: Firestore | null = null;
let auth: Auth | null = null;

/**
 * Firebase 앱 초기화
 * 환경변수가 설정되지 않으면 null 반환
 */
export function initFirebase(): FirebaseApp | null {
    if (app) return app;

    if (!firebaseConfig.apiKey || !firebaseConfig.projectId) {
        console.warn('Firebase 환경변수가 설정되지 않았습니다. docs/FIREBASE_SETUP.md 참조');
        return null;
    }

    try {
        app = initializeApp(firebaseConfig);
        return app;
    } catch (error) {
        console.error('Firebase 초기화 실패:', error);
        return null;
    }
}

/**
 * Firebase Auth 인스턴스 가져오기
 */
export function getFirebaseAuth(): Auth | null {
    if (auth) return auth;

    const firebaseApp = initFirebase();
    if (!firebaseApp) return null;

    auth = getAuth(firebaseApp);
    return auth;
}

/**
 * Firestore 인스턴스 가져오기
 */
export function getDb(): Firestore | null {
    if (db) return db;

    const firebaseApp = initFirebase();
    if (!firebaseApp) return null;

    db = getFirestore(firebaseApp);
    return db;
}

/**
 * Firebase 사용 가능 여부 확인
 */
export function isFirebaseEnabled(): boolean {
    return !!firebaseConfig.apiKey && !!firebaseConfig.projectId;
}

/**
 * 현재 Firebase 인증된 사용자 가져오기
 */
export function getCurrentUser() {
    const firebaseAuth = getFirebaseAuth();
    return firebaseAuth?.currentUser || null;
}

/**
 * Firebase Auth 상태가 준비될 때까지 대기
 */
export function waitForAuthReady(): Promise<boolean> {
    const firebaseAuth = getFirebaseAuth();
    if (!firebaseAuth) return Promise.resolve(false);

    return new Promise((resolve) => {
        const unsubscribe = onAuthStateChanged(firebaseAuth, (user) => {
            unsubscribe();
            resolve(!!user);
        });
    });
}

/**
 * Custom Token으로 Firebase 로그인
 * 백엔드에서 받은 custom token으로 Firebase 인증
 */
export async function signInWithFirebaseToken(customToken: string): Promise<boolean> {
    const firebaseAuth = getFirebaseAuth();
    if (!firebaseAuth) return false;

    try {
        await signInWithCustomToken(firebaseAuth, customToken);
        return true;
    } catch (error) {
        return false;
    }
}

/**
 * Firebase 로그아웃
 */
export async function signOutFirebase(): Promise<void> {
    const firebaseAuth = getFirebaseAuth();
    if (!firebaseAuth) return;

    try {
        await signOut(firebaseAuth);
    } catch (error) {
        console.error('Firebase 로그아웃 실패:', error);
    }
}


