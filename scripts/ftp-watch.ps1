<#
.SYNOPSIS
    SFTP 파일 감시 및 자동 업로드 스크립트

.DESCRIPTION
    backend/v1/api/ 폴더를 감시하여 파일 변경 시 자동으로 SFTP 서버에 업로드합니다.
    .env 파일에서 SFTP 설정을 읽어옵니다.

.PARAMETER Help
    사용법을 표시합니다.

.PARAMETER Test
    SFTP 연결을 테스트합니다.

.EXAMPLE
    .\ftp-watch.ps1
    파일 감시를 시작합니다.

.EXAMPLE
    .\ftp-watch.ps1 -Help
    사용법을 표시합니다.

.EXAMPLE
    .\ftp-watch.ps1 -Test
    SFTP 연결을 테스트합니다.
#>

param(
    [switch]$Help,
    [switch]$Test
)

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

$ScriptRoot = Split-Path -Parent $PSScriptRoot
$EnvFile = Join-Path $ScriptRoot ".env"

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Show-Help {
    Write-ColorOutput "=== SFTP 파일 감시 스크립트 ===" "Cyan"
    Write-Host ""
    Write-ColorOutput "사용법:" "Yellow"
    Write-Host "  .\ftp-watch.ps1          # 파일 감시 시작"
    Write-Host "  .\ftp-watch.ps1 -Help    # 도움말 표시"
    Write-Host "  .\ftp-watch.ps1 -Test    # SFTP 연결 테스트"
    Write-Host ""
    Write-ColorOutput "설정:" "Yellow"
    Write-Host "  .env 파일에서 SFTP 설정을 읽어옵니다:"
    Write-Host "    SFTP_HOST  - SFTP 서버 주소"
    Write-Host "    SFTP_PORT  - SFTP 포트 (기본: 22)"
    Write-Host "    SFTP_USER  - SFTP 사용자명"
    Write-Host "    SFTP_PASS  - SFTP 비밀번호"
    Write-Host "    SFTP_PATH  - SFTP 원격 경로"
    Write-Host ""
    Write-ColorOutput "감시 폴더:" "Yellow"
    Write-Host "  $WatchPath"
    Write-Host ""
}

function Read-EnvFile {
    if (-not (Test-Path $EnvFile)) {
        Write-ColorOutput "오류: .env 파일을 찾을 수 없습니다: $EnvFile" "Red"
        exit 1
    }

    $envVars = @{}
    Get-Content $EnvFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith("#")) {
            if ($line -match '^([^=]+)=(.*)$') {
                $key = $matches[1].Trim()
                $value = $matches[2].Trim()
                $value = $value -replace '^["'']|["'']$', ''
                $envVars[$key] = $value
            }
        }
    }
    return $envVars
}

