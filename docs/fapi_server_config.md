# FAPI 서버 설정 가이드 (Nginx & Apache)

## 1. 개요

FAPI의 API 라우팅을 위한 Nginx와 Apache 웹서버 설정 가이드입니다.

## 2. Apache 설정

### 2.1 기본 설정 (.htaccess)

**⚠️ 중요: 모든 에러 응답은 JSON 형식으로 반환해야 합니다.**

**api/.htaccess:**
```apache
RewriteEngine On
RewriteBase /api/

# API 라우팅
# 파일이나 디렉토리가 존재하지 않으면 index.php로 라우팅
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# 보안: Private 파일 접근 차단 (JSON으로 반환)
<FilesMatch "^(\.env|_common\.php)$">
    Header set Content-Type "application/json"
    Require all denied
</FilesMatch>

# 디렉토리 리스팅 차단
Options -Indexes
```

**참고:** Apache의 경우 404 에러는 위의 RewriteRule로 `index.php`로 라우팅되므로 PHP에서 처리됩니다. 웹서버 레벨의 403, 500 등의 에러는 VirtualHost 설정에서 처리해야 합니다.

### 2.2 루트 디렉토리 설정

**그누보드 루트/.htaccess:**
```apache
RewriteEngine On

# API 라우팅
RewriteCond %{REQUEST_URI} ^/api/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [QSA,L]

# 프론트엔드 라우팅 (Static 배포 시)
RewriteCond %{REQUEST_URI} !^/api/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ api/static/index.html [L]
```

### 2.3 Apache VirtualHost 설정

**⚠️ 중요: 모든 에러 응답은 JSON 형식으로 반환해야 합니다.**

**httpd-vhosts.conf:**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # API 디렉토리 설정
    <Directory /var/www/html/api>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
    
    # PHP 설정
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    # API 에러 페이지를 JSON으로 반환
    # ErrorDocument는 PHP 파일로 리다이렉트하여 JSON 응답 생성
    ErrorDocument 404 /api/error_handler.php
    ErrorDocument 403 /api/error_handler.php
    ErrorDocument 500 /api/error_handler.php
    
    # 또는 index.php에서 처리하도록 설정 (권장)
    # ErrorDocument 404 /api/index.php
    # ErrorDocument 403 /api/index.php
    # ErrorDocument 500 /api/index.php
