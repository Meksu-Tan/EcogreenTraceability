$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

Write-Host 'Stopping existing local dev processes...'
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

foreach ($process in $processes) {
    Write-Host "Stopping PID $($process.ProcessId)"
    Stop-Process -Id $process.ProcessId -Force
}

Write-Host 'Clearing Laravel config/cache...'
Push-Location $root
php artisan config:clear | Out-Host
php artisan route:clear | Out-Host
php artisan view:clear | Out-Host
Pop-Location

Push-Location $backend
php artisan config:clear | Out-Host
php artisan route:clear | Out-Host
php artisan view:clear | Out-Host
Pop-Location

Write-Host 'Starting Laravel Master on http://127.0.0.1:8001 ...'
Start-Process -FilePath php -ArgumentList @('artisan','serve','--host=127.0.0.1','--port=8001') -WorkingDirectory $root -WindowStyle Hidden

Write-Host 'Starting Laravel Backend API on http://127.0.0.1:8000 ...'
Start-Process -FilePath php -ArgumentList @('artisan','serve','--host=127.0.0.1','--port=8000') -WorkingDirectory $backend -WindowStyle Hidden

Write-Host 'Starting Vue Frontend on http://127.0.0.1:5173 ...'
Start-Process -FilePath npm.cmd -ArgumentList @('run','dev','--','--host','127.0.0.1','--port','5173') -WorkingDirectory $frontend -WindowStyle Hidden

Start-Sleep -Seconds 3

$targets = @(
    @{ Name = 'Laravel Master'; Url = 'http://127.0.0.1:8001' },
    @{ Name = 'Backend API'; Url = 'http://127.0.0.1:8000/api/user'; ExpectedStatus = 401; Headers = @{ Accept = 'application/json' } },
    @{ Name = 'Frontend'; Url = 'http://127.0.0.1:5173' }
)

foreach ($target in $targets) {
    try {
        $requestArgs = @{
            Uri = $target.Url
            UseBasicParsing = $true
            TimeoutSec = 5
        }
        if ($target.Headers) {
            $requestArgs.Headers = $target.Headers
        }

        $response = Invoke-WebRequest @requestArgs
        Write-Host "$($target.Name): OK ($($response.StatusCode))"
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($target.ExpectedStatus -and $statusCode -eq $target.ExpectedStatus) {
            Write-Host "$($target.Name): OK ($statusCode expected)"
        } else {
            Write-Host "$($target.Name): started, but health check failed - $($_.Exception.Message)"
        }
    }
}

Write-Host ''
Write-Host 'URLs:'
Write-Host '  Master   http://127.0.0.1:8001'
Write-Host '  Backend  http://127.0.0.1:8000'
Write-Host '  Frontend http://127.0.0.1:5173'
