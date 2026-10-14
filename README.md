# Sistema de Cálculo de Comisiones de Ventas

Aplicación web en CakePHP 5 para el cálculo de comisiones de vendedores. Desarrollada como ejercicio universitario MVC en la carrera de Ingeniería en Tecnologías de la Información (UDLA).

---

## Versiones utilizadas

| Componente | Versión |
|---|---|
| PHP | 8.5.11 |
| CakePHP | 5.4.3 |
| cakephp/migrations | 5.2.6 |
| cakephp/bake | ^3.6 |
| PHPUnit | 13.4.x |
| MySQL | 8.0.46 |
| Bootstrap | 5.3 (CDN) |

---

## Requisitos del sistema

- PHP >= 8.2 con extensiones: `bcmath`, `pdo_mysql`, `mbstring`, `intl`, `json`, `xml`
- MySQL 8.0+ o MariaDB 10.5+ accesible en `127.0.0.1:3306`
- Composer (solo para el entorno dev; el directorio `vendor/` ya está generado)
- PowerShell 5.1 o superior (Windows)

---

## 1. Crear la base de datos en MySQL Workbench

1. Abrir **MySQL Workbench** y conectarse al servidor local (root / tu contraseña).
2. Ir a **File > Open SQL Script** y seleccionar `database\create_database.sql`.
3. Ejecutar el script (botón ⚡ o `Ctrl+Shift+Enter`).

Alternativamente, desde PowerShell:

```powershell
mysql -u root -p < "database\create_database.sql"
```

---

## 2. Configuración local (sin revelar secretos)

1. Copia el archivo de ejemplo:

```powershell
Copy-Item "config\.env.example" "config\.env"
```

2. Edita `config\.env` y completa tu contraseña de MySQL:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_comisiones
DB_USERNAME=root
DB_PASSWORD=TU_CONTRASENA_AQUI
DEBUG=true
```

> **NUNCA** subas `config/.env` al repositorio. Está en `.gitignore`.

---

## 3. Migraciones y seeders

Desde la raíz del proyecto en PowerShell:

```powershell
# Opción A: script automático (recomendado)
.\database\setup.ps1

# Opción B: comandos manuales
php bin\cake.php migrations migrate
php bin\cake.php seeds run VendedoresSeeder
php bin\cake.php seeds run ReglasSeeder
php bin\cake.php seeds run VentasSeeder
```

> Los seeders son **idempotentes**: pueden ejecutarse más de una vez sin duplicar datos.

---

## 4. Inicio del servidor de desarrollo

```powershell
php -S localhost:8765 -t webroot/
```

Abrir en el navegador:

- 🏠 **Inicio**: [http://localhost:8765/comisiones](http://localhost:8765/comisiones)
- 🔍 **Detalle**: `http://localhost:8765/comisiones/detalle/{id}`

---

## 5. Ejecución de tests

El proyecto cuenta con una suite completa de pruebas unitarias y de integración que utiliza una base de datos aislada en SQLite (`tmp/tests.sqlite`), garantizando que la base de datos principal (`sistema_comisiones`) no sea modificada ni alterada.

```powershell
# Ejecutar toda la suite de pruebas (59 tests)
php vendor\bin\phpunit --colors=always

# Ejecutar únicamente las pruebas unitarias del servicio
php vendor\bin\phpunit --colors=always tests\TestCase\Service\CalculadoraComisionesServiceTest.php

# Ejecutar únicamente las pruebas de integración del controlador y filtros
php vendor\bin\phpunit --colors=always tests\TestCase\Controller\ComisionesControllerTest.php
```

Resultado esperado: **59 tests, 166 assertions, OK (100%)**.

---

## 6. Filtro de Ventas por Rango de Fechas

La aplicación permite filtrar las ventas acumuladas por un rango temporal en la pantalla principal y en la vista de detalle.

### Funcionamiento y Parámetros
- **`fecha_desde`**: Fecha inicial del rango (`input type="date"`).
- **`fecha_hasta`**: Fecha final del rango (`input type="date"`).
- **Extremos inclusivos**: `fecha_desde <= ventas.fecha <= fecha_hasta`.
- **Rango de un solo día**: Si `fecha_desde == fecha_hasta`, se calculan y muestran las ventas ocurridas exactamente en ese día.
- **Sin filtro**: Si ambos campos están vacíos o no se especifican en la URL, se procesan todas las ventas registradas históricamente.
- **Botón "Limpiar filtro"**: Restablece los campos y regresa a `/comisiones` sin parámetros GET.

### Recálculo de Comisiones
La comisión se calcula sobre el total acumulado en el rango seleccionado:
1. El ORM filtra exclusivamente las ventas asociadas dentro del rango (`contain()`).
2. Se conservan **todos los vendedores** en el resumen, incluso si no tienen ventas en el período.
3. Se selecciona la regla con la meta mínima más alta que cumpla: `total_del_rango >= meta_minima`.
4. El porcentaje de dicha regla se aplica al total completo del rango mediante aritmética de precisión exacta (BCMath).

### Manejo de Ausencia de Ventas
- **Escenario A (Rango activo sin ventas en toda la base)**: Se muestra el aviso visible *"No hay ventas registradas entre [fecha_desde] y [fecha_hasta]"*, totales globales en $0.00 USD, y cada vendedor muestra $0.00 con la indicación *"Sin ventas en este período"*.
- **Escenario B (Algunos vendedores con ventas y otros sin ventas)**: Quienes tienen ventas muestran sus montos y porcentajes calculados; quienes no tienen ventas muestran $0.00 y *"Sin ventas en este período"*. No se emite aviso global si al menos un vendedor tiene ventas.
- **Escenario C (Sin filtro y base vacía)**: Se muestra *"No hay ventas registradas"*.

