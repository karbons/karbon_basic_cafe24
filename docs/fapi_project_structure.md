# FAPI 프로젝트 구조 및 배포 가이드

## 1. 개요

API와 프론트엔드(SvelteKit)를 **같은 폴더에서 개발**하고, **형상관리(Git)**와 **배포**가 쉽도록 구성된 프로젝트 구조입니다.

## 2. 프로젝트 구조 (모노레포)

### 2.1 전체 구조

```
gnu_karbon/
├── .git/                  # Git 형상관리
├── .gitignore
├── README.md
├── api/                   # FAPI 백엔드 (그누보드에 배포)
│   ├── index.php
│   ├── _common.php
│   ├── routes/
│   ├── lib/
│   └── config/
├── frontend/              # SvelteKit 프론트엔드
│   ├── src/
│   ├── static/
│   ├── package.json
│   ├── svelte.config.js
│   └── vercel.json       # Vercel 배포 설정
├── scripts/              # 배포 스크립트
│   ├── deploy-api.sh
│   ├── deploy-static.sh
│   └── deploy-vercel.sh
└── docs/                 # 문서
    ├── fapi_*.md
    └── deployment.md
```

### 2.2 개발 환경

**같은 폴더에서 개발:**
- API: `api/` 폴더
- 프론트: `frontend/` 폴더
- Git으로 함께 관리
- 로컬 개발 시 프록시 설정으로 연동

## 3. Git 형상관리

### 3.1 .gitignore

**.gitignore:**
```
# API
api/.env
api/config/*.local.php
api/logs/
api/cache/

# Frontend
frontend/node_modules/
frontend/.svelte-kit/
frontend/build/
frontend/dist/
frontend/.env
frontend/.env.local

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db
```

### 3.2 브랜치 전략

```
main                    # 프로덕션 배포
├── develop            # 개발 브랜치
│   ├── feature/api-*
│   ├── feature/frontend-*
│   └── fix/*
└── release/*          # 릴리즈 브랜치
```

### 3.3 커밋 컨벤션

```
feat(api): 로그인 API 추가
fix(frontend): 로그인 폼 버그 수정
docs: 배포 가이드 추가
refactor(api): 라우터 코드 개선
```

## 4. 개발 환경 설정

### 4.1 로컬 개발 구조

```
localhost:5173          # SvelteKit 개발 서버 (프론트)
localhost/api           # PHP API 서버 (백엔드)
```

### 4.2 SvelteKit 설정 (프록시)

**frontend/svelte.config.js:**
```javascript
import adapter from '@sveltejs/adapter-static';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

/** @type {import('@sveltejs/kit').Config} */
const config = {
  preprocess: vitePreprocess(),
  
  kit: {
    adapter: adapter({
      pages: 'build',
      assets: 'build',
      fallback: 'index.html',
      precompress: false,
      strict: true
    }),
    
    // 개발 환경에서 API 프록시
    alias: {
      '$api': '../api'
    }
  }
};

export default config;
```

**frontend/vite.config.js:**
```javascript
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [sveltekit()],
  
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false
      }
    }
  }
});
```

### 4.3 API 개발 서버

