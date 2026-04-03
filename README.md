# 카본 빌더 Basic (그누보드5 + SvelteKit + Capacitor)
그누보드5 + SvelteKit + Capacitor 를 이용한 하이브리드앱 개발 보일러플레이트.


## 프로젝트 구조

karbon_basic_cafe24/
├── backend/                # 백엔드 관련
│   ├── v1/                # 그누보드5 + FAPI 통합형 (Cafe24 배포용)
│   │   ├── fapi/          # FAPI 코어 및 공통 라이브러리
│   │   ├── adm/           # 그누보드 관리자
│   │   └── ...            # 그누보드5 기본 엔진
│   └── rust/              # 향후 마이그레이션을 위한 Rust 백엔드
├── frontend/              # 프론트엔드 (SvelteKit)
│   ├── main/              # 랜딩페이지
│   ├── app/               # 사용자 웹/앱
│   └── admin/             # 관리자 페이지
├── scripts/               # 자동화 스크립트
│   ├── deploy-*.sh        # 파트별 배포 스크립트
│   └── doc/               # 상세 가이드 문서
├── docs/                  # 프로젝트 기술 문서
└── .env                   # 배포 및 개발 환경 통합 설정

## 주요 기능

### FAPI (File API)

- **동적 파일기반 라우팅**: `폴더/폴더/{변수}/파일` 형태의 직관적인 라우팅을 지원하며, SvelteKit의 동적 파일기반 라우팅 철학을 따릅니다.
- **SQLx 적용**: SQLx 바인딩 및 Rust의 SQLx 문법을 그대로 사용하여 향후 Rust 이전 시 비용을 최소화하였으며, 데이터베이스 마이그레이션 기능을 포함합니다.
- **도메인 모듈화**: 엔터프라이즈급 프로젝트 전환 시 마이크로서비스로의 분리를 고려한 도메인 기반 모듈화 설계를 적용했습니다.
- **HTTP Only Cookie**: JWT 토큰을 HTTP Only Cookie로 저장하여 XSS 공격으로부터 보안을 강화했습니다.
- **CORS 지원**: 동적 Origin 설정을 통해 다중 도메인 환경을 지원합니다.


### SvelteKit 프론트엔드

- **회원 기능**: 로그인, 로그아웃, 회원가입, 프로필
- **게시판 기능**: 목록, 상세, 작성, 수정, 삭제
- **최신글**: 게시판별 최신글 조회
- **메뉴**: PC/모바일 메뉴 출력
- **배너**: 위치별 배너 출력
- **팝업**: 팝업 관리 및 표시
- **내용관리**: 내용 조회
- **다국어 지원 (i18n)**: `/ko`, `/en`, `/ja`, `/zh` 등 폴더 기반의 직관적인 다국어 시스템 구축
- **Firebase 연동**: Firebase 실시간 채팅 기능 지원


### Capacitor

- push 알림
- 버전체크
- 딥링크



## 설치 및 설정

### 1. API 설정

1. `api/.env` 파일 생성 (`.env.example` 참고)
2. JWT 키 및 설정 값 입력
3. 그누보드5의 `api/` 폴더에 배포

```bash
# .env 파일 예시
APP_ENV=development
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
JWT_ACCESS_TOKEN_KEY=your-access-token-key
JWT_REFRESH_TOKEN_KEY=your-refresh-token-key
```

### 2. 프론트엔드 설정

```bash
cd frontend
npm install
npm run dev
```

### 3. 개발 환경

- API: `http://localhost/api` (PHP 서버)
- 프론트엔드: `http://localhost:5173` (Vite 개발 서버)

## API 엔드포인트

### 인증
- `POST /api/auth/login` - 로그인
- `POST /api/auth/logout` - 로그아웃
- `POST /api/auth/refresh` - 토큰 갱신
- `POST /api/auth/register` - 회원가입

### 회원
- `GET /api/member/profile` - 프로필 조회
- `PUT /api/member/update` - 회원정보 수정
- `DELETE /api/member/leave` - 회원탈퇴
- `GET /api/member/memo` - 쪽지 목록
- `GET /api/member/scrap` - 스크랩 목록
- `GET /api/member/point` - 포인트 내역

### 게시판
- `GET /api/bbs/{bo_table}` - 게시글 목록
- `GET /api/bbs/{bo_table}/{wr_id}` - 게시글 상세
- `POST /api/bbs/{bo_table}/write` - 게시글 작성
- `PUT /api/bbs/{bo_table}/update` - 게시글 수정
- `DELETE /api/bbs/{bo_table}/delete` - 게시글 삭제
- `GET /api/bbs/search` - 게시판 검색

### 기타
- `GET /api/latest/{bo_table}` - 최신글
- `GET /api/menu` - 메뉴 목록
- `GET /api/banner/{position}` - 배너 목록
- `GET /api/popup` - 팝업 목록
- `GET /api/content/{co_id}` - 내용 조회
- `GET /api/docs` - API 문서

## 파일 기반 라우팅

FAPI는 파일 경로가 API 경로로 자동 매핑됩니다:

```
routes/
├── auth/
│   └── login.php          → POST /api/auth/login
├── bbs/
│   └── [bo_table]/
│       ├── index.php      → GET /api/bbs/{bo_table}
│       └── [wr_id].php    → GET /api/bbs/{bo_table}/{wr_id}
└── _middleware.php        → Private (외부 접근 불가)
```

### 파일 명명 규칙

- **Public 파일**: `_` 접두사 없음 → 외부 접근 가능
- **Private 파일**: `_` 접두사로 시작 → include 전용
- **동적 경로**: `[변수명]` 형식 사용
- **HTTP 메서드**: 파일 내 `GET()`, `POST()`, `PUT()`, `DELETE()` 함수로 정의

## 배포

### Static Adapter (PHP 호스팅)

```bash
cd frontend
npm run build
# build 폴더의 내용을 api/static/ 폴더로 복사
```

### Vercel/Cloudflare (서브도메인)

프론트엔드를 Vercel/Cloudflare에 배포하고, API는 기존 PHP 서버 사용:

```
api.yourdomain.com      # API 서버
app.yourdomain.com      # 프론트엔드
```

## 문서
### 설치 및 배포 가이드
| 문서 | 설명 |
|------|------|
| [상세 설치 가이드](scripts/doc/install-detailed.md) | 로컬 환경 설치 및 설정 방법 |
| [Cafe24 배포 가이드](scripts/doc/deploy-cafe24.md) | Cafe24 호스팅 배포 방법 |
| [클라우드 배포 가이드](scripts/doc/deploy-cloud.md) | Vercel, Cloudflare, 자체 서버 배포 |
| [스크립트 문서](scripts/doc/scripts.md) | 자동화 스크립트 사용법 |

## 라이선스

이 프로젝트는 MIT 라이선스를 따릅니다.
