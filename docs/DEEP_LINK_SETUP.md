# Deep Link Setup Guide

Karbon 프로젝트의 안드로이드(App Links) 및 iOS(Universal Links) 설정을 위한 가이드입니다.

## 1. 서버 설정 (FAPI)

이미 `.well-known` 라우트가 구현되어 있습니다. 하지만 실제 동작을 위해 다음 환경 변수를 설정해야 합니다.

### `.env` 설정
```env
# 안드로이드 SHA256 지문 (Play Store 또는 로컬 키스토어에서 추출)
ANDROID_SHA256=FA:C3:99:66:D1:DC:60:F2:93:36:...

# iOS Team ID
IOS_TEAM_ID=ABC123DEFG

# 앱 스킴
APP_SCHEME=karbon
```

설정 후 `yourdomain.com/api/.well-known/assetlinks.json` 등이 정상적으로 JSON을 반환하는지 확인하세요.

## 2. 안드로이드 설정 (Android Studio)

### `AndroidManifest.xml` 수정
`app/src/main/AndroidManifest.xml`의 `<activity>` 태그 내부에 다음 `intent-filter`를 추가합니다.

```xml
<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="https" android:host="karbon.kr" />
</intent-filter>

<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="karbon" />
</intent-filter>
```

## 3. iOS 설정 (Xcode)

### Associated Domains 추가
1. Xcode에서 프로젝트 타겟 선택 -> `Signing & Capabilities` 탭 이동
2. `+ Capability` 버튼 클릭 -> `Associated Domains` 추가
3. `Domains` 항목에 `applinks:karbon.kr` 추가

### Info.plist (Custom Scheme)
`URL Types` 항목에 `karbon` 스킴을 추가합니다.

## 4. 앱 내 처리 로직

`frontend/src/routes/+layout.svelte`에 전역 리스너가 구현되어 있습니다. 앱이 열릴 때 URL을 분석하여 해당 경로로 자동 이동합니다.

### 테스트 방법
- 브라우저에서 `https://karbon.kr/api/link?path=/bbs/free/1` 접속 시 앱 전환 유도 페이지가 열립니다.
- 터미널(Android): `adb shell am start -W -a android.intent.action.VIEW -d "https://karbon.kr/bbs/free/1" com.gnuboard.karbon`
- 터미널(iOS): `xcrun simctl openurl booted https://karbon.kr/bbs/free/1`