### Validación en Cliente y Servidor
- **Cliente (JavaScript)**:
  - Al seleccionar `fecha_desde`, se establece automáticamente como atributo `min` en `fecha_hasta`.
  - Se impide el envío si solo se completa una fecha o si `fecha_hasta < fecha_desde`.
- **Servidor (PHP)**:
  - Validación estricta con parsing calendárico (`DateTimeImmutable` y comprobación de errores). Rechaza fechas inexistentes (p. ej. `2026-02-30`), formatos no conformes (`AAAA-MM-DD`) y tipos no escalares.
  - Si el filtro es inválido, no se ejecutan cálculos ni se presentan totales erróneos; se muestra el mensaje de error correspondiente y se mantiene el enlace para limpiar el filtro.

### Conservación del Filtro en el Detalle
Al hacer clic en "Ver detalle" desde un resumen filtrado:
- Los parámetros `fecha_desde` y `fecha_hasta` se conservan en la URL (`/comisiones/detalle/{id}?fecha_desde=...&fecha_hasta=...`).
- El detalle muestra únicamente las ventas del vendedor en dicho rango y recalcula su comisión.
- El botón "Volver al resumen" preserva el filtro aplicado.
- Si el vendedor no tiene ventas en el rango, se muestra *"Este vendedor no tiene ventas en el período seleccionado"* con total y comisión en 0.00 (sin emitir error 404).

---

## 7. Arquitectura MVC y Servicio de Dominio

```
src/
├── Controller/
│   └── ComisionesController.php    <- Controlador delgado
├── Model/
│   ├── Entity/
│   │   ├── Vendedor.php
│   │   ├── Venta.php
│   │   └── Regla.php
│   └── Table/
│       ├── VendedoresTable.php        <- hasMany Ventas
│       ├── VentasTable.php            <- belongsTo Vendedores
│       └── ReglasTable.php
└── Service/
    └── CalculadoraComisionesService.php  <- Lógica de dominio
templates/
├── layout/comisiones.php         <- Layout Bootstrap 5
└── Comisiones/
    ├── index.php                   <- Resumen global
    └── detalle.php                 <- Detalle por vendedor
```

**Flujo**:
1. El navegador hace una petición a `/comisiones`.
2. CakePHP enruta a `ComisionesController::index()`.
3. El controlador llama a `CalculadoraComisionesService::calcularResumen()`.
4. El servicio carga los vendedores con sus ventas en una sola consulta (ORM `contain()`).
5. El servicio consulta las reglas una sola vez y calcula las comisiones.
6. El controlador pasa los resultados a la vista `templates/Comisiones/index.php`.

---

## 8. Regla de comisión (por escalón, no progresiva)

Se elige la regla con **meta_minima más alta** tal que `total_vendido >= meta_minima`.
El porcentaje elegido se aplica al **total completo**.

| Total Vendido | Regla aplicada | Comisión |
|---|---|---|
| < 1000.00 | 0% | 0.00 |
| 1000.00 – 1999.99 | 5% | total × 0.05 |
| ≥ 2000.00 | 10% | total × 0.10 |

> ⚠️ Los umbrales son **inclusivos** (`>=`). Por ejemplo, exactamente $1,000.00 aplica 5%.

---

## 9. Resultados demo esperados

Despues de ejecutar los seeders, la página principal debe mostrar:

| Vendedor | Total Vendido | Meta Mínima | % Comisión | Comisión |
|---|---|---|---|---|
| Carla Torres | \$2,500.00 | \$2,000.00 | 10.00% | \$250.00 |
| Bruno Mendoza | \$1,500.00 | \$1,000.00 | 5.00% | \$75.00 |
| Ana López | \$800.00 | \$0.00 | 0.00% | \$0.00 |

**Totales globales:**
- Total vendido: **\$4,800.00 USD**
- Total comisiones: **\$325.00 USD**
- Vendedores: **3**

---

## 10. Problemas comunes

### Error de conexión a MySQL

```
ERROR 2002 (HY000): No se puede conectar al servidor MySQL
```

- Verifica que MySQL esté corriendo: Servicios de Windows > MySQL80.
- Comprueba `DB_HOST=127.0.0.1` (no `localhost`) en `config/.env`.
- Verifica la contraseña en `config/.env`.

### La página devuelve error 404 al abrir `/`

- Asegúrate de navegar a `http://localhost:8765/comisiones`.
- La raíz `/` redirige automáticamente a `/comisiones`.

### Error `Security.salt` no configurado

- Verifica que `config/app_local.php` existe y tiene el valor `salt`.
- Si lo eliminaste, restaura con: `Copy-Item config\app_local.example.php config\app_local.php`.

### Los seeders fallan por FK

```
ERROR: Vendedor 'Ana López' no encontrado
```

- Ejecuta los seeders en orden: primero `VendedoresSeeder`, luego `ReglasSeeder`, luego `VentasSeeder`.
- El script `database/setup.ps1` garantiza el orden correcto.

---

## 11. Deployment en producción

Ver [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) para instrucciones detalladas.