function Upload-FileToSFTP {
    param(
        [string]$LocalPath,
        [string]$SftpHost,
        [int]$SftpPort,
        [string]$SftpUser,
        [string]$SftpPass,
        [string]$SftpBasePath
    )

    try {
        $relativePath = $LocalPath.Substring($WatchPath.Length).TrimStart('\')
        $relativePath = $relativePath -replace '\\', '/'
        $remotePath = "$SftpBasePath/$relativePath"
        $remoteDir = Split-Path -Parent $remotePath
        
        Write-ColorOutput "업로드 중: $relativePath" "Yellow"
        
        $sftpUrl = "sftp://${SftpHost}:${SftpPort}${remotePath}"
        $request = [System.Net.FtpWebRequest]::Create($sftpUrl)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($SftpUser, $SftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        
        $fileContent = [System.IO.File]::ReadAllBytes($LocalPath)
        $request.ContentLength = $fileContent.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        $response = $request.GetResponse()
        Write-ColorOutput "✓ 업로드 완료: $relativePath" "Green"
        $response.Close()
        
        return $true
    }
    catch {
        Write-ColorOutput "✗ 업로드 실패: $relativePath" "Red"
        Write-ColorOutput "  오류: $($_.Exception.Message)" "Red"
        return $false
    }
}

function Test-SFTPConnection {
    param(
        [hashtable]$Config
    )
    
    Write-ColorOutput "=== SFTP 연결 테스트 ===" "Cyan"
    Write-Host ""
    Write-ColorOutput "SFTP 서버: $($Config.SFTP_HOST)" "White"
    Write-ColorOutput "포트: $($Config.SFTP_PORT)" "White"
    Write-ColorOutput "사용자명: $($Config.SFTP_USER)" "White"
    Write-ColorOutput "원격 경로: $($Config.SFTP_PATH)" "White"
    Write-Host ""
    
    try {
        $sftpUrl = "sftp://$($Config.SFTP_HOST):$($Config.SFTP_PORT)$($Config.SFTP_PATH)/"
        $request = [System.Net.FtpWebRequest]::Create($sftpUrl)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($Config.SFTP_USER, $Config.SFTP_PASS)
        $request.UsePassive = $true
        $request.KeepAlive = $false
        $request.Timeout = 10000
        
        $response = $request.GetResponse()
        Write-ColorOutput "✓ SFTP 연결 성공!" "Green"
        Write-ColorOutput "  상태: $($response.StatusDescription)" "Green"
        $response.Close()
        
        return $true
    }
    catch {
        Write-ColorOutput "✗ SFTP 연결 실패!" "Red"
        Write-ColorOutput "  오류: $($_.Exception.Message)" "Red"
        
        if ($_.Exception.InnerException) {
            Write-ColorOutput "  상세: $($_.Exception.InnerException.Message)" "Red"
        }
        
        return $false
    }
}

if ($Help) {
    Show-Help
    exit 0
}

$config = Read-EnvFile

$WatchPath = Join-Path $ScriptRoot "backend\v1\api"

$requiredKeys = @("SFTP_HOST", "SFTP_USER", "SFTP_PASS", "SFTP_PATH")
$missingKeys = $requiredKeys | Where-Object { -not $config.ContainsKey($_) -or -not $config[$_] }

if ($missingKeys.Count -gt 0) {
    Write-ColorOutput "오류: .env 파일에 다음 설정이 누락되었습니다:" "Red"
    $missingKeys | ForEach-Object { Write-Host "  - $_" }
    exit 1
}

if (-not $config.ContainsKey("SFTP_PORT") -or -not $config.SFTP_PORT) {
    $config.SFTP_PORT = 22
}

if ($Test) {
    $testResult = Test-SFTPConnection -Config $config
    exit $(if ($testResult) { 0 } else { 1 })
}

if (-not (Test-Path $WatchPath)) {
    Write-ColorOutput "오류: 감시 폴더를 찾을 수 없습니다: $WatchPath" "Red"
    exit 1
}

Write-ColorOutput "SFTP 연결 확인 중..." "Yellow"
if (-not (Test-SFTPConnection -Config $config)) {
    Write-ColorOutput "SFTP 연결에 실패했습니다. 설정을 확인해주세요." "Red"
    exit 1
}

Write-Host ""
Write-ColorOutput "=== SFTP 파일 감시 시작 ===" "Cyan"
Write-ColorOutput "감시 폴더: $WatchPath" "White"
Write-ColorOutput "SFTP 서버: $($config.SFTP_HOST)" "White"
Write-ColorOutput "원격 경로: $($config.SFTP_PATH)" "White"
Write-Host ""
Write-ColorOutput "파일 변경을 감시 중... (Ctrl+C로 종료)" "Green"
Write-Host ""

$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $WatchPath
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true
$watcher.NotifyFilter = [System.IO.NotifyFilters]::FileName -bor 
                        [System.IO.NotifyFilters]::LastWrite -bor
                        [System.IO.NotifyFilters]::DirectoryName

$uploadQueue = [System.Collections.Concurrent.ConcurrentDictionary[string,datetime]]::new()
$uploadTimer = New-Object System.Timers.Timer
$uploadTimer.Interval = 1000
$uploadTimer.AutoReset = $true

$uploadTimerAction = {
    $now = Get-Date
    $toUpload = @()
    
    foreach ($item in $uploadQueue.GetEnumerator()) {
        if (($now - $item.Value).TotalSeconds -ge 1) {
            $toUpload += $item.Key
        }
    }
    
    foreach ($path in $toUpload) {
        $null = $uploadQueue.TryRemove($path, [ref]$null)
        
        if (Test-Path $path) {
            $maxRetries = 3
            $retryCount = 0
            $success = $false
            
            while (-not $success -and $retryCount -lt $maxRetries) {
                $success = Upload-FileToSFTP -LocalPath $path `
                                           -SftpHost $config.SFTP_HOST `
                                           -SftpPort $config.SFTP_PORT `
                                           -SftpUser $config.SFTP_USER `
                                           -SftpPass $config.SFTP_PASS `
                                           -SftpBasePath $config.SFTP_PATH
                
                if (-not $success) {
                    $retryCount++
                    if ($retryCount -lt $maxRetries) {
                        Write-ColorOutput "  재시도 $retryCount/$maxRetries..." "Yellow"
                        Start-Sleep -Seconds 2
                    }
                end if
            }
            
            if (-not $success) {
                Write-ColorOutput "  최대 재시도 횟수 초과. 업로드 실패." "Red"
            }
        }
    }
}

Register-ObjectEvent -InputObject $uploadTimer -EventName Elapsed -Action $uploadTimerAction | Out-Null
$uploadTimer.Start()

$onChange = {
    param($sender, $e)
    
    $path = $e.FullPath
    $changeType = $e.ChangeType
    
    $fileName = [System.IO.Path]::GetFileName($path)
    if ($fileName.StartsWith(".") -or $fileName.StartsWith("~") -or $fileName.EndsWith(".tmp")) {
        return
    }
    
    if (Test-Path $path -PathType Container) {
        return
    }
    
    $uploadQueue[$path] = Get-Date
}

Register-ObjectEvent -InputObject $watcher -EventName Changed -Action $onChange | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName Created -Action $onChange | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName Renamed -Action $onChange | Out-Null

try {
    while ($true) {
        Start-Sleep -Seconds 1
    }
}
finally {
    Write-Host ""
    Write-ColorOutput "파일 감시를 종료합니다..." "Yellow"
    
    $uploadTimer.Stop()
    $uploadTimer.Dispose()
    $watcher.EnableRaisingEvents = $false
    $watcher.Dispose()
    
    Get-EventSubscriber | Unregister-Event
    Get-Job | Remove-Job -Force
    
    Write-ColorOutput "종료 완료." "Green"
}
