<?php
require_once 'Conexion.php';
require_once 'TicketF.php';

class PVentaF {
    private $db;
    
    public function __construct($conexion) {
        $this->db = $conexion;
    }
    
    public function generarFolio() {
        try {
            $stmt = $this->db->query("SELECT MAX(id_Venta) + 1 FROM tventa");
            $nextId = $stmt->fetchColumn() ?: 1;
            $folio = str_pad($nextId, 6, "0", STR_PAD_LEFT);
            return ['success' => true, 'folio' => $folio];
        } catch (PDOException $e) {
            error_log("Error al generar folio: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function obtenerDatosTicket($idVenta) {
        $stmt = $this->db->prepare("
            SELECT v.Folio, v.Fecha, v.Total, v.FormaPago,
                   c.Nombre AS Cliente,
                   u.Nombre AS Vendedor
            FROM tventa v
            JOIN tcliente c ON v.idCliente = c.id_Cliente
            JOIN tusuario u ON v.idUsuario = u.id_Usuario
            WHERE v.id_Venta = ?
        ");
        $stmt->execute([$idVenta]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->prepare("
            SELECT d.idLibro, l.Titulo, d.Cantidad, d.PrecioUnitario, d.Subtotal
            FROM tventadet d
            JOIN tlibro l ON d.idLibro = l.id_Libro
            WHERE d.idVenta = ?
        ");
        $stmt->execute([$idVenta]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['venta' => $venta, 'detalles' => $detalles];
    }
    
    public function registrarVenta($datosVenta, $detallesVenta) {
        try {
            // Validaciones iniciales mejoradas
            if (!isset($datosVenta['idUsuario']) || !isset($datosVenta['idCliente']) || !isset($datosVenta['formaPago'])) {
                error_log("Datos de venta incompletos: " . print_r($datosVenta, true));
                throw new Exception("Datos de venta incompletos");
            }

            if (empty($detallesVenta)) {
                error_log("No hay detalles de venta");
                throw new Exception("No se han seleccionado productos para la venta");
            }

            // Validar usuario
            $stmt = $this->db->prepare("SELECT id_Usuario FROM tusuario WHERE id_Usuario = ?");
            $stmt->execute([$datosVenta['idUsuario']]);
            if (!$stmt->fetch()) {
                error_log("Usuario no válido: " . $datosVenta['idUsuario']);
                throw new Exception("Usuario no válido");
            }

            $this->db->exec("SET @usuario_id = " . $datosVenta['idUsuario']);
            $this->db->beginTransaction();

            // Validar cliente
            $stmt = $this->db->prepare("SELECT id_Cliente FROM tcliente WHERE id_Cliente = ?");
            $stmt->execute([$datosVenta['idCliente']]);
            if (!$stmt->fetch()) {
                error_log("Cliente no existe: " . $datosVenta['idCliente']);
                throw new Exception("Cliente no existe");
            }
            
            // Generar folio
            $folioData = $this->generarFolio();
            if (!$folioData['success']) {
                error_log("Error al generar folio: " . $folioData['error']);
                throw new Exception($folioData['error']);
            }
            $folio = $folioData['folio'];
            
            // Calcular total y validar stock
            $total = 0;
            $librosInfo = [];
            foreach ($detallesVenta as $detalle) {
                if (!isset($detalle['idLibro']) || !isset($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    error_log("Datos de producto inválidos: " . print_r($detalle, true));
                    throw new Exception("Datos de producto inválidos");
                }
                
                $stmt = $this->db->prepare("SELECT Precio, Stock FROM tlibro WHERE id_Libro = ?");
                $stmt->execute([$detalle['idLibro']]);
                $libro = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(!$libro) {
                    error_log("Libro no encontrado: " . $detalle['idLibro']);
                    throw new Exception("Libro no encontrado");
                }
                
                if($libro['Stock'] < $detalle['cantidad']) {
                    error_log("Stock insuficiente para el libro ID: " . $detalle['idLibro'] . " (Stock: " . $libro['Stock'] . ", Cantidad solicitada: " . $detalle['cantidad'] . ")");
                    throw new Exception("Stock insuficiente para el libro ID: " . $detalle['idLibro']);
                }
                
                $subtotal = $libro['Precio'] * $detalle['cantidad'];
                $total += $subtotal;
                $librosInfo[$detalle['idLibro']] = [
                    'precio' => $libro['Precio'], 
                    'subtotal' => $subtotal,
                    'stock_actual' => $libro['Stock']
                ];
            }
            
            // Insertar venta
            $stmt = $this->db->prepare(
                "INSERT INTO tventa (Folio, Fecha, idCliente, idUsuario, FormaPago, Total)
                 VALUES (?, NOW(), ?, ?, ?, ?)"
            );
            $stmt->execute([$folio, $datosVenta['idCliente'], $datosVenta['idUsuario'], $datosVenta['formaPago'], $total]);
            $idVenta = $this->db->lastInsertId();
            
            // Insertar detalles
            foreach ($detallesVenta as $detalle) {
                $libro = $librosInfo[$detalle['idLibro']];
                $stmt = $this->db->prepare(
                    "INSERT INTO tventadet (idVenta, idLibro, Cantidad, PrecioUnitario, Subtotal)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$idVenta, $detalle['idLibro'], $detalle['cantidad'], $libro['precio'], $libro['subtotal']]);
                
                // Actualizar stock
                $stmt = $this->db->prepare("UPDATE tlibro SET Stock = Stock - ? WHERE id_Libro = ?");
                $stmt->execute([$detalle['cantidad'], $detalle['idLibro']]);
                
                error_log("Actualizado stock libro ID " . $detalle['idLibro'] . ": " . 
                         $libro['stock_actual'] . " -> " . ($libro['stock_actual'] - $detalle['cantidad']));
            }
            
            $this->db->commit();
            error_log("Venta registrada exitosamente. ID: " . $idVenta . ", Folio: " . $folio . ", Total: " . $total);
            
            return [
                'success' => true,
                'folio' => $folio,
                'idVenta' => $idVenta,
                'ticketUrl' => 'TicketI.php?venta=' . $idVenta
            ];
            
        } catch (Exception $e) {
            error_log("ERROR en registrarVenta: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            if ($this->db->inTransaction()) {
                error_log("Realizando rollback...");
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function handleAction($action, $data = []) {
        header('Content-Type: application/json');
        
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['SISTEMA']) || !isset($_SESSION['SISTEMA']['id'])) {
                error_log("Acceso no autorizado. Sesión: " . print_r($_SESSION, true));
                throw new Exception("Acceso no autorizado");
            }
            
            switch ($action) {
                case 'generarFolio':
                    $result = $this->generarFolio();
                    break;
                    
                case 'registrarVenta':
                    if (!isset($data['datos']) || !isset($data['detalles'])) {
                        error_log("Datos incompletos para la venta: " . print_r($data, true));
                        throw new Exception("Datos incompletos para la venta");
                    }
                    
                    $datos = json_decode($data['datos'], true);
                    $detalles = json_decode($data['detalles'], true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Error en formato JSON: " . json_last_error_msg() . "\nDatos: " . $data['datos'] . "\nDetalles: " . $data['detalles']);
                        throw new Exception("Error en formato JSON: " . json_last_error_msg());
                    }
                    
                    $result = $this->registrarVenta($datos, $detalles);
                    break;
                    
                default:
                    error_log("Acción no válida: " . $action);
                    throw new Exception("Acción no válida: " . $action);
            }
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Error en handleAction: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

if (isset($_POST['action'])) {
    require_once 'Conexion.php';
    $ventaManager = new PVentaF($GLOBALS['conexion']);
    $ventaManager->handleAction($_POST['action'], $_POST);
}
?>