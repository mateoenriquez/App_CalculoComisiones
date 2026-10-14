# Sistema de Cálculo de Comisiones de Ventas

Aplicación web en CakePHP 5 para el cálculo de comisiones de vendedores. Desarrollada como ejercicio universitario MVC en la carrera de Ingeniería en Tecnologías de la Información (UDLA).

> 🌐 **Despliegue en Producción**: [https://mateo-enriquez.alwaysdata.net](https://mateo-enriquez.alwaysdata.net)  

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

## 1. Filtro de Ventas por Rango de Fechas

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

## 2. Arquitectura MVC y Servicio de Dominio

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

## 3. Regla de comisión (por escalón, no progresiva)

Se elige la regla con **meta_minima más alta** tal que `total_vendido >= meta_minima`.
El porcentaje elegido se aplica al **total completo**.

| Total Vendido | Regla aplicada | Comisión |
|---|---|---|
| < 1000.00 | 0% | 0.00 |
| 1000.00 – 1999.99 | 5% | total × 0.05 |
| ≥ 2000.00 | 10% | total × 0.10 |

> ⚠️ Los umbrales son **inclusivos** (`>=`). Por ejemplo, exactamente $1,000.00 aplica 5%.

---

## 4. Despliegue en Producción (Alwaysdata)

La aplicación se encuentra desplegada y disponible públicamente en la nube:

* 🌐 **URL Pública Oficial**: [https://mateo-enriquez.alwaysdata.net](https://mateo-enriquez.alwaysdata.net)
* 📊 **Módulo de Comisiones**: [https://mateo-enriquez.alwaysdata.net/comisiones](https://mateo-enriquez.alwaysdata.net/comisiones)
* **Proveedor de Alojamiento**: Alwaysdata Cloud Hosting.
* **Stack en Servidor**: PHP 8.3 (FastCGI) + MariaDB 11.8 (InnoDB, UTF-8 mb4).
* **Seguridad Aplicada**:
  * `DocumentRoot` aislado estrictamente a `webroot/` (código fuente y configuraciones no accesibles vía HTTP).
  * `DEBUG=false` en el entorno remoto.
  * Certificado SSL TLS con redirección HTTPS obligatoria.
  * Base de datos persistente con migraciones y datos demo precargados.

Manual técnico de infraestructura, procedimientos de actualización sin caída y respaldos de base de datos, revisar  guía completa en [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

