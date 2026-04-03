# FAPI 구버전 분석 문서

## 1. 개요

이 문서는 그누보드5에 구현된 구버전 FAPI(Simple API)의 구조와 기능을 분석한 문서입니다. 새로운 FAPI 개발 시 참고 자료로 활용됩니다.

## 2. 아키텍처

### 2.1 라우팅 시스템

FAPI는 RESTful API 스타일의 URL 라우팅을 구현하고 있습니다.

**핵심 파일:**
- `api/index.php`: 메인 라우터
- `api/_common.php`: 공통 함수 및 설정

**라우팅 방식:**
```
URL 형식: /api/{폴더명}/{파일명}/{함수명}/{인자}
예시: /api/bbs/board/Index
예시: /api/member/login/Index
```

**특징:**
- URL을 파싱하여 파일명과 함수명을 자동 추출
- 함수명이 없을 경우 `Index` 함수를 기본 호출
- 대문자로 시작하는 함수만 외부에서 호출 가능 (보안)
- 함수 인자는 1개만 사용 가능 (URL의 마지막 요소)

### 2.2 보안 체계

#### CORS 설정
- 개발 모드(`G5_ORIGN_TYPE = "dev"`): 모든 Origin 허용
- 릴리즈 모드: 지정된 `G5_APP_URL`만 허용

#### JWT 인증
- Access Token: 15분 유효 (기본값)
- Refresh Token: 30일 유효 (기본값)
- HS256 알고리즘 사용
- 토큰은 `Authorization` 헤더의 `Bearer {token}` 형식으로 전달

#### 접근 제어
- `/api/lib` 폴더 접근 금지
- URL 경로에 `..` 포함 시 차단
- 함수명은 대문자로 시작해야 함

## 3. 주요 기능 모듈

### 3.1 회원 관련 (member/)

#### 3.1.1 로그인 (`member/login.php`)

**함수:** `Index()`

**요청 형식:**
```json
{
  "mb_id": "사용자ID",
  "mb_password": "비밀번호"
}
```

**응답 형식:**
```json
{
  "code": "00000",
  "msg": "로그인 성공",
  "data": {
    "accessToken": "JWT_ACCESS_TOKEN",
    "refreshToken": "암호화된_REFRESH_TOKEN",
    "mb": {
      "mb_id": "사용자ID",
      "mb_name": "이름",
      "mb_nick": "닉네임",
      "mb_level": "레벨",
      "mb_point": "포인트",
      "mb_memo_cnt": "쪽지수",
      "mb_scrap_cnt": "스크랩수"
    }
  },
  "time": 0.123
}
```

**에러 코드:**
- `00001`: 로그인 실패 (아이디/비밀번호 오류, 차단된 아이디, 탈퇴한 아이디, 메일인증 필요)
- `00002`: 관리자 data 폴더 쓰기 권한 없음

**주요 기능:**
- 소셜 로그인 지원 (확장 가능)
- 영카트 장바구니 연동
- 포인트 동기화
- JWT 토큰 발급

#### 3.1.2 회원가입 (`member/register.php`)

**함수:** `Index()`

현재 구현 상태: 빈 함수 (구현 필요)

#### 3.1.3 비밀번호 관련 (`member/password.php`)

**함수:** `Index()`

현재 구현 상태: 빈 함수 (구현 필요)

#### 3.1.4 토큰 갱신 (`member/refresh_token.php`)

**함수:** `Index()`

Refresh Token을 사용하여 새로운 Access Token 발급

### 3.2 게시판 관련 (bbs/)

#### 3.2.1 게시판 목록/상세 (`bbs/board.php`)

**함수:** `Index()`

**요청 형식:**
```json
{
  "bo_table": "게시판ID",
  "wr_id": "글ID (선택)",
  "page": "페이지번호",
  "page_rows": "페이지당행수",
  "stx": "검색어",
  "sop": "검색연산자 (and/or)",
  "sca": "카테고리",
  "sst": "정렬필드",
  "sod": "정렬방향"
}
```

