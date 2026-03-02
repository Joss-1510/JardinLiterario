<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require 'Conexion.php';

// Configuración de paginación
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filtros
$generoFilter = isset($_GET['genero']) ? $_GET['genero'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;

// Manejar solicitud de autocompletado (AJAX)
if (isset($_GET['term']) && !empty($_GET['term'])) {
    $term = trim($_GET['term']);
    try {
        $query = "SELECT DISTINCT Titulo FROM v_inventario WHERE Titulo LIKE ? ORDER BY Titulo LIMIT 10";
        $stmt = $conexion->prepare($query);
        $stmt->execute(["%$term%"]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row['Titulo'];
        }
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit();
        
    } catch (PDOException $e) {
        error_log("Error en autocomplete: " . $e->getMessage());
        echo json_encode([]);
        exit();
    }
}

try {
    // Consulta para contar el total de registros
    $countQuery = "SELECT COUNT(*) as total FROM v_inventario WHERE 1=1";
    $countParams = [];
    
    if (!empty($generoFilter)) {
        $countQuery .= " AND Genero = ?";
        $countParams[] = $generoFilter;
    }
    
    if (!empty($searchQuery)) {
        $countQuery .= " AND (Titulo LIKE ? OR Autor LIKE ? OR Editorial LIKE ?)";
        $searchParam = "%$searchQuery%";
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
    }
    
    $stmt = $conexion->prepare($countQuery);
    $stmt->execute($countParams);
    $totalLibros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalLibros / $limit);

    // Consulta principal para obtener los datos
    $query = "SELECT * FROM v_inventario WHERE 1=1";
    $params = [];

    if (!empty($generoFilter)) {
        $query .= " AND Genero = ?";
        $params[] = $generoFilter;
    }

    if (!empty($searchQuery)) {
        $query .= " AND (Titulo LIKE ? OR Autor LIKE ? OR Editorial LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $query .= " ORDER BY Titulo LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conexion->prepare($query);
    $stmt->execute($params);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener géneros para el filtro
    $generos = $conexion->query("SELECT DISTINCT Genero FROM v_inventario ORDER BY Genero")->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Error en Inventario: " . $e->getMessage());
    die("<div class='alert alert-danger'>Error al cargar el inventario. Por favor intente más tarde.</div>");
}

$current_page = basename(__FILE__);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Libros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <style>
        body {
            background-color: #4b6043;
            font-family: 'Roboto', sans-serif;
        }
        .container-main {
            padding: 20px;
        }
        .inventario-section {
            margin-top: 20px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .inventario-section h3 {
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
        .badge-agotado {
            background-color: #dc3545;
            color: white;
        }
        .badge-bajo {
            background-color: #fd7e14;
            color: #000;
        }
        .badge-disponible {
            background-color: #28a745;
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
        .precio-col {
            text-align: right;
            white-space: nowrap;
        }
        .ui-autocomplete {
            position: absolute;
            z-index: 1000;
            cursor: default;
            padding: 0;
            margin-top: 2px;
            list-style: none;
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        .ui-autocomplete li {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .ui-autocomplete li:hover {
            background-color: #f5f5f5;
        }
        .ui-autocomplete li a {
            color: #333;
            text-decoration: none;
            display: block;
        }
        .ui-helper-hidden-accessible {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="inventario-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">
                <i class="fas fa-arrow-left me-2"></i>Regresar
            </button>
            
            <h3>Inventario de Libros</h3>
            
            <div class="row mb-3">
                <div class="col-md-6 mb-2">
                    <form method="get" class="input-group">
                        <select name="genero" class="form-select">
                            <option value="">Todos los géneros</option>
                            <?php foreach ($generos as $genero): ?>
                                <option value="<?= htmlspecialchars($genero) ?>" <?= $generoFilter == $genero ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($genero) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-filtrar">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="get" class="input-group">
                        <input type="text" id="search-input" name="search" class="form-control" 
                               placeholder="Buscar libros..." value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                        <button type="submit" class="btn btn-filtrar">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                        <?php if ($generoFilter || $searchQuery): ?>
                            <a href="<?= $current_page ?>" class="btn btn-outline-danger ms-2">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Editorial</th>
                            <th>Género</th>
                            <th class="precio-col">Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($libros)): ?>
                            <?php foreach ($libros as $libro): ?>
                                <tr>
                                    <td><?= htmlspecialchars($libro['Titulo']) ?></td>
                                    <td><?= htmlspecialchars($libro['Autor']) ?></td>
                                    <td><?= htmlspecialchars($libro['Editorial']) ?></td>
                                    <td><?= htmlspecialchars($libro['Genero']) ?></td>
                                    <td class="precio-col">$<?= number_format($libro['Precio'], 2) ?></td>
                                    <td><?= htmlspecialchars($libro['Stock']) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = '';
                                        if ($libro['Estado'] == 'AGOTADO') {
                                            $badgeClass = 'badge-agotado';
                                        } elseif ($libro['Estado'] == 'STOCK BAJO') {
                                            $badgeClass = 'badge-bajo';
                                        } else {
                                            $badgeClass = 'badge-disponible';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($libro['Estado']) ?>
                                            <?php if ($libro['Estado'] == 'STOCK BAJO'): ?>
                                                <i class="fas fa-exclamation-triangle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No se encontraron libros en el inventario</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($generoFilter) && empty($searchQuery) && $totalPages > 1): ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(function() {
            // Autocompletado para el campo de búsqueda
            $("#search-input").autocomplete({
                source: function(request, response) {
                    $.getJSON(window.location.href, { term: request.term }, function(data) {
                        response(data);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#search-input").val(ui.item.value);
                    $(this).closest("form").submit();
                }
            });

            // Cerrar automáticamente las alertas
            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>