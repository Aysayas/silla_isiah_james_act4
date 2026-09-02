Param()

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

Write-Host ""
Write-Host "=== LavaLust Template - New Project Setup ===" -ForegroundColor Cyan
Write-Host "Run this ONCE, right after cloning this template for a new project." -ForegroundColor DarkGray
Write-Host ""

# -----------------------------------------------------------------
# 1. Ask for the new project's name
# -----------------------------------------------------------------
$displayName = Read-Host "What's this project called? (e.g. 'My Cool App')"
if ([string]::IsNullOrWhiteSpace($displayName)) {
    Write-Host "No name entered, aborting." -ForegroundColor Red
    exit 1
}

# Slug for package.json / folder-safe usage: lowercase, spaces -> hyphens, strip anything odd
$slug = $displayName.ToLower()
$slug = $slug -replace '[^a-z0-9]+', '-'
$slug = $slug.Trim('-')
if ([string]::IsNullOrWhiteSpace($slug)) { $slug = 'my-lavalust-app' }

# Docker/DB-safe prefix: same thing but with underscores (matches the "lavalust_" pattern
# already used for container names, network name, and DB name/user/password)
$dockerPrefix = $slug -replace '-', '_'

Write-Host ""
Write-Host "Project name:      $displayName"
Write-Host "package.json name: $slug"
Write-Host "Docker/env prefix: $dockerPrefix (containers, network, DB name/user/password)"
Write-Host ""

# -----------------------------------------------------------------
# 2. Rename everywhere: package.json, docker-compose.yml, .env.example, home.php
# -----------------------------------------------------------------
Write-Host "Renaming project references..." -ForegroundColor Cyan

if (Test-Path "package.json") {
    $content = Get-Content "package.json" -Raw
    $content = $content -replace '"name":\s*"[^"]*"', "`"name`": `"$slug`""
    Set-Content -Path "package.json" -Value $content -NoNewline
    Write-Host "  - package.json updated"
}

foreach ($f in @("docker-compose.yml", ".env.example")) {
    if (Test-Path $f) {
        $content = Get-Content $f -Raw
        $content = $content -replace 'lavalust_', "$dockerPrefix`_"
        Set-Content -Path $f -Value $content -NoNewline
        Write-Host "  - $f updated"
    }
}

if (Test-Path "app/views/home.php") {
    $content = Get-Content "app/views/home.php" -Raw
    $content = $content -replace 'LavaLust \+ Docker \+ Tailwind', $displayName
    $content = $content -replace 'LavaLust Docker Template', $displayName
    Set-Content -Path "app/views/home.php" -Value $content -NoNewline
    Write-Host "  - app/views/home.php updated"
}

# Regenerate .env from the freshly-renamed .env.example (fresh clones won't have
# a .env yet since it's gitignored; if one already exists here, back it up first)
if (Test-Path ".env") {
    Copy-Item ".env" ".env.bak" -Force
    Write-Host "  - existing .env backed up to .env.bak"
}
if (Test-Path ".env.example") {
    Copy-Item ".env.example" ".env" -Force
    Write-Host "  - .env created from .env.example"
}

Write-Host "Renaming done." -ForegroundColor Green
Write-Host ""

# -----------------------------------------------------------------
# 3. Fresh git history
# -----------------------------------------------------------------
$doGit = Read-Host "Detach from the template's git history and start fresh? (Y/n)"
if ($doGit -ne 'n' -and $doGit -ne 'N') {
    $oldRemote = $null
    try { $oldRemote = git remote get-url origin 2>$null } catch {}

    if (Test-Path ".git") {
        Remove-Item ".git" -Recurse -Force
        Write-Host "  - old .git history removed" -ForegroundColor Green
    }

    git init | Out-Null
    git add -A | Out-Null
    git commit -m "Initial commit: $displayName (from lavalust-docker-template)" | Out-Null
    git branch -M main | Out-Null
    Write-Host "  - fresh git repo initialized with one commit on 'main'" -ForegroundColor Green

    if ($oldRemote) {
        Write-Host "  (for reference, the template's old remote was: $oldRemote)" -ForegroundColor DarkGray
    }

    $newRemote = Read-Host "New GitHub repo URL for '$displayName' (leave blank to skip)"
    if (-not [string]::IsNullOrWhiteSpace($newRemote)) {
        git remote add origin $newRemote | Out-Null
        Write-Host "  - remote 'origin' set to $newRemote" -ForegroundColor Green

        $doPush = Read-Host "Push now? (y/N)"
        if ($doPush -eq 'y' -or $doPush -eq 'Y') {
            git push -u origin main
            Write-Host "  - pushed to $newRemote" -ForegroundColor Green
        } else {
            Write-Host "  - skipped push; run 'git push -u origin main' when ready" -ForegroundColor DarkGray
        }
    } else {
        Write-Host "  - skipped remote setup; add one later with: git remote add origin <url>" -ForegroundColor DarkGray
    }
} else {
    Write-Host "  - kept existing git history" -ForegroundColor DarkGray
}
Write-Host ""

# -----------------------------------------------------------------
# 4. Install dependencies
# -----------------------------------------------------------------
Write-Host "Installing dependencies..." -ForegroundColor Cyan

if (Get-Command npm -ErrorAction SilentlyContinue) {
    npm install
    Write-Host "  - npm dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  - npm not found on PATH, skipping (Docker's node service will still work)" -ForegroundColor Yellow
}

if (Test-Path "composer.json") {
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        composer install
        Write-Host "  - composer dependencies installed" -ForegroundColor Green
    } else {
        Write-Host "  - composer not found locally, skipping (the Dockerfile runs 'composer install' inside the php image build, so this is optional - only needed for local IDE autocomplete)" -ForegroundColor Yellow
    }
}
Write-Host ""

# -----------------------------------------------------------------
# 5. Start Docker
# -----------------------------------------------------------------
Write-Host "Starting Docker..." -ForegroundColor Cyan

docker info > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker does not appear to be running. Trying to start Docker Desktop..." -ForegroundColor Yellow
    Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe" -ErrorAction SilentlyContinue
    Write-Host "Waiting for Docker to come up, this can take a minute..."
    do {
        Start-Sleep -Seconds 5
        docker info > $null 2>&1
    } while ($LASTEXITCODE -ne 0)
    Write-Host "Docker is up." -ForegroundColor Green
}

docker-compose up -d --build
Start-Sleep -Seconds 8
docker-compose ps

# Open the project in VS Code
if (Get-Command code -ErrorAction SilentlyContinue) {
    code .
} else {
    Write-Host "VS Code CLI 'code' not found on PATH - skipping editor launch." -ForegroundColor Yellow
}

Start-Process "http://localhost"

Write-Host ""
Write-Host "=== '$displayName' is ready ===" -ForegroundColor Green
Write-Host "  App:        http://localhost"
Write-Host "  phpMyAdmin: http://localhost:8080"
Write-Host "  DB name/user/password now use the '$dockerPrefix' prefix"
Write-Host "  Stop with:  docker-compose down  (or double-click stop-dev.bat)"
Write-Host ""
Read-Host "Press Enter to close"