**응답 형식 (목록):**
```json
{
  "code": "00000",
  "data": {
    "board": "게시판정보",
    "list": "게시글목록",
    "total_page": "전체페이지수",
    "write_pages": "페이징HTML",
    "category_option": "카테고리옵션"
  }
}
```

**응답 형식 (상세):**
```json
{
  "code": "00000",
  "data": {
    "view": "게시글상세정보",
    "prev_href": "이전글링크",
    "next_href": "다음글링크",
    "write_href": "글쓰기링크",
    "update_href": "수정링크",
    "delete_href": "삭제링크"
  }
}
```

#### 3.2.2 게시판 목록 (`bbs/list.php`)

**함수:** 없음 (include로 사용)

**주요 기능:**
- 공지글 처리
- 검색 기능
- 카테고리 필터링
- 정렬 기능
- 페이징

#### 3.2.3 게시글 상세 (`bbs/view.php`)

**함수:** 없음 (include로 사용)

**주요 기능:**
- 게시글 내용 출력
- 이전/다음글 링크
- 댓글 정보
- 첨부파일 정보
- 권한 체크 (읽기, 수정, 삭제)

#### 3.2.4 게시글 작성/수정 (`bbs/write.php`)

**함수:** `Index()`

**요청 형식:**
```json
{
  "bo_table": "게시판ID",
  "wr_id": "글ID (수정시)",
  "w": "작성모드 (빈값:신규, u:수정, r:답변)",
  "subject": "제목",
  "content": "내용",
  "ca_name": "카테고리",
  "wr_option": "옵션 (html1,html2,secret,mail)"
}
```

**응답 형식:**
```json
{
  "code": "00000",
  "data": {
    "is_category": "카테고리사용여부",
    "category_option": "카테고리목록",
    "is_html": "HTML사용여부",
    "is_secret": "비밀글사용여부",
    "is_file": "파일첨부여부",
    "file_count": "파일첨부개수",
    "subject": "제목",
    "content": "내용",
    "editor_html": "에디터HTML",
    "captcha_html": "캡차HTML"
  }
}
```

**에러 코드:**
- `00001`: 일반 에러
- `00002`: 로그인 필요
- `00003`: 권한 없음
- `00004`: 권한 없음 (이전 페이지로)
- `00005`: 본인인증 필요
- `00006`: 성인인증 필요

#### 3.2.5 댓글 조회 (`bbs/view.php` 내 `View_comment()`)

**함수:** `View_comment()`

**요청 형식:**
```json
{
  "bo_table": "게시판ID",
  "wr_id": "글ID"
}
```

**응답 형식:**
```json
{
  "code": "00000",
  "data": {
    "list": "댓글목록",
    "comment_min": "최소글자수",
    "comment_max": "최대글자수",
    "comment_action_url": "댓글작성URL"
  }
}
```

#### 3.2.6 내용관리 (`bbs/content.php`)

**함수:** `Index()`

**요청 형식:**
```json
{
  "co_id": "내용ID",
  "co_seo_title": "SEO제목 (선택)"
}
```

**응답 형식:**
```json
{
  "code": "00000",
  "data": {
    "title": "제목",
    "content": "내용HTML",
    "himg": "상단이미지URL",
    "timg": "하단이미지URL"
  }
}
```

#### 3.2.7 그룹 정보 (`bbs/group.php`)

**함수:** `Index()`

현재 구현 상태: 기본 구조만 존재

### 3.3 쇼핑몰 관련 (shop/)

현재 구현 상태: 폴더만 존재, 파일 없음

## 4. 공통 함수

### 4.1 응답 함수

#### `json_return($result, $http_code, $error_code, $msg)`

**파라미터:**
- `$result`: 반환할 데이터
- `$http_code`: HTTP 상태 코드 (기본값: 200)
- `$error_code`: 에러 코드
- `$msg`: 메시지 (선택)

**에러 코드:**
- `00000`: 성공
- `00001`: 에러
- `00002`: 로그인 필요
- `00003`: 권한 없음 (메인으로 이동)
- `00004`: 권한 없음 (이전 페이지로)
- `00005`: 본인인증 필요
- `00006`: 성인인증 필요

