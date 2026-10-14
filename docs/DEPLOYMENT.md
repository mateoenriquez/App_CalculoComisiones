# Guía de Despliegue en Producción — Sistema de Comisiones

> **Proveedor verificado:** Alwaysdata (PaaS / Cloud Hosting)  
> **Fecha de verificación:** Octubre 2026  
> **Plan:** Free Tier permanente (100 MB de almacenamiento, sin tarjeta de crédito)

---

## 1. Requisitos del Servidor de Producción

| Requisito | Valor Requerido | Soportado en Alwaysdata |
|---|---|---|
| **PHP** | `>= 8.2` (CakePHP 5.4.3 exige PHP 8.2+) | Sí (8.2, 8.3 y 8.4 seleccionable en panel) |
| **Extensiones PHP** | `pdo_mysql`, `intl`, `mbstring`, `json`, `openssl`, `bcmath` | Sí (nativas en el entorno PHP) |
| **Base de Datos** | MySQL 8.0+ o MariaDB 10.5+ | Sí (MariaDB 10.x / 11.x administrado) |
| **DocumentRoot** | Apuntar a `webroot/` (aislando el código fuente) | Sí (configurable en panel: `Web > Sites`) |
| **Permisos** | Escritura en `tmp/` y `logs/` | Sí (permisos nativos de usuario Unix) |
| **CLI / Herramientas** | SSH, Composer, Git | Sí (preinstalados en el servidor) |
| **SSL / HTTPS** | Certificado válido | Sí (Let's Encrypt automático y gratuito) |

---

## 2. Arquitectura de Seguridad en Producción

### Aislamiento del Directorio Público
El servidor web debe configurar su raíz exclusivamente en el directorio `webroot/`:

```
/home/<usuario>/app/             <-- Raíz del proyecto (PRIVADA)
├── bin/                         (CLI privado)
├── config/                      (Configuración y secretos privados)
├── database/                    (Scripts SQL privados)
├── logs/                        (Logs privados)
├── src/                         (Código fuente privado)
├── templates/                   (Vistas privadas)
├── tmp/                         (Cache y sesiones privadas)
├── vendor/                      (Dependencias privadas)
└── webroot/                     <-- DOCUMENT ROOT (Único directorio público)
    ├── css/
    ├── js/
    ├── favicon.ico
    └── index.php                (Punto de entrada único)
```

**Bajo esta estructura:**
* `config/.env` o `config/app_local.php` **nunca** son accesibles vía web.
* `composer.json` y `composer.lock` quedan fuera del alcance HTTP.
* Archivos `.git`, código fuente y logs quedan protegidos de accesos directos.

---

## 3. Preparación desde Windows

En el entorno de desarrollo se han generado los artefactos listos para producción:

1. **Plantilla de configuración de producción:**  
   `config/app_local.production.php` (contiene la estructura segura con `DEBUG=false` y parámetros para Alwaysdata).
2. **Script de empaquetado reproducible:**  
   `deploy/prepare_release.ps1` (genera un paquete limpio en `dist/app_release.zip` sin `.git`, sin tests y sin credenciales locales).
3. **Esquema y datos demo completos:**  
   `database/production_schema_and_seeds.sql` (contiene el DDL de tablas, claves foráneas, datos demo y los registros de migración/seeder para sincronización con CakePHP).

---

## 4. Guía Paso a Paso para Desplegar en Alwaysdata

### Paso 1: Crear la cuenta gratuita en Alwaysdata
1. Ingresar a [alwaysdata.com](https://www.alwaysdata.com/) y registrar una cuenta gratuita.
2. Seleccionar un nombre de cuenta (ejemplo: `mateo-comisiones`).
3. La cuenta proporcionará un subdominio gratuito: `https://mateo-comisiones.alwaysdata.net`.
4. No se requiere ingresar tarjeta de crédito.

### Paso 2: Crear la Base de Datos MariaDB / MySQL
1. En el panel de Alwaysdata, ir a **Databases > MySQL**.
2. Crear una base de datos nueva con el nombre: `mateo-comisiones_db` (o el prefijo que asigne el panel).
3. En la sección **Users**, crear un usuario dedicado para la aplicación (o asignar permisos al usuario principal) con una contraseña segura.
4. Anotar:
   * **Host:** `mysql-mateo-comisiones.alwaysdata.net` (indicado en el panel).
   * **Base de datos:** `mateo-comisiones_db`.
   * **Usuario:** `mateo-comisiones_user`.
   * **Puerto:** `3306`.

### Paso 3: Configurar el Sitio Web en Alwaysdata
1. En el panel, ir a **Web > Sites**.
2. Editar el sitio por defecto o crear uno nuevo con la dirección: `mateo-comisiones.alwaysdata.net`.
3. Configurar los campos clave:
   * **Type:** `PHP`.
   * **PHP version:** Seleccionar `8.2` u `8.3`.
   * **Application path:** `/home/mateo-comisiones/app` (directorio privado).
   * **Working directory:** `/home/mateo-comisiones/app/webroot` (DocumentRoot público).
   * **SSL:** Marcar **Enable SSL** (Let's Encrypt gratuito automático).
4. Guardar los cambios.

### Paso 4: Subir los Archivos al Servidor

Existen dos métodos directos desde Windows:

#### Opción A: Despliegue mediante Git y SSH (Recomendado)
1. En el panel de Alwaysdata, ir a **SSH > SSH users** y habilitar tu usuario SSH con contraseña o clave pública.
2. Conectar por SSH desde PowerShell:
   ```powershell
   ssh mateo-comisiones@ssh-mateo-comisiones.alwaysdata.net
   ```
3. Clonar el repositorio en `/home/mateo-comisiones/app`:
   ```bash
   git clone https://github.com/mateoenriquez/App_CalculoComisiones.git app
   cd app
   ```
4. Instalar las dependencias de producción:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

#### Opción B: Despliegue mediante el ZIP generado
1. En Windows, ejecutar el script de preparación:
   ```powershell
   .\deploy\prepare_release.ps1
   ```
2. En el panel de Alwaysdata, ir a **Files** (gestor de archivos web) o conectar por SFTP.
3. Subir `dist/app_release.zip` a `/home/mateo-comisiones/` y extraerlo en la carpeta `app/`.
4. Conectar por SSH y ejecutar `composer install --no-dev --optimize-autoloader` dentro de `app/`.

### Paso 5: Configurar Credenciales y Seguridad en el Servidor
1. Dentro de `/home/mateo-comisiones/app/config/`, copiar la plantilla de producción:
   ```bash
   cp app_local.production.php app_local.php
   ```
2. Generar un token de seguridad único para producción:
   ```bash
   php bin/cake.php security generate_token
   ```
3. Editar `config/app_local.php` (usando `nano config/app_local.php` por SSH o mediante el gestor web de archivos) y completar:
   * `Security.salt` => El token de 64 caracteres generado.
   * `Datasources.default.host` => `mysql-mateo-comisiones.alwaysdata.net`
   * `Datasources.default.database` => Nombre de la base creada.
   * `Datasources.default.username` => Usuario de la base.
   * `Datasources.default.password` => Contraseña de la base.
   * `App.fullBaseUrl` => `https://mateo-comisiones.alwaysdata.net`
   * `debug` => `false`.

### Paso 6: Inicializar la Base de Datos y Datos Demo

#### Desde la consola SSH (Método CakePHP CLI):
```bash
cd /home/mateo-comisiones/app
php bin/cake.php migrations migrate
php bin/cake.php seeds run VendedoresSeeder
php bin/cake.php seeds run ReglasSeeder
php bin/cake.php seeds run VentasSeeder
```

#### Alternativa desde phpMyAdmin:
Si no se utiliza la consola, acceder a phpMyAdmin desde el panel de Alwaysdata, seleccionar la base de datos e importar directamente el archivo `database/production_schema_and_seeds.sql`.

### Paso 7: Permisos de Escritura
Verificar que las carpetas de cache y logs tengan permisos correctos:
```bash
chmod -R 775 /home/mateo-comisiones/app/tmp
chmod -R 775 /home/mateo-comisiones/app/logs
```

---

## 5. Protocolo de Pruebas Post-Despliegue

Verificar los siguientes 14 puntos en la URL pública:

| # | Prueba | URL / Acción | Resultado Esperado |
|---|---|---|---|
| 1 | **HTTPS** | `https://tu-cuenta.alwaysdata.net` | Candado verde, certificado SSL válido de Let's Encrypt. |
| 2 | **Redirección Raíz** | `https://tu-cuenta.alwaysdata.net/` | Redirige automáticamente con código HTTP 302 a `/comisiones`. |
| 3 | **Página Resumen** | `https://tu-cuenta.alwaysdata.net/comisiones` | Carga el listado de los 3 vendedores con tarjetas y tabla resumen. |
| 4 | **Cálculos y Demo** | Tabla principal | Ana López: \$800 (0% = \$0); Bruno Mendoza: \$1.500 (5% = \$75); Carla Torres: \$2.500 (10% = \$250). Total: \$4.800 ventas, \$325 comisiones. |
| 5 | **Detalle Vendedor** | Clic en "Ver Detalle" de Carla Torres | Carga `/comisiones/detalle/3` mostrando sus 4 ventas de enero (\$400, \$600, \$700, \$800). |
| 6 | **Filtro con Ventas** | Filtrar del `2026-01-01` al `2026-01-15` | Se recalculan los totales y comisiones correctamente con las ventas de la primera quincena. |
| 7 | **Filtro sin Ventas** | Filtrar del `2026-02-01` al `2026-02-28` | Muestra estado "Sin ventas registradas en el período" con comisiones en \$0.00 sin error PHP. |
| 8 | **Rango Inválido** | Desde `2026-01-31` hasta `2026-01-01` | Muestra mensaje de validación amigable: "La fecha inicial no puede ser posterior a la fecha final". |
| 9 | **Persistencia de Filtro** | Filtrar y entrar al detalle de un vendedor | Los parámetros de fecha se conservan en la URL y en el botón "Volver al resumen". |
| 10 | **Assets y CDN** | Inspeccionar red (F12) | CSS de Bootstrap 5 y estilos locales (`webroot/css`) cargan con estado 200/304. Sin errores 404 de fuentes o favicons. |
| 11 | **Control 404** | `https://tu-cuenta.alwaysdata.net/comisiones/detalle/999` | Muestra página de error 404 limpia y amigable, sin volcar trazas internas. |
| 12 | **Producción Segura** | Provocar una ruta inválida o error | `DEBUG=false` activo; no se exponen rutas locales del servidor, variables de entorno ni contraseñas. |
| 13 | **Persistencia de Datos** | Recargar y acceder desde otro navegador | Los datos se mantienen idénticos sin reiniciarse. |
| 14 | **Aislamiento de Archivos** | Intentar acceder a `/config/app.php`, `/.env`, `/composer.json` | Devuelve 404 / 403; los archivos privados no pueden descargarse vía HTTP. |

---

## 6. Procedimientos Operativos

### Actualizaciones de Código (Sin perder datos)
Para publicar mejoras sin sobrescribir la base de datos ni los secretos:
```bash
ssh mateo-comisiones@ssh-mateo-comisiones.alwaysdata.net
cd app
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/cake.php cache clear_all
```
> **Nota:** `config/app_local.php` está excluido de Git, por lo que tus credenciales de producción no serán alteradas al hacer `git pull`.

### Respaldo de Base de Datos
Desde la consola SSH o phpMyAdmin:
```bash
mysqldump -u mateo-comisiones_user -p -h mysql-mateo-comisiones.alwaysdata.net mateo-comisiones_db > backup_$(date +%Y%m%d).sql
```

### Restauración
```bash
mysql -u mateo-comisiones_user -p -h mysql-mateo-comisiones.alwaysdata.net mateo-comisiones_db < backup_20261013.sql
```

### Políticas de Inactividad y Límites del Proveedor
* **Almacenamiento:** El plan gratuito incluye 100 MB. La aplicación con dependencias de producción ocupa ~8.6 MB (menos del 10%).
* **Política de inactividad:** Para mantener activa la cuenta gratuita, Alwaysdata solicita iniciar sesión en el panel de administración web al menos una vez cada 120 días (4 meses).
