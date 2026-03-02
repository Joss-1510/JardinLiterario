<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

include 'Conexion.php';
include 'CRUD.php';
include 'EditorialF.php';

$crud = new EditorialF($conexion);

$todasEditoriales = $crud->read('teditorial');
$nombresEditoriales = array_column($todasEditoriales, 'Nombre');
$editoriales = $todasEditoriales;

// Manejo de operaciones POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['buscar_nombre'])) {
            $nombre = trim($_POST['buscar_nombre']);
            $editoriales = $crud->read('teditorial', ['Nombre' => $nombre]);
            
            if (empty($editoriales)) {
                $_SESSION['alerta'] = [
                    'tipo' => 'info',
                    'titulo' => 'Búsqueda',
                    'mensaje' => 'No se encontraron editoriales con ese nombre'
                ];
            }
        } elseif (isset($_POST['nuevo_nombre']) && isset($_POST['nueva_direccion']) && isset($_POST['nuevo_email']) && isset($_POST['nuevo_telefono'])) {
            $nuevo_nombre = trim($_POST['nuevo_nombre']);
            $nueva_direccion = trim($_POST['nueva_direccion']);
            $nuevo_email = trim($_POST['nuevo_email']);
            $nuevo_telefono = trim($_POST['nuevo_telefono']);
            
            if (empty($nuevo_nombre) || empty($nueva_direccion) || empty($nuevo_email) || empty($nuevo_telefono)) {
                throw new Exception("Todos los campos son requeridos");
            }

            if (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del email no es válido");
            }
            
            if ($crud->create('teditorial', [
                'Nombre' => $nuevo_nombre,
                'Direccion' => $nueva_direccion,
                'Email' => $nuevo_email,
                'Telefono' => $nuevo_telefono
            ])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Editorial agregada correctamente'
                ];
                header("Location: EditorialI.php");
                exit();
            } else {
                throw new Exception('Error al agregar la editorial');
            }
        } elseif (isset($_POST['id_eliminar'])) {
            $id_eliminar = (int)$_POST['id_eliminar'];
            $query = "UPDATE teditorial SET eliminado = 1 WHERE id_Editorial = :id";
            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':id', $id_eliminar, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Editorial eliminada correctamente'
                ];
                header("Location: EditorialI.php");
                exit();
            } else {
                throw new Exception('Error al eliminar la editorial');
            }
        } elseif (isset($_POST['id_editar']) && isset($_POST['nombre_editar']) && isset($_POST['direccion_editar']) && isset($_POST['email_editar']) && isset($_POST['telefono_editar'])) {
            $id_editar = (int)$_POST['id_editar'];
            $nombre_editar = trim($_POST['nombre_editar']);
            $direccion_editar = trim($_POST['direccion_editar']);
            $email_editar = trim($_POST['email_editar']);
            $telefono_editar = trim($_POST['telefono_editar']);
            
            if (empty($nombre_editar) || empty($direccion_editar) || empty($email_editar) || empty($telefono_editar)) {
                throw new Exception("Todos los campos son requeridos");
            }

            if (!filter_var($email_editar, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del email no es válido");
            }

            if ($crud->update('teditorial', [
                'Nombre' => $nombre_editar,
                'Direccion' => $direccion_editar,
                'Email' => $email_editar,
                'Telefono' => $telefono_editar
            ], ['id_Editorial' => $id_editar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Editorial actualizada correctamente'
                ];
                header("Location: EditorialI.php");
                exit();
            } else {
                throw new Exception('Error al actualizar la editorial');
            }
        }
    } catch (Exception $e) {
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'titulo' => 'Error',
            'mensaje' => $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Editoriales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
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
        .books-section h3 {
            text-align: center;
            font-size: 3rem; 
            color:rgb(0, 0, 0); 
        }
        .btn-regresar {
            background-color: #47b36e;
            border-color: #47b36e;
            color: #ffffff;
        }
        .btn-regresar:hover {
            background-color:rgb(73, 156, 103);
            border-color: rgb(73, 156, 103);
        }
        .btn-agregar {
            background-color: #a5d371;
            border-color: #a5d371;
            color: #ffffff;
        }
        .btn-agregar:hover {
            background-color:rgb(135, 171, 93);
            border-color: rgb(135, 171, 93);
        }
        .btn-buscar {
            background-color: #72cc6b; 
            border-color: #72cc6b;
            color: #ffffff;
        }
        .btn-buscar:hover {
            background-color:rgb(80, 149, 75);
            border-color: rgb(80, 149, 75);
        }
        .btn-editar {
            background-color: #ffe761;
            border-color: #ffe761;
            color: #000000;
        }
        .btn-editar:hover {
            background-color: #ffdc2e;
            border-color: #ffdc2e;
        }
        .btn-eliminar {
            background-color: #ff3232;
            border-color: #ff3232;
            color: #ffffff;
        }
        .btn-eliminar:hover {
            background-color: #ff1818;
            border-color: #ff1818;
        }
        .modal-header, .btn-custom-modal {
            background-color: #4caf50;
            border-color: #4caf50;
            color: #ffffff;
        }
        .table th, .table td {
            text-align: center;
        }
        .alert-auto-close {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['alerta'])): ?>
    <div class="alert alert-<?= $_SESSION['alerta']['tipo'] ?> alert-dismissible fade show alert-auto-close" role="alert">
        <strong><?= $_SESSION['alerta']['titulo'] ?>:</strong> <?= $_SESSION['alerta']['mensaje'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['alerta']); endif; ?>

    <div class="container-main">
        <div class="books-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">Regresar</button>
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='EditorialesEliminadas.php'">Editoriales Eliminadas</button>

            
            <h3>Editoriales</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarEditorialModal">Agregar Editorial</button>
            
            <form method="post" action="EditorialI.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" placeholder="Buscar por nombre" value="<?= isset($_POST['buscar_nombre']) ? htmlspecialchars($_POST['buscar_nombre']) : '' ?>">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])): ?>
                        <a href="EditorialI.php" class="btn btn-outline-danger">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($editoriales)): ?>
                        <?php foreach ($editoriales as $editorial): ?>
                            <tr>
                                <td><?= htmlspecialchars($editorial['Nombre']) ?></td>
                                <td><?= htmlspecialchars($editorial['Direccion']) ?></td>
                                <td><?= htmlspecialchars($editorial['Email']) ?></td>
                                <td><?= htmlspecialchars($editorial['Telefono']) ?></td>
                                <td>
                                    <button class='btn btn-primary btn-editar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editarEditorialModal' 
                                            data-id='<?= $editorial["id_Editorial"] ?>' 
                                            data-nombre='<?= htmlspecialchars($editorial["Nombre"]) ?>'
                                            data-direccion='<?= htmlspecialchars($editorial["Direccion"]) ?>'
                                            data-email='<?= htmlspecialchars($editorial["Email"]) ?>'
                                            data-telefono='<?= htmlspecialchars($editorial["Telefono"]) ?>'>
                                        Editar
                                    </button>
                                    <button class='btn btn-danger btn-eliminar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#eliminarEditorialModal' 
                                            data-id='<?= $editorial["id_Editorial"] ?>'>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='5'>No se encontraron editoriales</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Agregar Editorial -->
    <div class="modal fade" id="agregarEditorialModal" tabindex="-1" aria-labelledby="agregarEditorialModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarEditorialModalLabel">Agregar Nueva Editorial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="EditorialI.php">
                        <div class="mb-3">
                            <label for="nuevo_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nuevo_nombre" name="nuevo_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="nueva_direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="nueva_direccion" name="nueva_direccion" required>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="nuevo_email" name="nuevo_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="nuevo_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="nuevo_telefono" name="nuevo_telefono" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom-modal">Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Editorial -->
    <div class="modal fade" id="eliminarEditorialModal" tabindex="-1" aria-labelledby="eliminarEditorialModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarEditorialModalLabel">Eliminar Editorial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar esta editorial?</p>
                    <form method="post" action="EditorialI.php">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <button type="submit" class="btn btn-danger btn-custom-modal">Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Editorial -->
    <div class="modal fade" id="editarEditorialModal" tabindex="-1" aria-labelledby="editarEditorialModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarEditorialModalLabel">Editar Editorial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="EditorialI.php">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <div class="mb-3">
                            <label for="nombre_editar" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre_editar" name="nombre_editar" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion_editar" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion_editar" name="direccion_editar" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_editar" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_editar" name="email_editar" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono_editar" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono_editar" name="telefono_editar" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom-modal">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            var editoriales = <?php echo json_encode($nombresEditoriales); ?>;
            $("#buscar_nombre").autocomplete({
                source: editoriales,
                minLength: 1
            });

            $('#eliminarEditorialModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            $('#editarEditorialModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nombre = button.data('nombre');
                var direccion = button.data('direccion');
                var email = button.data('email');
                var telefono = button.data('telefono');
                var modal = $(this);
                modal.find('#id_editar').val(id);
                modal.find('#nombre_editar').val(nombre);
                modal.find('#direccion_editar').val(direccion);
                modal.find('#email_editar').val(email);
                modal.find('#telefono_editar').val(telefono);
            });

            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>