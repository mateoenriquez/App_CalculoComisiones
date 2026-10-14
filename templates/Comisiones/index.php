<?php
/**
 * Vista: Comisiones/index.php
 * Muestra el resumen de comisiones de todos los vendedores con soporte de filtro de fechas.
 *
 * Variables disponibles:
 *  @var \App\View\AppView $this
 *  @var array<int, array<string, mixed>>|null $resumen
 *  @var string|null $totalGlobalVendido
 *  @var string|null $totalGlobalComisiones
 *  @var int $totalGlobalVentasCount
 *  @var int $cantidadVendedores
 *  @var string $fechaDesde
 *  @var string $fechaHasta
 *  @var bool $filtroActivo
 *  @var string|null $errorFiltro
 *  @var string $titulo
 */

$this->layout = 'comisiones';
$this->assign('title', h($titulo));

$limpiarFiltroUrl = $this->Url->build(['controller' => 'Comisiones', 'action' => 'index']);
?>

<!-- Encabezado de página -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>
            <?= h($titulo) ?>
        </h1>
        <p class="text-muted mb-0">
            Resumen de ventas acumuladas y comisiones calculadas por vendedor
        </p>
    </div>
    <div>
        <?php if ($filtroActivo): ?>
            <span class="badge bg-primary fs-6 p-2 shadow-sm">
                <i class="bi bi-calendar-range me-1"></i>
                Período aplicado: <?= h($fechaDesde) ?> al <?= h($fechaHasta) ?>
            </span>
        <?php else: ?>
            <span class="badge bg-secondary fs-6 p-2 shadow-sm">
                <i class="bi bi-calendar-check me-1"></i>
                Período aplicado: Todas las fechas
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Tarjeta del Formulario de Filtro -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-semibold text-dark">
            <i class="bi bi-funnel text-primary me-2"></i>Filtro de Ventas por Fecha
        </h5>
    </div>
    <div class="card-body">
        <form id="filtroFechasForm" method="get" action="<?= $limpiarFiltroUrl ?>" novalidate>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="fecha_desde" class="form-label fw-semibold text-secondary small text-uppercase">
                        Fecha Inicial
                    </label>
                    <input type="date"
                           class="form-control <?= !empty($errorFiltro) ? 'is-invalid' : '' ?>"
                           id="fecha_desde"
                           name="fecha_desde"
                           value="<?= h($fechaDesde) ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_hasta" class="form-label fw-semibold text-secondary small text-uppercase">
                        Fecha Final
                    </label>
                    <input type="date"
                           class="form-control <?= !empty($errorFiltro) ? 'is-invalid' : '' ?>"
                           id="fecha_hasta"
                           name="fecha_hasta"
                           value="<?= h($fechaHasta) ?>"
                           <?= !empty($fechaDesde) ? 'min="' . h($fechaDesde) . '"' : '' ?>>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" id="btnFiltrar">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a href="<?= $limpiarFiltroUrl ?>" class="btn btn-outline-secondary" title="Quitar filtro y ver histórico">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar filtro
                    </a>
                </div>
            </div>

            <!-- Contenedor para mensajes de validación cliente -->
            <div id="clientValidationAlert" class="alert alert-warning mt-3 py-2 px-3 small d-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <span id="clientValidationMessage"></span>
            </div>
        </form>
    </div>
</div>

<!-- Alerta de Error de Validación de Servidor -->
<?php if (!empty($errorFiltro)): ?>
    <div class="alert alert-danger shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill fs-4 me-3 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>Error de filtro:</strong> <?= h($errorFiltro) ?>
            <div class="small mt-1">
                El cálculo no se ha ejecutado para evitar mostrar resultados incorrectos.
                Corrige el rango de fechas en el formulario o haz clic en
                <a href="<?= $limpiarFiltroUrl ?>" class="alert-link">Limpiar filtro</a> para ver el histórico completo.
            </div>
        </div>
    </div>