**api/.htaccess (Apache):**
```apache
RewriteEngine On
RewriteBase /api/

# API 라우팅
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**api/nginx.conf (Nginx):**
```nginx
location /api {
    try_files $uri $uri/ /api/index.php?$query_string;
    
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 5. 배포 전략

### 5.1 시나리오 1: 기존 PHP 호스팅 (Static Adapter)

**프론트엔드를 정적 파일로 빌드하여 PHP 호스팅에 배포**

#### 5.1.1 빌드 설정

**frontend/svelte.config.js:**
```javascript
import adapter from '@sveltejs/adapter-static';

export default {
  kit: {
    adapter: adapter({
      pages: 'build',
      assets: 'build',
      fallback: 'index.html',
      precompress: false
    })
  }
};
```

#### 5.1.2 빌드 및 배포

**scripts/deploy-static.sh:**
```bash
#!/bin/bash

echo "=== FAPI Static 배포 시작 ==="

# 1. 프론트엔드 빌드
cd frontend
npm run build
cd ..

# 2. 빌드 파일을 api 폴더로 복사
cp -r frontend/build/* api/static/

# 3. API 파일과 함께 배포
echo "배포 준비 완료!"
echo "api/ 폴더 전체를 서버에 업로드하세요."
```

**배포 구조:**
```
서버/
├── api/
│   ├── index.php
│   ├── routes/
│   ├── static/          # 프론트엔드 빌드 파일
│   │   ├── index.html
│   │   ├── _app/
│   │   └── assets/
│   └── .htaccess
```

#### 5.1.3 서버 설정

**api/.htaccess:**
```apache
RewriteEngine On
RewriteBase /

# API 라우팅
RewriteCond %{REQUEST_URI} ^/api/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [QSA,L]

# 프론트엔드 라우팅 (SPA)
RewriteCond %{REQUEST_URI} !^/api/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ api/static/index.html [L]
```

**nginx 설정:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html;
    index index.html;

    # API 라우팅
    location /api {
        try_files $uri $uri/ /api/index.php?$query_string;
        
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 프론트엔드 라우팅 (SPA)
    location / {
        try_files $uri $uri/ /api/static/index.html;
    }

    # 정적 파일 캐싱
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 5.2 시나리오 2: Vercel/Cloudflare 배포 (서브도메인)

**프론트엔드를 Vercel/Cloudflare에 배포하고 API는 기존 서버 사용**

#### 5.2.1 도메인 구조

```
api.yourdomain.com      # API 서버 (기존 PHP 호스팅)
app.yourdomain.com      # 프론트엔드 (Vercel/Cloudflare)
```

#### 5.2.2 Vercel 배포 설정

**frontend/vercel.json:**
```json
{
  "version": 2,
  "builds": [
    {
      "src": "package.json",
      "use": "@sveltejs/kit"
    }
  ],
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/$1"
    }
  ],
  "env": {
    "PUBLIC_API_URL": "https://api.yourdomain.com"
  }
}
```

**frontend/.env.production:**
```env
PUBLIC_API_URL=https://api.yourdomain.com
```

#### 5.2.3 Cloudflare Pages 배포

**frontend/wrangler.toml:**
```toml
name = "fapi-frontend"
compatibility_date = "2024-01-01"

[env.production]
vars = { PUBLIC_API_URL = "https://api.yourdomain.com" }
```

#### 5.2.4 CORS 설정 (API 서버)

**api/routes/_middleware.php:**
```php
<?php
function cors() {
    $allowedOrigins = [
        'https://app.yourdomain.com',
        'http://localhost:5173' // 개발 환경
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

cors();
```

#### 5.2.5 Cookie 설정 (서브도메인)

**api/lib/Auth.php:**
```php
public static function setAccessTokenCookie($token, $expiresIn = 900) {
    $options = [
        'expires' => time() + $expiresIn,
        'path' => '/',
        'domain' => '.yourdomain.com', // 서브도메인 공유
        'secure' => true, // HTTPS만
        'httponly' => true,
        'samesite' => 'None' // 크로스 도메인
    ];
    
    setcookie(self::$cookieName, $token, $options);
}
```

#### 5.2.6 프론트엔드 API 호출

**frontend/src/lib/api.ts:**
```typescript
const API_URL = import.meta.env.PUBLIC_API_URL || '/api';

export async function apiRequest(url: string, options: RequestInit = {}) {
    const response = await fetch(`${API_URL}${url}`, {
        ...options,
        credentials: 'include', // Cookie 전송
        headers: {
            'Content-Type': 'application/json',
            ...options.headers,
        },
    });
    
    return response.json();
}
```

## 6. 배포 스크립트

### 6.1 Static 배포 스크립트

**scripts/deploy-static.sh:**
```bash
#!/bin/bash

set -e

echo "=== FAPI Static 배포 ==="

# 환경 변수 확인
if [ -z "$DEPLOY_HOST" ]; then
    echo "DEPLOY_HOST 환경 변수를 설정하세요."
    exit 1
fi

# 1. 프론트엔드 빌드
echo "프론트엔드 빌드 중..."
cd frontend
npm install
npm run build
cd ..

# 2. 빌드 파일 복사
echo "빌드 파일 복사 중..."
rm -rf api/static
mkdir -p api/static
cp -r frontend/build/* api/static/

# 3. 서버에 배포
echo "서버에 배포 중..."
rsync -avz --delete api/ $DEPLOY_HOST:/path/to/api/

echo "배포 완료!"
```

### 6.2 Vercel 배포 스크립트

**scripts/deploy-vercel.sh:**
```bash
#!/bin/bash

set -e

echo "=== FAPI Vercel 배포 ==="

# 1. 프론트엔드 빌드 확인
cd frontend
npm install

# 2. Vercel 배포
vercel --prod

echo "배포 완료!"
```

### 6.3 Cloudflare 배포 스크립트

**scripts/deploy-cloudflare.sh:**
```bash
#!/bin/bash

set -e

echo "=== FAPI Cloudflare 배포 ==="

cd frontend
npm install
npm run build

# Cloudflare Pages 배포
wrangler pages deploy build --project-name=fapi-frontend

echo "배포 완료!"
```

## 7. CI/CD 설정

### 7.1 GitHub Actions

**.github/workflows/deploy.yml:**
```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy-api:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy API
        uses: appleboy/scp-action@master
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_KEY }}
          source: "api/"
          target: "/path/to/api/"
  
  deploy-frontend-static:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Build Frontend
        run: |
          cd frontend
          npm install
          npm run build
      
      - name: Deploy Static
        run: |
          cp -r frontend/build/* api/static/
          # 배포 로직
  
  deploy-frontend-vercel:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Deploy to Vercel
        uses: amondnet/vercel-action@v20
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.VERCEL_ORG_ID }}
          vercel-project-id: ${{ secrets.VERCEL_PROJECT_ID }}
          working-directory: ./frontend
```

## 8. 환경 변수 관리

### 8.1 개발 환경

**frontend/.env.local:**
```env
PUBLIC_API_URL=http://localhost/api
```

**api/.env.local:**
```env
APP_ENV=development
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

### 8.2 프로덕션 환경

**Static 배포:**
```env
PUBLIC_API_URL=/api
```

**Vercel/Cloudflare 배포:**
```env
PUBLIC_API_URL=https://api.yourdomain.com
```

## 9. 개발 워크플로우

### 9.1 로컬 개발

```bash
# 1. API 서버 시작 (PHP 내장 서버)
cd api
php -S localhost:8000

# 2. 프론트엔드 개발 서버 시작
cd frontend
npm run dev

# 브라우저: http://localhost:5173
# API: http://localhost:8000/api
```

### 9.2 Git 워크플로우

```bash
# 기능 개발
git checkout -b feature/new-api
# 개발...
git commit -m "feat(api): 새 API 추가"
git push origin feature/new-api

# PR 생성 및 머지
# develop 브랜치로 머지

# 릴리즈
git checkout -b release/1.0.0
# 테스트...
git checkout main
git merge release/1.0.0
git tag v1.0.0

# 배포
./scripts/deploy-static.sh
```

## 10. 문제 해결

### 10.1 Cookie 문제 (서브도메인)

**문제:** `app.yourdomain.com`에서 `api.yourdomain.com`의 쿠키를 읽을 수 없음

**해결:**
- Cookie domain을 `.yourdomain.com`으로 설정
- SameSite를 `None`으로 설정
- Secure 속성 필수 (HTTPS)

### 10.2 CORS 문제

**문제:** 프론트엔드에서 API 호출 시 CORS 에러

**해결:**
- API 서버의 CORS 설정 확인
- `Access-Control-Allow-Credentials: true` 확인
- Origin 허용 목록 확인

### 10.3 라우팅 문제

**문제:** SPA에서 새로고침 시 404

**해결:**
- 모든 경로를 `index.html`로 리다이렉트
- `.htaccess` 또는 nginx 설정 확인

## 11. 결론

### 11.1 핵심 장점

1. **형상관리**: Git으로 API와 프론트 함께 관리
2. **개발 편의**: 같은 폴더에서 개발
3. **배포 유연성**: Static 또는 Vercel/Cloudflare 선택
4. **AI 개발 지원**: 구조가 명확하여 AI가 이해하기 쉬움

### 11.2 배포 시나리오 선택

**기존 PHP 호스팅 사용:**
- Static Adapter 방식
- 단일 도메인
- 간단한 설정

**최신 인프라 활용:**
- Vercel/Cloudflare 배포
- 서브도메인 분리
- 더 나은 성능

이 구조로 개발과 배포가 모두 쉬워집니다!

