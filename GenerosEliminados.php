<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';

// Obtener géneros eliminados
function obtenerGenerosEliminados($conexion) {
    try {
        $query = "SELECT * FROM tgenero WHERE eliminado = 1 ORDER BY Nombre";
        $stmt = $conexion->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener géneros eliminados: " . $e->getMessage());
        return [];
    }
}

// Restaurar un género eliminado
function restaurarGenero($conexion, $idGenero) {
    try {
        $query = "UPDATE tgenero SET eliminado = 0 WHERE id_Genero = :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':id', $idGenero, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception("No se pudo ejecutar la consulta de restauración.");
        }
    } catch (PDOException $e) {
        error_log("Error al restaurar género: " . $e->getMessage());
        return false;
    }
}

// Manejo de restauración de géneros
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar'])) {
    if (restaurarGenero($conexion, $_POST['id_restaurar'])) {
        $_SESSION['alerta'] = [
            'tipo' => 'success',
            'titulo' => 'Éxito',
            'mensaje' => 'Género restaurado correctamente'
        ];
    } else {
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'titulo' => 'Error',
            'mensaje' => 'No se pudo restaurar el género'
        ];
    }
    header("Location: GenerosEliminados.php");
    exit();
}

// Obtener lista de géneros eliminados
$generosEliminados = obtenerGenerosEliminados($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Géneros Eliminados</title>
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
    </style>
</head>
<body>
    <div class="container-main">
        <div class="books-section">
            <button class="btn btn-secondary mb-3" onclick="location.href='GeneroI.php'">Regresar a Géneros</button>
            
            <h3>Géneros Eliminados</h3>
            
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
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($generosEliminados)): ?>
                            <?php foreach ($generosEliminados as $genero): ?>
                                <tr>
                                    <td><?= htmlspecialchars($genero['Nombre']) ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="id_restaurar" value="<?= $genero['id_Genero'] ?>">
                                            <button type="submit" name="restaurar" class="btn btn-restaurar">Restaurar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No hay géneros eliminados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
