# FAPI 배포 가이드 (그누보드 컨텐츠몰용)

## 1. 개요

그누보드 컨텐츠몰에 배포하기 쉽도록 **완전히 독립적인 구조**로 설계되었습니다. 기존 그누보드 파일과 분리되어 있어 설치/제거가 간단합니다.

## 2. 파일 구조 (독립적 구성)

### 2.1 설치 위치

```
gnuboard5/
├── api/                    # FAPI 전용 폴더 (완전 독립)
│   ├── index.php
│   ├── _common.php
│   ├── routes/
│   ├── lib/
│   └── config/
├── common.php              # 그누보드 기본 (변경 없음)
├── bbs/                    # 그누보드 기본 (변경 없음)
└── ...
```

**핵심:**
- `api/` 폴더 하나만 추가하면 됨
- 기존 그누보드 파일 수정 불필요
- 완전히 독립적으로 동작

### 2.2 최소 설치 파일

**필수 파일만 포함:**
```
api/
├── index.php              # 라우터 (단일 파일)
├── _common.php            # 공통 함수
├── routes/                # API 라우트
│   ├── _middleware.php
│   ├── auth/
│   ├── member/
│   └── bbs/
└── lib/                   # 라이브러리
    ├── Response.php
    ├── Auth.php
    └── jwt.php
```

**총 파일 수: 약 10-15개** (기능에 따라 추가)

## 3. 설치 방법

### 3.1 단계별 설치

**Step 1: 파일 업로드**
```bash
# api 폴더 전체를 그누보드 루트에 업로드
gnuboard5/
  └── api/  (새로 추가)
```

**Step 2: .htaccess 설정 (Apache)**
```apache
# gnuboard5/.htaccess에 추가
RewriteRule ^api/(.*)$ api/index.php [QSA,L]
```

**Step 3: nginx 설정 (Nginx)**
```nginx
# nginx.conf에 추가
location /api {
    try_files $uri $uri/ /api/index.php?$query_string;
}
```

**Step 4: 환경 변수 설정**
```bash
# api/.env 파일 생성 (선택사항)
cp api/.env.example api/.env
# .env 파일 편집
```

**끝!** 이제 `http://yourdomain.com/api/auth/login` 접근 가능

### 3.2 자동 설치 스크립트

**install.php:**
```php
<?php
// api/install.php
if (!defined("_GNUBOARD_")) exit;

echo "FAPI 설치 시작...\n";

// 1. 디렉토리 생성
$dirs = ['routes', 'lib', 'config'];
foreach ($dirs as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) {
        mkdir(__DIR__ . '/' . $dir, 0755, true);
        echo "✓ 디렉토리 생성: {$dir}\n";
    }
}

// 2. .env 파일 생성
if (!file_exists(__DIR__ . '/.env')) {
    copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
    echo "✓ .env 파일 생성\n";
}

// 3. 권한 설정
chmod(__DIR__ . '/.env', 0600);
echo "✓ 권한 설정 완료\n";

echo "설치 완료!\n";
```

## 4. 제거 방법

### 4.1 완전 제거

```bash
# api 폴더만 삭제하면 됨
rm -rf gnuboard5/api/

# .htaccess에서 라인 제거
# RewriteRule ^api/(.*)$ api/index.php [QSA,L]
```

**기존 그누보드 파일은 전혀 변경되지 않음!**

## 5. 모듈화 구조

### 5.1 기능별 분리

```
api/
├── core/                  # 핵심 기능 (필수)
│   ├── index.php
│   ├── _common.php
│   └── lib/
│       ├── Router.php
│       ├── Response.php
│       └── Auth.php
├── modules/               # 모듈 (선택적 설치)
│   ├── member/           # 회원 모듈
│   │   ├── routes/
│   │   └── lib/
│   ├── bbs/              # 게시판 모듈
│   │   ├── routes/
│   │   └── lib/
│   └── shop/             # 쇼핑몰 모듈
│       ├── routes/
│       └── lib/
└── plugins/              # 플러그인 (선택적 설치)
    ├── latest-posts/
    ├── menu/
    └── banner/
```

### 5.2 모듈 설치 시스템

**modules/member/install.php:**
```php
<?php
// 모듈 설치 스크립트
function install_member_module() {
    // 1. 라우트 파일 복사
    copy_routes('member', __DIR__ . '/routes');
    
    // 2. 라이브러리 복사
    copy_libs('member', __DIR__ . '/lib');
    
    // 3. 설정 추가
    add_config('modules.member.enabled', true);
    
    echo "회원 모듈 설치 완료!\n";
}
```

**사용:**
```bash
php api/modules/member/install.php
```

### 5.3 플러그인 시스템

**plugins/latest-posts/plugin.php:**
```php
<?php
// 플러그인 메타데이터
return [
    'name' => '최신글 플러그인',
    'version' => '1.0.0',
    'description' => '최신글 API 제공',
    'routes' => [
        'latest/[bo_table].php' => 'routes/latest/[bo_table].php'
    ],
    'install' => function() {
        // 설치 로직
    },
    'uninstall' => function() {
        // 제거 로직
    }
];
```

**플러그인 관리자:**
```php
<?php
// api/admin/plugins.php
function install_plugin($pluginName) {
    $plugin = load_plugin($pluginName);
    $plugin['install']();
    register_plugin($pluginName);
}

function uninstall_plugin($pluginName) {
    $plugin = load_plugin($pluginName);
    $plugin['uninstall']();
    unregister_plugin($pluginName);
}
```

## 6. 패키징 (컨텐츠몰 배포용)

### 6.1 배포 패키지 구조

