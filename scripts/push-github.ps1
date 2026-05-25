# Push ONINS to GitHub (run from repo root).
# Requires GitHub login as a user with write access to the target repo.
$ErrorActionPreference = "Stop"
Set-Location (Split-Path -Parent $PSScriptRoot)

$origin = "https://github.com/ManokManok/repair.git"

git remote set-url origin $origin

Write-Host "Pushing ONINS to ManokManok/repair ..."
git push origin main
if ($LASTEXITCODE -ne 0) { exit 1 }
Write-Host "OK: https://github.com/ManokManok/repair" -ForegroundColor Green
