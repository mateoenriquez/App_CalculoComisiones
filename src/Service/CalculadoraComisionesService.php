<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Regla;
use App\Model\Table\ReglasTable;
use App\Model\Table\VendedoresTable;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * CalculadoraComisionesService
 *
 * Servicio de dominio para el cálculo de comisiones de ventas.
 *
 * # Estrategia de precisión monetaria
 *
 * Para evitar errores de punto flotante, todos los cálculos monetarios usan
 * aritmética de cadenas decimales a través de BCMath (extensión verificada como
 * disponible en PHP 8.5.11 con la extensión bcmath).
 *
 * Pasos:
 *  1. Los montos llegan de MySQL como strings decimales (p. ej. "1500.00").
 *  2. La suma de ventas se acumula con bcadd($acumulado, $monto, 2).
 *  3. La comisión se calcula con bcmul($total, $porcentaje, 4) / 100,
 *     usando escala intermedia de 4 decimales para precisión.
 *  4. El resultado final se redondea a 2 decimales aplicando "half-up"
 *     mediante el método roundHalfUp() que usa únicamente operaciones de cadena.
 *
 * Regla de negocio (escalón, no progresiva):
 *  - Se elige la regla con meta_minima más alta tal que total >= meta_minima.
 *  - El porcentaje elegido se aplica al total completo.
 *  - Si ninguna regla aplica, comisión = 0.00.
 */
class CalculadoraComisionesService
{
    use LocatorAwareTrait;

    private VendedoresTable $vendedoresTable;
    private ReglasTable $reglasTable;

    public function __construct()
    {
        /** @var VendedoresTable $vendedoresTable */
        $vendedoresTable = $this->fetchTable('Vendedores');
        $this->vendedoresTable = $vendedoresTable;

        /** @var ReglasTable $reglasTable */
        $reglasTable = $this->fetchTable('Reglas');
        $this->reglasTable = $reglasTable;
    }

    /**
     * Genera el resumen de comisiones para todos los vendedores.
     *
     * Evita el problema N+1 cargando las ventas mediante contain() en una sola consulta.
     * Consulta las reglas una sola vez para toda la operación.
     *
     * @return array<int, array<string, mixed>> Resumen ordenado por total_vendido DESC, nombre ASC.
     *   Cada elemento contiene:
     *   - vendedor_id (int)
     *   - nombre_vendedor (string)
     *   - total_vendido (string, 2 dec)
     *   - porcentaje_comision (string, 2 dec)
     *   - monto_comision (string, 2 dec)
     *   - meta_minima_aplicada (string|null)
     */
    /**
     * Genera el resumen de comisiones para todos los vendedores, opcionalmente
     * filtrando las ventas por un rango de fechas inclusivo.
     *
     * Evita el problema N+1 cargando las ventas mediante contain() en una sola consulta.
     * Si se especifica rango de fechas, se aplica exclusivamente a la consulta de Ventas,
     * garantizando que todos los vendedores se conserven en el resultado.
     * Consulta las reglas una sola vez para toda la operación.
     *
     * @param string|null $fechaDesde Fecha inicial en formato YYYY-MM-DD o null.
     * @param string|null $fechaHasta Fecha final en formato YYYY-MM-DD o null.
     * @return array<int, array<string, mixed>> Resumen ordenado por total_vendido DESC, nombre ASC.
     */
    public function calcularResumen(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $hasFilter = ($fechaDesde !== null && $fechaHasta !== null);

        // Carga vendedores con sus ventas en una sola consulta (sin N+1)
        $query = $this->vendedoresTable
            ->find()
            ->orderBy(['Vendedores.nombre' => 'ASC']);

        if ($hasFilter) {
            $query->contain(['Ventas' => function ($q) use ($fechaDesde, $fechaHasta) {
                return $q
                    ->where([
                        'Ventas.fecha >=' => $fechaDesde,
                        'Ventas.fecha <=' => $fechaHasta,
                    ])
                    ->orderBy(['Ventas.fecha' => 'ASC', 'Ventas.id' => 'ASC']);
            }]);
        } else {
            $query->contain(['Ventas' => function ($q) {
                return $q->orderBy(['Ventas.fecha' => 'ASC', 'Ventas.id' => 'ASC']);
            }]);
        }

        $vendedores = $query->all();

        // Carga todas las reglas una sola vez, ordenadas de mayor a menor meta
        $reglas = $this->reglasTable
            ->find()
            ->orderBy(['meta_minima' => 'DESC'])
            ->all()
            ->toArray();

        $resumen = [];

        foreach ($vendedores as $vendedor) {
            $ventas = $vendedor->ventas ?? [];
            $cantidadVentas = count($ventas);
            $totalVendido = $this->calcularTotalVentas($ventas);
            $reglaAplicada = $this->elegirRegla($totalVendido, $reglas);
            $montoComision = $this->calcularComision($totalVendido, $reglaAplicada);

            $resumen[] = [
                'vendedor_id'          => $vendedor->id,
                'nombre_vendedor'      => $vendedor->nombre,
                'total_vendido'        => $totalVendido,
                'porcentaje_comision'  => $reglaAplicada !== null
                    ? $this->formatDecimal($reglaAplicada->porcentaje_comision, 2)
                    : '0.00',
                'monto_comision'       => $montoComision,
                'meta_minima_aplicada' => $reglaAplicada !== null
                    ? $this->formatDecimal($reglaAplicada->meta_minima, 2)
                    : null,
                'cantidad_ventas'      => $cantidadVentas,
                'sin_ventas'           => ($cantidadVentas === 0),
            ];
        }

        // Ordenar: total_vendido DESC, nombre_vendedor ASC (para empates)
        usort($resumen, function (array $a, array $b): int {
            $cmp = bccomp($b['total_vendido'], $a['total_vendido'], 2);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['nombre_vendedor'], $b['nombre_vendedor']);
        });

