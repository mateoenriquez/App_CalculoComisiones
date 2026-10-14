<?php
/**
 * Layout para el Sistema de Comisiones.
 * Usa Bootstrap 5 vía CDN. No carga estilos de CakePHP skeleton.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo ?? 'Sistema de Comisiones') ?></title>

    <!-- Bootstrap 5.3 CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .card-stat {
            border-left: 4px solid;
        }
        .card-stat-ventas  { border-color: #0d6efd; }
        .card-stat-comisiones { border-color: #198754; }
        .card-stat-vendedores { border-color: #fd7e14; }
        .table-comisiones thead {
            background-color: #212529;
            color: #fff;
        }
        .badge-comision-0   { background-color: #6c757d; }
        .badge-comision-5   { background-color: #0d6efd; }
        .badge-comision-10  { background-color: #198754; }
        .footer-app {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?= $this->Url->build(['controller' => 'Comisiones', 'action' => 'index']) ?>">
                <i class="bi bi-graph-up-arrow me-2"></i>Comisiones
            </a>
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMenu"
                    aria-controls="navMenu" aria-expanded="false" aria-label="Menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['controller' => 'Comisiones', 'action' => 'index']) ?>">
                            <i class="bi bi-people me-1"></i>Vendedores
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container pb-5">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>

    <!-- Pie de página -->
    <footer class="border-top py-3 mt-4">
        <div class="container footer-app text-center">
            Sistema de Cálculo de Comisiones &mdash; CakePHP 5 &bull; PHP 8 &bull; Bootstrap 5
        </div>
    </footer>

    <!-- Bootstrap 5 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFQ06d2mDMXfYDcFvUkZQA6xhxu"
            crossorigin="anonymous"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
