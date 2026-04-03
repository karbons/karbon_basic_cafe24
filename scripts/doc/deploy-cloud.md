# 클라우드 배포 가이드

Vercel, Cloudflare Pages, 자체 서버에 프론트엔드를 배포하는 방법을 설명합니다.

## 1. 아키텍처 개요

```
┌─────────────────┐     ┌─────────────────┐
│  Cloudflare     │     │   Cafe24        │
│  Pages/Vercel   │ ──→ │   PHP API       │
│  (프론트엔드)    │     │   (백엔드)      │
└─────────────────┘     └─────────────────┘
```

## 2. Vercel 배포

### 2.1 프로젝트 연결

1. Vercel 대시보드 → New Project
2. GitHub 저장소 연결
3. Root Directory: `frontend/main` (또는 원하는 폴더)

### 2.2 빌드 설정

| 설정 | 값 |
|------|-----|
| Framework | SvelteKit |
| Build Command | `npm run build` |
| Output Directory | `build` |

### 2.3 환경 변수

```
PUBLIC_API_URL=https://api.yourdomain.com
```

### 2.4 배포

```bash
# Vercel CLI 사용
npm i -g vercel
vercel --prod
```

## 3. Cloudflare Pages 배포

### 3.1 프로젝트 연결

1. Cloudflare 대시보드 → Pages
2. Create a project → Connect to Git
3. 저장소 선택

### 3.2 빌드 설정

| 설정 | 값 |
|------|-----|
| Production branch | main |
| Build command | `npm run build` |
| Build output directory | `build` |

### 3.3 환경 변수

```
PUBLIC_API_URL=https://api.yourdomain.com
```

### 3.4 배포

```bash
# Wrangler CLI 사용
npm i -g wrangler
wrangler pages deploy build
```

## 4. 자체 서버 배포 (Nginx)

### 4.1 Nginx 설정

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/karbon/main/build;
    index index.html;

    # SvelteKit SPA 모드
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 프록시
    location /api {
        proxy_pass http://localhost:8080/v1/fapi;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # 정적 파일 캐시
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 4.2 SSL 설정 (Let's Encrypt)

```bash
sudo certbot --nginx -d yourdomain.com
```

## 5. CORS 설정

프론트엔드 도메인과 백엔드 도메인이 다를 경우:

### 백엔드 CORS 설정 (config.php)

```php
define('CORS_ALLOWED_ORIGINS', [
    'https://your-app.vercel.app',
    'https://yourdomain.com',
    'http://localhost:5173'  // 개발용
]);
```

## 6. 환경별 adapter 설정

### adapter-static (호스팅, Vercel, Cloudflare)

```javascript
// svelte.config.js
import adapter from '@sveltejs/adapter-static';

export default {
    kit: {
        adapter: adapter({
            fallback: 'index.html'
        })
    }
};
```

### adapter-node (자체 서버)

```javascript
import adapter from '@sveltejs/adapter-node';

export default {
    kit: {
        adapter: adapter({
            out: 'build'
        })
    }
};
```

## 7. 문제 해결

### CORS 오류
- 백엔드 `config.php`의 CORS_ALLOWED_ORIGINS에 도메인 추가

### 빌드 실패
- Node.js 버전 확인 (18+)
- 환경 변수 설정 확인

### 라우팅 404
- SPA fallback 설정 확인
- Nginx `try_files` 설정 확인

---

## 다음 단계

- [Cafe24 배포 가이드](deploy-cafe24.md)
- [스크립트 문서](scripts.md)
- [상세 설치 가이드](install-detailed.md)
