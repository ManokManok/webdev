# Start ONINS Symfony API for mobile / local dev (port 8000)
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Test-ApiHealthy {
    try {
        $body = '{"email":"customer@onins.com","password":"customer123"}'
        $r = Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/login' -Method POST `
            -ContentType 'application/json' -Body $body -TimeoutSec 8
        return $r.status -eq 'success'
    } catch {
        return $false
    }
}

function Invoke-External {
    param(
        [Parameter(Mandatory = $true, Position = 0)]
        [scriptblock]$Command
    )

    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'SilentlyContinue'
    try {
        $output = & $Command 2>&1
        foreach ($line in @($output)) {
            $text = "$line"
            if ($text) {
                Write-Host $text
            }
        }
        if ($LASTEXITCODE -ne 0) {
            throw "Command failed with exit code $LASTEXITCODE"
        }
    } finally {
        $ErrorActionPreference = $previousPreference
    }
}

function Ensure-Database {
    $docker = Get-Command docker -ErrorAction SilentlyContinue
    if (-not $docker) {
        Write-Warning 'Docker not found. Ensure MySQL is running and DATABASE_URL in .env is correct.'
        return
    }

    Write-Host 'Starting MySQL + Mercure (docker compose up -d)...'
    Invoke-External { docker compose up -d mysql mercure }
    Start-Sleep -Seconds 6

    $php = Get-Command php -ErrorAction SilentlyContinue
    if ($php) {
        Invoke-External { php bin/console doctrine:database:create --if-not-exists }
        Invoke-External { php bin/console doctrine:migrations:migrate --no-interaction }
        Invoke-External { php bin/console app:ensure-demo-customer --no-interaction }
    }
}

function Start-ApiServer {
    $php = Get-Command php -ErrorAction SilentlyContinue
    if (-not $php) {
        Write-Error 'PHP is not on PATH. Install PHP (e.g. XAMPP) or add it to PATH.'
    }

    $symfony = Get-Command symfony -ErrorAction SilentlyContinue
    if ($symfony) {
        Invoke-External { symfony server:stop --all }
    }

    Start-Process -FilePath 'php' -ArgumentList '-S', '127.0.0.1:8000', '-t', 'public' `
        -WorkingDirectory $root -WindowStyle Hidden
}

if (Test-ApiHealthy) {
    Write-Host 'ONINS API already running at http://127.0.0.1:8000'
    Write-Host '  API: http://127.0.0.1:8000/api'
    Write-Host '  Demo: customer@onins.com / customer123'
    exit 0
}

Ensure-Database

Write-Host 'Starting ONINS API on http://127.0.0.1:8000 ...'
Start-ApiServer

$deadline = (Get-Date).AddSeconds(60)
while ((Get-Date) -lt $deadline) {
    if (Test-ApiHealthy) {
        Write-Host 'ONINS API is ready.'
        Write-Host '  API: http://127.0.0.1:8000/api'
        Write-Host '  Demo: customer@onins.com / customer123'
        exit 0
    }
    Start-Sleep -Seconds 2
}

Write-Error 'ONINS API did not become ready. Check .env, JWT keys (config/jwt), and MySQL (docker compose up -d).'
