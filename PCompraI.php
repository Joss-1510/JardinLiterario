<?php
session_start();

// Validación de sesión
if (!isset($_SESSION['SISTEMA']) || !is_array($_SESSION['SISTEMA']) || !isset($_SESSION['SISTEMA']['id'])) {
    $_SESSION['error'] = "Por favor inicie sesión nuevamente";
    header("Location: Login.php");
    exit();
}

require_once 'Conexion.php';
require_once 'PCompraF.php';

// Obtener listado de libros activos
$libros = [];
try {
    $stmt = $GLOBALS['conexion']->query("
        SELECT id_Libro, Codigo, Titulo, Precio 
        FROM tlibro 
        WHERE Stock IS NOT NULL
        ORDER BY Titulo
    ");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error al obtener libros: " . $e->getMessage());
    $libros = [];
}

// Obtener listado de proveedores activos
$proveedores = [];
try {
    $stmt = $GLOBALS['conexion']->query("
        SELECT id_Proveedor, Nombre 
        FROM tproveedor 
        ORDER BY Nombre
    ");
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error al obtener proveedores: " . $e->getMessage());
    $proveedores = [];
}

// Obtener folio inicial
$folioActual = "";
try {
    $compraManager = new PCompraF($GLOBALS['conexion']);
    $folioData = $compraManager->generarFolio();
    if ($folioData['success']) {
        $folioActual = $folioData['folio'];
    } else {
        throw new Exception($folioData['error']);
    }
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error al obtener folio inicial: " . $e->getMessage());
    $folioActual = "000001";
    mostrarAlerta("Error al generar folio inicial: " . $e->getMessage(), "error");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Compras - Librería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <style>
        body { background-color: #4b6043; font-family: 'Roboto', sans-serif; }
        .container-main { padding: 20px; }
        .purchase-section { 
            margin-top: 20px; 
            background-color: #ffffff; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); 
        }
        .purchase-section h3 { 
            text-align: center; 
            font-size: 3rem; 
            color: #000000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .btn-regresar { 
            background-color: #47b36e; 
            border-color: #47b36e; 
            color: #ffffff; 
        }
        .btn-regresar:hover { 
            background-color: #3a8e57; 
            border-color: #3a8e57; 
        }
        .btn-eliminar { 
            background-color: #ff3232; 
            border-color: #ff3232; 
            color: #ffffff; 
        }
        .btn-eliminar:hover { 
            background-color: #e60000; 
            border-color: #e60000; 
        }
        .btn-finalizar { 
            background-color: #4caf50; 
            border-color: #4caf50; 
            color: #ffffff; 
        }
        .btn-finalizar:hover { 
            background-color: #3e8e41; 
            border-color: #3e8e41; 
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
        .total-display { 
            font-size: 1.5rem; 
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .spin { 
            animation: spin 1s linear infinite; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        @media (max-width: 768px) { 
            .container-main { padding: 10px; } 
            .purchase-section h3 { font-size: 2rem; }
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 10000 !important;
        }
        .search-container { position: relative; }
        .search-icon { 
            position: absolute; 
            right: 10px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #6c757d;
        }
        .card-header {
            background-color: #3a4d34 !important;
            color: white !important;
        }
        .producto-agregado {
            animation: highlight 1.5s;
        }
        @keyframes highlight {
            0% { background-color: #d4edda; }
            100% { background-color: transparent; }
        }
        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="purchase-section">
            <button class="btn btn-regresar mb-3" onclick="location.href='Inicio.php'">
                <i class="bi bi-arrow-left"></i> Regresar
            </button>
            <h3>Registro de Compras</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Proveedor *</label>
                    <select class="form-select" id="proveedor-select" required>
                        <option value="" selected disabled>Seleccionar proveedor...</option>
                        <?php foreach($proveedores as $proveedor): ?>
                            <option value="<?= htmlspecialchars($proveedor['id_Proveedor']) ?>">
                                <?= htmlspecialchars($proveedor['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 search-container">
                    <label class="form-label fw-bold">Código producto</label>
                    <input type="text" class="form-control" id="codigo-libro" placeholder="Buscar por código">
                    <i class="bi bi-search search-icon"></i>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Cantidad *</label>
                    <input type="number" class="form-control" id="cantidad-producto" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Precio compra *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="precio-producto" step="0.01" min="0.01" required>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-9">
                    <label class="form-label fw-bold">Producto *</label>
                    <select class="form-select" id="select-producto" required>
                        <option value="" selected disabled>Seleccionar libro...</option>
                        <?php foreach($libros as $libro): ?>
                            <option value="<?= htmlspecialchars($libro['id_Libro']) ?>" 
                                    data-codigo="<?= htmlspecialchars($libro['Codigo']) ?>"
                                    data-precio="<?= htmlspecialchars($libro['Precio']) ?>">
                                <?= htmlspecialchars($libro['Titulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="agregar-producto">
                        <i class="bi bi-plus-circle"></i> Agregar
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="selected-products-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="120">Código</th>
                                    <th>Producto</th>
                                    <th width="120">P. Compra</th>
                                    <th width="100">Cantidad</th>
                                    <th width="120">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-card-checklist"></i> Resumen de Compra</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Folio:</label>
                                <p id="folio-resumen" class="fw-bold"><?= htmlspecialchars($folioActual) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Proveedor:</label>
                                <p id="proveedor-seleccionado" class="fw-bold text-muted">No seleccionado</p>
                            </div>
                            
                            <div class="mt-4">
                                <div class="d-flex justify-content-between align-items-center total-display">
                                    <strong>TOTAL:</strong>
                                    <strong class="text-primary" id="total">$0.00</strong>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <button class="btn btn-danger" id="cancel-purchase">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </button>
                                <button class="btn btn-finalizar" id="complete-purchase" disabled>
                                    <i class="bi bi-check-circle"></i> Registrar Compra
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script>
        const productosCompra = [];
        let folioActual = "<?= htmlspecialchars($folioActual) ?>";
        let compraEnProceso = false;
        const librosDisponibles = <?= json_encode($libros) ?>;
        
        $(document).ready(function() {
            // Inicialización
            $('#folio-resumen').text(folioActual);
            
            // Evento cambio de proveedor
            $('#proveedor-select').change(function() {
                const proveedor = $(this).find('option:selected').text();
                $('#proveedor-seleccionado').text(proveedor || 'No seleccionado')
                    .toggleClass('text-muted', !proveedor);
            }).trigger('change');
            
            // Autocompletado para búsqueda por código
            $('#codigo-libro').autocomplete({
                source: function(request, response) {
                    const term = request.term.toLowerCase();
                    const matches = librosDisponibles.filter(libro => 
                        libro.Codigo && libro.Codigo.toLowerCase().includes(term)
                    );
                    response(matches.map(libro => ({
                        label: `${libro.Codigo} - ${libro.Titulo}`,
                        value: libro.Codigo,
                        libro: libro
                    })));
                },
                select: function(event, ui) {
                    $('#select-producto').val(ui.item.libro.id_Libro).trigger('change');
                    $('#precio-producto').val(parseFloat(ui.item.libro.Precio).toFixed(2));
                    $('#cantidad-producto').focus();
                    $(this).val('');
                    return false;
                },
                minLength: 1,
                delay: 300
            });
            
            // Búsqueda por código con Enter
            $('#codigo-libro').keypress(function(e) {
                if(e.which === 13) {
                    const codigo = $(this).val().trim();
                    if(codigo) {
                        const libro = librosDisponibles.find(l => 
                            l.Codigo && l.Codigo.toLowerCase() === codigo.toLowerCase()
                        );
                        if(libro) {
                            $('#select-producto').val(libro.id_Libro).trigger('change');
                            $('#precio-producto').val(parseFloat(libro.Precio).toFixed(2));
                            $('#cantidad-producto').focus();
                            $(this).val('');
                        } else {
                            mostrarAlerta('No se encontró un libro con ese código', 'warning');
                        }
                    }
                    return false;
                }
            });
            
            // Sugerir precio al seleccionar producto
            $('#select-producto').change(function() {
                const precioActual = $('#precio-producto').val();
                if (!precioActual || parseFloat(precioActual) <= 0) {
                    const libroId = $(this).val();
                    if (libroId) {
                        const libro = librosDisponibles.find(l => l.id_Libro == libroId);
                        if (libro) {
                            $('#precio-producto').val(parseFloat(libro.Precio).toFixed(2));
                        }
                    }
                }
                $('#cantidad-producto').focus();
            });
            
            // Agregar producto con click o enter
            $('#agregar-producto').click(agregarProductoSeleccionado);
            $('#precio-producto').keypress(function(e) {
                if(e.which === 13) {
                    agregarProductoSeleccionado();
                    return false;
                }
            });
            
            // Eventos para acciones
            $(document).on('click', '.btn-eliminar', eliminarProducto);
            $('#cancel-purchase').click(cancelarCompra);
            $('#complete-purchase').click(finalizarCompra);
            
            // Enfoque inicial
            $('#proveedor-select').focus();
        });

        function agregarProductoSeleccionado() {
            // Validar campos requeridos
            if (!$('#proveedor-select').val()) {
                mostrarAlerta('Seleccione un proveedor', 'warning');
                $('#proveedor-select').focus();
                return;
            }
            
            const select = $('#select-producto');
            const option = select.find('option:selected');
            const idLibro = option.val();
            
            if(!idLibro) {
                mostrarAlerta('Seleccione un producto', 'warning');
                select.focus();
                return;
            }
            
            const cantidad = parseInt($('#cantidad-producto').val()) || 1;
            const precio = parseFloat($('#precio-producto').val());
            const codigo = option.data('codigo');
            const nombre = option.text();
            
            if(isNaN(precio) || precio <= 0) {
                mostrarAlerta('Ingrese un precio válido', 'warning');
                $('#precio-producto').focus();
                return;
            }
            
            const producto = {
                id: idLibro,
                codigo: codigo,
                nombre: nombre,
                precio: precio,
                cantidad: cantidad,
                total: (precio * cantidad).toFixed(2)
            };
            
            const index = productosCompra.findIndex(p => p.id === idLibro);
            
            if(index !== -1) {
                // Actualizar producto existente
                productosCompra[index].cantidad += cantidad;
                productosCompra[index].precio = precio;
                productosCompra[index].total = (precio * productosCompra[index].cantidad).toFixed(2);
                
                $(`#producto-${idLibro} td:nth-child(3)`).text(`$${precio.toFixed(2)}`);
                $(`#producto-${idLibro} td:nth-child(4)`).text(productosCompra[index].cantidad);
                $(`#producto-${idLibro} td:nth-child(5)`).text(`$${productosCompra[index].total}`);
                
                // Efecto visual
                $(`#producto-${idLibro}`).addClass('producto-agregado');
                setTimeout(() => $(`#producto-${idLibro}`).removeClass('producto-agregado'), 1500);
            } else {
                // Agregar nuevo producto
                productosCompra.push(producto);
                
                $('#selected-products-table tbody').append(`
                    <tr id="producto-${idLibro}" class="producto-agregado">
                        <td>${codigo}</td>
                        <td>${nombre}</td>
                        <td>$${precio.toFixed(2)}</td>
                        <td>${cantidad}</td>
                        <td>$${producto.total}</td>
                        <td class="text-center">
                            <button class="btn btn-outline-danger btn-sm btn-eliminar" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
                
                setTimeout(() => $(`#producto-${idLibro}`).removeClass('producto-agregado'), 1500);
            }
            
            actualizarTotales();
            resetearCamposProducto();
            $('#select-producto').focus();
        }

        function resetearCamposProducto() {
            $('#select-producto').val('');
            $('#codigo-libro').val('');
            $('#cantidad-producto').val(1);
            $('#precio-producto').val('');
        }

        function eliminarProducto() {
            const fila = $(this).closest('tr');
            const idProducto = parseInt(fila.attr('id').replace('producto-', ''));
            const index = productosCompra.findIndex(p => p.id === idProducto);
            
            if(index !== -1) {
                productosCompra.splice(index, 1);
                fila.remove();
                actualizarTotales();
            }
        }

        function actualizarTotales() {
            const total = productosCompra.reduce((sum, p) => sum + parseFloat(p.total), 0);
            $('#total').text(`$${total.toFixed(2)}`);
            
            // Habilitar/deshabilitar botón de finalizar
            $('#complete-purchase').prop('disabled', productosCompra.length === 0);
        }

        function cancelarCompra() {
            if(productosCompra.length === 0) return;
            
            if(confirm('¿Está seguro de cancelar esta compra? Se perderán todos los productos agregados.')) {
                productosCompra.length = 0;
                $('#selected-products-table tbody').empty();
                $('#proveedor-select').val('').trigger('change');
                $('#proveedor-seleccionado').text('No seleccionado').addClass('text-muted');
                $('#total').text('$0.00');
                obtenerNuevoFolio();
                $('#proveedor-select').focus();
            }
        }

        async function finalizarCompra() {
            if (compraEnProceso) return;
            compraEnProceso = true;
            
            const btn = $('#complete-purchase');
            const btnOriginal = btn.html();
            btn.html('<i class="bi bi-arrow-repeat spin"></i> Procesando...').prop('disabled', true);
            
            try {
                // Validaciones finales
                if(productosCompra.length === 0) {
                    throw new Error("No hay productos en la compra");
                }
                
                const proveedorId = $('#proveedor-select').val();
                if(!proveedorId) {
                    throw new Error("Seleccione un proveedor");
                }
                
                // Validar datos de productos
                const productosInvalidos = productosCompra.filter(p => 
                    !p.id || !p.cantidad || p.cantidad <= 0 || !p.precio || p.precio <= 0
                );
                
                if(productosInvalidos.length > 0) {
                    throw new Error("Algunos productos tienen datos inválidos");
                }
                
                // Preparar datos para enviar
                const datosCompra = {
                    idProveedor: proveedorId
                };
                
                const detallesCompra = productosCompra.map(p => ({
                    idLibro: p.id,
                    cantidad: p.cantidad,
                    precio: p.precio
                }));
                
                // Enviar al servidor
                const response = await $.ajax({
                    url: 'PCompraF.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'registrarCompra',
                        datos: JSON.stringify(datosCompra),
                        detalles: JSON.stringify(detallesCompra)
                    }
                });
                
                if(response.success) {
                    // Mostrar mensaje de éxito con detalles
                    const resumenMsg = `
                        <strong>¡Compra registrada exitosamente!</strong><br>
                        <strong>Folio:</strong> ${response.folio}<br>
                        <strong>Total:</strong> $${response.total}<br>
                    `;
                    
                    mostrarAlerta(resumenMsg, 'success', 8000);
                    
                    // Resetear todo el formulario
                    productosCompra.length = 0;
                    $('#selected-products-table tbody').empty();
                    $('#proveedor-select').val('').trigger('change');
                    $('#proveedor-seleccionado').text('No seleccionado').addClass('text-muted');
                    $('#total').text('$0.00');
                    
                    // Obtener nuevo folio
                    const nuevoFolio = await obtenerNuevoFolio();
                    $('#folio-resumen').text(nuevoFolio);
                    folioActual = nuevoFolio;
                    
                    $('#proveedor-select').focus();
                } else {
                    throw new Error(response.error || 'Error al procesar la compra');
                }
            } catch(error) {
                console.error('Error al finalizar compra:', error);
                
                let mensajeError = 'Error al procesar la compra';
                if(error.message.includes('Sesión')) {
                    mensajeError = 'La sesión expiró. Será redirigido...';
                    setTimeout(() => window.location.href = 'login.php', 3000);
                } else if(error.message.includes('proveedor')) {
                    mensajeError = 'Error con el proveedor: ' + error.message;
                } else if(error.message.includes('producto')) {
                    mensajeError = 'Error con los productos: ' + error.message;
                }
                
                mostrarAlerta(mensajeError, 'danger');
            } finally {
                btn.html(btnOriginal).prop('disabled', false);
                compraEnProceso = false;
            }
        }

        async function obtenerNuevoFolio() {
            try {
                const response = await $.ajax({
                    url: 'PCompraF.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { action: 'generarFolio' }
                });
                
                if(response.success) {
                    return response.folio;
                } else {
                    throw new Error(response.error || 'Error al generar folio');
                }
            } catch(error) {
                console.error('Error al obtener nuevo folio:', error);
                mostrarAlerta('Error al generar nuevo folio', 'warning');
                return "000000";
            }
        }

        function mostrarAlerta(mensaje, tipo = 'info', tiempo = 5000) {
            const iconos = {
                'success': 'bi-check-circle-fill',
                'danger': 'bi-exclamation-triangle-fill',
                'warning': 'bi-exclamation-circle-fill',
                'info': 'bi-info-circle-fill'
            };
            
            // Eliminar alertas anteriores
            $('.alert-auto-close').remove();
            
            const alerta = $(`
                <div class="alert alert-${tipo} alert-dismissible fade show alert-auto-close" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi ${iconos[tipo]} me-2"></i>
                        <div>${mensaje}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            `);
            
            $('body').append(alerta);
            
            // Cerrar automáticamente después del tiempo especificado
            setTimeout(() => {
                alerta.alert('close');
            }, tiempo);
        }
    </script>
</body>
</html>