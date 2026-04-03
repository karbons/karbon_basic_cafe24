# ===================================
# 환경 설정 스크립트 (Windows PowerShell)
# .env 파일 생성 및 설정
# ===================================

param(
    [switch]$Help,
    [switch]$Auto,
    [switch]$Interactive
)

# 색상 정의
$ColorRed = "Red"
$ColorGreen = "Green"
$ColorYellow = "Yellow"
$ColorBlue = "Cyan"

# 도움말 함수
function Show-Help {
    Write-Host "========================================" -ForegroundColor $ColorBlue
    Write-Host "  Karbon 환경 설정 스크립트 (Windows)" -ForegroundColor $ColorBlue
    Write-Host "========================================" -ForegroundColor $ColorBlue
    Write-Host ""
    Write-Host "사용법:" -ForegroundColor $ColorGreen
    Write-Host "  .\setup-env.ps1 [옵션]"
    Write-Host ""
    Write-Host "옵션:" -ForegroundColor $ColorGreen
    Write-Host "  -Help              이 도움말 표시"
    Write-Host "  -Interactive       대화형 모드 (기본값)"
    Write-Host "  -Auto              자동 모드 (기본값 사용)"
    Write-Host ""
    Write-Host "설명:" -ForegroundColor $ColorGreen
    Write-Host "  이 스크립트는 .env 파일을 생성하고 필요한 설정을 진행합니다."
    Write-Host "  - DEV_MODE: 개발 모드 선택 (local/remote)"
    Write-Host "  - FTP 설정: remote 모드 선택 시 FTP 정보 입력"
    Write-Host "  - 환경 파일 복사: 각 프로젝트 디렉토리에 .env 파일 복사"
    Write-Host ""
    Write-Host "예시:" -ForegroundColor $ColorGreen
    Write-Host "  .\setup-env.ps1                # 대화형 모드 실행"
    Write-Host "  .\setup-env.ps1 -Auto          # 자동 모드 실행"
    Write-Host ""
    Write-Host "========================================" -ForegroundColor $ColorBlue
}

# 도움말 표시
if ($Help) {
    Show-Help
    exit 0
}

# 변수 초기화
$InteractiveMode = $true
if ($Auto) {
    $InteractiveMode = $false
}

$DevMode = ""
$FtpHost = ""
$FtpUser = ""
$FtpPass = ""
$FtpPath = ""
$JwtAccessKey = ""
$JwtRefreshKey = ""

# 랜덤 키 생성 함수
function Get-RandomKey {
    $length = 64
    $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    $result = ""
    for ($i = 0; $i -lt $length; $i++) {
        $result += $characters[(Get-Random -Minimum 0 -Maximum $characters.Length)]
    }
    return $result
}

# 스크립트 디렉토리
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "========================================" -ForegroundColor $ColorYellow
Write-Host "  Karbon 환경 설정 시작" -ForegroundColor $ColorYellow
Write-Host "========================================" -ForegroundColor $ColorYellow
Write-Host ""

# .env 파일 생성 (없으면)
$EnvFile = Join-Path $ScriptDir ".env"
if (-not (Test-Path $EnvFile)) {
    Write-Host "→ .env 파일 생성 중..." -ForegroundColor $ColorYellow
    Copy-Item (Join-Path $ScriptDir ".env.example") $EnvFile
    Write-Host "✓ .env 파일 생성 완료" -ForegroundColor $ColorGreen
} else {
    Write-Host "✓ .env 파일이 이미 존재합니다" -ForegroundColor $ColorGreen
}

# 대화형 모드
if ($InteractiveMode) {
    Write-Host ""
    Write-Host "--- DEV_MODE 설정 ---" -ForegroundColor $ColorBlue
    Write-Host "개발 모드를 선택하세요:" -ForegroundColor $ColorYellow
    Write-Host "  1) local  - 로컬 개발 환경"
    Write-Host "  2) remote - 원격 서버 배포"
    
    $DevModeChoice = Read-Host "선택 (1 또는 2) [기본값: 1]"
    if ([string]::IsNullOrWhiteSpace($DevModeChoice)) {
        $DevModeChoice = "1"
    }
    
    switch ($DevModeChoice) {
        "1" {
            $DevMode = "local"
            Write-Host "✓ DEV_MODE=local 선택됨" -ForegroundColor $ColorGreen
        }
        "2" {
            $DevMode = "remote"
            Write-Host "✓ DEV_MODE=remote 선택됨" -ForegroundColor $ColorGreen
            
            # FTP 설정 입력
            Write-Host ""
            Write-Host "--- FTP 설정 ---" -ForegroundColor $ColorBlue
            
            $FtpHost = Read-Host "FTP 호스트 [기본값: your-ftp-host.com]"
            if ([string]::IsNullOrWhiteSpace($FtpHost)) {
                $FtpHost = "your-ftp-host.com"
            }
            
            $FtpUser = Read-Host "FTP 사용자명 [기본값: your-ftp-username]"
            if ([string]::IsNullOrWhiteSpace($FtpUser)) {
                $FtpUser = "your-ftp-username"
            }
            
            $FtpPass = Read-Host "FTP 비밀번호 [기본값: your-ftp-password]" -AsSecureString
            if ($FtpPass.Length -eq 0) {
                $FtpPass = "your-ftp-password"
            } else {
                $FtpPass = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToCoTaskMemUnicode($FtpPass))
            }
            
            $FtpPath = Read-Host "FTP 경로 [기본값: /public_html/karbon]"
            if ([string]::IsNullOrWhiteSpace($FtpPath)) {
                $FtpPath = "/public_html/karbon"
            }
            
            Write-Host "✓ FTP 설정 완료" -ForegroundColor $ColorGreen
        }
        default {
            Write-Host "✗ 잘못된 선택입니다. 기본값(local)을 사용합니다." -ForegroundColor $ColorRed
            $DevMode = "local"
        }
    }
} else {
    # 자동 모드 - 기본값 사용
    $DevMode = "local"
    $FtpHost = "your-ftp-host.com"
    $FtpUser = "your-ftp-username"
    $FtpPass = "your-ftp-password"
    $FtpPath = "/public_html/karbon"
    Write-Host "✓ 자동 모드: 기본값 사용" -ForegroundColor $ColorGreen
}

