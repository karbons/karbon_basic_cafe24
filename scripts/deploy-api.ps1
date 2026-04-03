# ===================================
# API 배포 스크립트 (Windows)
# gnu5_api/gnuboard/api/ 폴더를 FTP 서버로 업로드
# ===================================

param(
    [switch]$DryRun,
    [switch]$Help
)

# 색상 함수
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

# 경로 설정
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$ApiDir = Join-Path $ProjectRoot "gnu5_api\gnuboard\api"

# 도움말 표시
if ($Help) {
    Write-ColorOutput Blue "========================================"
    Write-ColorOutput Blue "  API 배포 스크립트 사용법"
    Write-ColorOutput Blue "========================================"
    Write-Output ""
    Write-ColorOutput Yellow "사용법:"
    Write-Output "  .\deploy-api.ps1 [옵션]"
    Write-Output ""
    Write-ColorOutput Yellow "옵션:"
    Write-Output "  -Help       이 도움말을 표시합니다"
    Write-Output "  -DryRun     실제 업로드 없이 업로드될 파일 목록만 표시합니다"
    Write-Output ""
    Write-ColorOutput Yellow "설명:"
    Write-Output "  gnu5_api\gnuboard\api\ 폴더를 FTP 서버로 업로드합니다."
    Write-Output "  FTP 설정은 .env 파일에서 읽어옵니다."
    Write-Output ""
    Write-ColorOutput Yellow "필요한 환경 변수 (.env):"
    Write-Output "  FTP_HOST    FTP 서버 주소"
    Write-Output "  FTP_USER    FTP 사용자명"
    Write-Output "  FTP_PASS    FTP 비밀번호"
    Write-Output "  FTP_PATH    FTP 업로드 경로"
    Write-Output ""
    exit 0
}

# .env 파일 로드
$EnvFile = Join-Path $ProjectRoot ".env"
if (-not (Test-Path $EnvFile)) {
    Write-ColorOutput Red "✗ .env 파일을 찾을 수 없습니다: $EnvFile"
    exit 1
}

# .env 파일 파싱
$EnvVars = @{}
Get-Content $EnvFile | ForEach-Object {
    if ($_ -match '^\s*([^#][^=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $value = $matches[2].Trim()
        # 따옴표 제거
        $value = $value -replace '^["'']|["'']$', ''
        $EnvVars[$key] = $value
    }
}

# FTP 설정 확인
if (-not $EnvVars.ContainsKey('FTP_HOST') -or 
    -not $EnvVars.ContainsKey('FTP_USER') -or 
    -not $EnvVars.ContainsKey('FTP_PASS') -or 
    -not $EnvVars.ContainsKey('FTP_PATH')) {
    Write-ColorOutput Red "✗ FTP 설정이 완전하지 않습니다. .env 파일을 확인하세요."
    Write-ColorOutput Yellow "필요한 변수: FTP_HOST, FTP_USER, FTP_PASS, FTP_PATH"
    exit 1
}

$FtpHost = $EnvVars['FTP_HOST']
$FtpUser = $EnvVars['FTP_USER']
$FtpPass = $EnvVars['FTP_PASS']
$FtpPath = $EnvVars['FTP_PATH']

# API 디렉토리 확인
if (-not (Test-Path $ApiDir)) {
    Write-ColorOutput Red "✗ API 디렉토리를 찾을 수 없습니다: $ApiDir"
    exit 1
}

Write-ColorOutput Yellow "========================================"
if ($DryRun) {
    Write-ColorOutput Yellow "  API 배포 시뮬레이션 (Dry Run)"
} else {
    Write-ColorOutput Yellow "  API 배포 시작"
}
Write-ColorOutput Yellow "========================================"

Write-ColorOutput Green "✓ API 디렉토리: $ApiDir"
Write-ColorOutput Green "✓ FTP 서버: $FtpHost"
Write-ColorOutput Green "✓ FTP 경로: $FtpPath"

# FTP 업로드 함수
function Upload-FtpDirectory {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [string]$FtpServer,
        [string]$Username,
        [string]$Password,
        [bool]$IsDryRun
    )
    
    $files = Get-ChildItem -Path $LocalPath -Recurse -File
    $totalFiles = $files.Count
    $currentFile = 0
    
    foreach ($file in $files) {
        $currentFile++
        $relativePath = $file.FullName.Substring($LocalPath.Length).TrimStart('\')
        $remoteFile = "$RemotePath/api/$($relativePath -replace '\\', '/')"
        
        if ($IsDryRun) {
            Write-ColorOutput Green "  → $relativePath"
        } else {
            $progress = [math]::Round(($currentFile / $totalFiles) * 100)
            Write-Progress -Activity "FTP 업로드 중" -Status "$currentFile / $totalFiles 파일" -PercentComplete $progress
            
            try {
                $ftpUri = "ftp://$FtpServer$remoteFile"
                $ftpRequest = [System.Net.FtpWebRequest]::Create($ftpUri)
                $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($Username, $Password)
                $ftpRequest.UseBinary = $true
                $ftpRequest.UsePassive = $true
                
                # 파일 내용 읽기
                $fileContent = [System.IO.File]::ReadAllBytes($file.FullName)
                $ftpRequest.ContentLength = $fileContent.Length
                
                # 업로드
                $requestStream = $ftpRequest.GetRequestStream()
                $requestStream.Write($fileContent, 0, $fileContent.Length)
                $requestStream.Close()
                
                $response = $ftpRequest.GetResponse()
                $response.Close()
                
                Write-ColorOutput Green "  ✓ $relativePath"
            } catch {
                Write-ColorOutput Red "  ✗ $relativePath : $($_.Exception.Message)"
            }
        }
    }
    
    Write-Progress -Activity "FTP 업로드 중" -Completed
}

if ($DryRun) {
    Write-ColorOutput Yellow "→ 업로드될 파일 목록 확인 중..."
    Write-ColorOutput Blue "업로드될 파일:"
    Upload-FtpDirectory -LocalPath $ApiDir -RemotePath $FtpPath -FtpServer $FtpHost -Username $FtpUser -Password $FtpPass -IsDryRun $true
    Write-Output ""
    Write-ColorOutput Yellow "========================================"
    Write-ColorOutput Blue "  Dry Run 완료 (실제 업로드 안 됨)"
    Write-ColorOutput Yellow "========================================"
} else {
    Write-ColorOutput Yellow "→ FTP 서버로 파일 업로드 중..."
    Upload-FtpDirectory -LocalPath $ApiDir -RemotePath $FtpPath -FtpServer $FtpHost -Username $FtpUser -Password $FtpPass -IsDryRun $false
    Write-ColorOutput Green "✓ 파일 업로드 완료"
    Write-Output ""
    Write-ColorOutput Yellow "========================================"
    Write-ColorOutput Green "  API 배포 완료!"
    Write-ColorOutput Yellow "========================================"
}
