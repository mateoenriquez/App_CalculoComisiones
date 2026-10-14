<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Regla;
use App\Service\CalculadoraComisionesService;
use Cake\TestSuite\TestCase;

/**
 * Tests unitarios del CalculadoraComisionesService.
 *
 * Estos tests NO requieren conexión a base de datos.
 * Prueban únicamente la lógica de cálculo (total, regla, comisión, redondeo).
 *
 * Ejecutar con:
 *   vendor\bin\phpunit tests\TestCase\Service\CalculadoraComisionesServiceTest.php
 */
class CalculadoraComisionesServiceTest extends TestCase
{
    private CalculadoraComisionesService $servicio;

    /** @var \App\Model\Entity\Regla[] */
    private array $reglas;

    /**
     * Configura el servicio y las reglas de prueba antes de cada test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instanciamos el servicio sin llamar al constructor (que requiere BD)
        // usando ReflectionClass, ya que los métodos que probamos son puros.
        $reflection = new \ReflectionClass(CalculadoraComisionesService::class);
        /** @var CalculadoraComisionesService $servicio */
        $servicio = $reflection->newInstanceWithoutConstructor();
        $this->servicio = $servicio;

        // Reglas demo: meta 0%=0, 5%=1000, 10%=2000 — ordenadas DESC por meta_minima
        $regla0  = new Regla(['meta_minima' => '0.00',    'porcentaje_comision' => '0.00']);
        $regla5  = new Regla(['meta_minima' => '1000.00', 'porcentaje_comision' => '5.00']);
        $regla10 = new Regla(['meta_minima' => '2000.00', 'porcentaje_comision' => '10.00']);

