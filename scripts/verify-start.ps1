param([Parameter(Mandatory = $true)][string]$ApplicationUrl)
$ErrorActionPreference = 'Stop'
$health = Invoke-RestMethod -Uri "$($ApplicationUrl.TrimEnd('/'))/health/ready" -TimeoutSec 15
if ($health.status -ne 'ok' -or $health.application -ne 'olxv2') {
    throw 'The configured URL does not serve this OLXv2 application. Check APP_PORT and APP_URL for a Windows/WSL port conflict.'
}
Write-Host "Verified OLXv2 at $ApplicationUrl" -ForegroundColor Green
