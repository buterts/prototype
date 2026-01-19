# Database Setup Script for Windows
# Run in PowerShell

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Agricultural Marketplace - DB Setup" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Check if MySQL is accessible
try {
    $mysqlPath = Get-Command mysql -ErrorAction Stop
    Write-Host "✓ MySQL found at: $($mysqlPath.Source)" -ForegroundColor Green
} catch {
    Write-Host "❌ MySQL is not found in PATH" -ForegroundColor Red
    Write-Host "Please ensure MySQL is installed and in your system PATH" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""

# Prompt for credentials
$dbUser = Read-Host "Enter MySQL username (default: root)"
if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = "root" }

$securePass = Read-Host "Enter MySQL password (press Enter if none)" -AsSecureString
$dbPass = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToCoTaskMemUnicode($securePass))

Write-Host ""
Write-Host "📦 Creating database from schema..." -ForegroundColor Yellow

# Run schema.sql
$schemaPath = Join-Path $PSScriptRoot "config\schema.sql"

if (-not (Test-Path $schemaPath)) {
    Write-Host "❌ schema.sql not found at $schemaPath" -ForegroundColor Red
    exit 1
}

try {
    if ([string]::IsNullOrWhiteSpace($dbPass)) {
        Get-Content $schemaPath | mysql -u $dbUser 2>&1
    } else {
        Get-Content $schemaPath | mysql -u $dbUser -p"$dbPass" 2>&1
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Database created successfully" -ForegroundColor Green
    } else {
        Write-Host "⚠ Warning: Database creation may have issues" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error creating database: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "👥 Adding sample data..." -ForegroundColor Yellow

$sampleData = @"
USE agri_system;

INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active) 
VALUES ('farmer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'John', 'Farmer', '555-1111', 2, 1);

INSERT INTO farmer_profiles (user_id, farm_name, farm_size, crops_grown, certification)
VALUES (1, 'Green Valley Farm', 50, 'Tomatoes, Carrots, Lettuce', 'Organic');

INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('consumer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Jane', 'Consumer', '555-2222', 3, 1);

INSERT INTO consumer_profiles (user_id, address, postal_code)
VALUES (2, '123 Main St', '12345');

INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('admin@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Admin', 'User', '555-3333', 1, 1);

INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) VALUES
(1, 'Tomato', 'Fresh red tomatoes', 'Vegetables', 3.50, 100, 'lb', 1),
(1, 'Carrot', 'Organic carrots', 'Vegetables', 2.50, 150, 'lb', 1),
(1, 'Lettuce', 'Green lettuce head', 'Vegetables', 1.50, 80, 'head', 1),
(1, 'Cucumber', 'Fresh cucumbers', 'Vegetables', 2.00, 60, 'lb', 1),
(1, 'Bell Pepper', 'Red bell peppers', 'Vegetables', 4.00, 50, 'lb', 1);
"@

$tempFile = New-TemporaryFile
Set-Content -Path $tempFile.FullName -Value $sampleData -Force

try {
    if ([string]::IsNullOrWhiteSpace($dbPass)) {
        Get-Content $tempFile.FullName | mysql -u $dbUser 2>&1
    } else {
        Get-Content $tempFile.FullName | mysql -u $dbUser -p"$dbPass" 2>&1
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Sample data added successfully" -ForegroundColor Green
    } else {
        Write-Host "⚠ Warning: Some sample data may not have been added" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠ Warning: Error adding sample data: $_" -ForegroundColor Yellow
} finally {
    Remove-Item $tempFile.FullName -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "✅ Database setup complete!" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Test Credentials:" -ForegroundColor Green
Write-Host "   Farmer:   farmer@test.com / password123" -ForegroundColor White
Write-Host "   Consumer: consumer@test.com / password123" -ForegroundColor White
Write-Host "   Admin:    admin@test.com / password123" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Access the system at:" -ForegroundColor Green
Write-Host "   http://localhost/agri/auth/login.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Go to http://localhost/agri/auth/login.php" -ForegroundColor White
Write-Host "2. Login with one of the test credentials above" -ForegroundColor White
Write-Host "3. Test the order management system" -ForegroundColor White
Write-Host ""

Read-Host "Press Enter to close"
