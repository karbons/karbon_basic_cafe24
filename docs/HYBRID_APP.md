# 📱 하이브리드앱 기능

Karbon 보일러플레이트가 제공하는 하이브리드앱 기능을 소개합니다.

## 🎯 기능 목록

- ✅ Push Notifications (푸시 알림)
- ✅ Deep Linking (딥링크)
- ✅ In-App Browser (인앱 브라우저)
- ✅ Firebase Chat (실시간 채팅)
- ✅ Payment Integration (결제 연동)
- ✅ Safe Area (안전 영역)
- ✅ Splash Screen (스플래시 화면)
- ✅ App Update Check (앱 업데이트 체크)

---

## 🔔 Push Notifications

### 기능 설명
Firebase Cloud Messaging을 통해 푸시 알림을 수신합니다.

### 사용법
```typescript
import { initDevice } from '$lib/util/device';

// 앱 시작 시 초기화
initDevice();
```

### 설정
1. Firebase Console에서 프로젝트 생성
2. `google-services.json` (Android) / `GoogleService-Info.plist` (iOS) 다운로드
3. `.env` 파일에 Firebase 설정 추가

---

## 🔗 Deep Linking

### 기능 설명
앱 외부에서 특정 URL로 앱을 열 수 있습니다.

### 사용법
```typescript
// +layout.svelte
import { App } from '@capacitor/app';

App.addListener('appUrlOpen', (data) => {
  const url = data.url;
  // URL 처리 로직
});
```

### URL Scheme 설정
- iOS: `Info.plist`에 URL Types 추가
- Android: `AndroidManifest.xml`에 intent-filter 추가

---

## 🌐 In-App Browser

### 기능 설명
외부 링크를 앱 내 브라우저에서 엽니다.

### 사용법
```typescript
import { Browser } from '@capacitor/browser';

// 외부 링크 열기
await Browser.open({ url: 'https://example.com' });

// 닫기
await Browser.close();
```

---

## 💬 Firebase Chat

### 기능 설명
Firebase Firestore를 이용한 실시간 채팅 기능.

### 사용법
```typescript
import { initFirebase } from '$lib/firebase/firebase';

// Firebase 초기화
initFirebase();
```

### 설정
- Firebase Console에서 Firestore 데이터베이스 생성
- 보안 규칙 설정

---

## 💳 Payment Integration

### 기능 설명
결제 게이트웨이 연동 (KG이니시스, 토스페이먼츠 등).

### 사용법
```typescript
import { Browser } from '@capacitor/browser';

// 결제 페이지 열기
await Browser.open({ url: paymentUrl });

// 결제 결과 딥링크로 수신
```

---

## 📐 Safe Area

### 기능 설명
iPhone 노치, 홈 인디케이터 등 안전 영역 처리.

### 사용법
```svelte
<!-- Tailwind CSS 클래스 사용 -->
<div class="pt-safe pb-safe">
  <!-- 컨텐츠 -->
</div>
```

### CSS 클래스
- `pt-safe`: 상단 안전 영역 패딩
- `pb-safe`: 하단 안전 영역 패딩

---

## 🎨 Splash Screen

### 기능 설명
앱 시작 시 표시되는 스플래시 화면.

### 커스터마이징
- iOS: `ios/App/App/Assets.xcassets/Splash.imageset/`
- Android: `android/app/src/main/res/drawable*/`

---

## 🔄 App Update Check

### 기능 설명
앱 스토어의 최신 버전을 확인하고 업데이트를 유도합니다.

### 사용법
```typescript
import { checkAppUpdate, showUpdatePrompt } from '$lib/util/appUpdate';

// 앱 시작 시 버전 체크
const updateInfo = await checkAppUpdate();
if (updateInfo.needsUpdate) {
  await showUpdatePrompt(updateInfo);
}
```

### API 엔드포인트
```
GET /api/app/version
```

### 응답
```json
{
  "current_version": "1.0.0",
  "min_version": "0.9.0",
  "update_url": {
    "ios": "https://apps.apple.com/...",
    "android": "https://play.google.com/..."
  }
}
```

---

## 🛠️ Capacitor 플러그인 목록

| 플러그인 | 용도 |
|----------|------|
| `@capacitor/app` | 앱 생명주기, 딥링크 |
| `@capacitor/browser` | 인앱 브라우저 |
| `@capacitor/device` | 기기 정보 |
| `@capacitor/push-notifications` | 푸시 알림 |
| `@capacitor/splash-screen` | 스플래시 화면 |

---

## 📝 다음 단계

- [🚀 배포 가이드](./DEPLOYMENT.md)
- [💻 개발 환경 설정](./DEVELOPMENT.md)