        // Orden DESC: la primera con total >= meta es la correcta
        $this->reglas = [$regla10, $regla5, $regla0];
    }


    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->servicio, $this->reglas);
    }

    // -------------------------------------------------------------------------
    // elegirRegla: selección por escalón con condición >=
    // -------------------------------------------------------------------------

    /** Total 0.00 → aplica regla del 0% (meta_minima 0.00) */
    public function testElegirReglaTotal0(): void
    {
        $regla = $this->servicio->elegirRegla('0.00', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('0.00', bcadd((string)$regla->meta_minima, '0', 2));
        $this->assertSame('0.00', bcadd((string)$regla->porcentaje_comision, '0', 2));
    }

    /** Total 999.99 → aplica regla del 0% (no alcanza 1000) */
    public function testElegirReglaTotalMenorQue1000(): void
    {
        $regla = $this->servicio->elegirRegla('999.99', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('0.00', bcadd((string)$regla->porcentaje_comision, '0', 2));
    }

    /** Total 1000.00 → aplica exactamente el 5% (umbral exacto) */
    public function testElegirReglaTotal1000(): void
    {
        $regla = $this->servicio->elegirRegla('1000.00', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('5.00', bcadd((string)$regla->porcentaje_comision, '0', 2));
    }

    /** Total 1999.99 → aplica 5% (entre 1000 y 2000) */
    public function testElegirReglaTotalEntre1000Y2000(): void
    {
        $regla = $this->servicio->elegirRegla('1999.99', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('5.00', bcadd((string)$regla->porcentaje_comision, '0', 2));
    }

    /** Total 2000.00 → aplica exactamente 10% (umbral exacto) */
    public function testElegirReglaTotal2000(): void
    {
        $regla = $this->servicio->elegirRegla('2000.00', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('10.00', bcadd((string)$regla->porcentaje_comision, '0', 2));
    }

    /** Total 2500.00 → aplica 10% (supera 2000) */
    public function testElegirReglaTotal2500(): void
    {
        $regla = $this->servicio->elegirRegla('2500.00', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('10.00', bcadd((string)$regla->porcentaje_comision, '0', 2));
    }

    /** Sin reglas en la tabla → devuelve null */
    public function testElegirReglaSinReglas(): void
    {
        $regla = $this->servicio->elegirRegla('1500.00', []);
        $this->assertNull($regla);
    }

    /** Selección de la meta más alta: con meta 1000 y 2000 y total 1500 → elige 1000 (no 2000) */
    public function testElegirReglaMasAltaAplicable(): void
    {
        // Con total 1500 y reglas 0%=0, 5%=1000, 10%=2000 → debe elegir 1000 (5%)
        $regla = $this->servicio->elegirRegla('1500.00', $this->reglas);
        $this->assertNotNull($regla);
        $this->assertSame('1000.00', bcadd((string)$regla->meta_minima, '0', 2));
    }

    // -------------------------------------------------------------------------
    // calcularComision: verificación de fórmula con BCMath
    // -------------------------------------------------------------------------

    /** Comisión Ana: 800 * 0% = 0.00 */
    public function testComisionAnaLopez(): void
    {
        $regla = $this->servicio->elegirRegla('800.00', $this->reglas);
        $comision = $this->servicio->calcularComision('800.00', $regla);
        $this->assertSame('0.00', $comision);
    }

    /** Comisión Bruno: 1500 * 5% = 75.00 */
    public function testComisionBrunoMendoza(): void
    {
        $regla = $this->servicio->elegirRegla('1500.00', $this->reglas);
        $comision = $this->servicio->calcularComision('1500.00', $regla);
        $this->assertSame('75.00', $comision);
    }

    /** Comisión Carla: 2500 * 10% = 250.00 */
    public function testComisionCarlaTorres(): void
    {
        $regla = $this->servicio->elegirRegla('2500.00', $this->reglas);
        $comision = $this->servicio->calcularComision('2500.00', $regla);
        $this->assertSame('250.00', $comision);
    }

    /** Sin regla aplicable → comisión 0.00 */
    public function testComisionSinRegla(): void
    {
        $comision = $this->servicio->calcularComision('1500.00', null);
        $this->assertSame('0.00', $comision);
    }

    // -------------------------------------------------------------------------
    // roundHalfUp: precisión monetaria
    // -------------------------------------------------------------------------

    /** 0.005 → 0.01 (medio centavo hacia arriba) */
    public function testRoundHalfUpMedioCentavo(): void
    {
        $resultado = $this->servicio->roundHalfUp('0.005', 2);
        $this->assertSame('0.01', $resultado);
    }

    /** 0.004 → 0.00 (menos de medio centavo, truncar) */
    public function testRoundHalfUpMenosDeMedioCentavo(): void
    {
        $resultado = $this->servicio->roundHalfUp('0.004', 2);
        $this->assertSame('0.00', $resultado);
    }

    /** 75.005 → 75.01 */
    public function testRoundHalfUpCentavoExacto(): void
    {
        $resultado = $this->servicio->roundHalfUp('75.005', 2);
        $this->assertSame('75.01', $resultado);
    }

    /** 250.00 → sin cambio */
    public function testRoundHalfUpSinCambio(): void
    {
        $resultado = $this->servicio->roundHalfUp('250.00', 2);
        $this->assertSame('250.00', $resultado);
    }

    // -------------------------------------------------------------------------
    // calcularTotalVentas: con entidades mock
    // -------------------------------------------------------------------------

    /** Array vacío → total 0.00 (vendedor sin ventas) */
    public function testTotalVentasVacioEsCero(): void
    {
        $total = $this->servicio->calcularTotalVentas([]);
        $this->assertSame('0.00', $total);
    }

    /** Suma de montos Ana: 100 + 150 + 200 + 350 = 800.00 */
    public function testTotalVentasAna(): void
    {
        $montos = ['100.00', '150.00', '200.00', '350.00'];
        $ventas = array_map(function (string $m): object {
            return new class($m) {
                public function __construct(public readonly string $monto) {}
            };
        }, $montos);

        $total = $this->servicio->calcularTotalVentas($ventas);
        $this->assertSame('800.00', $total);
    }

    /** Suma de montos Bruno: 250 + 350 + 400 + 500 = 1500.00 */
    public function testTotalVentasBruno(): void
    {
        $montos = ['250.00', '350.00', '400.00', '500.00'];
        $ventas = array_map(function (string $m): object {
            return new class($m) {
                public function __construct(public readonly string $monto) {}
            };
        }, $montos);

        $total = $this->servicio->calcularTotalVentas($ventas);
        $this->assertSame('1500.00', $total);
    }

    /** Suma de montos Carla: 400 + 600 + 700 + 800 = 2500.00 */
    public function testTotalVentasCarla(): void
    {
        $montos = ['400.00', '600.00', '700.00', '800.00'];
        $ventas = array_map(function (string $m): object {
            return new class($m) {
                public function __construct(public readonly string $monto) {}
            };
        }, $montos);

        $total = $this->servicio->calcularTotalVentas($ventas);
        $this->assertSame('2500.00', $total);
    }

    // -------------------------------------------------------------------------
    // validarFiltroFechas: validación estricta de rangos y fechas
    // -------------------------------------------------------------------------

    /** 1. Sin parámetros (null, null) → Válido, sin filtro */
    public function testValidarFiltroSinParametros(): void
    {
        $res = $this->servicio->validarFiltroFechas(null, null);
        $this->assertTrue($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertNull($res['error']);
        $this->assertNull($res['desde']);
        $this->assertNull($res['hasta']);
    }

    /** 2. Ambos campos vacíos ('', '') → Válido, sin filtro */
    public function testValidarFiltroAmbosCamposVacios(): void
    {
        $res = $this->servicio->validarFiltroFechas('', '');
        $this->assertTrue($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertNull($res['error']);
        $this->assertNull($res['desde']);
        $this->assertNull($res['hasta']);
    }

    /** 3. Solo fecha inicial ('2026-01-05', '') → Error rango incompleto */
    public function testValidarFiltroSoloFechaInicial(): void
    {
        $res = $this->servicio->validarFiltroFechas('2026-01-05', '');
        $this->assertFalse($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertSame('Debes seleccionar una fecha inicial y una fecha final.', $res['error']);
    }

    /** 4. Solo fecha final ('', '2026-01-20') → Error rango incompleto */
    public function testValidarFiltroSoloFechaFinal(): void
    {
        $res = $this->servicio->validarFiltroFechas('', '2026-01-20');
        $this->assertFalse($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertSame('Debes seleccionar una fecha inicial y una fecha final.', $res['error']);
    }

    /** 5. Fecha imposible ('2026-02-30') → Error formato/fecha inválida */
    public function testValidarFiltroFechaImposible(): void
    {
        $res = $this->servicio->validarFiltroFechas('2026-02-30', '2026-03-05');
        $this->assertFalse($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertSame('Introduce fechas válidas en formato AAAA-MM-DD.', $res['error']);
    }

    /** 6. Formato incorrecto ('05/01/2026') → Error formato */
    public function testValidarFiltroFormatoIncorrecto(): void
    {
        $res = $this->servicio->validarFiltroFechas('05/01/2026', '2026-01-20');
        $this->assertFalse($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertSame('Introduce fechas válidas en formato AAAA-MM-DD.', $res['error']);
    }

    /** 7. Parámetro enviado como array → Error formato/tipo */
    public function testValidarFiltroParametroArray(): void
    {
        $res = $this->servicio->validarFiltroFechas(['2026-01-05'], '2026-01-20');
        $this->assertFalse($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertSame('Introduce fechas válidas en formato AAAA-MM-DD.', $res['error']);
    }

    /** 8. Fecha final anterior a la inicial ('2026-01-20' a '2026-01-10') → Error */
    public function testValidarFiltroFechaFinalAnterior(): void
    {
        $res = $this->servicio->validarFiltroFechas('2026-01-20', '2026-01-10');
        $this->assertFalse($res['valido']);
        $this->assertFalse($res['filtrado']);
        $this->assertSame('La fecha final no puede ser anterior a la fecha inicial.', $res['error']);
    }

    /** 9. Ambas fechas iguales ('2026-01-05', '2026-01-05') → Válido, filtrado activo */
    public function testValidarFiltroAmbasFechasIguales(): void
    {
        $res = $this->servicio->validarFiltroFechas('2026-01-05', '2026-01-05');
        $this->assertTrue($res['valido']);
        $this->assertTrue($res['filtrado']);
        $this->assertNull($res['error']);
        $this->assertSame('2026-01-05', $res['desde']);
        $this->assertSame('2026-01-05', $res['hasta']);
    }

    /** Rango válido estándar ('2026-01-05' a '2026-01-20') → Válido, filtrado activo */
    public function testValidarFiltroRangoValido(): void
    {
        $res = $this->servicio->validarFiltroFechas('2026-01-05', '2026-01-20');
        $this->assertTrue($res['valido']);
        $this->assertTrue($res['filtrado']);
        $this->assertNull($res['error']);
        $this->assertSame('2026-01-05', $res['desde']);
        $this->assertSame('2026-01-20', $res['hasta']);
    }
}
