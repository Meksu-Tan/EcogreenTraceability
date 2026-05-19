$patterns = @(
    '*artisan*serve*--port=8001*',
    '*artisan*serve*--port=8000*',
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
    Write-Host "Stopping PID $($process.ProcessId): $($process.CommandLine)"
    Stop-Process -Id $process.ProcessId -Force
}
