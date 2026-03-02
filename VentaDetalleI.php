<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require 'Conexion.php';
require 'VentaDetalleF.php';

$ventaDetalle = new VentaDetalleF($conexion);

// Configuración de paginación y búsqueda
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$totalRegistros = $ventaDetalle->getTotalCount($busqueda);
$totalPages = ceil($totalRegistros / $limit);

// Obtener datos
$ventas = $ventaDetalle->obtenerVentasAgrupadas($limit, $page, $busqueda);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color: #4b6043;
            font-family: 'Roboto', sans-serif;
        }
        .container-main {
            padding: 20px;
        }
        .data-section {
            margin-top: 20px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .data-section h3 {
            text-align: center;
            font-size: 3rem;
            color: rgb(0, 0, 0);
        }
        .btn-regresar {
            background-color: #47b36e;
            border-color: #47b36e;
            color: #ffffff;
        }
        .btn-regresar:hover {
            background-color: rgb(73, 156, 103);
            border-color: rgb(73, 156, 103);
        }
        .table th {
            background-color: #4b6043;
            color: white;
        }
        .badge-venta {
            background-color: #17a2b8;
            color: white;
        }
        .venta-anulada {
            background-color: #f8d7da;
            text-decoration: line-through;
        }
        .badge-anulada {
            background-color: #dc3545;
            color: white;
        }
        .btn-action {
            margin: 2px;
            min-width: 30px;
        }
        .detalles-venta {
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
        }
        .modal-anulacion {
            max-width: 600px;
        }
        .search-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        @media (max-width: 768px) {
            .container-main { padding: 10px; }
            .data-section h3 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div id="loading" class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div class="container-main">
        <div class="data-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">
                <i class="fas fa-arrow-left me-2"></i>Regresar
            </button>
            
            <h3>Registro de Ventas</h3>

            <!-- Barra de búsqueda -->
            <div class="search-box">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" name="busqueda" placeholder="Buscar por folio, cliente, vendedor..." 
                                   value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <?php if (!empty($busqueda)): ?>
                                <a href="VentaDetalleI.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Mensaje de resultados -->
            <?php if (!empty($busqueda)): ?>
                <div class="alert alert-info mb-3">
                    Mostrando resultados para: <strong><?= htmlspecialchars($busqueda) ?></strong>
                    <span class="badge bg-primary ms-2"><?= $totalRegistros ?> registros</span>
                </div>
            <?php endif; ?>

            <!-- Tabla de ventas -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Total</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <h5>No se encontraron ventas</h5>
                                    <?php if (!empty($busqueda)): ?>
                                        <a href="VentaDetalleI.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-undo"></i> Mostrar todas
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $venta): ?>
                                <tr class="<?= ($venta['Anulado'] ?? false) ? 'venta-anulada' : '' ?>">
                                    <td>
                                        <span class="badge <?= ($venta['Anulado'] ?? false) ? 'badge-anulada' : 'badge-venta' ?>">
                                            <?= htmlspecialchars($venta['Folio']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($venta['Fecha']) ?></td>
                                    <td><?= htmlspecialchars($venta['Cliente'] ?? 'No especificado') ?></td>
                                    <td><?= htmlspecialchars($venta['Vendedor'] ?? 'Sistema') ?></td>
                                    <td>$<?= number_format($venta['Total'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= $venta['TotalProductos'] ?> producto(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap">
                                            <!-- Botón Detalles -->
                                            <button class="btn btn-sm btn-info btn-action btn-detalles" 
                                                    data-id="<?= $venta['id_Venta'] ?>"
                                                    <?= ($venta['Anulado'] ?? false) ? 'disabled' : '' ?>>
                                                <i class="fas fa-list"></i>
                                            </button>
                                            
                                            <!-- Botón Imprimir -->
                                            <button class="btn btn-sm btn-success btn-action btn-imprimir" 
                                                    data-id="<?= $venta['id_Venta'] ?>">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-3">
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Primera página
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&busqueda=<?= urlencode($busqueda) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $totalPages ?>&busqueda=<?= urlencode($busqueda) ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para detalles -->
    <div class="modal fade" id="detallesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Detalles de Venta <span id="folio-venta"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detalles-contenido"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            var detallesModal = new bootstrap.Modal(document.getElementById('detallesModal'));
            var anularModal = new bootstrap.Modal(document.getElementById('anularModal'));
            var ventaAAnular = null;
            
            // Función para mostrar/ocultar loading
            function toggleLoading(show) {
                $('#loading').css('display', show ? 'flex' : 'none');
            }
            
            // Detalles de venta
            $('.btn-detalles').click(function() {
                var idVenta = $(this).data('id');
                $('#detalles-contenido').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Cargando detalles...</p></div>');
                $('#folio-venta').text('#' + $(this).closest('tr').find('.badge').text());
                detallesModal.show();
                
                toggleLoading(true);
                
                $.ajax({
                    url: 'obtener_detalles_venta.php',
                    method: 'POST',
                    data: { idVenta: idVenta },
                    success: function(response) {
                        $('#detalles-contenido').html(response);
                        toggleLoading(false);
                    },
                    error: function() {
                        $('#detalles-contenido').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                        toggleLoading(false);
                    }
                });
            });

            // Imprimir ticket
            $('.btn-imprimir').click(function() {
                var idVenta = $(this).data('id');
                window.open('TicketI.php?venta=' + idVenta, '_blank');
            });
            
            // Limpiar modal al cerrar
            $('#anularModal').on('hidden.bs.modal', function () {
                $('#motivo-anulacion').val('');
                ventaAAnular = null;
            });
        });
    </script>
</body>
</html>