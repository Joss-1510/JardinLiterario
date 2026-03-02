<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';
require_once 'LibroF.php';

try {
    $libroF = new LibroF($conexion);
    
    // Manejo de sugerencias para autocompletado (AJAX)
    if (isset($_GET['action']) && $_GET['action'] == 'suggest' && isset($_GET['term'])) {
        header('Content-Type: application/json');
        echo json_encode($libroF->obtenerSugerenciasBusqueda($_GET['term']));
        exit();
    }
    
    // Obtener datos para los selectores
    $autores = $libroF->read('tautor');
    $editoriales = $libroF->read('teditorial');
    $generos = $libroF->read('tgenero');
    $sagas = $libroF->read('tsaga');
    
    // Para autocompletado
    $todosLibros = $libroF->obtenerLibrosCompletos();

    // Búsqueda
    $terminoBusqueda = $_GET['buscar'] ?? '';
    if (!empty($terminoBusqueda)) {
        $libros = $libroF->buscarLibros($terminoBusqueda);
    } else {
        $libros = $todosLibros;
    }

    // Operaciones CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Agregar
            if (isset($_POST['nuevo_titulo'])) {
                // Validar que el código no exista
                $existeCodigo = $libroF->read('tlibro', ['Codigo' => $_POST['nuevo_codigo']]);
                if (!empty($existeCodigo)) {
                    throw new Exception("El código de barras ya existe");
                }

                $datosLibro = [
                    'Codigo' => htmlspecialchars(trim($_POST['nuevo_codigo'])),
                    'Titulo' => htmlspecialchars(trim($_POST['nuevo_titulo'])),
                    'idAutor' => (int)$_POST['nuevo_autor'],
                    'idEditorial' => (int)$_POST['nueva_editorial'],
                    'idGenero' => (int)$_POST['nuevo_genero'],
                    'Precio' => (float)$_POST['nuevo_precio'],
                    'Stock' => (int)$_POST['nuevo_stock'],
                    'idSaga' => !empty($_POST['nueva_saga']) ? (int)$_POST['nueva_saga'] : null,
                    'NumeroTomo' => !empty($_POST['nuevo_numero_tomo']) ? (int)$_POST['nuevo_numero_tomo'] : null
                ];
                
                if ($libroF->create('tlibro', $datosLibro)) {
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito',
                        'mensaje' => 'Libro agregado correctamente'
                    ];
                }
            }
            
            // Editar
            if (isset($_POST['id_editar'])) {
                try {
                    $datosActualizar = [
                        'Codigo' => htmlspecialchars(trim($_POST['codigo_editar'])),
                        'Titulo' => htmlspecialchars(trim($_POST['titulo_editar'])),
                        'idAutor' => (int)$_POST['autor_editar'],
                        'idEditorial' => (int)$_POST['editorial_editar'],
                        'idGenero' => (int)$_POST['genero_editar'],
                        'Precio' => (float)$_POST['precio_editar'],
                        'Stock' => (int)$_POST['stock_editar'],
                        'idSaga' => !empty($_POST['saga_editar']) ? (int)$_POST['saga_editar'] : null,
                        'NumeroTomo' => !empty($_POST['numero_tomo_editar']) ? (int)$_POST['numero_tomo_editar'] : null
                    ];
                    
                    if ($libroF->update('tlibro', $datosActualizar, ['id_Libro' => (int)$_POST['id_editar']])) {
                        $_SESSION['alerta'] = [
                            'tipo' => 'success',
                            'titulo' => 'Éxito',
                            'mensaje' => 'Libro actualizado correctamente'
                        ];
                    }
                } catch (Exception $e) {
                    $_SESSION['alerta'] = [
                        'tipo' => 'danger',
                        'titulo' => 'Error',
                        'mensaje' => $e->getMessage()
                    ];
                }
                header("Location: LibroI.php" . (!empty($terminoBusqueda) ? "?buscar=" . urlencode($terminoBusqueda) : ""));
                exit();
            }
            
            // Eliminar
            if (isset($_POST['id_eliminar'])) {
                if ($libroF->delete('tlibro', ['id_Libro' => (int)$_POST['id_eliminar']])) {
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito',
                        'mensaje' => 'Libro eliminado correctamente'
                    ];
                }
                header("Location: LibroI.php" . (!empty($terminoBusqueda) ? "?buscar=" . urlencode($terminoBusqueda) : ""));
                exit();
            }
            
        } catch (Exception $e) {
            $_SESSION['alerta'] = [
                'tipo' => 'danger',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage()
            ];
        }
        header("Location: LibroI.php" . (!empty($terminoBusqueda) ? "?buscar=" . urlencode($terminoBusqueda) : ""));
        exit();
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
    <title>Gestión de Libros</title>
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
        .search-clear {
            margin-left: 5px;
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
            <button class="btn btn-warning mb-3 ms-2" onclick="location.href='LibrosEliminados.php'">Libros Eliminados</button>
            <h3>Libros</h3>
            <button class="btn btn-success mb-3 btn-agregar" data-bs-toggle="modal" data-bs-target="#agregarLibroModal">Agregar Libro</button>
            
            <form method="get" action="LibroI.php" id="form-busqueda">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="buscar_titulo" name="buscar" 
                           placeholder="Buscar por nombre"
                           value="<?= htmlspecialchars($terminoBusqueda) ?>">
                    <button class="btn btn-outline-secondary btn-buscar" type="submit">Buscar</button>
                    <?php if (!empty($terminoBusqueda)): ?>
                        <a href="LibroI.php" class="btn btn-outline-danger search-clear">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Editorial</th>
                            <th>Género</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Saga/Tomo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($libros)): ?>
                            <?php foreach ($libros as $libro): ?>
                                <tr>
                                    <td><?= htmlspecialchars($libro['Codigo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($libro['Titulo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($libro['NombreAutor'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($libro['NombreEditorial'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($libro['NombreGenero'] ?? 'N/A') ?></td>
                                    <td>$<?= isset($libro['Precio']) ? number_format($libro['Precio'], 2) : '0.00' ?></td>
                                    <td><?= $libro['Stock'] ?? 0 ?></td>
                                    <td>
                                        <?php if (!empty($libro['NombreSaga'])): ?>
                                            <?= htmlspecialchars($libro['NombreSaga']) ?>
                                            <?= isset($libro['NumeroTomo']) ? "(Tomo {$libro['NumeroTomo']})" : '' ?>
                                        <?php else: ?>
                                            <span class="text-muted">Independiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-editar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editarLibroModal"
                                                data-id="<?= $libro['id_Libro'] ?>"
                                                data-codigo="<?= htmlspecialchars($libro['Codigo'] ?? '') ?>"
                                                data-titulo="<?= htmlspecialchars($libro['Titulo'] ?? '') ?>"
                                                data-autor="<?= $libro['idAutor'] ?? '' ?>"
                                                data-editorial="<?= $libro['idEditorial'] ?? '' ?>"
                                                data-genero="<?= $libro['idGenero'] ?? '' ?>"
                                                data-precio="<?= $libro['Precio'] ?? 0 ?>"
                                                data-stock="<?= $libro['Stock'] ?? 0 ?>"
                                                data-saga="<?= $libro['idSaga'] ?? '' ?>"
                                                data-numero-tomo="<?= $libro['NumeroTomo'] ?? '' ?>">
                                            Editar
                                        </button>
                                        <button class="btn btn-danger btn-eliminar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#eliminarLibroModal"
                                                data-id="<?= $libro['id_Libro'] ?>">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10">No se encontraron libros<?= !empty($terminoBusqueda) ? " para '".htmlspecialchars($terminoBusqueda)."'" : "" ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Libro -->
    <div class="modal fade" id="agregarLibroModal" tabindex="-1" aria-labelledby="agregarLibroModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarLibroModalLabel">Agregar Nuevo Libro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="LibroI.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Código de Barras <span class="text-danger">*</span></label>
                                <input type="text" name="nuevo_codigo" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" name="nuevo_titulo" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Autor <span class="text-danger">*</span></label>
                                <select name="nuevo_autor" class="form-select" required>
                                    <option value="">Seleccionar autor...</option>
                                    <?php foreach ($autores as $autor): ?>
                                    <option value="<?= $autor['id_Autor'] ?>"><?= htmlspecialchars($autor['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Editorial <span class="text-danger">*</span></label>
                                <select name="nueva_editorial" class="form-select" required>
                                    <option value="">Seleccionar editorial...</option>
                                    <?php foreach ($editoriales as $editorial): ?>
                                    <option value="<?= $editorial['id_Editorial'] ?>"><?= htmlspecialchars($editorial['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Género <span class="text-danger">*</span></label>
                                <select name="nuevo_genero" class="form-select" required>
                                    <option value="">Seleccionar género...</option>
                                    <?php foreach ($generos as $genero): ?>
                                    <option value="<?= $genero['id_Genero'] ?>"><?= htmlspecialchars($genero['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Precio <span class="text-danger">*</span></label>
                                <input type="number" name="nuevo_precio" class="form-control" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Stock <span class="text-danger">*</span></label>
                                <input type="number" name="nuevo_stock" class="form-control" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Saga (opcional)</label>
                                <select name="nueva_saga" class="form-select">
                                    <option value="">Sin saga</option>
                                    <?php foreach ($sagas as $saga): ?>
                                    <option value="<?= $saga['id_Saga'] ?>"><?= htmlspecialchars($saga['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Número de Tomo (opcional)</label>
                                <input type="number" name="nuevo_numero_tomo" class="form-control" min="1">
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

    
        <!-- Modal Eliminar Libro -->
        <div class="modal fade" id="eliminarLibroModal" tabindex="-1" aria-labelledby="eliminarLibroModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eliminarLibroModalLabel">Eliminar Libro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas marcar este libro como eliminado?</p>
                        <p class="text-muted"><small>Esta acción realizará un borrado lógico. El libro no se mostrará en las listas pero permanecerá en la base de datos.</small></p>
                        <form method="post" action="LibroI.php">
                            <input type="hidden" name="id_eliminar" id="id_eliminar">
                            <?php if (!empty($terminoBusqueda)): ?>
                                <input type="hidden" name="buscar" value="<?= htmlspecialchars($terminoBusqueda) ?>">
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger btn-custom-modal">Confirmar Eliminación</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal Editar Libro -->
    <div class="modal fade" id="editarLibroModal" tabindex="-1" aria-labelledby="editarLibroModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarLibroModalLabel">Editar Libro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="LibroI.php">
                        <input type="hidden" name="id_editar" id="edit_id">
                        <?php if (!empty($terminoBusqueda)): ?>
                            <input type="hidden" name="buscar" value="<?= htmlspecialchars($terminoBusqueda) ?>">
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Código de Barras <span class="text-danger">*</span></label>
                                <input type="text" name="codigo_editar" id="edit_codigo" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" name="titulo_editar" id="edit_titulo" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Autor <span class="text-danger">*</span></label>
                                <select name="autor_editar" id="edit_autor" class="form-select" required>
                                    <option value="">Seleccionar autor...</option>
                                    <?php foreach ($autores as $autor): ?>
                                    <option value="<?= $autor['id_Autor'] ?>"><?= htmlspecialchars($autor['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Editorial <span class="text-danger">*</span></label>
                                <select name="editorial_editar" id="edit_editorial" class="form-select" required>
                                    <option value="">Seleccionar editorial...</option>
                                    <?php foreach ($editoriales as $editorial): ?>
                                    <option value="<?= $editorial['id_Editorial'] ?>"><?= htmlspecialchars($editorial['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Género <span class="text-danger">*</span></label>
                                <select name="genero_editar" id="edit_genero" class="form-select" required>
                                    <option value="">Seleccionar género...</option>
                                    <?php foreach ($generos as $genero): ?>
                                    <option value="<?= $genero['id_Genero'] ?>"><?= htmlspecialchars($genero['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Precio <span class="text-danger">*</span></label>
                                <input type="number" name="precio_editar" id="edit_precio" class="form-control" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Stock <span class="text-danger">*</span></label>
                                <input type="number" name="stock_editar" id="edit_stock" class="form-control" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Saga (opcional)</label>
                                <select name="saga_editar" id="edit_saga" class="form-select">
                                    <option value="">Sin saga</option>
                                    <?php foreach ($sagas as $saga): ?>
                                    <option value="<?= $saga['id_Saga'] ?>"><?= htmlspecialchars($saga['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Número de Tomo (opcional)</label>
                                <input type="number" name="numero_tomo_editar" id="edit_numero_tomo" class="form-control" min="1">
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
            // Autocompletado mejorado con AJAX
            $("#buscar_titulo").autocomplete({
                source: function(request, response) {
                    console.log("Solicitando sugerencias para: " + request.term);
                    $.getJSON("LibroI.php?action=suggest&term=" + encodeURIComponent(request.term), function(data) {
                        console.log("Sugerencias recibidas:", data);
                        if(data.length > 0) {
                            response(data);
                        } else {
                            console.log("No se encontraron sugerencias");
                            response([{label: "No se encontraron resultados", value: null}]);
                        }
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    if(ui.item.value) {
                        $(this).val(ui.item.label);
                        $("#form-busqueda").submit();
                    }
                    return false;
                },
                open: function() {
                    $(this).autocomplete("widget").css({
                        "width": $(this).outerWidth() + "px",
                        "z-index": 9999
                    });
                }
            });

            // Configurar modal de eliminación
            $('#eliminarLibroModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                $(this).find('#id_eliminar').val(id);
            });

            // Configurar modal de edición
            $('#editarLibroModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var modal = $(this);
                
                modal.find('#edit_id').val(button.data('id'));
                modal.find('#edit_codigo').val(button.data('codigo'));
                modal.find('#edit_titulo').val(button.data('titulo'));
                modal.find('#edit_autor').val(button.data('autor'));
                modal.find('#edit_editorial').val(button.data('editorial'));
                modal.find('#edit_genero').val(button.data('genero'));
                modal.find('#edit_precio').val(button.data('precio'));
                modal.find('#edit_stock').val(button.data('stock'));
                modal.find('#edit_saga').val(button.data('saga'));
                modal.find('#edit_numero_tomo').val(button.data('numero-tomo'));
            });

            // Cerrar automáticamente las alertas después de 5 segundos
            setTimeout(function() {
                $('.alert-auto-close').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>