# .env 파일 업데이트
Write-Host ""
Write-Host "→ .env 파일 업데이트 중..." -ForegroundColor $ColorYellow

# 파일 읽기
$EnvContent = Get-Content $EnvFile -Raw

# DEV_MODE 업데이트
if ($EnvContent -match "^DEV_MODE=") {
    $EnvContent = $EnvContent -replace "^DEV_MODE=.*$", "DEV_MODE=$DevMode"
} else {
    $EnvContent += "`nDEV_MODE=$DevMode"
}

# FTP 설정 업데이트
if ($EnvContent -match "^FTP_HOST=") {
    $EnvContent = $EnvContent -replace "^FTP_HOST=.*$", "FTP_HOST=$FtpHost"
} else {
    $EnvContent += "`nFTP_HOST=$FtpHost"
}

if ($EnvContent -match "^FTP_USER=") {
    $EnvContent = $EnvContent -replace "^FTP_USER=.*$", "FTP_USER=$FtpUser"
} else {
    $EnvContent += "`nFTP_USER=$FtpUser"
}

if ($EnvContent -match "^FTP_PASS=") {
    $EnvContent = $EnvContent -replace "^FTP_PASS=.*$", "FTP_PASS=$FtpPass"
} else {
    $EnvContent += "`nFTP_PASS=$FtpPass"
}

if ($EnvContent -match "^FTP_PATH=") {
    $EnvContent = $EnvContent -replace "^FTP_PATH=.*$", "FTP_PATH=$FtpPath"
} else {
    $EnvContent += "`nFTP_PATH=$FtpPath"
}

# JWT 키 자동 생성 및 업데이트
Write-Host "→ JWT 보안 키 확인 중..." -ForegroundColor $ColorYellow

# JWT_ACCESS_TOKEN_KEY 확인
if ($EnvContent -match "^JWT_ACCESS_TOKEN_KEY=(your-access-token-key-change-in-production|)$") {
    Write-Host "  - JWT_ACCESS_TOKEN_KEY 생성 중..." -ForegroundColor $ColorYellow
    $NewKey = Get-RandomKey
    $EnvContent = $EnvContent -replace "^JWT_ACCESS_TOKEN_KEY=.*$", "JWT_ACCESS_TOKEN_KEY=$NewKey"
}

# JWT_REFRESH_TOKEN_KEY 확인
if ($EnvContent -match "^JWT_REFRESH_TOKEN_KEY=(your-refresh-token-key-change-in-production|)$") {
    Write-Host "  - JWT_REFRESH_TOKEN_KEY 생성 중..." -ForegroundColor $ColorYellow
    $NewKey = Get-RandomKey
    $EnvContent = $EnvContent -replace "^JWT_REFRESH_TOKEN_KEY=.*$", "JWT_REFRESH_TOKEN_KEY=$NewKey"
}

# 파일 쓰기
Set-Content $EnvFile $EnvContent -Encoding UTF8

Write-Host "✓ .env 파일 업데이트 완료" -ForegroundColor $ColorGreen

# 환경 파일 복사
Write-Host ""
Write-Host "→ 환경 파일 복사 중..." -ForegroundColor $ColorYellow

function Copy-EnvFile {
    param(
        [string]$TargetDir,
        [string]$TargetName
    )
    
    if (Test-Path $TargetDir) {
        Copy-Item $EnvFile (Join-Path $TargetDir ".env") -Force
        Write-Host "✓ $TargetName/.env 복사 완료" -ForegroundColor $ColorGreen
    } else {
        Write-Host "⚠ $TargetName 디렉토리를 찾을 수 없습니다" -ForegroundColor $ColorYellow
    }
}

Copy-EnvFile (Join-Path $ScriptDir "backend") "backend"
Copy-EnvFile (Join-Path $ScriptDir "backend" "gnuboard" "api") "backend/gnuboard/api"
Copy-EnvFile (Join-Path $ScriptDir "frontend" "app") "frontend/app"
Copy-EnvFile (Join-Path $ScriptDir "frontend" "manager") "frontend/manager"

Write-Host ""
Write-Host "========================================" -ForegroundColor $ColorYellow
Write-Host "  환경 설정 완료!" -ForegroundColor $ColorGreen
Write-Host "  DEV_MODE: $DevMode" -ForegroundColor $ColorGreen
if ($DevMode -eq "remote") {
    Write-Host "  FTP_HOST: $FtpHost" -ForegroundColor $ColorGreen
}
Write-Host "========================================" -ForegroundColor $ColorYellow