```
fapi-package/
├── api/                   # FAPI 코어
│   ├── core/
│   └── lib/
├── modules/               # 모듈들
│   ├── member/
│   ├── bbs/
│   └── shop/
├── plugins/               # 플러그인들
│   ├── latest-posts/
│   ├── menu/
│   └── banner/
├── install.php            # 자동 설치 스크립트
├── README.md
└── CHANGELOG.md
```

### 6.2 설치 가이드 포함

**README.md:**
```markdown
# FAPI 설치 가이드

## 빠른 설치

1. 압축 파일을 그누보드 루트에 업로드
2. 압축 해제
3. 브라우저에서 `http://yourdomain.com/api/install.php` 접속
4. 완료!

## 모듈 설치

```bash
php api/modules/member/install.php
php api/modules/bbs/install.php
```

## 플러그인 설치

관리자 페이지 > 플러그인 > 설치
```

### 6.3 버전 관리

**package.json (또는 version.php):**
```php
<?php
return [
    'name' => 'FAPI',
    'version' => '1.0.0',
    'gnuboard_version' => '5.5.0+',
    'php_version' => '7.4+',
    'modules' => [
        'member' => '1.0.0',
        'bbs' => '1.0.0',
        'shop' => '1.0.0'
    ],
    'plugins' => [
        'latest-posts' => '1.0.0',
        'menu' => '1.0.0',
        'banner' => '1.0.0'
    ]
];
```

## 7. 의존성 관리

### 7.1 그누보드 의존성

**최소 의존성:**
- `common.php` (그누보드 기본)
- `lib/common.lib.php` (그누보드 기본)
- 그누보드 DB 연결

**독립적 기능:**
- 자체 라우터
- 자체 응답 처리
- 자체 인증 시스템 (JWT)

### 7.2 호환성 체크

**compatibility.php:**
```php
<?php
function check_compatibility() {
    $errors = [];
    
    // 그누보드 버전 체크
    if (version_compare(G5_GNUBOARD_VER, '5.5.0', '<')) {
        $errors[] = '그누보드 5.5.0 이상이 필요합니다.';
    }
    
    // PHP 버전 체크
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $errors[] = 'PHP 7.4 이상이 필요합니다.';
    }
    
    // 필수 함수 체크
    $required = ['json_encode', 'json_decode', 'openssl_encrypt'];
    foreach ($required as $func) {
        if (!function_exists($func)) {
            $errors[] = "필수 함수가 없습니다: {$func}";
        }
    }
    
    return $errors;
}
```

## 8. 업데이트 시스템

### 8.1 자동 업데이트

**update.php:**
```php
<?php
function update_fapi($fromVersion, $toVersion) {
    // 1. 백업
    backup_files();
    
    // 2. 파일 업데이트
    update_core_files();
    
    // 3. 데이터베이스 마이그레이션
    migrate_database($fromVersion, $toVersion);
    
    // 4. 캐시 클리어
    clear_cache();
    
    echo "업데이트 완료: {$fromVersion} → {$toVersion}\n";
}
```

### 8.2 마이그레이션 스크립트

**migrations/1.0.0_to_1.1.0.php:**
```php
<?php
function migrate_1_0_0_to_1_1_0() {
    // 새 테이블 생성
    sql_query("CREATE TABLE IF NOT EXISTS g5_api_tokens ...");
    
    // 데이터 변환
    // ...
    
    echo "마이그레이션 완료\n";
}
```

## 9. 테마/빌더 연동

### 9.1 SvelteKit 연동

**sveltekit-integration/package.json:**
```json
{
  "name": "fapi-sveltekit-integration",
  "version": "1.0.0",
  "dependencies": {
    "@sveltejs/kit": "^2.0.0"
  },
  "fapi": {
    "api_url": "/api",
    "modules": ["member", "bbs"]
  }
}
```

### 9.2 Capacitor 연동

**capacitor-integration/config.xml:**
```xml
<widget>
    <name>FAPI Mobile App</name>
    <fapi>
        <api_url>https://yourdomain.com/api</api_url>
        <modules>
            <module>member</module>
            <module>bbs</module>
        </modules>
    </fapi>
</widget>
```

## 10. 배포 체크리스트

### 10.1 패키징 전 확인

- [ ] 모든 파일이 `api/` 폴더 내에 있는지 확인
- [ ] 기존 그누보드 파일 수정 없이 동작하는지 확인
- [ ] 설치 스크립트 테스트
- [ ] 제거 스크립트 테스트
- [ ] 모듈 설치/제거 테스트
- [ ] 플러그인 설치/제거 테스트
- [ ] 문서 작성 완료

### 10.2 배포 패키지 포함 사항

- [ ] 설치 가이드 (README.md)
- [ ] 변경 이력 (CHANGELOG.md)
- [ ] 라이선스 파일
- [ ] 예제 파일
- [ ] AI 컨텍스트 문서

## 11. 결론

### 11.1 핵심 원칙

1. **완전 독립**: `api/` 폴더 하나만 추가
2. **기존 파일 보존**: 그누보드 기본 파일 수정 없음
3. **모듈화**: 기능별로 분리하여 선택적 설치
4. **쉬운 배포**: 압축 파일 하나로 배포 가능

### 11.2 배포 시나리오

**시나리오 1: 기본 FAPI만**
```
fapi-core.zip
  └── api/
      └── core/
```

**시나리오 2: FAPI + 모듈**
```
fapi-full.zip
  └── api/
      ├── core/
      └── modules/
          ├── member/
          ├── bbs/
          └── shop/
```

**시나리오 3: FAPI + 테마 + 빌더**
```
fapi-complete.zip
  └── api/
  └── themes/
  └── builders/
```

이 구조로 컨텐츠몰 배포가 매우 쉬워집니다!

