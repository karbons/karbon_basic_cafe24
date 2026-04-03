# FAPI 모듈화 및 플러그인 시스템

## 1. 개요

FAPI는 기능을 모듈과 플러그인으로 분리하여 **선택적 설치**가 가능하도록 설계되었습니다. 빌더, 테마, 플러그인 등을 독립적으로 판매하고 설치할 수 있습니다.

## 2. 구조

### 2.1 계층 구조

```
FAPI Core (필수)
  ├── Router
  ├── Response
  └── Auth

Modules (선택적)
  ├── Member Module
  ├── BBS Module
  └── Shop Module

Plugins (선택적)
  ├── Latest Posts Plugin
  ├── Menu Plugin
  └── Banner Plugin

Themes (선택적)
  ├── SvelteKit Theme
  └── Capacitor Theme

Builders (선택적)
  └── No-Code Builder
```

### 2.2 파일 구조

```
api/
├── core/                  # 핵심 기능 (필수)
│   ├── index.php
│   ├── _common.php
│   └── lib/
│       ├── Router.php
│       ├── Response.php
│       └── Auth.php
├── modules/               # 모듈 (선택적)
│   ├── member/
│   │   ├── install.php
│   │   ├── uninstall.php
│   │   ├── routes/
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   └── profile.php
│   │   └── lib/
│   │       └── MemberHelper.php
│   ├── bbs/
│   │   ├── install.php
│   │   ├── routes/
│   │   │   ├── [bo_table]/
│   │   │   │   ├── index.php
│   │   │   │   └── write.php
│   │   │   └── search.php
│   │   └── lib/
│   └── shop/
│       ├── install.php
│       ├── routes/
│       └── lib/
├── plugins/               # 플러그인 (선택적)
│   ├── latest-posts/
│   │   ├── plugin.php
│   │   ├── routes/
│   │   │   └── [bo_table].php
│   │   └── lib/
│   ├── menu/
│   │   ├── plugin.php
│   │   └── routes/
│   └── banner/
│       ├── plugin.php
│       └── routes/
└── themes/                # 테마 (선택적)
    ├── sveltekit/
    │   ├── theme.json
    │   ├── src/
    │   └── package.json
    └── capacitor/
        ├── theme.json
        └── src/
```

## 3. 모듈 시스템

### 3.1 모듈 정의

**modules/member/module.json:**
```json
{
  "name": "Member Module",
  "version": "1.0.0",
  "description": "회원 관리 모듈",
  "author": "FAPI Team",
  "dependencies": {
    "core": ">=1.0.0"
  },
  "routes": [
    "routes/auth/login.php",
    "routes/auth/register.php",
    "routes/member/profile.php"
  ],
  "libs": [
    "lib/MemberHelper.php"
  ],
  "config": {
    "jwt_enabled": true,
    "social_login": false
  }
}
```

### 3.2 모듈 설치

**modules/member/install.php:**
```php
<?php
function install_member_module() {
    $modulePath = __DIR__;
    $apiPath = dirname(dirname(__DIR__));
    
    // 1. 라우트 파일 복사
    $routes = [
        'routes/auth/login.php' => $apiPath . '/routes/auth/login.php',
        'routes/auth/register.php' => $apiPath . '/routes/auth/register.php',
        'routes/member/profile.php' => $apiPath . '/routes/member/profile.php'
    ];
    
    foreach ($routes as $source => $target) {
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        copy($modulePath . '/' . $source, $target);
        echo "✓ 라우트 설치: {$source}\n";
    }
    
    // 2. 라이브러리 복사
    copy($modulePath . '/lib/MemberHelper.php', $apiPath . '/lib/MemberHelper.php');
    echo "✓ 라이브러리 설치\n";
    
    // 3. 설정 추가
    $config = [
        'modules' => [
            'member' => [
                'enabled' => true,
                'version' => '1.0.0'
            ]
        ]
    ];
    save_config($config);
    echo "✓ 설정 저장\n";
    
    // 4. 데이터베이스 테이블 생성 (필요시)
    create_member_tables();
    echo "✓ 데이터베이스 설정\n";
    
    echo "회원 모듈 설치 완료!\n";
}

function create_member_tables() {
    // Refresh Token 테이블 등
    sql_query("CREATE TABLE IF NOT EXISTS g5_member_rejwt ...");
}
```

### 3.3 모듈 제거

**modules/member/uninstall.php:**
```php
<?php
function uninstall_member_module() {
    $apiPath = dirname(dirname(__DIR__));
    
    // 1. 라우트 파일 제거
    $routes = [
        $apiPath . '/routes/auth/login.php',
        $apiPath . '/routes/auth/register.php',
        $apiPath . '/routes/member/profile.php'
    ];
    
    foreach ($routes as $file) {
        if (file_exists($file)) {
            unlink($file);
            echo "✓ 라우트 제거: {$file}\n";
        }
    }
    
    // 2. 라이브러리 제거
    $libFile = $apiPath . '/lib/MemberHelper.php';
    if (file_exists($libFile)) {
        unlink($libFile);
        echo "✓ 라이브러리 제거\n";
    }
    
    // 3. 설정 제거
    remove_config('modules.member');
    echo "✓ 설정 제거\n";
    
    // 4. 데이터베이스 테이블 제거 (선택적)
    // drop_member_tables();
    
    echo "회원 모듈 제거 완료!\n";
}
```

