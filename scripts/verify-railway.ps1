# Verify webdev on Railway (MySQL, API, web admin). Exit 1 on any failure.
$ErrorActionPreference = 'Stop'
$Base = 'https://webdev-production-c694.up.railway.app'
$Api = "$Base/api"
$failed = 0

function Assert($name, $condition, $detail = '') {
    if ($condition) {
        Write-Host "[OK] $name" -ForegroundColor Green
    } else {
        Write-Host "[FAIL] $name" -ForegroundColor Red
        if ($detail) { Write-Host "       $detail" -ForegroundColor DarkGray }
        $script:failed++
    }
}

Write-Host "Verifying $Base ..." -ForegroundColor Cyan

# Health
try {
    $health = (Invoke-WebRequest -Uri "$Base/health.html" -UseBasicParsing -TimeoutSec 30).Content.Trim()
    Assert 'health.html' ($health -eq 'ok') "got: $health"
} catch {
    Assert 'health.html' $false $_.Exception.Message
}

# Web login page
try {
    $loginPage = Invoke-WebRequest -Uri "$Base/login" -UseBasicParsing -TimeoutSec 30
    Assert 'GET /login' ($loginPage.StatusCode -eq 200 -and $loginPage.Content -match 'login')
} catch {
    Assert 'GET /login' $false $_.Exception.Message
}

# API login (customer)
try {
    $body = '{"email":"customer@onins.com","password":"customer123"}'
    $login = Invoke-RestMethod -Uri "$Api/login" -Method POST -ContentType 'application/json' -Body $body -TimeoutSec 30
    Assert 'POST /api/login' ($login.status -eq 'success' -and $login.token)
    $token = $login.token
} catch {
    Assert 'POST /api/login' $false $_.Exception.Message
    $token = $null
}

# API profile (JWT)
if ($token) {
    try {
        $headers = @{ Authorization = "Bearer $token" }
        $profile = Invoke-RestMethod -Uri "$Api/profile" -Headers $headers -TimeoutSec 30
        $profileEmail = $profile.data.email
        if (-not $profileEmail) { $profileEmail = $profile.email }
        Assert 'GET /api/profile' ($profileEmail -eq 'customer@onins.com')
    } catch {
        Assert 'GET /api/profile' $false $_.Exception.Message
    }

    try {
        $products = Invoke-RestMethod -Uri "$Api/products" -Headers $headers -TimeoutSec 30
        $hasProducts = @($products).Count -gt 0 -or @($products.data).Count -gt 0 -or ($products -is [array] -and $products.Length -gt 0)
        if (-not $hasProducts -and $products.PSObject.Properties['hydra:member']) {
            $hasProducts = @($products.'hydra:member').Count -gt 0
        }
        Assert 'GET /api/products (catalog)' $hasProducts 'No products — fixtures may be missing'
    } catch {
        Assert 'GET /api/products' $false $_.Exception.Message
    }
}

# Admin web login page
try {
    $admin = Invoke-WebRequest -Uri "$Base/admin" -UseBasicParsing -MaximumRedirection 0 -TimeoutSec 30 -ErrorAction SilentlyContinue
} catch {
    if ($_.Exception.Response.StatusCode -eq 302 -or $_.Exception.Response.StatusCode.value__ -eq 302) {
        $admin = $_.Exception.Response
    }
}
try {
    $adminGet = Invoke-WebRequest -Uri "$Base/admin" -UseBasicParsing -TimeoutSec 30
    Assert 'GET /admin' ($adminGet.StatusCode -eq 200 -or $adminGet.StatusCode -eq 302)
} catch {
    Assert 'GET /admin' $false $_.Exception.Message
}

Write-Host ''
if ($failed -gt 0) {
    Write-Host "$failed check(s) failed." -ForegroundColor Red
    exit 1
}
Write-Host 'All checks passed.' -ForegroundColor Green
exit 0