        return $resumen;
    }

    /**
     * Calcula la comisión para un único vendedor por su ID, opcionalmente
     * filtrando sus ventas por un rango de fechas inclusivo.
     *
     * Reutiliza la misma lógica de dominio que calcularResumen().
     *
     * @param int $vendedorId ID del vendedor.
     * @param string|null $fechaDesde Fecha inicial en formato YYYY-MM-DD o null.
     * @param string|null $fechaHasta Fecha final en formato YYYY-MM-DD o null.
     * @return array<string, mixed>|null null si el vendedor no existe.
     */
    public function calcularParaVendedor(int $vendedorId, ?string $fechaDesde = null, ?string $fechaHasta = null): ?array
    {
        $hasFilter = ($fechaDesde !== null && $fechaHasta !== null);

        $query = $this->vendedoresTable
            ->find()
            ->where(['Vendedores.id' => $vendedorId]);

        if ($hasFilter) {
            $query->contain(['Ventas' => function ($q) use ($fechaDesde, $fechaHasta) {
                return $q
                    ->where([
                        'Ventas.fecha >=' => $fechaDesde,
                        'Ventas.fecha <=' => $fechaHasta,
                    ])
                    ->orderBy(['Ventas.fecha' => 'ASC', 'Ventas.id' => 'ASC']);
            }]);
        } else {
            $query->contain(['Ventas' => function ($q) {
                return $q->orderBy(['Ventas.fecha' => 'ASC', 'Ventas.id' => 'ASC']);
            }]);
        }

        $vendedor = $query->first();

        if ($vendedor === null) {
            return null;
        }

        $reglas = $this->reglasTable
            ->find()
            ->orderBy(['meta_minima' => 'DESC'])
            ->all()
            ->toArray();

        $ventas = $vendedor->ventas ?? [];
        $cantidadVentas = count($ventas);
        $totalVendido = $this->calcularTotalVentas($ventas);
        $reglaAplicada = $this->elegirRegla($totalVendido, $reglas);
        $montoComision = $this->calcularComision($totalVendido, $reglaAplicada);

        return [
            'vendedor_id'          => $vendedor->id,
            'nombre_vendedor'      => $vendedor->nombre,
            'total_vendido'        => $totalVendido,
            'porcentaje_comision'  => $reglaAplicada !== null
                ? $this->formatDecimal($reglaAplicada->porcentaje_comision, 2)
                : '0.00',
            'monto_comision'       => $montoComision,
            'meta_minima_aplicada' => $reglaAplicada !== null
                ? $this->formatDecimal($reglaAplicada->meta_minima, 2)
                : null,
            'ventas'               => $ventas,
            'cantidad_ventas'      => $cantidadVentas,
            'fecha_desde'          => $fechaDesde,
            'fecha_hasta'          => $fechaHasta,
        ];
    }

    /**
     * Comprueba si un vendedor existe por su ID.
     *
     * @param int $vendedorId ID del vendedor.
     * @return bool
     */
    public function existeVendedor(int $vendedorId): bool
    {
        return $this->vendedoresTable->exists(['id' => $vendedorId]);
    }

    /**
     * Valida de manera estricta los parámetros del filtro de fechas.
     *
     * Reglas:
     *  - Ambos nulos o vacíos: válido, sin filtro (comportamiento histórico).
     *  - Uno solo presente: error "Debes seleccionar una fecha inicial y una fecha final."
     *  - No-strings (p. ej. arrays): error "Introduce fechas válidas en formato AAAA-MM-DD."
     *  - Formato distinto a YYYY-MM-DD o fecha imposible (p. ej. 2026-02-30):
     *    error "Introduce fechas válidas en formato AAAA-MM-DD."
     *  - fecha_hasta < fecha_desde: error "La fecha final no puede ser anterior a la fecha inicial."
     *  - Rango válido (incluyendo fechas iguales): válido, filtrado activo.
     *
     * @param mixed $desde Parámetro fecha_desde.
     * @param mixed $hasta Parámetro fecha_hasta.
     * @return array{valido: bool, error: ?string, desde: ?string, hasta: ?string, filtrado: bool}
     */
    public function validarFiltroFechas(mixed $desde, mixed $hasta): array
    {
        $desdeVacio = ($desde === null || $desde === '');
        $hastaVacio = ($hasta === null || $hasta === '');

        // 1. Ambos ausentes o vacíos -> Sin filtro (histórico)
        if ($desdeVacio && $hastaVacio) {
            return [
                'valido'   => true,
                'error'    => null,
                'desde'    => null,
                'hasta'    => null,
                'filtrado' => false,
            ];
        }

        // 2. Solo uno presente -> Rango incompleto
        if ($desdeVacio || $hastaVacio) {
            return [
                'valido'   => false,
                'error'    => 'Debes seleccionar una fecha inicial y una fecha final.',
                'desde'    => is_string($desde) ? $desde : null,
                'hasta'    => is_string($hasta) ? $hasta : null,
                'filtrado' => false,
            ];
        }

        // 3. Debe ser string escalar (rechazar arrays, objetos, números)
        if (!is_string($desde) || !is_string($hasta)) {
            return [
                'valido'   => false,
                'error'    => 'Introduce fechas válidas en formato AAAA-MM-DD.',
                'desde'    => null,
                'hasta'    => null,
                'filtrado' => false,
            ];
        }

        // 4. Formato estricto YYYY-MM-DD y fecha calendáricamente válida
        if (!$this->esFechaCalendarioValida($desde) || !$this->esFechaCalendarioValida($hasta)) {
            return [
                'valido'   => false,
                'error'    => 'Introduce fechas válidas en formato AAAA-MM-DD.',
                'desde'    => $desde,
                'hasta'    => $hasta,
                'filtrado' => false,
            ];
        }

        // 5. fecha_hasta no puede ser anterior a fecha_desde
        if ($hasta < $desde) {
            return [
                'valido'   => false,
                'error'    => 'La fecha final no puede ser anterior a la fecha inicial.',
                'desde'    => $desde,
                'hasta'    => $hasta,
                'filtrado' => false,
            ];
        }

        return [
            'valido'   => true,
            'error'    => null,
            'desde'    => $desde,
            'hasta'    => $hasta,
            'filtrado' => true,
        ];
    }

    /**
     * Valida estrictamente que una cadena represente una fecha en formato YYYY-MM-DD
     * calendáricamente válida, sin conversiones silenciosas.
     *
     * @param string $fecha Cadena de fecha a evaluar.
     * @return bool
     */
    public function esFechaCalendarioValida(string $fecha): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($dt === false) {
            return false;
        }

        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        return $dt->format('Y-m-d') === $fecha;
    }

    /**
     * Suma los montos de un array de ventas usando BCMath (precisión exacta).
     *
     * @param \App\Model\Entity\Venta[] $ventas Array de entidades Venta.
     * @return string Total con 2 decimales, p. ej. "1500.00".
     */
    public function calcularTotalVentas(array $ventas): string
    {
        $total = '0.00';
        foreach ($ventas as $venta) {
            // El monto viene de MySQL como string decimal; no pasa por float
            $monto = (string)$venta->monto;
            if (!is_numeric($monto) || bccomp($monto, '0', 2) < 0) {
                continue; // seguridad: omitir valores no válidos
            }
            $total = bcadd($total, $monto, 2);
        }

        return $total;
    }

    /**
     * Elige la regla de comisión aplicable.
     *
     * Regla de negocio (escalón, no progresiva):
     *   Se elige la regla con meta_minima MÁS ALTA tal que total >= meta_minima.
     *   Las reglas llegan ordenadas DESC por meta_minima, por lo que la primera
     *   que cumple la condición es la correcta.
     *
     * Ejemplos:
     *   999.99  -> 0%   (solo cumple meta_minima=0)
     *   1000.00 -> 5%   (cumple metas 0 y 1000; se elige 1000)
     *   1999.99 -> 5%
     *   2000.00 -> 10%
     *
     * @param string $totalVendido Total vendido como string decimal.
     * @param \App\Model\Entity\Regla[] $reglas Reglas ordenadas DESC por meta_minima.
     * @return \App\Model\Entity\Regla|null Regla aplicable, o null si ninguna aplica.
     */
    public function elegirRegla(string $totalVendido, array $reglas): ?Regla
    {
        foreach ($reglas as $regla) {
            $meta = (string)$regla->meta_minima;
            // bccomp devuelve 0 si igual, 1 si total > meta, -1 si total < meta
            if (bccomp($totalVendido, $meta, 2) >= 0) {
                return $regla;
            }
        }

        return null;
    }

    /**
     * Calcula el monto de comisión aplicando el porcentaje de la regla al total.
     *
     * Fórmula: comision = total_vendido * porcentaje_comision / 100
     * Escala intermedia: 4 decimales para evitar pérdidas.
     * Redondeo final: half-up a 2 decimales.
     *
     * @param string $totalVendido Total vendido como string decimal.
     * @param \App\Model\Entity\Regla|null $regla Regla aplicable (null = 0%).
     * @return string Monto de comisión con 2 decimales.
     */
    public function calcularComision(string $totalVendido, ?Regla $regla): string
    {
        if ($regla === null) {
            return '0.00';
        }

        $porcentaje = (string)$regla->porcentaje_comision;
        // Multiplicar con escala intermedia de 4 para no perder decimales
        $comisionBruta = bcmul($totalVendido, $porcentaje, 4);
        // Dividir por 100 con escala 4
        $comisionConEscala = bcdiv($comisionBruta, '100', 4);
        // Redondear half-up a 2 decimales
        return $this->roundHalfUp($comisionConEscala, 2);
    }

    /**
     * Redondeo half-up a N decimales usando únicamente operaciones de cadena.
     *
     * No convierte a float en ningún momento.
     * "Half-up": 0.005 -> 0.01, 0.004 -> 0.00.
     *
     * @param string $value Valor decimal como string.
     * @param int $scale Número de decimales destino.
     * @return string Valor redondeado con $scale decimales.
     */
    public function roundHalfUp(string $value, int $scale): string
    {
        // Añadir 0.5 * 10^(-scale) y luego truncar con bcadd/bcdiv
        $pivot = bcdiv('5', bcpow('10', (string)($scale + 1), $scale + 2), $scale + 2);

        // Si el número es negativo, restar el pivot antes de truncar
        if (str_starts_with(ltrim($value), '-')) {
            $adjusted = bcsub($value, $pivot, $scale + 2);
        } else {
            $adjusted = bcadd($value, $pivot, $scale + 2);
        }

        // Truncar a $scale decimales
        return bcadd($adjusted, '0', $scale);
    }

    /**
     * Formatea un valor decimal string a N decimales exactos.
     *
     * @param string|int|float $value Valor de entrada.
     * @param int $scale Decimales destino.
     * @return string Valor formateado.
     */
    private function formatDecimal(mixed $value, int $scale): string
    {
        return bcadd((string)$value, '0', $scale);
    }
}
