# Verify web form login (session + CSRF) on Railway or local.
$ErrorActionPreference = 'Stop'
$Base = if ($env:WEB_BASE_URL) { $env:WEB_BASE_URL.TrimEnd('/') } else { 'https://webdev-production-c694.up.railway.app' }
$Email = if ($env:LOGIN_EMAIL) { $env:LOGIN_EMAIL } else { 'admin@onins' }
$Password = if ($env:LOGIN_PASSWORD) { $env:LOGIN_PASSWORD } else { 'admin123' }
$cookieJar = Join-Path $env:TEMP "onins-login-cookies-$([Guid]::NewGuid().ToString('n')).txt"

try {
    Write-Host "GET $Base/login ..." -ForegroundColor Cyan
    $loginPage = Invoke-WebRequest -Uri "$Base/login" -SessionVariable session -UseBasicParsing
    if ($loginPage.Content -notmatch 'name="_csrf_token"\s+value="([^"]+)"') {
        throw 'CSRF token not found on login page'
    }
    $csrf = $Matches[1]
    Write-Host "CSRF token obtained." -ForegroundColor DarkGray

    Write-Host "POST $Base/login_check ..." -ForegroundColor Cyan
    $body = @{
        _username   = $Email
        _password   = $Password
        _csrf_token = $csrf
    }
    $post = Invoke-WebRequest -Uri "$Base/login_check" -Method POST -Body $body -WebSession $session -MaximumRedirection 0 -UseBasicParsing -ErrorAction SilentlyContinue
    $status = $post.StatusCode
} catch {
    if ($_.Exception.Response) {
        $status = [int]$_.Exception.Response.StatusCode
        $location = $_.Exception.Response.Headers['Location']
    } else {
        throw
    }
}

if ($status -eq 302 -and $location -match '/admin') {
    Write-Host "[OK] Login succeeded (redirect to admin)." -ForegroundColor Green
    exit 0
}
if ($status -eq 302) {
    Write-Host "[OK] Login succeeded (redirect: $location)." -ForegroundColor Green
    exit 0
}

if ($status -eq 200 -and $post.Content -match 'Invalid CSRF token') {
    Write-Host "[FAIL] Invalid CSRF token on login." -ForegroundColor Red
    exit 1
}

Write-Host "[FAIL] Unexpected response (HTTP $status)." -ForegroundColor Red
exit 1
finally {
    Remove-Item -LiteralPath $cookieJar -ErrorAction SilentlyContinue
}
