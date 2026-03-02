<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'Conexion.php';
include 'CRUD.php';
include 'ClienteF.php';

$crud = new ClienteF($conexion);

$todosClientes = $crud->read('tcliente');
$nombresClientes = array_column($todosClientes, 'Nombre');

// Inicializar variable de clientes
$clientes = $todosClientes;

// Manejo de operaciones POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['buscar_nombre'])) {
            $nombre = trim($_POST['buscar_nombre']);
            $clientes = $crud->read('tcliente', ['Nombre' => $nombre]);
        } elseif (isset($_POST['nuevo_nombre'])) {
            $nuevo_nombre = trim($_POST['nuevo_nombre']);
            
            if (empty($nuevo_nombre)) {
                throw new Exception("El nombre del cliente no puede estar vacío");
            }
            
            if ($crud->create('tcliente', ['Nombre' => $nuevo_nombre])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Cliente agregado correctamente'
                ];
                header("Location: ClienteI.php");
                exit();
            } else {
                throw new Exception('Error al agregar el cliente');
            }
        } elseif (isset($_POST['id_eliminar'])) {
            $id_eliminar = (int)$_POST['id_eliminar'];
            if ($crud->delete('tcliente', ['id_Cliente' => $id_eliminar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Cliente eliminado correctamente'
                ];
                header("Location: ClienteI.php");
                exit();
            } else {
                throw new Exception('Error al eliminar el cliente');
            }
        } elseif (isset($_POST['id_editar']) && isset($_POST['nombre_editar'])) {
            $id_editar = (int)$_POST['id_editar'];
            $nombre_editar = trim($_POST['nombre_editar']);
            
            if (empty($nombre_editar)) {
                throw new Exception("El nombre del cliente no puede estar vacío");
            }
            
            if ($crud->update('tcliente', ['Nombre' => $nombre_editar], ['id_Cliente' => $id_editar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Cliente actualizado correctamente'
                ];
                header("Location: ClienteI.php");
                exit();
            } else {
                throw new Exception('Error al actualizar el cliente');
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
    <title>Gestión de Clientes</title>
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
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='ClientesEliminados.php'">Clientes Eliminados</button>
            <h3>Clientes</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarClienteModal">Agregar Cliente</button>
            
            <form method="post" action="ClienteI.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" placeholder="Buscar por nombre">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])): ?>
                        <a href="ClienteI.php" class="btn btn-outline-danger">Limpiar</a>
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
                    <?php if (!empty($clientes)): ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['Nombre']) ?></td>
                                <td>
                                    <button class='btn btn-primary btn-editar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editarClienteModal' 
                                            data-id='<?= $cliente["id_Cliente"] ?>' 
                                            data-nombre='<?= htmlspecialchars($cliente["Nombre"]) ?>'>
                                        Editar
                                    </button>
                                    <button class='btn btn-danger btn-eliminar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#eliminarClienteModal' 
                                            data-id='<?= $cliente["id_Cliente"] ?>'>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='2'>No se encontraron clientes</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Agregar Cliente -->
    <div class="modal fade" id="agregarClienteModal" tabindex="-1" aria-labelledby="agregarClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarClienteModalLabel">Agregar Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="ClienteI.php">
                        <div class="mb-3">
                            <label for="nuevo_nombre" class="form-label">Nombre del Cliente</label>
                            <input type="text" class="form-control" id="nuevo_nombre" name="nuevo_nombre" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom-modal">Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Cliente -->
    <div class="modal fade" id="eliminarClienteModal" tabindex="-1" aria-labelledby="eliminarClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarClienteModalLabel">Eliminar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar este cliente?</p>
                    <form method="post" action="ClienteI.php">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <button type="submit" class="btn btn-danger btn-custom-modal">Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Cliente -->
    <div class="modal fade" id="editarClienteModal" tabindex="-1" aria-labelledby="editarClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarClienteModalLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="ClienteI.php">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <div class="mb-3">
                            <label for="nombre_editar" class="form-label">Nombre del Cliente</label>
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
            var clientes = <?php echo json_encode($nombresClientes); ?>;
            
            $("#buscar_nombre").autocomplete({
                source: clientes,
                minLength: 1,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    $(this).closest('form').submit();
                }
            });

            // Configurar modal de eliminación
            $('#eliminarClienteModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            // Configurar modal de edición
            $('#editarClienteModal').on('show.bs.modal', function (event) {
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
        });
    </script>
</body>
</html>