</VirtualHost>
```

**참고:** Apache의 `ErrorDocument`는 PHP 파일로 리다이렉트할 수 있습니다. `error_handler.php` 파일을 사용하거나, `index.php`에서 에러를 처리하도록 설정할 수 있습니다.

## 3. Nginx 설정

### 3.1 기본 설정

**⚠️ 중요: 모든 에러 응답은 JSON 형식으로 반환해야 합니다.**

**nginx.conf:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html;
    index index.html index.php;

    # API 라우팅
    location /api {
        try_files $uri $uri/ /api/index.php?$query_string;
        
        # PHP-FPM 설정
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # 보안: Private 파일 접근 차단 (JSON으로 반환)
        location ~ ^/api/(_common\.php|lib/|config/|\.env) {
            add_header Content-Type application/json always;
            return 403 '{"code":"00003","msg":"접근 권한이 없습니다.","data":null,"time":0}';
        }
    }
    
    # API 에러 페이지를 JSON으로 반환
    error_page 404 = @api_json_error;
    error_page 403 = @api_json_error;
    error_page 500 = @api_json_error;
    error_page 502 = @api_json_error;
    error_page 503 = @api_json_error;
    
    location @api_json_error {
        default_type application/json;
        add_header Content-Type application/json always;
        
        # 에러 코드에 따라 다른 응답
        if ($status = 404) {
            return 404 '{"code":"00001","msg":"페이지를 찾을 수 없습니다.","data":null,"time":0}';
        }
        if ($status = 403) {
            return 403 '{"code":"00003","msg":"접근 권한이 없습니다.","data":null,"time":0}';
        }
        if ($status = 500) {
            return 500 '{"code":"00001","msg":"서버 오류가 발생했습니다.","data":null,"time":0}';
        }
        if ($status = 502) {
            return 502 '{"code":"00001","msg":"서버 연결 오류가 발생했습니다.","data":null,"time":0}';
        }
        if ($status = 503) {
            return 503 '{"code":"00001","msg":"서비스를 일시적으로 사용할 수 없습니다.","data":null,"time":0}';
        }
        
        # 기본 에러 응답
        return 500 '{"code":"00001","msg":"서버 오류가 발생했습니다.","data":null,"time":0}';
    }
    
    # API 경로에서만 에러 페이지 적용
    location ~ ^/api {
        error_page 404 = @api_json_error;
        error_page 403 = @api_json_error;
        error_page 500 = @api_json_error;
        error_page 502 = @api_json_error;
        error_page 503 = @api_json_error;
    }

    # 프론트엔드 라우팅 (Static 배포 시)
    location / {
        try_files $uri $uri/ /api/static/index.html;
    }

    # 정적 파일 캐싱
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP 파일 처리
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3.2 서브도메인 설정 (Vercel/Cloudflare 배포)

**API 서버 (api.yourdomain.com):**
```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/html/api;
    index index.php;

    # API 라우팅
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # CORS 헤더 (개발 환경)
    add_header Access-Control-Allow-Origin "https://app.yourdomain.com" always;
    add_header Access-Control-Allow-Credentials "true" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, X-Requested-With" always;

    # OPTIONS 요청 처리
    if ($request_method = OPTIONS) {
        return 204;
    }
}
```

**프론트엔드 서버 (app.yourdomain.com):**
```nginx
server {
    listen 80;
    server_name app.yourdomain.com;
    root /var/www/html/frontend/build;
    index index.html;

    # SPA 라우팅
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 정적 파일 캐싱
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3.3 HTTPS 설정 (Let's Encrypt)

**Nginx SSL 설정:**
```nginx
server {
    listen 443 ssl http2;
    server_name api.yourdomain.com;
    root /var/www/html/api;
    index index.php;

    # SSL 인증서
    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;
    
    # SSL 설정
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # API 라우팅
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# HTTP to HTTPS 리다이렉트
server {
    listen 80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

## 4. PHP-FPM 설정

### 4.1 PHP-FPM 풀 설정

**/etc/php/8.1/fpm/pool.d/www.conf:**
```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

### 4.2 PHP 설정

**php.ini:**
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
memory_limit = 128M

; JSON 지원
extension=json

; OpenSSL 지원 (JWT용)
extension=openssl
```

## 5. 보안 설정

### 5.1 파일 접근 제한

**⚠️ 중요: 모든 에러 응답은 JSON 형식으로 반환해야 합니다.**

**Nginx:**
```nginx
# Private 파일 접근 차단 (JSON으로 반환)
location ~ ^/api/(_common\.php|lib/|config/|\.env) {
    add_header Content-Type application/json always;
    return 403 '{"code":"00003","msg":"접근 권한이 없습니다.","data":null,"time":0}';
}

# 디렉토리 리스팅 차단 (JSON으로 반환)
location ~ ^/api/routes/ {
    add_header Content-Type application/json always;
    return 403 '{"code":"00003","msg":"접근 권한이 없습니다.","data":null,"time":0}';
}
```

**Apache:**
```apache
# Private 파일 접근 차단 (JSON으로 반환)
<FilesMatch "^(\.env|_common\.php)$">
    Header set Content-Type "application/json"
    Require all denied
</FilesMatch>

# 디렉토리 리스팅 차단
Options -Indexes
```

### 5.2 Rate Limiting (Nginx)

```nginx
# Rate limiting 설정
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;

server {
    location /api {
        limit_req zone=api_limit burst=20 nodelay;
        
        # 기존 설정...
    }
}
```

## 6. 성능 최적화

### 6.1 캐싱 설정

**Nginx:**
```nginx
# API 응답 캐싱 (선택적)
location /api {
    # 캐시하지 않음 (기본)
    add_header Cache-Control "no-cache, no-store, must-revalidate";
    
    # 또는 특정 엔드포인트만 캐싱
    location ~ ^/api/menu$ {
        proxy_cache api_cache;
        proxy_cache_valid 200 1h;
        add_header Cache-Control "public, max-age=3600";
    }
}
```

### 6.2 Gzip 압축

**Nginx:**
```nginx
gzip on;
gzip_vary on;
gzip_min_length 1000;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

**Apache:**
```apache
LoadModule deflate_module modules/mod_deflate.so

<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \.(?:gif|jpe?g|png)$ no-gzip dont-vary
</Location>
```

## 7. 로깅 설정

### 7.1 Nginx 로그

```nginx
server {
    # 액세스 로그
    access_log /var/log/nginx/api-access.log;
    
    # 에러 로그
    error_log /var/log/nginx/api-error.log;
    
    # API만 별도 로그
    location /api {
        access_log /var/log/nginx/api-only.log;
        # ...
    }
}
```

### 7.2 PHP 에러 로그

**php.ini:**
```ini
log_errors = On
error_log = /var/log/php/error.log
display_errors = Off
```

## 8. 모니터링

### 8.1 헬스체크 엔드포인트

**api/routes/health.php:**
```php
<?php
function GET() {
    json_return([
        'status' => 'ok',
        'timestamp' => time(),
        'version' => '1.0.0'
    ], 200, '00000');
}
```

**Nginx 헬스체크:**
```nginx
location /api/health {
    access_log off;
    return 200 "healthy\n";
    add_header Content-Type text/plain;
}
```

## 9. 배포 후 확인 사항

### 9.1 체크리스트

- [ ] API 라우팅 동작 확인: `curl http://yourdomain.com/api/health`
- [ ] CORS 설정 확인 (서브도메인 사용 시)
- [ ] Cookie 설정 확인 (도메인, SameSite)
- [ ] HTTPS 설정 확인 (프로덕션)
- [ ] 파일 접근 제한 확인 (Private 파일)
- [ ] 로그 확인 (에러 없음)
- [ ] 성능 확인 (응답 시간)

### 9.2 테스트 명령어

```bash
# API 라우팅 테스트
curl http://yourdomain.com/api/health

# CORS 테스트
curl -H "Origin: https://app.yourdomain.com" \
     -H "Access-Control-Request-Method: POST" \
     -X OPTIONS \
     http://api.yourdomain.com/api/auth/login

# Cookie 테스트
curl -v -c cookies.txt \
     -b cookies.txt \
     -X POST \
     -H "Content-Type: application/json" \
     -d '{"mb_id":"test","mb_password":"test"}' \
     http://api.yourdomain.com/api/auth/login
```

## 10. 문제 해결

### 10.1 404 에러

**원인:** 라우팅 규칙이 제대로 적용되지 않음

**해결:**
- `.htaccess` 파일 확인 (Apache)
- Nginx 설정 확인
- 파일 권한 확인

### 10.2 500 에러

**원인:** PHP 에러 또는 권한 문제

**해결:**
- PHP 에러 로그 확인
- 파일 권한 확인 (`chmod 755`)
- PHP-FPM 상태 확인

### 10.3 CORS 에러

**원인:** CORS 헤더가 제대로 설정되지 않음

**해결:**
- `_middleware.php`의 CORS 설정 확인
- Nginx/Apache의 CORS 헤더 확인
- Origin 허용 목록 확인

## 11. 결론

이 설정으로 FAPI가 안정적으로 동작하며, 보안과 성능도 최적화됩니다!

