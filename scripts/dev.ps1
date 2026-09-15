param(
    [ValidateSet('Start', 'Stop', 'Compose')]
    [string]$Action = 'Start',
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$DockerArguments
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
$useWsl = $false
$distribution = if ($env:OLXV2_WSL_DISTRO) { $env:OLXV2_WSL_DISTRO } else { 'Ubuntu' }
try {
    if (Get-Command docker -ErrorAction SilentlyContinue) {
        & docker info --format '{{.ServerVersion}}' 2>$null | Out-Null
        if ($LASTEXITCODE -ne 0) { throw 'Start Docker Desktop, then run this script again.' }
        $mountRoot = $projectRoot
    } else {
        $useWsl = $true
        & wsl -d $distribution -- docker info --format '{{.ServerVersion}}' 2>$null | Out-Null
        if ($LASTEXITCODE -ne 0) { throw 'Docker is unavailable. See README.md for Docker Desktop or WSL setup.' }
        $wslCompatiblePath = $projectRoot.Replace('\', '/')
        $mountRoot = (& wsl -d $distribution -- wslpath -a $wslCompatiblePath)
        if ($mountRoot) { $mountRoot = $mountRoot.Trim() }
        if ($LASTEXITCODE -ne 0) { throw 'Cannot resolve the project directory inside WSL.' }
    }
    function Invoke-ProjectDocker {
        param([string[]]$Arguments)
        if ($useWsl) {
            & wsl -d $distribution --cd $mountRoot -- docker @Arguments
        } else {
            & docker @Arguments
        }
        if ($LASTEXITCODE -ne 0) { throw "Docker command failed (exit $LASTEXITCODE)." }
    }
    . (Join-Path $PSScriptRoot 'wsl-lifetime.ps1')
    $keeperState = Join-Path $projectRoot '.tools/wsl-keeper.json'
    if ($Action -eq 'Start') {
        if ($useWsl) { Start-ProjectWslKeeper -Distribution $distribution -WorkingDirectory $mountRoot -StateFile $keeperState }
        if (-not (Test-Path -LiteralPath '.env')) {
            Invoke-ProjectDocker @('run', '--rm', '-v', "${mountRoot}:/work", '-w', '/work', 'php:8.4-cli-bookworm', 'php', 'scripts/setup-env.php')
        }
        # A stopped Vite process must not leave a stale hot-file in the default built-asset mode.
        $hotFile = Join-Path $projectRoot 'public/hot'
        if (Test-Path -LiteralPath $hotFile) { Remove-Item -LiteralPath $hotFile }
        Invoke-ProjectDocker @('compose', 'up', '-d', '--build', '--wait', '--wait-timeout', '300')
        $applicationUrl = (Get-Content -LiteralPath '.env' | Where-Object { $_ -match '^APP_URL=' } | Select-Object -First 1) -replace '^APP_URL=', ''
        $mailPort = (Get-Content -LiteralPath '.env' | Where-Object { $_ -match '^MAILPIT_PORT=' } | Select-Object -First 1) -replace '^MAILPIT_PORT=', ''
        Write-Host ""
        & (Join-Path $PSScriptRoot 'verify-start.ps1') -ApplicationUrl $applicationUrl
        Write-Host "Application: $applicationUrl" -ForegroundColor Green
        Write-Host "Mailpit: http://localhost:$mailPort"
        Write-Host 'Development data is preserved in Docker named volumes.'
    } elseif ($Action -eq 'Stop') {
        Invoke-ProjectDocker @('compose', '--profile', 'dev', 'stop')
        Stop-ProjectWslKeeper -StateFile $keeperState
        Write-Host 'Stopped. Databases, images, email, and credentials are preserved.'
    } else {
        if (-not $DockerArguments) { throw 'Supply a Compose command, for example: ps' }
        Invoke-ProjectDocker (@('compose') + $DockerArguments)
    }
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
