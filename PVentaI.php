<?php
session_start();

if (!isset($_SESSION['SISTEMA'])) {
    $_SESSION['error'] = "Por favor inicie sesión nuevamente";
    header("Location: Login.php");
    exit();
}

if (!is_array($_SESSION['SISTEMA']) || !isset($_SESSION['SISTEMA']['id'])) {
    session_destroy();
    header("Location: Login.php?error=sesion_invalida");
    exit();
}

require_once 'Conexion.php';
require_once 'PVentaF.php';

// Obtener listado de libros con stock
$libros = [];
try {
    $stmt = $GLOBALS['conexion']->query("SELECT id_Libro, Codigo, Titulo, Precio, Stock FROM tlibro WHERE Stock > 0 AND eliminado = 0 ORDER BY Titulo");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener libros: " . $e->getMessage());
}

// Obtener listado de clientes
$clientes = [];
try {
    $stmt = $GLOBALS['conexion']->query("SELECT id_Cliente, Nombre FROM tcliente ORDER BY Nombre");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener clientes: " . $e->getMessage());
}

// Obtener folio inicial
$folioActual = "";
$ventaManager = new PVentaF($GLOBALS['conexion']);
$folioData = $ventaManager->generarFolio();
if ($folioData['success']) {
    $folioActual = $folioData['folio'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Librería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <style>
        body { background-color: #4b6043; font-family: 'Roboto', sans-serif; }
        .container-main { padding: 20px; }
        .books-section { margin-top: 20px; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        .books-section h3 { text-align: center; font-size: 3rem; color:rgb(0, 0, 0); }
        .btn-regresar { background-color: #47b36e; border-color: #47b36e; color: #ffffff; }
        .btn-regresar:hover { background-color:rgb(73, 156, 103); border-color: rgb(73, 156, 103); }
        .btn-eliminar { background-color: #ff3232; border-color: #ff3232; color: #ffffff; }
        .btn-eliminar:hover { background-color: #ff1818; border-color: #ff1818; }
        .btn-finalizar { background-color: #4caf50; border-color: #4caf50; color: #ffffff; }
        .btn-finalizar:hover { background-color: #3e8e41; border-color: #3e8e41; }
        .table th, .table td { text-align: center; vertical-align: middle; }
        .alert-auto-close { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; }
        .total-display { font-size: 1.5rem; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media (max-width: 768px) { 
            .container-main { padding: 10px; } 
            .books-section h3 { font-size: 2rem; }
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
        #pagoEfectivoModal .modal-body input[readonly] {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        #cambioCalculado {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        .cambio-label {
            font-size: 1.1rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="books-section">
            <button class="btn btn-secondary mb-3 btn-regresar" onclick="location.href='Inicio.php'">
                <i class="bi bi-arrow-left"></i> Regresar
            </button>
            <h3>Punto de Venta</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select class="form-select" id="cliente-select">
                        <option value="0" selected>Seleccionar cliente...</option>
                        <?php foreach($clientes as $cliente): ?>
                            <option value="<?= htmlspecialchars($cliente['id_Cliente']) ?>">
                                <?= htmlspecialchars($cliente['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Forma de Pago</label>
                    <select class="form-select" id="forma-pago">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TARJETA">Tarjeta</option>
                    </select>
                </div>
                <div class="col-md-2 search-container">
                    <label class="form-label">Código (opcional)</label>
                    <input type="text" class="form-control" id="codigo-libro" placeholder="Buscar por código">
                    <i class="bi bi-search search-icon"></i>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad-producto" value="1" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Producto</label>
                    <select class="form-select" id="select-producto">
                        <option value="" selected>Seleccionar libro...</option>
                        <?php foreach($libros as $libro): ?>
                            <option value="<?= htmlspecialchars($libro['id_Libro']) ?>" 
                                    data-codigo="<?= htmlspecialchars($libro['Codigo']) ?>"
                                    data-precio="<?= htmlspecialchars($libro['Precio']) ?>"
                                    data-stock="<?= htmlspecialchars($libro['Stock']) ?>">
                                <?= htmlspecialchars($libro['Titulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="selected-products-table">
                            <thead>
                                <tr>
                                    <th width="120">Código</th>
                                    <th>Nombre</th>
                                    <th width="120">P. Unitario</th>
                                    <th width="100">Cantidad</th>
                                    <th width="120">Total</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-receipt"></i> Resumen de Venta</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Folio:</label>
                                <p id="folio-resumen" class="fw-bold"><?= htmlspecialchars($folioActual) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cliente:</label>
                                <p id="cliente-seleccionado" class="fw-bold">-</p>
                            </div>
                            
                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between total-display">
                                    <strong>Total:</strong>
                                    <strong class="text-success" id="total">$0.00</strong>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <button class="btn btn-danger" id="cancel-sale">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </button>
                                <button class="btn btn-finalizar" id="complete-sale">
                                    <i class="bi bi-check-circle"></i> Finalizar Venta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para pago en efectivo -->
    <div class="modal fade" id="pagoEfectivoModal" tabindex="-1" aria-labelledby="pagoEfectivoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="pagoEfectivoModalLabel">Pago en Efectivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Total a Pagar:</label>
                        <input type="text" class="form-control" id="totalPagar" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Efectivo Recibido:</label>
                        <input type="number" class="form-control" id="efectivoRecibido" min="0" step="0.01" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label cambio-label">Cambio:</label>
                        <input type="text" class="form-control" id="cambioCalculado" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmarPago">Confirmar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script>
        const productosVenta = [];
        let folioActual = "<?= htmlspecialchars($folioActual) ?>";
        let ventaEnProceso = false;
        const librosDisponibles = <?= json_encode($libros) ?>;
        
        $(document).ready(function() {
            $('#folio-resumen').text(folioActual);
            $('#cliente-select').change(function() {
                $('#cliente-seleccionado').text($(this).find('option:selected').text() || '-');
            });
            
            // Autocompletado para búsqueda por código
            $('#codigo-libro').autocomplete({
                source: function(request, response) {
                    const term = request.term.toLowerCase();
                    const matches = librosDisponibles.filter(libro => 
                        libro.Codigo.toLowerCase().includes(term)
                    );
                    response(matches.map(libro => ({
                        label: `${libro.Codigo} - ${libro.Titulo} (Stock: ${libro.Stock})`,
                        value: libro.Codigo,
                        libro: libro
                    })));
                },
                select: function(event, ui) {
                    $('#select-producto').val(ui.item.libro.id_Libro).trigger('change');
                    $(this).val('');
                    return false;
                },
                minLength: 1,
                delay: 300
            });
            
            // Búsqueda directa con Enter
            $('#codigo-libro').keypress(function(e) {
                if(e.which === 13) {
                    const codigo = $(this).val().trim();
                    if(codigo) {
                        const libro = librosDisponibles.find(l => 
                            l.Codigo.toLowerCase() === codigo.toLowerCase()
                        );
                        if(libro) {
                            $('#select-producto').val(libro.id_Libro).trigger('change');
                            $(this).val('');
                        } else {
                            mostrarAlerta('No se encontró un libro con ese código', 'warning');
                        }
                    }
                    return false;
                }
            });
            
            $('#select-producto').change(agregarProductoSeleccionado);
            $(document).on('click', '.btn-eliminar', eliminarProducto);
            $('#cancel-sale').click(cancelarVenta);
            $('#complete-sale').click(finalizarVenta);
            
        });

        function agregarProductoSeleccionado() {
            
            const select = $(this);
            const option = select.find('option:selected');
            const idLibro = option.val();
            
            if(!idLibro) return;
            
            const cantidad = parseInt($('#cantidad-producto').val()) || 1;
            const stock = parseInt(option.data('stock'));
            const precio = parseFloat(option.data('precio'));
            const codigo = option.data('codigo');
            const nombre = option.text().split(' - ')[0];
            
            if(stock <= 0) {
                mostrarAlerta('No hay stock disponible para este producto', 'warning');
                return;
            }
            
            const producto = {
                id: idLibro,
                codigo: codigo,
                nombre: nombre,
                precio: precio,
                cantidad: cantidad,
                stock: stock,
                total: (precio * cantidad).toFixed(2)
            };
            
            const index = productosVenta.findIndex(p => p.id === idLibro);
            
            if(index !== -1) {
                const nuevaCantidad = productosVenta[index].cantidad + cantidad;
                
                if(nuevaCantidad > producto.stock) {
                    mostrarAlerta(`No hay suficiente stock. Disponible: ${producto.stock}`, 'warning');
                    return;
                }
                
                productosVenta[index].cantidad = nuevaCantidad;
                productosVenta[index].total = (producto.precio * nuevaCantidad).toFixed(2);
                
                $(`#producto-${idLibro} td:nth-child(4)`).text(nuevaCantidad);
                $(`#producto-${idLibro} td:nth-child(5)`).text(`$${productosVenta[index].total}`);
            } else {
                productosVenta.push(producto);
                
                $('#selected-products-table tbody').append(`
                    <tr id="producto-${idLibro}">
                        <td>${codigo}</td>
                        <td>${nombre}</td>
                        <td>$${precio.toFixed(2)}</td>
                        <td>${cantidad}</td>
                        <td>$${producto.total}</td>
                        <td>
                            <button class="btn btn-danger btn-sm btn-eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            }
            
            actualizarTotales();
            select.val('');
            $('#cantidad-producto').val(1);
            $('#codigo-libro').focus();
        }

        function eliminarProducto() {
            
            const fila = $(this).closest('tr');
            const idProducto = parseInt(fila.attr('id').replace('producto-', ''));
            const index = productosVenta.findIndex(p => p.id === idProducto);
            if(index !== -1) productosVenta.splice(index, 1);
            fila.remove();
            actualizarTotales();
        }

        function actualizarTotales() {
            const total = productosVenta.reduce((sum, p) => sum + parseFloat(p.total), 0);
            $('#total').text(`$${total.toFixed(2)}`);
        }

        function cancelarVenta() {
            
            if(productosVenta.length === 0) return;
            
            if(confirm('¿Está seguro de cancelar esta venta? Se perderán todos los productos agregados.')) {
                productosVenta.length = 0;
                $('#selected-products-table tbody').empty();
                $('#cliente-select').val('0').trigger('change');
                $('#total').text('$0.00');
                $('#codigo-libro').focus();
                obtenerNuevoFolio();
            }
        }

        async function finalizarVenta() {
            if (ventaEnProceso) return;
            
            ventaEnProceso = true;
            
            const btn = $('#complete-sale');
            const btnOriginal = btn.html();
            btn.html('<i class="bi bi-arrow-repeat spin"></i> Procesando...').prop('disabled', true);
            
            try {
                if(productosVenta.length === 0) {
                    mostrarAlerta('No hay productos en la venta', 'warning');
                    return;
                }
                
                const clienteId = $('#cliente-select').val();
                if(clienteId === '0') {
                    mostrarAlerta('Seleccione un cliente antes de finalizar', 'warning');
                    return;
                }
                
                const formaPago = $('#forma-pago').val();
                const totalVenta = productosVenta.reduce((sum, p) => sum + parseFloat(p.total), 0);
                
                // Si es pago en efectivo, mostrar modal
                if(formaPago === 'EFECTIVO') {
                    btn.html(btnOriginal).prop('disabled', false);
                    ventaEnProceso = false;
                    
                    $('#totalPagar').val('$' + totalVenta.toFixed(2));
                    $('#efectivoRecibido').val('').focus();
                    $('#cambioCalculado').val('');
                    
                    const pagoModal = new bootstrap.Modal(document.getElementById('pagoEfectivoModal'));
                    pagoModal.show();
                    
                    // Calcular cambio cuando cambia el efectivo recibido
                    $('#efectivoRecibido').on('input', function() {
                        const efectivo = parseFloat($(this).val()) || 0;
                        const cambio = efectivo - totalVenta;
                        $('#cambioCalculado').val('$' + (cambio >= 0 ? cambio.toFixed(2) : '0.00'));
                    });
                    
                    // Manejar confirmación de pago
                    $('#confirmarPago').off('click').on('click', async function() {
                        const efectivo = parseFloat($('#efectivoRecibido').val()) || 0;
                        if(efectivo < totalVenta) {
                            mostrarAlerta('El efectivo recibido es menor al total a pagar', 'warning');
                            return;
                        }
                        
                        pagoModal.hide();
                        await procesarVenta(totalVenta);
                    });
                    
                    return;
                }
                
                // Si no es efectivo, procesar directamente
                await procesarVenta(totalVenta);
                
            } catch(error) {
                console.error('Error:', error);
                let errorMsg = 'Error al procesar la venta';
                if(error.responseJSON && error.responseJSON.error) {
                    errorMsg = error.responseJSON.error;
                }
                mostrarAlerta(errorMsg, 'danger');
            } finally {
                btn.html(btnOriginal).prop('disabled', false);
                ventaEnProceso = false;
            }
        }

        async function procesarVenta(totalVenta) {
            const btn = $('#complete-sale');
            const btnOriginal = btn.html();
            btn.html('<i class="bi bi-arrow-repeat spin"></i> Procesando...').prop('disabled', true);
            ventaEnProceso = true;
            
            try {
                const folioMostrado = $('#folio-resumen').text();
                const clienteId = $('#cliente-select').val();
                
                const datosVenta = {
                    idCliente: clienteId,
                    idUsuario: <?= $_SESSION['SISTEMA']['id'] ?? 0 ?>,
                    formaPago: $('#forma-pago').val()
                };
                
                const detallesVenta = productosVenta.map(p => ({
                    idLibro: p.id,
                    cantidad: p.cantidad,
                    precio: p.precio
                }));
                
                const response = await $.ajax({
                    url: 'PVentaF.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'registrarVenta',
                        datos: JSON.stringify(datosVenta),
                        detalles: JSON.stringify(detallesVenta)
                    }
                });
                
                if(response.success) {
                    mostrarAlertaConTicket(
                        `Venta ${folioMostrado} registrada correctamente`,
                        response.ticketUrl
                    );
                    
                    // Resetear interfaz
                    productosVenta.length = 0;
                    $('#selected-products-table tbody').empty();
                    $('#total').text('$0.00');
                    $('#cliente-select').val('0').trigger('change');
                    
                    // Obtener nuevo folio
                    const nuevoFolio = await obtenerNuevoFolio();
                    $('#folio-resumen').text(nuevoFolio);
                    folioActual = nuevoFolio;
                } else {
                    throw new Error(response.error || 'Error al procesar la venta');
                }
            } catch(error) {
                console.error('Error al procesar venta:', error);
                let errorMsg = 'Error al procesar la venta';
                if(error.responseJSON && error.responseJSON.error) {
                    errorMsg = error.responseJSON.error;
                }
                mostrarAlerta(errorMsg, 'danger');
            } finally {
                btn.html(btnOriginal).prop('disabled', false);
                ventaEnProceso = false;
            }
        }

        function mostrarAlertaConTicket(mensaje, ticketUrl) {
            const alertaHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 350px;">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="mb-2">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>${mensaje}</strong>
                        </div>
                        <button onclick="imprimirTicket('${ticketUrl}')" 
                                class="btn btn-sm btn-success fw-bold">
                            <i class="bi bi-printer"></i> IMPRIMIR TICKET
                        </button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            $('.alert-dismissible').alert('close');
            $('body').append(alertaHTML);
            setTimeout(() => $('.alert-dismissible').alert('close'), 15000);
        }

        function imprimirTicket(url) {
            const ticketWindow = window.open(url, "_blank");
            ticketWindow.onload = function() {
                ticketWindow.print();
            };
        }

        async function obtenerNuevoFolio() {
            try {
                const response = await $.ajax({
                    url: 'PVentaF.php',
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
                console.error('Error al obtener folio:', error);
                mostrarAlerta('Error al generar folio: ' + error.message, 'danger');
                return "000000";
            }
        }

        function mostrarAlerta(mensaje, tipo = 'info') {
            const iconos = {
                'success': 'bi-check-circle',
                'danger': 'bi-exclamation-triangle',
                'warning': 'bi-exclamation-circle',
                'info': 'bi-info-circle'
            };
            
            const alerta = $(`
                <div class="alert alert-${tipo} alert-dismissible fade show alert-auto-close" role="alert">
                    <i class="bi ${iconos[tipo]}"></i> <strong>${tipo === 'danger' ? 'Error' : 
                      tipo === 'success' ? 'Éxito' : 
                      tipo === 'warning' ? 'Advertencia' : 'Info'}:</strong> ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
            
            $('body').append(alerta);
            setTimeout(() => alerta.alert('close'), 5000);
        }
    </script>
</body>
</html>