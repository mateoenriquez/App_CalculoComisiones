<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Pruebas completas para ComisionesController con soporte de filtros por rango de fechas.
 *
 * Cubre todos los 18 casos obligatorios:
 *  1. Sin parámetros: comportamiento histórico sin filtro.
 *  2. Ambos campos vacíos (?fecha_desde=&fecha_hasta=).
 *  3. Solo fecha inicial.
 *  4. Solo fecha final.
 *  5. Fecha imposible (p. ej. 2026-02-30).
 *  6. Formato incorrecto (p. ej. 05/01/2026).
 *  7. Parámetro enviado como array (fecha_desde[]).
 *  8. Fecha final anterior a la inicial.
 *  9. Ambas fechas iguales (un solo día).
 *  10. Venta exactamente en la fecha inicial: incluida.
 *  11. Venta exactamente en la fecha final: incluida.
 *  12. Venta anterior o posterior al rango: excluida.
 *  13. Rango sin ninguna venta en toda la base.
 *  14. Un vendedor sin ventas y otro con ventas en el período.
 *  15. Comisión recalculada según el total del rango.
 *  16. Totales del resumen coherentes con el detalle.
 *  17. Conservación del filtro al abrir detalle y volver.
 *  18. Vendedor inexistente: respuesta 404.
 */
class ComisionesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures aislados para el entorno de prueba SQLite (no tocan sistema_comisiones).
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Vendedores',
        'app.Reglas',
        'app.Ventas',
    ];

    /**
     * 1. Sin parámetros: muestra comportamiento histórico completo sin filtro.
     */
    public function testIndexSinParametros(): void
    {
        $this->get('/comisiones');

        $this->assertResponseOk();
        $this->assertResponseContains('Sistema de Cálculo de Comisiones');
        $this->assertResponseContains('Período aplicado: Todas las fechas');
        // Carla: 2500.00, Bruno: 1500.00, Ana: 800.00
        $this->assertResponseContains('4,800.00');
        $this->assertResponseContains('325.00');
        $this->assertResponseContains('Carla Torres');
        $this->assertResponseContains('Bruno Mendoza');
        $this->assertResponseContains('Ana López');
    }

    /**
     * 2. Ambos campos vacíos (?fecha_desde=&fecha_hasta=): trata como ausencia de filtro.
     */
    public function testIndexAmbosCamposVacios(): void
    {
        $this->get('/comisiones?fecha_desde=&fecha_hasta=');

        $this->assertResponseOk();
        $this->assertResponseContains('Período aplicado: Todas las fechas');
        $this->assertResponseContains('4,800.00');
        $this->assertResponseNotContains('Error de filtro');
    }

    /**
     * 3. Solo fecha inicial: error "Debes seleccionar una fecha inicial y una fecha final."
     */
    public function testIndexSoloFechaInicial(): void
    {
        $this->get('/comisiones?fecha_desde=2026-01-05&fecha_hasta=');

        $this->assertResponseOk();
        $this->assertResponseContains('Debes seleccionar una fecha inicial y una fecha final.');
        // No debe mostrar totales calculados engañosos
        $this->assertResponseNotContains('4,800.00');
        $this->assertResponseContains('Limpiar filtro');
    }

    /**
     * 4. Solo fecha final: error "Debes seleccionar una fecha inicial y una fecha final."
     */
    public function testIndexSoloFechaFinal(): void
    {
        $this->get('/comisiones?fecha_desde=&fecha_hasta=2026-01-20');

        $this->assertResponseOk();
        $this->assertResponseContains('Debes seleccionar una fecha inicial y una fecha final.');
        $this->assertResponseNotContains('4,800.00');
    }

    /**
     * 5. Fecha imposible (2026-02-30): error "Introduce fechas válidas en formato AAAA-MM-DD."
     */
    public function testIndexFechaImposible(): void
    {
        $this->get('/comisiones?fecha_desde=2026-02-30&fecha_hasta=2026-03-05');

        $this->assertResponseOk();
        $this->assertResponseContains('Introduce fechas válidas en formato AAAA-MM-DD.');
        $this->assertResponseNotContains('4,800.00');
    }

    /**
     * 6. Formato incorrecto (05/01/2026): error "Introduce fechas válidas en formato AAAA-MM-DD."
     */
    public function testIndexFormatoIncorrecto(): void
    {
        $this->get('/comisiones?fecha_desde=05/01/2026&fecha_hasta=2026-01-20');

        $this->assertResponseOk();
        $this->assertResponseContains('Introduce fechas válidas en formato AAAA-MM-DD.');
        $this->assertResponseNotContains('4,800.00');
    }

    /**
     * 7. Parámetro enviado como array: error "Introduce fechas válidas en formato AAAA-MM-DD."
     */
    public function testIndexParametroArray(): void
    {
        $this->get('/comisiones?fecha_desde[]=2026-01-05&fecha_hasta=2026-01-20');

        $this->assertResponseOk();
        $this->assertResponseContains('Introduce fechas válidas en formato AAAA-MM-DD.');
        $this->assertResponseNotContains('4,800.00');
    }

    /**
     * 8. Fecha final anterior a la inicial: error "La fecha final no puede ser anterior a la fecha inicial."
     */
    public function testIndexFechaFinalAnterior(): void
    {
        $this->get('/comisiones?fecha_desde=2026-01-20&fecha_hasta=2026-01-10');

        $this->assertResponseOk();
        $this->assertResponseContains('La fecha final no puede ser anterior a la fecha inicial.');
        $this->assertResponseNotContains('4,800.00');
    }

    /**
     * 9. Ambas fechas iguales (2026-01-05 al 2026-01-05): rango válido de un solo día.
     */
    public function testIndexAmbasFechasIguales(): void
    {
        $this->get('/comisiones?fecha_desde=2026-01-05&fecha_hasta=2026-01-05');

        $this->assertResponseOk();
        $this->assertResponseContains('Período aplicado: 2026-01-05 al 2026-01-05');
        // El 2026-01-05 Ana tiene una venta de 100.00 USD
        $this->assertResponseContains('100.00');
    }

    /**
     * 10. Venta exactamente en la fecha inicial: debe ser incluida (inclusivo >=).
     */
    public function testVentaEnFechaInicialIncluida(): void
    {
        // Ana tiene venta el 2026-01-05 por 100.00
        $this->get('/comisiones?fecha_desde=2026-01-05&fecha_hasta=2026-01-06');

        $this->assertResponseOk();
        // Venta de Ana el 2026-01-05 ($100) y venta de Bruno el 2026-01-06 ($250) incluidas
        $this->assertResponseContains('350.00'); // total global vendido
    }

    /**
     * 11. Venta exactamente en la fecha final: debe ser incluida (inclusivo <=).
     */
    public function testVentaEnFechaFinalIncluida(): void
    {
        // Bruno tiene venta el 2026-01-06 por 250.00
        $this->get('/comisiones?fecha_desde=2026-01-04&fecha_hasta=2026-01-05');

        $this->assertResponseOk();
        // Venta del 2026-01-05 incluida ($100), Bruno del 2026-01-06 no incluida
        $this->assertResponseContains('100.00');
    }

    /**
     * 12. Venta anterior o posterior al rango: debe ser excluida.
     */
    public function testVentasFueraDeRangoExcluidas(): void
    {
        // Rango 2026-01-10 al 2026-01-15:
        // Ana: 2026-01-05 (100) anterior -> excluida. 2026-01-12 (150) -> incluida. 2026-01-19, 2026-01-26 posteriores -> excluidas.
        // Bruno: 2026-01-13 (350) -> incluida.
        // Carla: 2026-01-14 (600) -> incluida.
        // Total global esperado: 150 + 350 + 600 = 1100.00 USD
        $this->get('/comisiones?fecha_desde=2026-01-10&fecha_hasta=2026-01-15');

        $this->assertResponseOk();
        $this->assertResponseContains('1,100.00');
    }

    /**
     * 13. Rango sin ninguna venta: aviso visible, totales 0.00, todos los vendedores presentes.
     */
    public function testRangoSinNingunaVenta(): void
    {
        $this->get('/comisiones?fecha_desde=2025-01-01&fecha_hasta=2025-01-02');

        $this->assertResponseOk();
        $this->assertResponseContains('No hay ventas registradas entre 2025-01-01 y 2025-01-02');
        $this->assertResponseContains('$0.00 USD');
        // Todos los 3 vendedores conservados en la tabla
        $this->assertResponseContains('Ana López');
        $this->assertResponseContains('Bruno Mendoza');
        $this->assertResponseContains('Carla Torres');
        $this->assertResponseContains('Sin ventas en este período');
    }

    /**
     * 14. Un vendedor sin ventas y otro con ventas: muestra resultados de quienes tienen ventas y 0.00 para quienes no.
     */
    public function testUnVendedorConVentasYOtroSinVentas(): void
    {
        // 2026-01-05 al 2026-01-05: Solo Ana tiene ventas (100.00). Bruno y Carla no.
        $this->get('/comisiones?fecha_desde=2026-01-05&fecha_hasta=2026-01-05');

        $this->assertResponseOk();
        // NO debe mostrar aviso global de "No hay ventas" porque Ana sí tiene
        $this->assertResponseNotContains('No hay ventas registradas entre');
        // Ana tiene ventas
        $this->assertResponseContains('Ana López');
        $this->assertResponseContains('100.00');
        // Bruno y Carla marcados como sin ventas
        $this->assertResponseContains('Sin ventas en este período');
    }

    /**
     * 15. Comisión recalculada según el total del rango (no histórico).
     */
     public function testComisionRecalculadaSegunTotalDelRango(): void
     {
         // Rango 2026-01-01 al 2026-01-15:
         // Carla vende 400 (01-07) + 600 (01-14) = 1000.00 USD.
         // Históricamente Carla vendió 2500 (10% = 250.00).
         // En este período vendió 1000.00 -> meta 1000.00 -> porcentaje 5.00% -> comisión 50.00 USD.
         $this->get('/comisiones/detalle/3?fecha_desde=2026-01-01&fecha_hasta=2026-01-15');

         $this->assertResponseOk();
         // En el detalle de Carla para este rango, el total vendido es 1,000.00 y la comisión es 50.00 USD
         $this->assertResponseContains('1,000.00');
         $this->assertResponseContains('50.00');
         // No debe tener su comisión histórica de 250.00 USD
         $this->assertResponseNotContains('250.00');
     }

    /**
     * 16. Totales del resumen coherentes con el detalle.
     */
    public function testTotalesResumenCoherentesConDetalle(): void
    {
        $desde = '2026-01-01';
        $hasta = '2026-01-15';

        // 1. Resumen
        $this->get("/comisiones?fecha_desde={$desde}&fecha_hasta={$hasta}");
        $this->assertResponseOk();
        // Carla tiene 1,000.00 de ventas y 50.00 de comisión en el período
        $this->assertResponseContains('1,000.00');
        $this->assertResponseContains('50.00');

        // 2. Detalle de Carla (ID = 3) con el mismo filtro
        $this->get("/comisiones/detalle/3?fecha_desde={$desde}&fecha_hasta={$hasta}");
        $this->assertResponseOk();
        $this->assertResponseContains('1,000.00');
        $this->assertResponseContains('50.00');
        $this->assertResponseContains('Período aplicado: 2026-01-01 al 2026-01-15');
    }

    /**
     * 17. Conservación del filtro al abrir detalle y volver.
     */
    public function testConservacionDelFiltroAlAbrirDetalleYVolver(): void
    {
        $desde = '2026-01-05';
        $hasta = '2026-01-20';

        // 1. Resumen con filtro: el enlace a detalle debe contener los query params
        $this->get("/comisiones?fecha_desde={$desde}&fecha_hasta={$hasta}");
        $this->assertResponseOk();
        $this->assertResponseContains("fecha_desde={$desde}");
        $this->assertResponseContains("fecha_hasta={$hasta}");

        // 2. Detalle: el botón "Volver al resumen" debe conservar los query params
        $this->get("/comisiones/detalle/1?fecha_desde={$desde}&fecha_hasta={$hasta}");
        $this->assertResponseOk();
        $this->assertResponseContains("fecha_desde={$desde}");
        $this->assertResponseContains("fecha_hasta={$hasta}");
        $this->assertResponseContains('Volver al resumen');
    }

    /**
     * 18. Vendedor inexistente: debe responder con 404 (incluso con parámetros de filtro).
     */
    public function testVendedorInexistenteDevuelve404(): void
    {
        $this->get('/comisiones/detalle/999');
        $this->assertResponseCode(404);

        // También con parámetros de filtro
        $this->get('/comisiones/detalle/999?fecha_desde=2026-01-01&fecha_hasta=2026-01-15');
        $this->assertResponseCode(404);
    }

    /**
     * Vendedor existente sin ventas en el rango: 200 OK, mensaje amigable, no 404.
     */
    public function testDetalleVendedorSinVentasEnRangoNoEs404(): void
    {
        $this->get('/comisiones/detalle/1?fecha_desde=2025-01-01&fecha_hasta=2025-01-02');
        $this->assertResponseOk();
        $this->assertResponseContains('Este vendedor no tiene ventas en el período seleccionado.');
        $this->assertResponseContains('$0.00');
    }

    /**
     * Detalle con filtro inválido: muestra error de validación sin cálculos erróneos.
     */
    public function testDetalleConFiltroInvalidoMuestraError(): void
    {
        $this->get('/comisiones/detalle/1?fecha_desde=2026-01-20&fecha_hasta=2026-01-10');
        $this->assertResponseOk();
        $this->assertResponseContains('La fecha final no puede ser anterior a la fecha inicial.');
    }
}
