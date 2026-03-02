<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';
require_once 'CajaF.php';

$cajaManager = new CajaF($GLOBALS['conexion']);
$estadoCaja = $cajaManager->obtenerEstadoCaja();
$historial = $cajaManager->obtenerHistorial();

// Calcular totales
$ventasHoy = $cajaManager->obtenerVentasDelDia();
$totalCalculado = $estadoCaja ? ($estadoCaja['Apertura'] + $ventasHoy) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Caja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #4b6043; font-family: 'Roboto', sans-serif; }
        .container-main { padding: 20px; }
        .data-section { margin-top: 20px; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        .data-section h3 { text-align: center; font-size: 3rem; color: rgb(0, 0, 0); }
        .btn-regresar { background-color: #47b36e; border-color: #47b36e; color: #ffffff; }
        .btn-regresar:hover { background-color: rgb(73, 156, 103); border-color: rgb(73, 156, 103); }
        .table th { background-color: #4b6043; color: white; }
        .badge-caja { background-color: #17a2b8; color: white; }
        .btn-action { margin: 2px; min-width: 30px; }
        .search-container { position: relative; margin-bottom: 20px; }
        .search-icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #6c757d; }
        .caja-status { font-size: 1.2rem; font-weight: 500; }
        .caja-abierta { color: #28a745; }
        .caja-cerrada { color: #dc3545; }
        .balance-box { border-left: 4px solid #4b6043; padding-left: 15px; }
        .loading-spinner { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .spinner-border { width: 3rem; height: 3rem; }
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
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="window.history.back()">
                <i class="fas fa-arrow-left me-2"></i>Regresar
            </button>
            
            <h3>Gestión de Caja</h3>

            <!-- Estado de Caja -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Estado Actual</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($estadoCaja && $estadoCaja['Cierre'] === null): ?>
                                <div class="alert alert-success">
                                    <div class="d-flex justify-content-between">
                                        <span class="caja-status caja-abierta">
                                            <i class="fas fa-lock-open me-2"></i>CAJA ABIERTA
                                        </span>
                                        <span>Fecha: <?= date('d/m/Y', strtotime($estadoCaja['Fecha'])) ?></span>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6 balance-box">
                                            <h6>Apertura:</h6>
                                            <h4>$<?= number_format($estadoCaja['Apertura'], 2) ?></h4>
                                        </div>
                                        <div class="col-md-6 balance-box">
                                            <h6>Ventas Hoy:</h6>
                                            <h4 id="total-ventas">$<?= number_format($ventasHoy, 2) ?></h4>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <h5>Total calculado: <strong id="total-calculado">$<?= number_format($totalCalculado, 2) ?></strong></h5>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCerrarCaja">
                                            <i class="fas fa-lock me-2"></i>Cerrar Caja
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <div class="caja-status caja-cerrada">
                                        <i class="fas fa-lock me-2"></i>CAJA CERRADA
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
                                            <i class="fas fa-lock-open me-2"></i>Abrir Caja
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Resumen</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Total Ventas Hoy</h6>
                                            <h4 class="card-text text-success" id="resumen-ventas">$<?= number_format($ventasHoy, 2) ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Estado Caja</h6>
                                            <h4 class="card-text text-primary">
                                                <?= $estadoCaja && $estadoCaja['Cierre'] === null ? 'Abierta' : 'Cerrada' ?>
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Cajas -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Cajas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Apertura</th>
                                    <th>Cierre</th>
                                    <th>Estado</th>
                                    <th>Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $caja): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($caja['Fecha'])) ?></td>
                                    <td>$<?= number_format($caja['Apertura'], 2) ?></td>
                                    <td>
                                        <?= $caja['Cierre'] !== null ? '$'.number_format($caja['Cierre'], 2) : '--' ?>
                                    </td>
                                    <td>
                                        <?php if ($caja['Cierre'] === null): ?>
                                            <span class="badge bg-success">Abierta</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cerrada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($caja['Cierre'] !== null): ?>
                                            <?php $diferencia = $caja['Cierre'] - $caja['Apertura']; ?>
                                            <span class="<?= $diferencia >= 0 ? 'text-success' : 'text-danger' ?>">
                                                $<?= number_format(abs($diferencia), 2) ?>
                                            </span>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Abrir Caja -->
    <div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Abrir Caja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formAbrirCaja">
                        <div class="mb-3">
                            <label for="montoApertura" class="form-label">Monto de Apertura</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="montoApertura" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnAbrirCaja">
                        <i class="fas fa-lock-open me-2"></i>Abrir Caja
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cerrar Caja -->
    <div class="modal fade" id="modalCerrarCaja" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Cerrar Caja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contenido-cierre">
                        <div class="alert alert-primary">
                            <h5 class="alert-heading">Resumen de Cierre Automático</h5>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Apertura:</strong></p>
                                    <p class="mb-1"><strong>Ventas del día:</strong></p>
                                    <hr>
                                    <p class="mb-1"><strong>Total calculado:</strong></p>
                                </div>
                                <div class="col-6 text-end">
                                    <p class="mb-1">$<?= number_format($estadoCaja['Apertura'] ?? 0, 2) ?></p>
                                    <p class="mb-1">+ $<span id="ventas-cierre"><?= number_format($ventasHoy, 2) ?></span></p>
                                    <hr>
                                    <p class="mb-1"><strong>$<span id="total-cierre"><?= number_format($totalCalculado, 2) ?></span></strong></p>
                                </div>
                            </div>
                            <input type="hidden" id="montoCierreCalculado" value="<?= $totalCalculado ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnCerrarCaja">
                        <i class="fas fa-lock me-2"></i>Confirmar Cierre
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Función para mostrar/ocultar loading
        function toggleLoading(show) {
            $('#loading').css('display', show ? 'flex' : 'none');
        }

        // Función para actualizar los totales
        function actualizarTotales() {
            $.ajax({
                url: 'CajaF.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'calcularCierre'
                },
                success: function(response) {
                    if (response) {
                        $('#total-ventas').text('$' + response.ventas.toFixed(2));
                        $('#total-calculado').text('$' + response.total.toFixed(2));
                        $('#resumen-ventas').text('$' + response.ventas.toFixed(2));
                        
                        // Actualizar modal de cierre
                        $('#ventas-cierre').text(response.ventas.toFixed(2));
                        $('#total-cierre').text(response.total.toFixed(2));
                        $('#montoCierreCalculado').val(response.total);
                    }
                }
            });
        }

        // Actualizar cada 30 segundos
        setInterval(actualizarTotales, 30000);

        $(document).ready(function() {
            // Actualizar al cargar la página
            actualizarTotales();

            // Abrir caja
            $('#btnAbrirCaja').click(function() {
                const monto = $('#montoApertura').val();
                if (!monto || parseFloat(monto) <= 0) {
                    Swal.fire('Error', 'Ingrese un monto válido', 'error');
                    return;
                }

                toggleLoading(true);
                
                $.ajax({
                    url: 'CajaF.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'abrirCaja',
                        monto: monto
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Éxito', 'Caja abierta correctamente', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.error || 'No se pudo abrir la caja', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error al comunicarse con el servidor', 'error');
                    },
                    complete: function() {
                        toggleLoading(false);
                    }
                });
            });

            // Cerrar caja con el monto calculado
            $('#btnCerrarCaja').click(function() {
                const monto = $('#montoCierreCalculado').val();
                
                if (!monto) {
                    Swal.fire('Error', 'No se pudo obtener el monto calculado', 'error');
                    return;
                }

                toggleLoading(true);
                
                $.ajax({
                    url: 'CajaF.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'cerrarCaja',
                        monto: monto
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Éxito', 'Caja cerrada correctamente', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.error || 'No se pudo cerrar la caja', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error al comunicarse con el servidor', 'error');
                    },
                    complete: function() {
                        toggleLoading(false);
                    }
                });
            });
        });
    </script>
</body>
</html>