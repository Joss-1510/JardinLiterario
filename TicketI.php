<?php
session_start();
require_once 'Conexion.php';
require_once 'PVentaF.php';
require_once 'TicketF.php';

if (!isset($_GET['venta']) || !is_numeric($_GET['venta'])) {
    die('ID de venta no válido');
}

// Validar sesión
if (!isset($_SESSION['SISTEMA']) || !isset($_SESSION['SISTEMA']['id'])) {
    die('Acceso no autorizado');
}

$idVenta = $_GET['venta'];

try {
    // Obtener datos de la venta
    $ventaManager = new PVentaF($GLOBALS['conexion']);
    $datosTicket = $ventaManager->obtenerDatosTicket($idVenta);
    
    // Verificar que exista la venta
    if (empty($datosTicket['venta'])) {
        die('Venta no encontrada');
    }
    
    // Generar el ticket con logo local
    $ticket = new Ticket(
        $datosTicket['venta'],
        $datosTicket['detalles'],
        [
            'empresa' => 'Jardín Literario',
            'direccion' => 'Paseo de los Nardos #3',
            'telefono' => '348 108 0658',
            'rfc' => 'FOBE801113PBA',
            'logo_url' => './Imagenes/Logo.png', 
            'logo_width' => '60px', 
            'color_principal' => '#2c3e50', 
            'color_secundario' => '#3498db',
            'mensaje_footer' => '¡Gracias por visitar Jardín Literario!'
        ]
    );
    
    // Mostrar el ticket
    echo $ticket->generar();
    
} catch (Exception $e) {
    die('Error al generar ticket: ' . $e->getMessage());
}
?>