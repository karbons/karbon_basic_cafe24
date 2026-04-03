# GNU Karbon 배포 가이드

이 문서는 frontend와 manager 프로젝트를 karbon 서버에 배포하는 방법을 설명합니다.

## 📋 사전 요구 사항

### 1. SSH 설정

`~/.ssh/config` 파일에 다음 설정이 있어야 합니다:

```
Host karbon
    HostName 114.203.209.27
    User karbon
    IdentityFile ~/.ssh/karbon_ssh_key
    Port 22000
```

### 2. SSH 키 권한 설정

```bash
chmod 600 ~/.ssh/karbon_ssh_key
```

### 3. 서버 접속 테스트

```bash
ssh karbon
```

---

## 🚀 배포 방법

### Frontend 배포 (https://karbon.kr)

```bash
cd /Users/imjongpil/works/gnu_karbon/scripts
./deploy-frontend.sh
```

### Manager 배포 (https://karbon.kr/manager)

```bash
cd /Users/imjongpil/works/gnu_karbon/scripts
./deploy-manager.sh
```

---

## ⚙️ Apache 설정 (.htaccess)

SvelteKit SPA가 정상 동작하려면 서버에 `.htaccess` 파일이 필요합니다.

### .htaccess 배포

**최초 1회만** 실행하면 됩니다:

```bash
# 서버에 .htaccess 파일 업로드
scp -P 22000 /Users/imjongpil/works/gnu_karbon/scripts/.htaccess karbon:/srv/docker/apache-php/sites/default/

# manager 폴더에도 복사 (manager용 fallback)
ssh -p 22000 karbon "cp /srv/docker/apache-php/sites/default/.htaccess /srv/docker/apache-php/sites/default/manager/.htaccess"
```

### Apache mod_rewrite 활성화

서버에서 mod_rewrite가 활성화되어 있어야 합니다:

```bash
# 서버에 접속
ssh karbon

# mod_rewrite 활성화 (Ubuntu/Debian)
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### AllowOverride 설정

Apache 설정에서 `.htaccess`를 사용할 수 있도록 `AllowOverride All`이 설정되어 있어야 합니다.

---

## 📁 배포 구조

배포 후 서버의 디렉토리 구조:

```
/srv/docker/apache-php/sites/default/
├── .htaccess           # SPA fallback 설정
├── index.html          # frontend 메인
├── app/                # frontend 앱 파일
├── ...
└── manager/            # manager 프로젝트
    ├── .htaccess       # manager SPA fallback
    ├── index.html      # manager 메인
    └── app/            # manager 앱 파일
```

---

## 🔧 트러블슈팅

### 404 에러가 발생하는 경우

1. `.htaccess` 파일이 서버에 있는지 확인
2. Apache `mod_rewrite`가 활성화되어 있는지 확인
3. `AllowOverride All` 설정 확인

### 권한 오류가 발생하는 경우

```bash
# 서버에서 권한 수정
ssh karbon
sudo chown -R www-data:www-data /srv/docker/apache-php/sites/default
sudo chmod -R 755 /srv/docker/apache-php/sites/default
```

### rsync 연결 실패

```bash
# SSH 연결 테스트
ssh -p 22000 karbon "echo 'Connected!'"

# SSH 키 권한 확인
ls -la ~/.ssh/karbon_ssh_key
```

---

## 📝 스크립트 설명

| 스크립트 | 설명 |
|---------|------|
| `deploy-frontend.sh` | frontend 빌드 후 서버 루트에 배포 |
| `deploy-manager.sh` | manager 빌드 후 /manager 경로에 배포 |
| `.htaccess` | Apache SPA fallback 설정 파일 |
