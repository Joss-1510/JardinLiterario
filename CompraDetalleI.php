<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';
require_once 'CompraDetalleF.php';

$compraManager = new CompraDetalleF($GLOBALS['conexion']);

// Configuración de paginación
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalRegistros = $compraManager->getTotalCount();
$totalPages = ceil($totalRegistros / $limit);

// Obtener listado de compras
$compras = $compraManager->obtenerComprasAgrupadas($limit, $page);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Compras</title>
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
        .badge-compra {
            background-color: #17a2b8;
            color: white;
        }
        .btn-action {
            margin: 2px;
            min-width: 30px;
        }
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .modal-detalle .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }
        @media (max-width: 768px) {
            .container-main { padding: 10px; }
            .data-section h3 { font-size: 2rem; }
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
            
            <h3>Registro de Compras</h3>

            <!-- Barra de búsqueda -->
            <div class="search-container">
                <input type="text" class="form-control" id="buscar-compra" placeholder="Buscar por folio o proveedor...">
                <i class="fas fa-search search-icon"></i>
            </div>

            <!-- Tabla de compras -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($compras as $compra): ?>
                        <tr>
                            <td>
                                <span class="badge badge-compra">
                                    <?= htmlspecialchars($compra['Folio']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($compra['Proveedor']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($compra['Fecha'])) ?></td>
                            <td>$<?= number_format($compra['Total'], 2) ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= $compra['Productos'] ?> producto(s)
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info btn-action" 
                                        onclick="verDetalleCompra(<?= $compra['id_Compra'] ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-3">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para detalles -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetalleLabel">Detalles de Compra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-content">
                    <!-- Contenido cargado dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Función para mostrar/ocultar el spinner de carga
        function toggleLoading(show) {
            document.getElementById('loading').style.display = show ? 'flex' : 'none';
        }

        // Función para ver detalles de compra
        function verDetalleCompra(idCompra) {
            toggleLoading(true);
            
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            // Plantilla de carga
            $('#modal-content').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles de la compra...</p>
                </div>
            `);

            $.ajax({
                url: 'CompraDetalleF.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'obtenerDatosCompra',
                    idCompra: idCompra
                },
                success: function(response) {
                    toggleLoading(false);
                    
                    if (response && response.compra) {
                        const compra = response.compra;
                        const detalles = response.detalles;
                        
                        let htmlContent = `
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Información de Compra</h6>
                                    <div class="card card-body">
                                        <p><strong>Folio:</strong> ${compra.Folio}</p>
                                        <p><strong>Fecha:</strong> ${new Date(compra.Fecha).toLocaleString()}</p>
                                        <p><strong>Total:</strong> $${parseFloat(compra.Total).toFixed(2)}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Información de Proveedor</h6>
                                    <div class="card card-body">
                                        <p><strong>Nombre:</strong> ${compra.Proveedor}</p>
                                        ${compra.RFC ? `<p><strong>RFC:</strong> ${compra.RFC}</p>` : ''}
                                        ${compra.Direccion ? `<p><strong>Dirección:</strong> ${compra.Direccion}</p>` : ''}
                                        ${compra.Telefono ? `<p><strong>Teléfono:</strong> ${compra.Telefono}</p>` : ''}
                                    </div>
                                </div>
                            </div>
                            <h6>Productos Comprados</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>P. Compra</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        let total = 0;
                        detalles.forEach(detalle => {
                            total += parseFloat(detalle.Subtotal);
                            htmlContent += `
                                <tr>
                                    <td>${detalle.Codigo || 'N/A'}</td>
                                    <td>${detalle.Producto}</td>
                                    <td>${detalle.Cantidad}</td>
                                    <td>$${parseFloat(detalle.PrecioCompra).toFixed(2)}</td>
                                    <td>$${parseFloat(detalle.Subtotal).toFixed(2)}</td>
                                </tr>`;
                        });
                        
                        htmlContent += `
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">TOTAL:</th>
                                            <th>$${total.toFixed(2)}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>`;
                        
                        $('#modal-content').html(htmlContent);
                    } else {
                        $('#modal-content').html(`
                            <div class="alert alert-danger">
                                No se encontraron detalles para esta compra
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    toggleLoading(false);
                    $('#modal-content').html(`
                        <div class="alert alert-danger">
                            Error al cargar los detalles: ${error}
                        </div>
                    `);
                }
            });
        }
        
        // Búsqueda en tiempo real
        $('#buscar-compra').on('input', function() {
            const texto = $(this).val().toLowerCase();
            $('table tbody tr').each(function() {
                const fila = $(this).text().toLowerCase();
                $(this).toggle(fila.indexOf(texto) > -1);
            });
        });
    </script>
</body>
</html>