### 3.4 모듈 관리자

**admin/modules.php:**
```php
<?php
function list_modules() {
    $modulesDir = __DIR__ . '/../modules';
    $modules = [];
    
    $dirs = scandir($modulesDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $modulePath = $modulesDir . '/' . $dir;
        $moduleJson = $modulePath . '/module.json';
        
        if (file_exists($moduleJson)) {
            $modules[$dir] = json_decode(file_get_contents($moduleJson), true);
            $modules[$dir]['installed'] = is_module_installed($dir);
        }
    }
    
    return $modules;
}

function install_module($moduleName) {
    $installFile = __DIR__ . "/../modules/{$moduleName}/install.php";
    if (file_exists($installFile)) {
        include $installFile;
        install_member_module(); // 함수명은 모듈마다 다를 수 있음
    }
}

function uninstall_module($moduleName) {
    $uninstallFile = __DIR__ . "/../modules/{$moduleName}/uninstall.php";
    if (file_exists($uninstallFile)) {
        include $uninstallFile;
        uninstall_member_module();
    }
}
```

## 4. 플러그인 시스템

### 4.1 플러그인 정의

**plugins/latest-posts/plugin.php:**
```php
<?php
return [
    'name' => 'Latest Posts Plugin',
    'version' => '1.0.0',
    'description' => '최신글 API 제공 플러그인',
    'author' => 'FAPI Team',
    'dependencies' => [
        'core' => '>=1.0.0',
        'modules' => ['bbs'] // BBS 모듈 필요
    ],
    'routes' => [
        'latest/[bo_table].php' => 'routes/latest/[bo_table].php'
    ],
    'hooks' => [
        'before_request' => 'latest_posts_before_request',
        'after_response' => 'latest_posts_after_response'
    ],
    'install' => function() {
        // 설치 로직
        copy_routes('latest-posts');
        echo "최신글 플러그인 설치 완료!\n";
    },
    'uninstall' => function() {
        // 제거 로직
        remove_routes('latest-posts');
        echo "최신글 플러그인 제거 완료!\n";
    },
    'activate' => function() {
        // 활성화 로직
        enable_plugin('latest-posts');
    },
    'deactivate' => function() {
        // 비활성화 로직
        disable_plugin('latest-posts');
    }
];
```

### 4.2 플러그인 라우트

**plugins/latest-posts/routes/latest/[bo_table].php:**
```php
<?php
// GET /api/latest/{bo_table}
function GET($bo_table) {
    $latest = get_latest_posts($bo_table, 10);
    json_return(['latest' => $latest], 200, '00000');
}
```

### 4.3 플러그인 관리자

**admin/plugins.php:**
```php
<?php
function load_plugin($pluginName) {
    $pluginFile = __DIR__ . "/../plugins/{$pluginName}/plugin.php";
    if (file_exists($pluginFile)) {
        return include $pluginFile;
    }
    return null;
}

function install_plugin($pluginName) {
    $plugin = load_plugin($pluginName);
    if ($plugin && isset($plugin['install'])) {
        $plugin['install']();
        register_plugin($pluginName);
        echo "플러그인 설치 완료: {$pluginName}\n";
    }
}

function uninstall_plugin($pluginName) {
    $plugin = load_plugin($pluginName);
    if ($plugin && isset($plugin['uninstall'])) {
        $plugin['uninstall']();
        unregister_plugin($pluginName);
        echo "플러그인 제거 완료: {$pluginName}\n";
    }
}

function activate_plugin($pluginName) {
    $plugin = load_plugin($pluginName);
    if ($plugin && isset($plugin['activate'])) {
        $plugin['activate']();
        update_plugin_status($pluginName, 'active');
    }
}

function deactivate_plugin($pluginName) {
    $plugin = load_plugin($pluginName);
    if ($plugin && isset($plugin['deactivate'])) {
        $plugin['deactivate']();
        update_plugin_status($pluginName, 'inactive');
    }
}
```

## 5. 테마 시스템

### 5.1 테마 정의

**themes/sveltekit/theme.json:**
```json
{
  "name": "SvelteKit Theme",
  "version": "1.0.0",
  "description": "SvelteKit 기반 프론트엔드 테마",
  "type": "sveltekit",
  "fapi_version": ">=1.0.0",
  "modules": ["member", "bbs"],
  "plugins": ["latest-posts", "menu", "banner"],
  "install": {
    "npm": "npm install",
    "build": "npm run build"
  }
}
```

### 5.2 테마 설치

