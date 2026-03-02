<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';
require_once 'CRUD.php';
require_once 'AutorF.php';

$crud = new AutorF($conexion);

// Obtener listado de autores no eliminados
$autores = $crud->read('tautor');
$nombresAutores = array_column($autores, 'Nombre');

// Manejo de operaciones POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['nuevo_nombre'])) {
            $nuevo_nombre = trim($_POST['nuevo_nombre']);
            if (empty($nuevo_nombre)) {
                throw new Exception("El nombre del autor no puede estar vacío");
            }
            
            // Verificar si el autor ya existe
            $autor_existente = $crud->read('tautor', ['Nombre' => $nuevo_nombre]);
            if (!empty($autor_existente)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    // Respuesta para AJAX
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Este autor ya está registrado']);
                    exit();
                } else {
                    // Respuesta para submit normal
                    $_SESSION['alerta'] = [
                        'tipo' => 'warning',
                        'titulo' => 'Advertencia',
                        'mensaje' => 'Este autor ya está registrado'
                    ];
                    header("Location: AutorI.php");
                    exit();
                }
            }
            
            if ($crud->create('tautor', ['Nombre' => $nuevo_nombre])) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    // Respuesta para AJAX
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Autor agregado correctamente']);
                    exit();
                } else {
                    // Respuesta para submit normal
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito',
                        'mensaje' => 'Autor agregado correctamente'
                    ];
                    header("Location: AutorI.php");
                    exit();
                }
            }
        } 
        elseif (isset($_POST['id_eliminar'])) {
            $id_eliminar = (int)$_POST['id_eliminar'];
            if ($crud->delete('tautor', ['id_Autor' => $id_eliminar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Autor marcado como eliminado correctamente'
                ];
                header("Location: AutorI.php");
                exit();
            }
        }
        elseif (isset($_POST['id_editar']) && isset($_POST['nombre_editar'])) {
            $id_editar = (int)$_POST['id_editar'];
            $nombre_editar = trim($_POST['nombre_editar']);
            
            if (empty($nombre_editar)) {
                throw new Exception("El nombre del autor no puede estar vacío");
            }
            
            if ($crud->update('tautor', ['Nombre' => $nombre_editar], ['id_Autor' => $id_editar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Autor actualizado correctamente'
                ];
                header("Location: AutorI.php");
                exit();
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

// Búsqueda por nombre
if (isset($_POST['buscar_nombre'])) {
    $nombre = trim($_POST['buscar_nombre']);
    $autores = $crud->read('tautor', ['Nombre' => $nombre]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Autores</title>
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
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='AutoresEliminados.php'">Autores Eliminados</button>
            
            <h3>Autores</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarAutorModal">Agregar Autor</button>
            
            <form method="post" action="AutorI.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" placeholder="Buscar por nombre">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])): ?>
                        <a href="AutorI.php" class="btn btn-outline-danger">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($autores)): ?>
                        <?php foreach ($autores as $autor): ?>
                            <tr>
                                <td><?= htmlspecialchars($autor['Nombre']) ?></td>
                                <td>
                                    <button class='btn btn-primary btn-editar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editarAutorModal' 
                                            data-id='<?= $autor["id_Autor"] ?>' 
                                            data-nombre='<?= htmlspecialchars($autor["Nombre"]) ?>'>
                                        Editar
                                    </button>
                                    <button class='btn btn-danger btn-eliminar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#eliminarAutorModal' 
                                            data-id='<?= $autor["id_Autor"] ?>'>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='2'>No se encontraron autores</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Agregar Autor -->
    <div class="modal fade" id="agregarAutorModal" tabindex="-1" aria-labelledby="agregarAutorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarAutorModalLabel">Agregar Nuevo Autor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formAgregarAutor" method="post">
                        <div class="mb-3">
                            <label for="nuevo_nombre" class="form-label">Nombre del Autor</label>
                            <input type="text" class="form-control" id="nuevo_nombre" name="nuevo_nombre" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom-modal">Agregar</button>
                    </form>
                    <div id="mensajeError" class="alert alert-warning mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Autor -->
    <div class="modal fade" id="eliminarAutorModal" tabindex="-1" aria-labelledby="eliminarAutorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarAutorModalLabel">Eliminar Autor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas marcar este autor como eliminado?</p>
                    <p class="text-muted"><small>Esta acción realizará un borrado lógico. El autor no se mostrará en las listas pero permanecerá en la base de datos.</small></p>
                    <form method="post" action="AutorI.php">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <button type="submit" class="btn btn-danger btn-custom-modal">Confirmar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Autor -->
    <div class="modal fade" id="editarAutorModal" tabindex="-1" aria-labelledby="editarAutorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarAutorModalLabel">Editar Autor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="AutorI.php">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <div class="mb-3">
                            <label for="nombre_editar" class="form-label">Nombre del Autor</label>
                            <input type="text" class="form-control" id="nombre_editar" name="nombre_editar" required>
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
            var autores = <?php echo json_encode($nombresAutores); ?>;
            
            $("#buscar_nombre").autocomplete({
                source: autores,
                minLength: 1,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    $(this).closest('form').submit();
                }
            });

            // Configurar modal de eliminación
            $('#eliminarAutorModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            // Configurar modal de edición
            $('#editarAutorModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nombre = button.data('nombre');
                
                var modal = $(this);
                modal.find('#id_editar').val(id);
                modal.find('#nombre_editar').val(nombre);
            });

            // Cerrar automáticamente las alertas después de 5 segundos
            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);

            // Manejar el envío del formulario con AJAX
            $('#formAgregarAutor').on('submit', function(e) {
                e.preventDefault();
                $('#mensajeError').addClass('d-none');
                
                $.ajax({
                    url: 'AutorI.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#agregarAutorModal').modal('hide');
                            location.reload();
                        } else {
                            $('#mensajeError').removeClass('d-none').text(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#mensajeError').removeClass('d-none').text('Error al procesar la solicitud');
                    }
                });
            });
            
            // Limpiar mensaje de error cuando se abre el modal
            $('#agregarAutorModal').on('show.bs.modal', function() {
                $('#mensajeError').addClass('d-none').text('');
                $('#formAgregarAutor')[0].reset();
            });
        });
    </script>
</body>
</html>