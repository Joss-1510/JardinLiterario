<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';
require_once 'LibroF.php';

$libroF = new LibroF($conexion);

// Manejar restauración de libros
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar'])) {
    try {
        if ($libroF->restaurarLibro($_POST['id_restaurar'])) {
            $_SESSION['alerta'] = [
                'tipo' => 'success',
                'titulo' => 'Éxito',
                'mensaje' => 'Libro restaurado correctamente'
            ];
        }
    } catch (Exception $e) {
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'titulo' => 'Error',
            'mensaje' => $e->getMessage()
        ];
    }
    header("Location: LibrosEliminados.php");
    exit();
}

$librosEliminados = $libroF->obtenerLibrosEliminados();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros Eliminados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #4b6043;
            font-family: 'Roboto', sans-serif;
        }
        .container-main {
            padding: 20px;
        }
        .books-section {
            margin-top: 20px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-restaurar {
            background-color: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }
        .btn-restaurar:hover {
            background-color: #45a049;
            border-color: #45a049;
        }
        .btn-eliminar-def {
            background-color: #f44336;
            border-color: #f44336;
            color: white;
        }
        .btn-eliminar-def:hover {
            background-color: #d32f2f;
            border-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="books-section">
            <button class="btn btn-secondary mb-3" onclick="location.href='LibroI.php'">Regresar a Libros</button>
            
            <h3>Libros Eliminados</h3>
            
            <?php if (isset($_SESSION['alerta'])): ?>
            <div class="alert alert-<?= $_SESSION['alerta']['tipo'] ?> alert-dismissible fade show" role="alert">
                <strong><?= $_SESSION['alerta']['titulo'] ?>:</strong> <?= $_SESSION['alerta']['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alerta']); endif; ?>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Editorial</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($librosEliminados)): ?>
                            <?php foreach ($librosEliminados as $libro): ?>
                                <tr>
                                    <td><?= htmlspecialchars($libro['Codigo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($libro['Titulo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($libro['NombreAutor'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($libro['NombreEditorial'] ?? 'N/A') ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="id_restaurar" value="<?= $libro['id_Libro'] ?>">
                                            <button type="submit" name="restaurar" class="btn btn-restaurar">Restaurar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No hay libros eliminados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cerrar automáticamente las alertas después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    </script>
</body>
</html>