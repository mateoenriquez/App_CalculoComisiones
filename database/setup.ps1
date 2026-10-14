#Requires -Version 5.1
<#
.SYNOPSIS
    Script de preparación del Sistema de Comisiones de Ventas.
    Verifica requisitos, ejecuta migraciones y seeders.

.DESCRIPTION
    Este script realiza las siguientes acciones:
      1. Verifica que PHP >= 8.2 esté disponible.
      2. Verifica que el archivo config/.env exista (con DB_PASSWORD configurado).
      3. Ejecuta las migraciones de CakePHP Migrations.
      4. Ejecuta los seeders en el orden correcto (vendedores, reglas, ventas).

    IMPORTANTE:
      - No borra ni reinicializa la base de datos automáticamente.
      - No guarda contraseñas en este script.
      - No se ejecuta al cargar la web; es un script de despliegue manual.

.EXAMPLE
    .\database\setup.ps1
    .\database\setup.ps1 -SoloMigraciones
#>

[CmdletBinding()]
param(
    [switch]$SoloMigraciones,
    [switch]$SoloSeeders
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Ruta del proyecto (directorio padre de este script)
$ProjectRoot = Split-Path -Parent $PSScriptRoot

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Sistema de Comisiones - Setup del entorno" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ─── 1. Verificar PHP ────────────────────────────────────────────────────────
Write-Host "[1/4] Verificando PHP..." -ForegroundColor Yellow
try {
    $phpVersion = & php --version 2>&1 | Select-Object -First 1
    Write-Host "      $phpVersion" -ForegroundColor Green
} catch {
    Write-Error "PHP no está disponible en el PATH. Instala PHP >= 8.2 y agrégalo al PATH del sistema."
    exit 1
}

# ─── 2. Verificar que existe config/.env ─────────────────────────────────────
Write-Host "[2/4] Verificando config/.env..." -ForegroundColor Yellow
$envFile = Join-Path $ProjectRoot "config\.env"
if (-not (Test-Path $envFile)) {
    Write-Host ""
    Write-Host "  ⚠ El archivo config/.env no existe." -ForegroundColor Red
    Write-Host "  Crea config/.env copiando config/.env.example:" -ForegroundColor White
    Write-Host "      Copy-Item 'config\.env.example' 'config\.env'" -ForegroundColor DarkGray
    Write-Host "  Luego completa DB_PASSWORD con tu contraseña de MySQL." -ForegroundColor White
    Write-Host ""
    exit 1
}
Write-Host "      config/.env encontrado." -ForegroundColor Green

# ─── 3. Ejecutar migraciones ─────────────────────────────────────────────────
if (-not $SoloSeeders) {
    Write-Host "[3/4] Ejecutando migraciones..." -ForegroundColor Yellow
    Push-Location $ProjectRoot
    try {
        & php bin\cake.php migrations migrate
        if ($LASTEXITCODE -ne 0) {
            Write-Error "Las migraciones fallaron (código $LASTEXITCODE). Revisa el mensaje de error."
            exit $LASTEXITCODE
        }
        Write-Host "      Migraciones aplicadas correctamente." -ForegroundColor Green
    } finally {
        Pop-Location
    }
} else {
    Write-Host "[3/4] Omitiendo migraciones (-SoloSeeders)." -ForegroundColor DarkGray
}

# ─── 4. Ejecutar seeders ─────────────────────────────────────────────────────
if (-not $SoloMigraciones) {
    Write-Host "[4/4] Ejecutando seeders..." -ForegroundColor Yellow
    Push-Location $ProjectRoot
    try {
        # Orden obligatorio: vendedores antes que ventas (FK)
        $seeders = @('VendedoresSeeder', 'ReglasSeeder', 'VentasSeeder')
        foreach ($seed in $seeders) {
            Write-Host "      Ejecutando $seed..." -ForegroundColor DarkGray
            & php bin\cake.php seeds run $seed
            if ($LASTEXITCODE -ne 0) {
                Write-Error "El seeder $seed falló (código $LASTEXITCODE). Revisa el mensaje de error."
                exit $LASTEXITCODE
            }
        }
        Write-Host "      Todos los seeders ejecutados correctamente." -ForegroundColor Green
    } finally {
        Pop-Location
    }
} else {
    Write-Host "[4/4] Omitiendo seeders (-SoloMigraciones)." -ForegroundColor DarkGray
}

# ─── Resultado ────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  ✅ Setup completado con éxito." -ForegroundColor Green
Write-Host ""
Write-Host "  Iniciar el servidor de desarrollo:" -ForegroundColor White
Write-Host "      php -S localhost:8765 -t webroot/" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  Abrir en el navegador:" -ForegroundColor White
Write-Host "      http://localhost:8765/comisiones" -ForegroundColor DarkGray
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
