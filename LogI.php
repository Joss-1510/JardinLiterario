<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require 'Conexion.php';
require 'LogF.php';

$log = new Log($conexion);

// Configuración de paginación
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalLogs = $log->getTotalCount();
$totalPages = ceil($totalLogs / $limit);

// Filtros
$actionFilter = isset($_GET['action']) ? $_GET['action'] : null;
$searchQuery = isset($_GET['search']) ? $_GET['search'] : null;

// Obtener datos
if (!empty($actionFilter)) {
    $logs = $log->getByAction($actionFilter, $limit);
} elseif (!empty($searchQuery)) {
    $logs = $log->search($searchQuery, $limit);
} else {
    $logs = $log->getAll($limit, $page);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Actividades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #4b6043;
            font-family: 'Roboto', sans-serif;
        }
        .container-main {
            padding: 20px;
        }
        .logs-section {
            margin-top: 20px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .logs-section h3 {
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
        .btn-filtrar {
            background-color: #72cc6b;
            border-color: #72cc6b;
            color: #ffffff;
        }
        .btn-filtrar:hover {
            background-color: rgb(80, 149, 75);
            border-color: rgb(80, 149, 75);
        }
        .badge-insert {
            background-color: #28a745;
            color: white;
        }
        .badge-update {
            background-color: #ffc107;
            color: #000;
        }
        .badge-delete {
            background-color: #dc3545;
            color: white;
        }
        .table th {
            background-color: #4b6043;
            color: white;
        }
        .alert-auto-close {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .pagination .page-item.active .page-link {
            background-color: #4b6043;
            border-color: #4b6043;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="logs-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">
                <i class="fas fa-arrow-left me-2"></i>Regresar
            </button>
            
            <h3>Registro de Actividades</h3>
            
            <div class="row mb-3">
                <div class="col-md-6 mb-2">
                    <form method="get" class="input-group">
                        <select name="action" class="form-select">
                            <option value="">Todas las acciones</option>
                            <option value="INSERT" <?= $actionFilter == 'INSERT' ? 'selected' : '' ?>>Creaciones</option>
                            <option value="UPDATE" <?= $actionFilter == 'UPDATE' ? 'selected' : '' ?>>Actualizaciones</option>
                            <option value="DELETE" <?= $actionFilter == 'DELETE' ? 'selected' : '' ?>>Eliminaciones</option>
                            <option value="VENTA_CREADA" <?= $actionFilter == 'VENTA_CREADA' ? 'selected' : '' ?>>Ventas</option>
                        </select>
                        <button type="submit" class="btn btn-filtrar">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="get" class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar en detalles..." value="<?= htmlspecialchars($searchQuery) ?>">
                        <button type="submit" class="btn btn-filtrar">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                        <?php if ($actionFilter || $searchQuery): ?>
                            <a href="LogI.php" class="btn btn-outline-danger">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Tabla de logs -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $logItem): ?>
                                <tr>
                                    <td><?= htmlspecialchars($logItem['usuario_nombre'] ?? 'Sistema') ?></td>
                                    <td><?= htmlspecialchars($logItem['fecha_formateada'] ?? $logItem['Fecha']) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = '';
                                        if ($logItem['Accion'] == 'INSERT') {
                                            $badgeClass = 'badge-insert';
                                        } elseif ($logItem['Accion'] == 'UPDATE') {
                                            $badgeClass = 'badge-update';
                                        } else {
                                            $badgeClass = 'badge-delete';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($logItem['Accion']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($logItem['Detalle']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No se encontraron registros de actividad</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if (empty($actionFilter) && empty($searchQuery) && $totalPages > 1): ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cerrar automáticamente las alertas después de 5 segundos
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert-auto-close');
            alerts.forEach(function(alert) {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>