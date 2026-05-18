$patterns = @(
    '*artisan*serve*',
    '*php.exe -S 127.0.0.1:8001*',
    '*php.exe  -S 127.0.0.1:8001*',
    '*php.exe -S 127.0.0.1:8000*',
    '*php.exe  -S 127.0.0.1:8000*',
    '*artisan*serve*--port=8001*',
    '*artisan*serve*--port 8001*',
    '*artisan*serve*--port=8000*',
    '*artisan*serve*--port 8000*',
    '*vite*--host*127.0.0.1*',
    '*npm*run*dev*--*--host*127.0.0.1*'
)

$processes = Get-CimInstance Win32_Process | Where-Object {
    $commandLine = $_.CommandLine
    $patterns | Where-Object { $commandLine -like $_ }
}

if (-not $processes) {
    Write-Host 'No local dev processes found.'
    exit 0
}

foreach ($process in $processes) {
    if ($process.ProcessId -eq $PID) {
        continue
    }
    Write-Host "Stopping PID $($process.ProcessId): $($process.CommandLine)"
    Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue
}

$ports = @(8001, 8000, 5173)
$listeners = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue |
Where-Object { $ports -contains $_.LocalPort } |
Select-Object -ExpandProperty OwningProcess -Unique

foreach ($listenerPid in $listeners) {
    if ($listenerPid -and $listenerPid -ne $PID) {
        Write-Host "Stopping listener PID $listenerPid"
        Stop-Process -Id $listenerPid -Force -ErrorAction SilentlyContinue
    }
}