<?php else: ?>

    <!-- Aviso sobre ausencia de ventas según el escenario -->
    <?php if ($totalGlobalVentasCount === 0): ?>
        <?php if ($filtroActivo): ?>
            <!-- Escenario A: Rango activo sin ventas registradas -->
            <div class="alert alert-warning shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-4 me-3 flex-shrink-0 text-warning"></i>
                <div>
                    <strong>Sin ventas en el período:</strong>
                    No hay ventas registradas entre <?= h($fechaDesde) ?> y <?= h($fechaHasta) ?>.
                    <div class="small text-muted mt-1">
                        Todos los vendedores conservan su cálculo con total $0.00 USD y comisión $0.00 USD conforme a las reglas del sistema.
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Escenario C: Sin filtro y sin ventas en la base -->
            <div class="alert alert-info shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-4 me-3 flex-shrink-0"></i>
                <div>
                    <strong>Información:</strong> No hay ventas registradas.
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Tarjetas de totales globales -->
    <div class="row g-4 mb-4">
        <!-- Total global vendido -->
        <div class="col-md-4">
            <div class="card card-stat card-stat-ventas shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="fs-1 text-primary">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Vendido <?= $filtroActivo ? '(Período)' : 'Global' ?></div>
                        <div class="fs-4 fw-bold">
                            $<?= h(number_format((float)($totalGlobalVendido ?? '0.00'), 2)) ?> USD
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total global de comisiones -->
        <div class="col-md-4">
            <div class="card card-stat card-stat-comisiones shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="fs-1 text-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Comisiones <?= $filtroActivo ? '(Período)' : 'Global' ?></div>
                        <div class="fs-4 fw-bold">
                            $<?= h(number_format((float)($totalGlobalComisiones ?? '0.00'), 2)) ?> USD
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Número de vendedores -->
        <div class="col-md-4">
            <div class="card card-stat card-stat-vendedores shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="fs-1 text-warning">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Número de Vendedores</div>
                        <div class="fs-4 fw-bold"><?= h((string)$cantidadVendedores) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de vendedores -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-table me-2 text-primary"></i>Detalle por Vendedor
            </h5>
            <?php if ($filtroActivo): ?>
                <span class="badge bg-light text-dark border">
                    Ventas en período: <strong><?= h((string)$totalGlobalVentasCount) ?></strong>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($resumen)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No hay vendedores registrados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-comisiones table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Vendedor</th>
                                <th class="text-end">Total Vendido</th>
                                <th class="text-end">Meta Mínima</th>
                                <th class="text-center">% Comisión</th>
                                <th class="text-end">Comisión a Pagar</th>
                                <th class="text-center">Estado / Ventas</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen as $fila): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold">
                                        <i class="bi bi-person-circle me-2 text-secondary"></i>
                                        <?= h($fila['nombre_vendedor']) ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        $<?= h(number_format((float)$fila['total_vendido'], 2)) ?> USD
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?php if ($fila['meta_minima_aplicada'] !== null): ?>
                                            $<?= h(number_format((float)$fila['meta_minima_aplicada'], 2)) ?> USD
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin regla aplicable</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $pct = (float)$fila['porcentaje_comision'];
                                        $badgeClass = match(true) {
                                            $pct >= 10.0 => 'bg-success',
                                            $pct >= 5.0  => 'bg-primary',
                                            default      => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> fs-6">
                                            <?= h(number_format($pct, 2)) ?>%
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold font-monospace">
                                        $<?= h(number_format((float)$fila['monto_comision'], 2)) ?> USD
                                    </td>
                                    <td class="text-center">
                                        <?php if ($fila['sin_ventas']): ?>
                                            <span class="badge bg-light text-secondary border">
                                                <i class="bi bi-dash-circle me-1"></i>Sin ventas en este período
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-receipt me-1"></i><?= h((string)$fila['cantidad_ventas']) ?> <?= $fila['cantidad_ventas'] === 1 ? 'venta' : 'ventas' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $detalleQueryParams = [];
                                        if ($filtroActivo && !empty($fechaDesde) && !empty($fechaHasta)) {
                                            $detalleQueryParams = [
                                                'fecha_desde' => $fechaDesde,
                                                'fecha_hasta' => $fechaHasta,
                                            ];
                                        }
                                        $detalleUrl = $this->Url->build([
                                            'controller' => 'Comisiones',
                                            'action'     => 'detalle',
                                            $fila['vendedor_id'],
                                            '?'          => $detalleQueryParams,
                                        ]);
                                        ?>
                                        <a href="<?= $detalleUrl ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Nota sobre la regla de negocio -->
    <div class="alert alert-info mt-4 shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Regla de comisión por escalón:</strong>
        Se aplica el porcentaje de la meta más alta alcanzada sobre el total vendido completo del período.
        No es progresiva. Los extremos del rango de fechas son inclusivos (fecha_desde &le; fecha &le; fecha_hasta).
    </div>

<?php endif; ?>

<!-- Script de Validación en Cliente -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('filtroFechasForm');
    var inputDesde = document.getElementById('fecha_desde');
    var inputHasta = document.getElementById('fecha_hasta');
    var alertBox = document.getElementById('clientValidationAlert');
    var alertMsg = document.getElementById('clientValidationMessage');

    function showError(msg) {
        alertMsg.textContent = msg;
        alertBox.classList.remove('d-none');
    }

    function clearError() {
        alertBox.classList.add('d-none');
        alertMsg.textContent = '';
        inputDesde.classList.remove('is-invalid');
        inputHasta.classList.remove('is-invalid');
    }

    // Al seleccionar fecha inicial, actualizar atributo min de fecha final
    inputDesde.addEventListener('change', function () {
        if (inputDesde.value) {
            inputHasta.min = inputDesde.value;
            // Si fecha_hasta ya tiene un valor anterior a la nueva fecha inicial
            if (inputHasta.value && inputHasta.value < inputDesde.value) {
                showError('La fecha final no puede ser anterior a la fecha inicial.');
                inputHasta.classList.add('is-invalid');
            } else {
                clearError();
            }
        } else {
            inputHasta.removeAttribute('min');
            clearError();
        }
    });

    inputHasta.addEventListener('change', function () {
        if (inputDesde.value && inputHasta.value) {
            if (inputHasta.value < inputDesde.value) {
                showError('La fecha final no puede ser anterior a la fecha inicial.');
                inputHasta.classList.add('is-invalid');
            } else {
                clearError();
            }
        } else {
            clearError();
        }
    });

    form.addEventListener('submit', function (e) {
        var desde = inputDesde.value.trim();
        var hasta = inputHasta.value.trim();

        // 1. Ambos vacíos: se permite el envío (actúa como limpiar filtro)
        if (!desde && !hasta) {
            return true;
        }

        // 2. Solo uno completo: impedir envío
        if (!desde || !hasta) {
            e.preventDefault();
            showError('Debes seleccionar una fecha inicial y una fecha final.');
            if (!desde) {
                inputDesde.classList.add('is-invalid');
                inputDesde.focus();
            } else {
                inputHasta.classList.add('is-invalid');
                inputHasta.focus();
            }
            return false;
        }

        // 3. Fecha final anterior a la inicial
        if (hasta < desde) {
            e.preventDefault();
            showError('La fecha final no puede ser anterior a la fecha inicial.');
            inputHasta.classList.add('is-invalid');
            inputHasta.focus();
            return false;
        }

        clearError();
        return true;
    });
});
</script>
