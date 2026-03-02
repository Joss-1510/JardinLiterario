<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Conexion.php';
require_once 'UsuarioF.php';

try {
    $usuarioF = new UsuarioF($conexion);
    $roles = $usuarioF->read('trol');
    
    // Para autocompletado
    $todosUsuarios = $usuarioF->obtenerUsuariosCompletos();
    $nombresBusqueda = [];
    foreach ($todosUsuarios as $usuario) {
        $nombresBusqueda[] = $usuario['Nombre'] . ' ' . $usuario['Apellido'];
    }

    // Búsqueda
    $usuarios = [];
    if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])) {
        $usuarios = $usuarioF->buscarUsuarios($_POST['buscar_nombre']);
    } else {
        $usuarios = $todosUsuarios;
    }

    // Operaciones CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Agregar
            if (isset($_POST['nuevo_nombre'])) {
                $personaData = [
                    'Nombre' => htmlspecialchars(trim($_POST['nuevo_nombre'])),
                    'Apellido' => htmlspecialchars(trim($_POST['nuevo_apellido'])),
                    'Email' => htmlspecialchars(trim($_POST['nuevo_email'])),
                    'Telefono' => htmlspecialchars(trim($_POST['nuevo_telefono'] ?? ''))
                ];
                $idPersona = $usuarioF->create('tpersona', $personaData);
                
                $usuarioData = [
                    'Nombre' => htmlspecialchars(trim($_POST['nuevo_usuario'])),
                    'Contra' => trim($_POST['nuevo_password']),
                    'idPersona' => $idPersona,
                    'idRol' => (int)$_POST['nuevo_rol']
                ];
                if ($usuarioF->create('tusuario', $usuarioData)) {
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito',
                        'mensaje' => 'Usuario agregado correctamente'
                    ];
                    header("Location: UsuarioI.php");
                    exit();
                }
            }
            
            // Editar
            if (isset($_POST['id_editar'])) {
                $personaData = [
                    'Nombre' => htmlspecialchars(trim($_POST['nombre_editar'])),
                    'Apellido' => htmlspecialchars(trim($_POST['apellido_editar'])),
                    'Email' => htmlspecialchars(trim($_POST['email_editar'])),
                    'Telefono' => htmlspecialchars(trim($_POST['telefono_editar'] ?? ''))
                ];
                $usuarioActual = $usuarioF->read('tusuario', ['id_Usuario' => (int)$_POST['id_editar']]);
                if (!empty($usuarioActual)) {
                    $idPersona = $usuarioActual[0]['idPersona'];
                    $usuarioF->update('TPersona', $personaData, ['id_Persona' => $idPersona]);
                    $usuarioData = [
                        'Nombre' => htmlspecialchars(trim($_POST['usuario_editar'])),
                        'idRol' => (int)$_POST['rol_editar']
                    ];
                    if (!empty(trim($_POST['password_editar']))) {
                        $usuarioData['Contra'] = trim($_POST['password_editar']);
                    }
                    $usuarioF->update('tusuario', $usuarioData, ['id_Usuario' => (int)$_POST['id_editar']]);
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito',
                        'mensaje' => 'Usuario actualizado correctamente'
                    ];
                    header("Location: UsuarioI.php");
                    exit();
                }
            }
            
            // Eliminar
            if (isset($_POST['id_eliminar'])) {
                $usuarioActual = $usuarioF->read('tusuario', ['id_Usuario' => (int)$_POST['id_eliminar']]);
                if (!empty($usuarioActual)) {
                    $idPersona = $usuarioActual[0]['idPersona'];
            
                    // Marcar el usuario como eliminado
                    $usuarioF->delete('tusuario', ['id_Usuario' => (int)$_POST['id_eliminar']]);
            
                    // En lugar de eliminar la persona, solo marcarla como eliminada
                    if (!empty($idPersona)) {
                        $usuarioF->update('TPersona', ['eliminado' => 1], ['id_Persona' => $idPersona]);
                    }
            
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito',
                        'mensaje' => 'Usuario eliminado correctamente'
                    ];
                    header("Location: UsuarioI.php");
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
    
} catch (PDOException $e) {
    $_SESSION['alerta'] = [
        'tipo' => 'danger',
        'titulo' => 'Error de conexión',
        'mensaje' => $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
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
        .users-section {
            margin-top: 20px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .users-section h3 {
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
            vertical-align: middle;
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
        <div class="users-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">Regresar</button>
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='UsuariosEliminados.php'">Usuarios Eliminados</button>
            <h3>Usuarios</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarUsuarioModal">Agregar Usuario</button>
            
            <form method="post" action="UsuarioI.php">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_nombre" name="buscar_nombre" placeholder="Buscar por nombre">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (isset($_POST['buscar_nombre']) && !empty($_POST['buscar_nombre'])): ?>
                        <a href="UsuarioI.php" class="btn btn-outline-danger">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']) ?></td>
                                    <td><?= htmlspecialchars($usuario['NombreUsuario']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Email']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Telefono']) ?></td>
                                    <td><?= htmlspecialchars($usuario['NombreRol']) ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-editar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editarUsuarioModal"
                                                data-id="<?= $usuario['id_Usuario'] ?>"
                                                data-nombre="<?= htmlspecialchars($usuario['Nombre']) ?>"
                                                data-apellido="<?= htmlspecialchars($usuario['Apellido']) ?>"
                                                data-email="<?= htmlspecialchars($usuario['Email']) ?>"
                                                data-telefono="<?= htmlspecialchars($usuario['Telefono']) ?>"
                                                data-usuario="<?= htmlspecialchars($usuario['NombreUsuario']) ?>"
                                                data-rol="<?= $usuario['idRol'] ?>">
                                            Editar
                                        </button>
                                        <button class="btn btn-danger btn-eliminar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#eliminarUsuarioModal"
                                                data-id="<?= $usuario['id_Usuario'] ?>">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No se encontraron usuarios</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Usuario -->
    <div class="modal fade" id="agregarUsuarioModal" tabindex="-1" aria-labelledby="agregarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarUsuarioModalLabel">Agregar Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="UsuarioI.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nuevo_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="nuevo_apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="nuevo_email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="nuevo_telefono" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                <input type="text" name="nuevo_usuario" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <input type="password" name="nuevo_password" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Rol <span class="text-danger">*</span></label>
                                <select name="nuevo_rol" class="form-select" required>
                                    <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id_Rol'] ?>"><?= htmlspecialchars($rol['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-custom-modal">Agregar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Usuario -->
    <div class="modal fade" id="eliminarUsuarioModal" tabindex="-1" aria-labelledby="eliminarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarUsuarioModalLabel">Eliminar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas marcar este usuario como eliminado?</p>
                    <p class="text-muted"><small>Esta acción realizará un borrado lógico. El usuario no se mostrará en las listas pero permanecerá en la base de datos.</small></p>
                    <form method="post" action="UsuarioI.php">
                        <input type="hidden" name="id_eliminar" id="id_eliminar">
                        <button type="submit" class="btn btn-danger btn-custom-modal">Confirmar Eliminación</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="UsuarioI.php">
                        <input type="hidden" name="id_editar" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_editar" id="edit_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido_editar" id="edit_apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email_editar" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono_editar" id="edit_telefono" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                <input type="text" name="usuario_editar" id="edit_usuario" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="password_editar" class="form-control" placeholder="Dejar vacío para no cambiar">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Rol <span class="text-danger">*</span></label>
                                <select name="rol_editar" id="edit_rol" class="form-select" required>
                                    <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id_Rol'] ?>"><?= htmlspecialchars($rol['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-custom-modal">Guardar Cambios</button>
                        </div>
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
            var nombres = <?php echo json_encode($nombresBusqueda); ?>;
            $("#buscar_nombre").autocomplete({
                source: nombres,
                minLength: 1,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    $(this).closest('form').submit();
                }
            });

            // Configurar modal de eliminación
            $('#eliminarUsuarioModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            // Configurar modal de edición
            $('#editarUsuarioModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var modal = $(this);
                modal.find('#edit_id').val(button.data('id'));
                modal.find('#edit_nombre').val(button.data('nombre'));
                modal.find('#edit_apellido').val(button.data('apellido'));
                modal.find('#edit_email').val(button.data('email'));
                modal.find('#edit_telefono').val(button.data('telefono'));
                modal.find('#edit_usuario').val(button.data('usuario'));
                modal.find('#edit_rol').val(button.data('rol'));
            });

            // Cerrar automáticamente las alertas después de 5 segundos
            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>