**응답 형식:**
```json
{
  "code": "에러코드",
  "msg": "메시지",
  "data": "데이터",
  "time": "실행시간(초)"
}
```

### 4.2 인증 함수

#### `get_jwt_member()`

JWT 토큰에서 회원 정보 추출

**반환값:**
- 토큰 유효: 회원 정보 배열
- 토큰 만료: HTTP 203 응답 후 종료
- 토큰 없음: 빈 회원 정보 배열

#### `set_refresh_token($token, $mb_id, $uuid, $agent)`

Refresh Token을 데이터베이스에 저장

#### `get_refresh_token($uuid)`

UUID로 Refresh Token 조회

### 4.3 암호화 함수

#### `encrypt($data)`

AES-256-CBC 암호화

#### `decrypt($data)`

AES-256-CBC 복호화

### 4.4 유틸리티 함수

#### `gen_uuid_v4()`

UUID v4 생성

#### `header_origin()`

CORS 헤더 설정

## 5. 설정 상수

### 5.1 기본 설정

```php
define('G5_API_DIR', 'api');
define('G5_API_URL', G5_URL.'/'.G5_API_DIR);
define('G5_API_PATH', G5_PATH.'/'.G5_API_DIR);
```

### 5.2 CORS 설정

```php
define('G5_ORIGN_TYPE', 'dev'); // dev or release
define('G5_APP_URL', 'http://localhost'); // release 시 사용
```

### 5.3 JWT 설정

```php
define('G5_JWT_ACCESS_TOKEN_KEY', 'long-jwt-key');
define('G5_JWT_REFESH_TOKEN_KEY', 'long-refresh');
define('G5_JWT_CRYPT_KEY', 'long-refresh-key');
define('G5_JWT_AUDIENCE', 'gnuboard5.canweb.co.kr');
define('G5_JWT_ACCESS_MTIME', 15); // 분
define('G5_JWT_RERESH_DATE', 30); // 일
```

## 6. 데이터베이스 구조

### 6.1 Refresh Token 테이블

**테이블명:** `g5_member_rejwt`

**컬럼:**
- `mb_id`: 회원ID
- `uuid`: UUID
- `agent`: User Agent
- `refresh_token`: Refresh Token
- `reg_datetime`: 등록일시

## 7. 보안 고려사항

### 7.1 현재 구현된 보안

1. **함수명 검증**: 대문자로 시작하는 함수만 호출 가능
2. **경로 검증**: `/api/lib` 폴더 접근 금지
3. **경로 순회 방지**: `..` 문자열 차단
4. **JWT 토큰 검증**: 모든 API 호출 시 토큰 검증
5. **CORS 설정**: 개발/운영 환경 분리

### 7.2 개선 필요 사항

1. **API Key 인증**: Referer 체크 외 추가 인증 방식
2. **Rate Limiting**: 요청 제한 기능
3. **입력값 검증**: SQL Injection, XSS 방지 강화
4. **에러 메시지**: 상세한 에러 정보 노출 최소화
5. **HTTPS 강제**: 프로덕션 환경에서 HTTPS만 허용

## 8. 성능 최적화

### 8.1 캐시 활용

- 게시판 목록 캐시 (`g5_latest_cache_data`)
- 최신글 캐시

### 8.2 쿼리 최적화

- 인덱스 활용
- 불필요한 조인 최소화
- 페이징 처리

## 9. 확장 가능성

### 9.1 현재 구조의 장점

1. **모듈화**: 폴더별 기능 분리
2. **유연한 라우팅**: URL 기반 자동 라우팅
3. **표준화된 응답**: 일관된 JSON 응답 형식
4. **확장 용이**: 새 폴더/파일 추가만으로 기능 확장

### 9.2 개선 제안

1. **버전 관리**: `/api/v1/`, `/api/v2/` 형식 지원
2. **문서화**: Swagger/OpenAPI 자동 생성
3. **테스트**: 단위 테스트 및 통합 테스트 추가
4. **로깅**: API 호출 로그 및 모니터링

## 10. 참고 자료

- JWT 라이브러리: `api/lib/jwt.php`
- 그누보드 공통 함수: `common.php`
- 그누보드 라이브러리: `lib/` 폴더

