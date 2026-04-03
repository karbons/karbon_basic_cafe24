# Firebase 채팅 시스템 설정 가이드

이 문서는 Gnuboard Karbon 프로젝트에서 Firebase 채팅 기능을 사용하기 위한 설정 가이드입니다.

---

## 1. Firebase 프로젝트 생성

### 1.1 Firebase Console 접속
[Firebase Console](https://console.firebase.google.com/)에 접속하여 Google 계정으로 로그인합니다.

### 1.2 새 프로젝트 생성
1. **"프로젝트 추가"** 버튼 클릭
2. 프로젝트 이름 입력 (예: `gnuboard-karbon-chat`)
3. Google Analytics 설정 (선택사항, 비활성화 권장)
4. **"프로젝트 만들기"** 클릭

---

## 2. Firestore 데이터베이스 설정

### 2.1 Firestore 생성
1. Firebase Console 좌측 메뉴에서 **"Firestore Database"** 클릭
2. **"데이터베이스 만들기"** 클릭
3. **"프로덕션 모드에서 시작"** 선택
4. 위치 선택: **asia-northeast3 (서울)** 추천
5. **"사용 설정"** 클릭

### 2.2 보안 규칙 설정
Firestore Database > 규칙 탭에서 아래 규칙으로 교체:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // 채팅방: 참여자만 읽기/쓰기 가능
    match /chatRooms/{roomId} {
      allow read, write: if request.auth != null 
        && request.auth.uid in resource.data.participants;
      allow create: if request.auth != null;
      
      // 메시지: 채팅방 참여자만 접근
      match /messages/{messageId} {
        allow read, write: if request.auth != null;
      }
    }
    
    // 사용자 프로필: 본인만 쓰기, 모두 읽기
    match /users/{userId} {
      allow read: if request.auth != null;
      allow write: if request.auth != null && request.auth.uid == userId;
    }
  }
}
```

---

## 3. 웹 앱 등록

### 3.1 앱 추가
1. Firebase Console 프로젝트 설정(⚙️) > **"일반"** 탭
2. **"내 앱"** 섹션에서 웹 아이콘(`</>`) 클릭
3. 앱 닉네임 입력 (예: `karbon-web`)
4. **"앱 등록"** 클릭

### 3.2 Config 값 복사
등록 후 표시되는 `firebaseConfig` 객체를 복사합니다:

```javascript
const firebaseConfig = {
  apiKey: "AIza...",
  authDomain: "your-project.firebaseapp.com",
  projectId: "your-project",
  storageBucket: "your-project.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123"
};
```

---

## 4. 환경변수 설정

### 4.1 .env 파일 생성
`frontend/.env` 파일에 아래 환경변수 추가:

```env
# Firebase Configuration
VITE_FIREBASE_API_KEY=AIza...
VITE_FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
VITE_FIREBASE_PROJECT_ID=your-project
VITE_FIREBASE_STORAGE_BUCKET=your-project.appspot.com
VITE_FIREBASE_MESSAGING_SENDER_ID=123456789
VITE_FIREBASE_APP_ID=1:123456789:web:abc123
```

### 4.2 .env.example 업데이트
다른 개발자를 위해 `frontend/.env.example`에도 동일한 형식으로 추가 (값은 비워둠)

---

## 5. Firebase 패키지 설치

```bash
cd frontend
npm install firebase
```

---

## 6. Firebase Authentication 활성화 (필수)

Custom Token 인증을 사용하려면 **반드시 Firebase Authentication을 활성화**해야 합니다.

1. [Firebase Console](https://console.firebase.google.com/) → 프로젝트 선택
2. 왼쪽 메뉴에서 **Authentication** 클릭
3. **시작하기** 버튼 클릭
4. **Sign-in method** 탭에서 **Anonymous** (익명 로그인) 활성화

> ⚠️ **중요**: Custom Token으로 로그인하려면 최소 하나의 로그인 방법이 활성화되어 있어야 합니다. Anonymous가 가장 간단합니다.

활성화하지 않으면 다음 에러가 발생합니다:
```
FirebaseError: Error (auth/configuration-not-found)
```

---

## 7. Custom Token 인증 설정 (보안 강화)

Gnuboard 회원 시스템과 Firebase를 안전하게 연동하려면 Custom Token 방식을 사용합니다.

### 6.1 서비스 계정 키 생성
1. Firebase Console → 프로젝트 설정(⚙️) → **"서비스 계정"** 탭
2. **"새 비공개 키 생성"** 클릭
3. JSON 파일 다운로드
4. 파일을 `frontend/` 디렉토리에 저장 (예: `karbon-builder-firebase-adminsdk-xxxxx.json`)

> ⚠️ **주의**: 이 파일은 절대 Git에 커밋하지 마세요! `.gitignore`에 추가되어 있어야 합니다.

### 6.2 백엔드 환경변수 설정
`gnu5_api/gnuboard/api/.env` 파일에 추가:

```env
# Firebase Admin SDK 설정
FIREBASE_SERVICE_ACCOUNT_PATH=/path/to/your-firebase-adminsdk.json
```

### 6.3 PHP Firebase Admin SDK 설치

> ⚠️ **중요**: PHP 버전에 맞는 SDK 버전을 설치해야 합니다!

| SDK 버전 | PHP 요구 버전 | 설치 명령어 |
|---------|-------------|-----------|
| 7.x | PHP 8.2+ | `composer require kreait/firebase-php` |
| 6.x | PHP 8.1+ | `composer require kreait/firebase-php:^6.0` |
| 5.x | PHP 7.4+ | `composer require kreait/firebase-php:^5.0` |

PHP 버전 확인:
```bash
php -v
```

설치 (PHP 8.1 예시):
```bash
cd gnu5_api/gnuboard/api
composer require kreait/firebase-php:^6.0
```

### 6.4 Firestore 보안 규칙 (Custom Token 사용 시)
Custom Token을 사용하면 아래 규칙으로 보안을 강화할 수 있습니다:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // 채팅방: 인증된 사용자만 접근
    match /chatRooms/{roomId} {
      allow read, write: if request.auth != null;
      
      match /messages/{messageId} {
        allow read, write: if request.auth != null;
      }
    }
  }
}
```

---

## 체크리스트

- [ ] Firebase 프로젝트 생성
- [ ] Firestore 데이터베이스 활성화
- [ ] 보안 규칙 설정
- [ ] 웹 앱 등록 및 Config 값 복사
- [ ] `.env` 환경변수 설정
- [ ] `npm install firebase` 실행
- [ ] (선택) 서비스 계정 키 생성 및 Custom Token 설정

---

## 문제 해결

### "Firebase App not initialized" 에러
→ `.env` 파일에 환경변수가 올바르게 설정되었는지 확인

### "Permission denied" 에러
→ Firestore 보안 규칙 확인 및 로그인 상태 확인
→ Custom Token 인증이 제대로 되었는지 확인

### 실시간 업데이트가 안될 때
→ 브라우저 콘솔에서 WebSocket 연결 상태 확인

