# Guía de Deployment — Sistema de Comisiones

> Documento generado: octubre 2026

---

## Requisitos del servidor de producción

| Requisito | Valor requerido |
|---|---|
| PHP | >= 8.2 |
| Extensiones PHP | `bcmath`, `pdo_mysql`, `mbstring`, `intl`, `json`, `xml`, `opcache` |
| Base de datos | MySQL 8.0+ o MariaDB 10.5+ |
| DocumentRoot | Apuntar a `webroot/` (no a la raíz del proyecto) |
| Escritura | Directorios `tmp/` y `logs/` con permisos de escritura |

---

## Opciones de hosting verificadas (octubre 2026)

### 1. Servidor VPS / servidor propio (recomendado)

Cualquier servidor con PHP >= 8.2 + MySQL es compatible. Ejemplos populares:
- **DigitalOcean Droplet** (desde ~\$6 USD/mes)
- **Hostinger VPS** (desde ~\$5 USD/mes)
- **servidor universitario** si la UDLA provee acceso SSH

**Ventajas**: control total, SSH, Composer, DocumentRoot configurable.

### 2. Hosting compartido con PHP 8.2+

Muchos hostings compartidos soportan PHP 8.2. Verificar antes de contratar:
- Que tenga acceso SSH o panel de archivo para subir `vendor/`.
- Que permita cambiar el DocumentRoot a `webroot/`.
- Que tenga MySQL accesible.

Ejemplos a verificar: Hostinger, SiteGround, A2Hosting.

> **Advertencia**: No afirmar que ningún hosting es gratuito sin comprobarlo en su sitio oficial en la fecha actual. Las condiciones cambian con frecuencia.

### 3. Alternativa local: XAMPP / Laragon

Para demostración universitaria sin servidor remoto:
- [Laragon](https://laragon.org) incluye PHP 8.x, MySQL y Apache preconfigurados.
- Configurar el `DocumentRoot` del virtual host a `webroot/`.

---

## Procedimiento genérico de deployment

### Paso 1: Subir archivos

No incluir en el servidor:
- `config/.env` (contiene secretos)
- `vendor/` si no hay Composer en el servidor (subir por separado o generar localmente)

Subir al servidor:
```
config/
templates/
src/
webroot/
tests/
database/
plugins/
resources/
bin/
composer.json
composer.lock
index.php
.htaccess
```

### Paso 2: Instalar dependencias en el servidor

Si el servidor tiene Composer:
```bash
composer install --no-dev --optimize-autoloader
```

Si no tiene Composer, generar localmente y subir `vendor/`:
```powershell
composer install --no-dev --optimize-autoloader
# Luego subir la carpeta vendor/ por FTP/SFTP
```

### Paso 3: Crear la base de datos de producción

```sql
CREATE DATABASE sistema_comisiones CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'comisiones_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT SELECT, INSERT, UPDATE, DELETE ON sistema_comisiones.* TO 'comisiones_user'@'localhost';
```

> Usar un usuario específico, no root.

### Paso 4: Configurar variables de entorno en el servidor

Crear `config/.env` en el servidor (NO subirlo desde el repositorio):

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_comisiones
DB_USERNAME=comisiones_user
DB_PASSWORD=contraseña_segura
DEBUG=false
SECURITY_SALT=nuevo_salt_de_64_caracteres_aleatorios
APP_FULL_BASE_URL=https://tu-dominio.com
```

### Paso 5: Ejecutar migraciones y seeders

Desde el servidor (SSH):
```bash
php bin/cake.php migrations migrate
php bin/cake.php migrations seed --seed VendedoresSeeder
php bin/cake.php migrations seed --seed ReglasSeeder
php bin/cake.php migrations seed --seed VentasSeeder
```

### Paso 6: Permisos

```bash
chmod -R 775 tmp/
chmod -R 775 logs/
```

### Paso 7: Configurar DocumentRoot

En Apache (`httpd.conf` o virtual host):
```apache
<VirtualHost *:80>
    DocumentRoot "/ruta/al/proyecto/webroot"
    ServerName tu-dominio.com
    <Directory "/ruta/al/proyecto/webroot">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

En Nginx:
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /ruta/al/proyecto/webroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Checklist de producción

- [ ] `DEBUG=false` en las variables de entorno.
- [ ] `SECURITY_SALT` único y generado para producción.
- [ ] `APP_FULL_BASE_URL` configurado (requerido por CakePHP 5 en producción).
- [ ] Usuario de base de datos dedicado (no root).
- [ ] `config/.env` no subido al repositorio.
- [ ] `DocumentRoot` apuntando a `webroot/`.
- [ ] Permisos de escritura en `tmp/` y `logs/`.
- [ ] Migraciones ejecutadas manualmente (no automáticamente al cargar la web).
- [ ] Ninguna ruta pública para instalar o sembrar la base de datos.
