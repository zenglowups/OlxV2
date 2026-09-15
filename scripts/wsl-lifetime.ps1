# Keep this WSL instance alive while the app is running. systemd alone does not do that.
function Get-ProjectWslKeeper {
    param([string]$StateFile)
    if (-not (Test-Path -LiteralPath $StateFile)) { return $null }
    try {
        $state = Get-Content -LiteralPath $StateFile -Raw | ConvertFrom-Json
        $process = Get-Process -Id $state.ProcessId -ErrorAction Stop
        if ($process.ProcessName -eq 'wsl' -and
            $process.StartTime.ToUniversalTime().Ticks.ToString() -eq $state.StartTicks) {
            return $process
        }
    } catch { return $null }
    return $null
}
function Start-ProjectWslKeeper {
    param([string]$Distribution, [string]$WorkingDirectory, [string]$StateFile)
    if (Get-ProjectWslKeeper -StateFile $StateFile) { return }
    [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($StateFile)) | Out-Null
    $arguments = @('-d', $Distribution, '--exec', '/bin/sleep', 'infinity')
    $logDirectory = [IO.Path]::GetDirectoryName($StateFile)
    $keeper = Start-Process -FilePath "$env:WINDIR\System32\wsl.exe" -ArgumentList $arguments -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $logDirectory 'wsl-keeper.stdout.log') -RedirectStandardError (Join-Path $logDirectory 'wsl-keeper.stderr.log')
    if ($keeper.WaitForExit(1000)) { throw ('WSL keepalive failed: ' + (Get-Content (Join-Path $logDirectory 'wsl-keeper.stderr.log') -Raw)) }
    @{
        ProcessId = $keeper.Id
        StartTicks = $keeper.StartTime.ToUniversalTime().Ticks.ToString()
    } | ConvertTo-Json | Set-Content -LiteralPath $StateFile -Encoding UTF8
}
function Stop-ProjectWslKeeper {
    param([string]$StateFile)
    $keeper = Get-ProjectWslKeeper -StateFile $StateFile
    if ($keeper) { Stop-Process -Id $keeper.Id -ErrorAction SilentlyContinue }
    if (Test-Path -LiteralPath $StateFile) { Remove-Item -LiteralPath $StateFile }
}
