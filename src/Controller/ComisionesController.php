<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalculadoraComisionesService;
use Cake\Http\Exception\NotFoundException;

/**
 * ComisionesController
 *
 * Controlador delgado: delega toda la lógica al CalculadoraComisionesService.
 * Expone dos acciones: index (resumen global) y detalle (vendedor individual),
 * con soporte para filtrado por rango de fechas inclusivo.
 */
class ComisionesController extends AppController
{
    private CalculadoraComisionesService $servicio;

    /**
     * Inicialización del controlador.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->servicio = new CalculadoraComisionesService();
    }

    /**
     * Muestra el resumen de comisiones de todos los vendedores.
     * Soporta filtro opcional por rango de fechas (fecha_desde y fecha_hasta).
     *
     * @return void
     */
    public function index(): void
    {
        $fechaDesdeRaw = $this->request->getQuery('fecha_desde');
        $fechaHastaRaw = $this->request->getQuery('fecha_hasta');

        $validacion = $this->servicio->validarFiltroFechas($fechaDesdeRaw, $fechaHastaRaw);

        if (!$validacion['valido']) {
            // Error de validación: no ejecutar el cálculo con rango inválido ni mostrar totales falsos
            $this->set([
                'resumen'                => null,
                'totalGlobalVendido'     => null,
                'totalGlobalComisiones'  => null,
                'totalGlobalVentasCount' => 0,
                'cantidadVendedores'     => 0,
                'fechaDesde'             => is_string($fechaDesdeRaw) ? $fechaDesdeRaw : '',
                'fechaHasta'             => is_string($fechaHastaRaw) ? $fechaHastaRaw : '',
                'filtroActivo'           => false,
                'errorFiltro'            => $validacion['error'],
                'titulo'                 => 'Sistema de Cálculo de Comisiones',
            ]);

            return;
        }

        $resumen = $this->servicio->calcularResumen($validacion['desde'], $validacion['hasta']);

        // Totales globales calculados con BCMath (sin float)
        $totalGlobalVendido     = '0.00';
        $totalGlobalComisiones  = '0.00';
        $totalGlobalVentasCount = 0;

        foreach ($resumen as $fila) {
            $totalGlobalVendido     = bcadd($totalGlobalVendido, $fila['total_vendido'], 2);
            $totalGlobalComisiones  = bcadd($totalGlobalComisiones, $fila['monto_comision'], 2);
            $totalGlobalVentasCount += $fila['cantidad_ventas'];
        }

        $this->set([
            'resumen'                => $resumen,
            'totalGlobalVendido'     => $totalGlobalVendido,
            'totalGlobalComisiones'  => $totalGlobalComisiones,
            'totalGlobalVentasCount' => $totalGlobalVentasCount,
            'cantidadVendedores'     => count($resumen),
            'fechaDesde'             => $validacion['desde'] ?? '',
            'fechaHasta'             => $validacion['hasta'] ?? '',
            'filtroActivo'           => $validacion['filtrado'],
            'errorFiltro'            => null,
            'titulo'                 => 'Sistema de Cálculo de Comisiones',
        ]);
    }

    /**
     * Muestra el detalle de un vendedor y sus ventas.
     * Soporta filtro opcional por rango de fechas (fecha_desde y fecha_hasta).
     *
     * @param int|string|null $id ID del vendedor.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException Si el vendedor no existe o el ID no es válido.
     */
    public function detalle(int|string|null $id = null): void
    {
        // Validar que el ID sea un entero positivo
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw new NotFoundException('Vendedor no encontrado.');
        }

        $vendedorId = (int)$id;

        // Comprobar existencia del vendedor primero (para devolver 404 si no existe)
        if (!$this->servicio->existeVendedor($vendedorId)) {
            throw new NotFoundException('El vendedor con ID ' . h((string)$id) . ' no existe.');
        }

        $fechaDesdeRaw = $this->request->getQuery('fecha_desde');
        $fechaHastaRaw = $this->request->getQuery('fecha_hasta');

        $validacion = $this->servicio->validarFiltroFechas($fechaDesdeRaw, $fechaHastaRaw);

        if (!$validacion['valido']) {
            // Mostrar error de validación con opción de corregir o volver
            $this->set([
                'resultado'    => null,
                'vendedorId'   => $vendedorId,
                'fechaDesde'   => is_string($fechaDesdeRaw) ? $fechaDesdeRaw : '',
                'fechaHasta'   => is_string($fechaHastaRaw) ? $fechaHastaRaw : '',
                'filtroActivo' => false,
                'errorFiltro'  => $validacion['error'],
                'titulo'       => 'Detalle de Comisión - Error en Filtro',
            ]);

            return;
        }

        $resultado = $this->servicio->calcularParaVendedor($vendedorId, $validacion['desde'], $validacion['hasta']);

        if ($resultado === null) {
            throw new NotFoundException('El vendedor con ID ' . h((string)$id) . ' no existe.');
        }

        $this->set([
            'resultado'    => $resultado,
            'vendedorId'   => $vendedorId,
            'fechaDesde'   => $validacion['desde'] ?? '',
            'fechaHasta'   => $validacion['hasta'] ?? '',
            'filtroActivo' => $validacion['filtrado'],
            'errorFiltro'  => null,
            'titulo'       => 'Detalle de Comisión: ' . h($resultado['nombre_vendedor']),
        ]);
    }
}
