$ErrorActionPreference = "Stop"
$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")

Set-Location (Join-Path $PSScriptRoot "..")

Write-Host "Building and starting containers..."
docker compose up -d --build

Write-Host "Waiting for database..."
Start-Sleep -Seconds 8

Write-Host "Running migrations..."
docker compose run --rm --no-deps --entrypoint php worker bin/console migrations:migrate

Write-Host "Loading fixtures..."
docker compose run --rm --no-deps --entrypoint php worker bin/console fixtures:load

Write-Host ""
Write-Host "Ready:"
Write-Host "  Frontend:  http://localhost:4200"
Write-Host "  API:       http://localhost:8080/api/health"
Write-Host "  Swagger:   http://localhost:8080/api/docs"
Write-Host "  RabbitMQ:  http://localhost:15672 (guest/guest)"
Write-Host "  Login:     admin@example.com / admin123"
