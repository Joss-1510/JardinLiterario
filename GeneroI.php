<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

include 'Conexion.php';
include 'CRUD.php';
include 'GeneroF.php';

$crud = new GeneroF($conexion);

$todosGeneros = $crud->read('tgenero');
$nombresGeneros = array_column($todosGeneros, 'Nombre');

// Inicializar variable de géneros
$generos = $todosGeneros;

// Manejo de operaciones POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['buscar_nombre'])) {
            $nombre = trim($_POST['buscar_nombre']);
            $generos = $crud->read('tgenero', ['Nombre' => $nombre]);
        } elseif (isset($_POST['nuevo_nombre'])) {
            $nuevo_nombre = trim($_POST['nuevo_nombre']);
            $descripcion = trim($_POST['nueva_descripcion']);
            
            if (empty($nuevo_nombre)) {
                throw new Exception("El nombre del género no puede estar vacío");
            }
            
            $datosGenero = [
                'Nombre' => $nuevo_nombre,
                'Descripcion' => $descripcion
            ];
            
            if ($crud->create('tgenero', $datosGenero)) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Género agregado correctamente'
                ];
                header("Location: GeneroI.php");
                exit();
            } else {
                throw new Exception('Error al agregar el género');
            }
        } elseif (isset($_POST['id_eliminar'])) {
            $id_eliminar = (int)$_POST['id_eliminar'];
            if ($crud->delete('tgenero', ['id_Genero' => $id_eliminar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Género eliminado correctamente'
                ];
                header("Location: GeneroI.php");
                exit();
            } else {
                throw new Exception('Error al eliminar el género');
            }
        } elseif (isset($_POST['id_editar']) && isset($_POST['nombre_editar'])) {
            $id_editar = (int)$_POST['id_editar'];
            $nombre_editar = trim($_POST['nombre_editar']);
            $descripcion_editar = trim($_POST['descripcion_editar']);
            
            if (empty($nombre_editar)) {
                throw new Exception("El nombre del género no puede estar vacío");
            }
            
            $datosActualizar = [
                'Nombre' => $nombre_editar,
                'Descripcion' => $descripcion_editar
            ];
            
            if ($crud->update('tgenero', $datosActualizar, ['id_Genero' => $id_editar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Género actualizado correctamente'
                ];
                header("Location: GeneroI.php");
                exit();
            } else {
                throw new Exception('Error al actualizar el género');
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
    <title>Gestión de Géneros</title>
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
    <!-- Contenedor de alertas -->
    <?php if (isset($_SESSION['alerta'])): ?>
    <div class="alert alert-<?= $_SESSION['alerta']['tipo'] ?> alert-dismissible fade show alert-auto-close" role="alert">
        <strong><?= $_SESSION['alerta']['titulo'] ?>:</strong> <?= $_SESSION['alerta']['mensaje'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['alerta']); endif; ?>

    <div class="container-main">
        <div class="books-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">Regresar</button>
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='GenerosEliminados.php'">Generos Eliminados</button>

            <h3>Géneros Literarios</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarGeneroModal">Agregar Género</button>
            
            <form method="post" action="GeneroI.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" placeholder="Buscar por nombre">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])): ?>
                        <a href="GeneroI.php" class="btn btn-outline-danger">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($generos)): ?>
                        <?php foreach ($generos as $genero): ?>
                            <tr>
                                <td><?= htmlspecialchars($genero['Nombre']) ?></td>
                                <td><?= htmlspecialchars($genero['Descripcion']) ?></td>
                                <td>
                                    <button class='btn btn-primary btn-editar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editarGeneroModal' 
                                            data-id='<?= $genero["id_Genero"] ?>' 
                                            data-nombre='<?= htmlspecialchars($genero["Nombre"]) ?>'
                                            data-descripcion='<?= htmlspecialchars($genero["Descripcion"]) ?>'>
                                        Editar
                                    </button>
                                    <button class='btn btn-danger btn-eliminar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#eliminarGeneroModal' 
                                            data-id='<?= $genero["id_Genero"] ?>'>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='3'>No se encontraron géneros</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Agregar Género -->
    <div class="modal fade" id="agregarGeneroModal" tabindex="-1" aria-labelledby="agregarGeneroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarGeneroModalLabel">Agregar Nuevo Género</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="GeneroI.php">
                        <div class="mb-3">
                            <label for="nuevo_nombre" class="form-label">Nombre del Género</label>
                            <input type="text" class="form-control" id="nuevo_nombre" name="nuevo_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="nueva_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="nueva_descripcion" name="nueva_descripcion" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom-modal">Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Género -->
    <div class="modal fade" id="eliminarGeneroModal" tabindex="-1" aria-labelledby="eliminarGeneroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarGeneroModalLabel">Eliminar Género</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar este género?</p>
                    <form method="post" action="GeneroI.php">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <button type="submit" class="btn btn-danger btn-custom-modal">Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Género -->
    <div class="modal fade" id="editarGeneroModal" tabindex="-1" aria-labelledby="editarGeneroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarGeneroModalLabel">Editar Género</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="GeneroI.php">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <div class="mb-3">
                            <label for="nombre_editar" class="form-label">Nombre del Género</label>
                            <input type="text" class="form-control" id="nombre_editar" name="nombre_editar" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion_editar" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion_editar" name="descripcion_editar" rows="3"></textarea>
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
            // Autocompletado para el campo de búsqueda
            var generos = <?php echo json_encode($nombresGeneros); ?>;
            
            $("#buscar_nombre").autocomplete({
                source: generos,
                minLength: 1,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    $(this).closest('form').submit();
                }
            });

            // Configurar modal de eliminación
            $('#eliminarGeneroModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            // Configurar modal de edición
            $('#editarGeneroModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nombre = button.data('nombre');
                var descripcion = button.data('descripcion');
                
                var modal = $(this);
                modal.find('#id_editar').val(id);
                modal.find('#nombre_editar').val(nombre);
                modal.find('#descripcion_editar').val(descripcion);
            });

            // Cerrar automáticamente las alertas después de 5 segundos
            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>