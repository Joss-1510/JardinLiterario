<?php
session_start();
require 'Conexion.php';
require 'VentaDetalleF.php';

if (!isset($_SESSION['SISTEMA'])) {
    die('<div class="alert alert-danger">Acceso no autorizado</div>');
}

if (isset($_POST['idVenta']) && is_numeric($_POST['idVenta'])) {
    $ventaDetalle = new VentaDetalleF($conexion);
    $detalles = $ventaDetalle->obtenerDetallesVenta($_POST['idVenta']);
    
    if (empty($detalles)) {
        echo '<div class="alert alert-warning">No se encontraron detalles para esta venta</div>';
        exit();
    }
    
    $html = '<table class="table table-sm table-hover">';
    $html .= '<thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">P. Unitario</th><th class="text-end">Subtotal</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($detalles as $detalle) {
        $html .= '<tr>';
        $html .= '<td>'.htmlspecialchars($detalle['Producto']).'</td>';
        $html .= '<td class="text-end">'.htmlspecialchars($detalle['Cantidad']).'</td>';
        $html .= '<td class="text-end">$'.number_format($detalle['PrecioUnitario'], 2).'</td>';
        $html .= '<td class="text-end">$'.number_format($detalle['Subtotal'], 2).'</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    echo $html;
} else {
    echo '<div class="alert alert-danger">ID de venta no especificado</div>';
}
?>