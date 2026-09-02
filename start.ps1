# LavaLust Docker Quick Start Script for Windows PowerShell

Write-Host "🚀 Starting LavaLust Docker Environment..." -ForegroundColor Green
Write-Host ""

# Check if Docker is running
$dockerRunning = docker ps 2>$null
if (-not $dockerRunning) {
    Write-Host "❌ Docker is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}

Write-Host "✅ Docker is running" -ForegroundColor Green

# Check if docker-compose.yml exists
if (-not (Test-Path "docker-compose.yml")) {
    Write-Host "❌ docker-compose.yml not found. Make sure you're in the project root." -ForegroundColor Red
    exit 1
}

Write-Host "✅ docker-compose.yml found" -ForegroundColor Green

# Copy .env if needed
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✅ Created .env from .env.example" -ForegroundColor Green
    }
}

# Create storage directories if needed
$dirs = @("storage", "storage/sessions", "logs")
foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✅ Created $dir directory" -ForegroundColor Green
    }
}

# Start Docker Compose
Write-Host ""
Write-Host "Starting containers..." -ForegroundColor Cyan
docker-compose up -d

# Wait for services to be ready
Write-Host ""
Write-Host "Waiting for services to start..." -ForegroundColor Cyan
Start-Sleep -Seconds 10

# Check status
Write-Host ""
Write-Host "✅ Docker environment started!" -ForegroundColor Green
Write-Host ""
docker-compose ps
Write-Host ""
Write-Host "🌐 Access your application:" -ForegroundColor Cyan
Write-Host "   • App: http://localhost" -ForegroundColor Yellow
Write-Host "   • phpMyAdmin: http://localhost:8080" -ForegroundColor Yellow
Write-Host ""
Write-Host "📦 Next steps:" -ForegroundColor Cyan
Write-Host "   1. Configure database in your LavaLust config file" -ForegroundColor Yellow
Write-Host "   2. Run migrations (if needed): docker-compose exec php php -r 'your_migration_command'" -ForegroundColor Yellow
Write-Host "   3. Install Composer dependencies: docker-compose exec php composer install" -ForegroundColor Yellow
Write-Host ""
Write-Host "🛑 To stop: docker-compose down" -ForegroundColor Cyan
Write-Host ""
