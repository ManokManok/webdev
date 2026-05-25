# Push ONINS to GitHub (run from repo root).
# Requires GitHub login as a user with write access to the target repo.
$ErrorActionPreference = "Stop"
Set-Location (Split-Path -Parent $PSScriptRoot)

$canonical = "https://github.com/ninocabajon1/webdevnino.git"
$deploy = "https://github.com/ninocabajon2-star/mao-nani.git"

git remote set-url origin $canonical
if (-not (git remote | Select-String -Quiet "^deploy$")) {
    git remote add deploy $deploy
}

Write-Host "Pushing to canonical: ninocabajon1/webdevnino ..."
git push origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host "Canonical push failed (login as ninocabajon1). Pushing to deploy remote..." -ForegroundColor Yellow
    git push deploy main --force
    if ($LASTEXITCODE -ne 0) { exit 1 }
    Write-Host "OK: https://github.com/ninocabajon2-star/mao-nani" -ForegroundColor Green
} else {
    Write-Host "OK: https://github.com/ninocabajon1/webdevnino" -ForegroundColor Green
}
