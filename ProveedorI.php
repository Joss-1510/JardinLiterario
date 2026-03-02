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
include 'ProveedorF.php';

$crud = new ProveedorF($conexion);

$todosProveedores = $crud->read('tproveedor');
$nombresProveedores = array_column($todosProveedores, 'Nombre');

// Inicializar variable de proveedores
$proveedores = $todosProveedores;

// Manejo de operaciones POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['buscar_nombre'])) {
            $nombre = trim($_POST['buscar_nombre']);
            $proveedores = $crud->read('tproveedor', ['Nombre' => $nombre]);
        } elseif (isset($_POST['nuevo_nombre'])) {
            $datosProveedor = [
                'Nombre' => trim($_POST['nuevo_nombre']),
                'Telefono' => trim($_POST['nuevo_telefono']),
                'Email' => trim($_POST['nuevo_email']),
                'Direccion' => trim($_POST['nueva_direccion']),
                'RFC' => trim($_POST['nuevo_rfc'])
            ];
            
            if (empty($datosProveedor['Nombre'])) {
                throw new Exception("El nombre del proveedor no puede estar vacío");
            }
            
            if ($crud->create('tproveedor', $datosProveedor)) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Proveedor agregado correctamente'
                ];
                header("Location: ProveedorI.php");
                exit();
            } else {
                throw new Exception('Error al agregar el proveedor');
            }
        } elseif (isset($_POST['id_eliminar'])) {
            $id_eliminar = (int)$_POST['id_eliminar'];
            if ($crud->delete('tproveedor', ['id_Proveedor' => $id_eliminar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Proveedor eliminado correctamente'
                ];
                header("Location: ProveedorI.php");
                exit();
            } else {
                throw new Exception('Error al eliminar el proveedor');
            }
        } elseif (isset($_POST['id_editar'])) {
            $id_editar = (int)$_POST['id_editar'];
            $datosActualizar = [
                'Nombre' => trim($_POST['nombre_editar']),
                'Telefono' => trim($_POST['telefono_editar']),
                'Email' => trim($_POST['email_editar']),
                'Direccion' => trim($_POST['direccion_editar']),
                'RFC' => trim($_POST['rfc_editar'])
            ];
            
            if (empty($datosActualizar['Nombre'])) {
                throw new Exception("El nombre del proveedor no puede estar vacío");
            }
            
            if ($crud->update('tproveedor', $datosActualizar, ['id_Proveedor' => $id_editar])) {
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Éxito',
                    'mensaje' => 'Proveedor actualizado correctamente'
                ];
                header("Location: ProveedorI.php");
                exit();
            } else {
                throw new Exception('Error al actualizar el proveedor');
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
    <title>Gestión de Proveedores</title>
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
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='ProveedoresEliminados.php'">Proveedores Eliminados</button>
            <h3>Proveedores</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarProveedorModal">Agregar Proveedor</button>
            
            <form method="post" action="ProveedorI.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" placeholder="Buscar por nombre">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])): ?>
                        <a href="ProveedorI.php" class="btn btn-outline-danger">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>RFC</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proveedores)): ?>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td><?= htmlspecialchars($proveedor['Nombre']) ?></td>
                                <td><?= htmlspecialchars($proveedor['Telefono']) ?></td>
                                <td><?= htmlspecialchars($proveedor['Email']) ?></td>
                                <td><?= htmlspecialchars($proveedor['Direccion']) ?></td>
                                <td><?= htmlspecialchars($proveedor['RFC']) ?></td>
                                <td>
                                    <button class='btn btn-primary btn-editar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editarProveedorModal' 
                                            data-id='<?= $proveedor["id_Proveedor"] ?>' 
                                            data-nombre='<?= htmlspecialchars($proveedor["Nombre"]) ?>'
                                            data-telefono='<?= htmlspecialchars($proveedor["Telefono"]) ?>'
                                            data-email='<?= htmlspecialchars($proveedor["Email"]) ?>'
                                            data-direccion='<?= htmlspecialchars($proveedor["Direccion"]) ?>'
                                            data-rfc='<?= htmlspecialchars($proveedor["RFC"]) ?>'>
                                        Editar
                                    </button>
                                    <button class='btn btn-danger btn-eliminar' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#eliminarProveedorModal' 
                                            data-id='<?= $proveedor["id_Proveedor"] ?>'>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='6'>No se encontraron proveedores</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Agregar Proveedor -->
    <div class="modal fade" id="agregarProveedorModal" tabindex="-1" aria-labelledby="agregarProveedorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarProveedorModalLabel">Agregar Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="ProveedorI.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nuevo_nombre" class="form-label">Nombre*</label>
                                <input type="text" class="form-control" id="nuevo_nombre" name="nuevo_nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nuevo_telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="nuevo_telefono" name="nuevo_telefono">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nuevo_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="nuevo_email" name="nuevo_email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nuevo_rfc" class="form-label">RFC</label>
                                <input type="text" class="form-control" id="nuevo_rfc" name="nuevo_rfc">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nueva_direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="nueva_direccion" name="nueva_direccion" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom-modal">Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Proveedor -->
    <div class="modal fade" id="eliminarProveedorModal" tabindex="-1" aria-labelledby="eliminarProveedorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarProveedorModalLabel">Eliminar Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar este proveedor?</p>
                    <form method="post" action="ProveedorI.php">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <button type="submit" class="btn btn-danger btn-custom-modal">Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Proveedor -->
    <div class="modal fade" id="editarProveedorModal" tabindex="-1" aria-labelledby="editarProveedorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarProveedorModalLabel">Editar Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="ProveedorI.php">
                        <input type="hidden" name="id_editar" id="id_editar">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_editar" class="form-label">Nombre*</label>
                                <input type="text" class="form-control" id="nombre_editar" name="nombre_editar" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono_editar" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono_editar" name="telefono_editar">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email_editar" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email_editar" name="email_editar">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="rfc_editar" class="form-label">RFC</label>
                                <input type="text" class="form-control" id="rfc_editar" name="rfc_editar">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="direccion_editar" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion_editar" name="direccion_editar" rows="3"></textarea>
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
            var proveedores = <?php echo json_encode($nombresProveedores); ?>;
            
            $("#buscar_nombre").autocomplete({
                source: proveedores,
                minLength: 1,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    $(this).closest('form').submit();
                }
            });

            // Configurar modal de eliminación
            $('#eliminarProveedorModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            // Configurar modal de edición
            $('#editarProveedorModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var nombre = button.data('nombre');
                var telefono = button.data('telefono');
                var email = button.data('email');
                var direccion = button.data('direccion');
                var rfc = button.data('rfc');
                
                var modal = $(this);
                modal.find('#id_editar').val(id);
                modal.find('#nombre_editar').val(nombre);
                modal.find('#telefono_editar').val(telefono);
                modal.find('#email_editar').val(email);
                modal.find('#direccion_editar').val(direccion);
                modal.find('#rfc_editar').val(rfc);
            });

            // Cerrar automáticamente las alertas después de 5 segundos
            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>