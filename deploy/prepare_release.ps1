#Requires -Version 5.1
<#
.SYNOPSIS
    Script de empaquetado y preparación para despliegue en producción (Alwaysdata).
.DESCRIPTION
    Crea un paquete limpio en el directorio 'dist/app_release' y un archivo ZIP 'dist/app_release.zip'
    excluyendo secretos locales, archivos de test, git y logs.
.EXAMPLE
    .\deploy\prepare_release.ps1
    .\deploy\prepare_release.ps1 -ConVendor
#>

[CmdletBinding()]
param(
    [switch]$ConVendor
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$DistDir = Join-Path $ProjectRoot "dist"
$ReleaseDir = Join-Path $DistDir "app_release"
$ZipFile = Join-Path $DistDir "app_release.zip"

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  Preparando paquete de producción para Alwaysdata" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Limpiar directorio dist previo
if (Test-Path $DistDir) {
    Write-Host "[1/5] Limpiando artefactos anteriores en dist/..." -ForegroundColor Yellow
    Remove-Item $DistDir -Recurse -Force
}
New-Item -ItemType Directory -Path $ReleaseDir -Force | Out-Null

# 2. Copiar carpetas y archivos esenciales del core CakePHP
Write-Host "[2/5] Copiando código fuente y configuración limpia..." -ForegroundColor Yellow

$Carpetas = @('bin', 'config', 'database', 'plugins', 'resources', 'src', 'templates', 'webroot')
foreach ($c in $Carpetas) {
    $src = Join-Path $ProjectRoot $c
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination $ReleaseDir -Recurse -Force
    }
}

$Archivos = @('composer.json', 'composer.lock', 'index.php', '.htaccess')
foreach ($f in $Archivos) {
    $src = Join-Path $ProjectRoot $f
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination $ReleaseDir -Force
    }
}

# 3. Eliminar cualquier secreto residual que pudiera haberse copiado en config/
Write-Host "[3/5] Verificando exclusión de secretos locales..." -ForegroundColor Yellow
$Secretos = @(
    (Join-Path (Join-Path $ReleaseDir "config") ".env"),
    (Join-Path (Join-Path $ReleaseDir "config") "app_local.php")
)
foreach ($s in $Secretos) {
    if (Test-Path $s) {
        Remove-Item $s -Force
        Write-Host "      Excluido archivo sensible: $(Split-Path $s -Leaf)" -ForegroundColor DarkGray
    }
}

# 4. Crear estructura requerida para tmp/ y logs/ con permisos limpios
Write-Host "[4/5] Creando directorios requeridos tmp/ y logs/..." -ForegroundColor Yellow
$DirsRequeridos = @(
    "logs",
    "tmp",
    "tmp\cache",
    "tmp\cache\models",
    "tmp\cache\persistent",
    "tmp\sessions"
)
foreach ($d in $DirsRequeridos) {
    $target = Join-Path $ReleaseDir $d
    if (-not (Test-Path $target)) {
        New-Item -ItemType Directory -Path $target -Force | Out-Null
    }
}

# Copiar vendor si se solicita
if ($ConVendor) {
    Write-Host "      Copiando dependencias de producción de vendor/..." -ForegroundColor Yellow
    $vendorSrc = Join-Path $ProjectRoot "vendor"
    if (Test-Path $vendorSrc) {
        Copy-Item -Path $vendorSrc -Destination $ReleaseDir -Recurse -Force
    }
}

# 5. Generar archivo comprimido ZIP
Write-Host "[5/5] Comprimiendo paquete en dist/app_release.zip..." -ForegroundColor Yellow
Compress-Archive -Path "$ReleaseDir\*" -DestinationPath $ZipFile -Force

$zipSizeMb = [math]::Round((Get-Item $ZipFile).Length / 1MB, 2)

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "  ✅ Paquete de producción generado con éxito" -ForegroundColor Green
Write-Host "  Destino: $ZipFile ($zipSizeMb MB)" -ForegroundColor White
Write-Host "==========================================================" -ForegroundColor Green
Write-Host ""