**themes/sveltekit/install.php:**
```php
<?php
function install_sveltekit_theme() {
    $themePath = __DIR__;
    
    // 1. 의존성 확인
    check_dependencies(['fapi' => '>=1.0.0']);
    check_modules(['member', 'bbs']);
    
    // 2. npm 패키지 설치
    exec('cd ' . $themePath . ' && npm install');
    
    // 3. 빌드
    exec('cd ' . $themePath . ' && npm run build');
    
    // 4. 설정 파일 생성
    create_config_file();
    
    echo "SvelteKit 테마 설치 완료!\n";
}
```

## 6. 빌더 시스템

### 6.1 빌더 정의

**builders/no-code-builder/builder.json:**
```json
{
  "name": "No-Code Builder",
  "version": "1.0.0",
  "description": "드래그 앤 드롭 빌더",
  "type": "builder",
  "components": [
    "board-list",
    "latest-posts",
    "menu",
    "banner"
  ]
}
```

### 6.2 빌더 컴포넌트

**builders/no-code-builder/components/board-list.php:**
```php
<?php
// 빌더 컴포넌트 정의
return [
    'name' => 'board-list',
    'label' => '게시판 목록',
    'api_endpoint' => '/api/bbs/{bo_table}',
    'props' => [
        'bo_table' => [
            'type' => 'string',
            'required' => true,
            'label' => '게시판 ID'
        ],
        'page' => [
            'type' => 'number',
            'default' => 1,
            'label' => '페이지'
        ]
    ],
    'template' => 'components/board-list.svelte'
];
```

## 7. 패키징 및 배포

### 7.1 모듈 패키징

**package-module.sh:**
```bash
#!/bin/bash
MODULE_NAME=$1
VERSION=$2

# 모듈 디렉토리 압축
cd modules/$MODULE_NAME
zip -r ../../packages/${MODULE_NAME}-${VERSION}.zip .

# 메타데이터 추가
echo "패키지 생성 완료: packages/${MODULE_NAME}-${VERSION}.zip"
```

### 7.2 플러그인 패키징

**package-plugin.sh:**
```bash
#!/bin/bash
PLUGIN_NAME=$1
VERSION=$2

cd plugins/$PLUGIN_NAME
zip -r ../../packages/${PLUGIN_NAME}-${VERSION}.zip .

echo "패키지 생성 완료: packages/${PLUGIN_NAME}-${VERSION}.zip"
```

### 7.3 테마 패키징

**package-theme.sh:**
```bash
#!/bin/bash
THEME_NAME=$1
VERSION=$2

cd themes/$THEME_NAME
# node_modules 제외하고 압축
zip -r ../../packages/${THEME_NAME}-${VERSION}.zip . -x "node_modules/*"

echo "패키지 생성 완료: packages/${THEME_NAME}-${VERSION}.zip"
```

## 8. 설치 가이드 생성

### 8.1 모듈 설치 가이드

**modules/member/README.md:**
```markdown
# Member Module 설치 가이드

## 설치 방법

1. 압축 파일을 `api/modules/` 폴더에 업로드
2. 압축 해제
3. 설치 스크립트 실행:
   ```bash
   php api/modules/member/install.php
   ```
4. 완료!

## 사용 방법

설치 후 다음 API 엔드포인트가 자동으로 활성화됩니다:
- POST /api/auth/login
- POST /api/auth/register
- GET /api/member/profile

## 제거 방법

```bash
php api/modules/member/uninstall.php
```
```

## 9. 의존성 관리

### 9.1 의존성 체크

**lib/DependencyChecker.php:**
```php
<?php
class DependencyChecker {
    public static function check($module) {
        $moduleJson = __DIR__ . "/../modules/{$module}/module.json";
        $config = json_decode(file_get_contents($moduleJson), true);
        
        $errors = [];
        
        // FAPI 버전 체크
        if (isset($config['dependencies']['core'])) {
            if (!version_compare(FAPI_VERSION, $config['dependencies']['core'], '>=')) {
                $errors[] = "FAPI 버전이 부족합니다. 필요: {$config['dependencies']['core']}";
            }
        }
        
        // 모듈 의존성 체크
        if (isset($config['dependencies']['modules'])) {
            foreach ($config['dependencies']['modules'] as $dep) {
                if (!is_module_installed($dep)) {
                    $errors[] = "필수 모듈이 설치되지 않았습니다: {$dep}";
                }
            }
        }
        
        return $errors;
    }
}
```

## 10. 결론

### 10.1 핵심 장점

1. **모듈화**: 기능별로 독립적으로 설치/제거 가능
2. **플러그인 시스템**: 작은 기능도 플러그인으로 제공
3. **테마 시스템**: 프론트엔드 테마도 독립적으로 관리
4. **빌더 시스템**: 노코드 빌더도 플러그인처럼 설치

### 10.2 판매 시나리오

**시나리오 1: 기본 패키지**
- FAPI Core + Member Module + BBS Module

**시나리오 2: 확장 패키지**
- FAPI Core + 모든 모듈 + 기본 플러그인

**시나리오 3: 테마 패키지**
- SvelteKit Theme + 필요한 모듈

**시나리오 4: 빌더 패키지**
- No-Code Builder + 컴포넌트 세트

이 구조로 각 기능을 독립적으로 판매하고 설치할 수 있습니다!

