<?php
/**
 * Vista: Comisiones/detalle.php
 * Muestra el detalle de comisión y ventas de un vendedor individual con soporte para filtro de fechas.
 *
 * Variables disponibles:
 *  @var \App\View\AppView $this
 *  @var array<string, mixed>|null $resultado
 *  @var int|null $vendedorId
 *  @var string $fechaDesde
 *  @var string $fechaHasta
 *  @var bool $filtroActivo
 *  @var string|null $errorFiltro
 *  @var string $titulo
 */

$this->layout = 'comisiones';
$this->assign('title', h($titulo));

// Parámetros para enlace de retorno
$volverQueryParams = [];
if ($filtroActivo && !empty($fechaDesde) && !empty($fechaHasta)) {
    $volverQueryParams = [
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
    ];
}
$volverUrl = $this->Url->build([
    'controller' => 'Comisiones',
    'action'     => 'index',
    '?'          => $volverQueryParams,
]);
?>

<!-- Botón volver al resumen -->
<div class="mb-4">
    <a href="<?= $volverUrl ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al resumen
    </a>
</div>

<?php if (!empty($errorFiltro)): ?>
    <!-- Alerta de Error de Validación en el Rango del Detalle -->
    <div class="alert alert-danger shadow-sm d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill fs-3 me-3 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-1 fw-bold">Parámetros de filtro inválidos</h5>
            <p class="mb-2"><?= h($errorFiltro) ?></p>
            <hr class="my-2">
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= $this->Url->build(['controller' => 'Comisiones', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Volver al resumen principal sin filtro
                </a>
                <?php if (!empty($vendedorId)): ?>
                    <a href="<?= $this->Url->build(['controller' => 'Comisiones', 'action' => 'detalle', $vendedorId]) ?>" class="btn btn-sm btn-danger">
                        <i class="bi bi-person-lines-fill me-1"></i>Ver histórico completo de este vendedor
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php elseif ($resultado !== null): ?>

    <!-- Encabezado del vendedor y período -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                 style="width: 60px; height: 60px; flex-shrink: 0;">
                <i class="bi bi-person-fill text-white fs-3"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold mb-0"><?= h($resultado['nombre_vendedor']) ?></h1>
                <p class="text-muted mb-0">ID Vendedor #<?= h((string)$resultado['vendedor_id']) ?></p>
            </div>
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

    <!-- Tarjeta de resumen de comisión -->
    <div class="row g-4 mb-4">
        <!-- Total vendido -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-2 mb-1">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Vendido <?= $filtroActivo ? '(Período)' : '' ?></div>
                    <div class="fs-5 fw-bold font-monospace">
                        $<?= h(number_format((float)$resultado['total_vendido'], 2)) ?>
                    </div>
                    <div class="text-muted small">USD</div>
                </div>
            </div>
        </div>

        <!-- Meta mínima aplicada -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-secondary shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-secondary fs-2 mb-1">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Meta Mínima</div>
                    <?php if ($resultado['meta_minima_aplicada'] !== null): ?>
                        <div class="fs-5 fw-bold font-monospace">
                            $<?= h(number_format((float)$resultado['meta_minima_aplicada'], 2)) ?>
                        </div>
                        <div class="text-muted small">USD</div>
                    <?php else: ?>
                        <div class="text-muted fst-italic fs-6 mt-1">Sin regla aplicable</div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- Porcentaje de comisión -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning fs-2 mb-1">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Porcentaje</div>
                    <div class="fs-5 fw-bold">
                        <?= h(number_format((float)$resultado['porcentaje_comision'], 2)) ?>%
                    </div>
                    <div class="text-muted small">de comisión</div>
                </div>
            </div>
        </div>

        <!-- Comisión a pagar -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-success shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-2 mb-1">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Comisión a Pagar</div>
                    <div class="fs-5 fw-bold font-monospace text-success">
                        $<?= h(number_format((float)$resultado['monto_comision'], 2)) ?>
                    </div>
                    <div class="text-muted small">USD</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fórmula de cálculo -->
    <div class="alert alert-light border shadow-sm mb-4" role="alert">
        <strong>Cálculo:</strong>
        $<?= h(number_format((float)$resultado['total_vendido'], 2)) ?> &times;
        <?= h(number_format((float)$resultado['porcentaje_comision'], 2)) ?>%
        =
        <strong class="text-success">
            $<?= h(number_format((float)$resultado['monto_comision'], 2)) ?> USD
        </strong>
        <span class="text-muted ms-2">(comisión por escalón aplicada sobre el total del período)</span>
    </div>

    <!-- Tabla de ventas -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-list-ul me-2 text-primary"></i>
                Ventas Registradas
                <?php if ($filtroActivo): ?>
                    <span class="text-muted small fw-normal">(en el período seleccionado)</span>
                <?php endif; ?>
            </h5>
            <?php if (!empty($resultado['ventas'])): ?>
                <span class="badge bg-secondary"><?= h((string)count($resultado['ventas'])) ?> <?= count($resultado['ventas']) === 1 ? 'venta' : 'ventas' ?></span>
            <?php endif ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($resultado['ventas'])): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2 fw-semibold">
                        <?php if ($filtroActivo): ?>
                            Este vendedor no tiene ventas en el período seleccionado.
                        <?php else: ?>
                            Este vendedor no tiene ventas registradas.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Fecha</th>
                                <th class="text-end pe-4">Monto (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $contador = 1;
                            foreach ($resultado['ventas'] as $venta):
                            ?>
                                <tr>
                                    <td class="ps-4 text-muted"><?= h((string)$contador++) ?></td>
                                    <td>
                                        <i class="bi bi-calendar3 me-2 text-muted"></i>
                                        <?= h((string)$venta->fecha) ?>
                                    </td>
                                    <td class="text-end pe-4 font-monospace fw-semibold">
                                        $<?= h(number_format((float)$venta->monto, 2)) ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="ps-4 fw-bold">Total <?= $filtroActivo ? 'del Período' : '' ?></td>
                                <td class="text-end pe-4 fw-bold font-monospace text-primary">
                                    $<?= h(number_format((float)$resultado['total_vendido'], 2)) ?> USD
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>

<?php endif; ?>
