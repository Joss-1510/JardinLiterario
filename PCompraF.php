<?php
require_once 'Conexion.php';

class PCompraF {
    private $db;
    
    public function __construct($conexion) {
        $this->db = $conexion;
    }
    
    public function generarFolio() {
        try {
            $stmt = $this->db->query("SELECT MAX(id_Compra) FROM tcompra");
            $maxId = $stmt->fetchColumn();
            $nextId = ($maxId === null) ? 1 : $maxId + 1;
            return ['success' => true, 'folio' => str_pad($nextId, 6, "0", STR_PAD_LEFT)];
        } catch (PDOException $e) {
            error_log("Error al generar folio: " . $e->getMessage());
            return ['success' => false, 'error' => "Error al generar folio"];
        }
    }
    
    public function registrarCompra($datosCompra, $detallesCompra) {
        try {
            // Validación de sesión mejorada
            if (session_status() === PHP_SESSION_NONE) @session_start();
            if (!isset($_SESSION['SISTEMA'])) {
                throw new Exception("Acceso no autorizado. Sesión no válida.");
            }

            // Establecer usuario para triggers
            $userId = $_SESSION['SISTEMA']['id'] ?? 0;
            $this->db->exec("SET @usuario_id = " . $this->db->quote($userId));

            $this->db->beginTransaction();

            // Validar proveedor
            if (empty($datosCompra['idProveedor'])) {
                throw new Exception("No se especificó proveedor");
            }

            $stmt = $this->db->prepare("SELECT id_Proveedor FROM tproveedor WHERE id_Proveedor = ?");
            $stmt->execute([$datosCompra['idProveedor']]);
            if (!$stmt->fetch()) {
                throw new Exception("El proveedor no existe");
            }

            // Generar folio
            $folioData = $this->generarFolio();
            if (!$folioData['success']) {
                throw new Exception($folioData['error']);
            }
            $folio = $folioData['folio'];

            // Validar productos
            if (empty($detallesCompra) || !is_array($detallesCompra)) {
                throw new Exception("No se han proporcionado productos");
            }

            $total = 0;
            $productosInfo = [];
            
            foreach ($detallesCompra as $detalle) {
                // Validación de cada producto
                if (empty($detalle['idLibro']) || empty($detalle['cantidad']) || $detalle['cantidad'] <= 0 || empty($detalle['precio']) || $detalle['precio'] <= 0) {
                    throw new Exception("Datos de producto inválidos");
                }

                // Verificar existencia del libro y obtener datos
                $stmt = $this->db->prepare("SELECT id_Libro, idSaga, NumeroTomo FROM tlibro WHERE id_Libro = ?");
                $stmt->execute([$detalle['idLibro']]);
                $libro = $stmt->fetch();
                
                if (!$libro) {
                    throw new Exception("El libro con ID {$detalle['idLibro']} no existe");
                }

                // Manejar libros con saga (para evitar conflictos con trigger TG_ValidarTomoSaga)
                if ($libro['idSaga'] !== null && $libro['NumeroTomo'] === null) {
                    $this->db->prepare("UPDATE tlibro SET NumeroTomo = 1 WHERE id_Libro = ?")->execute([$detalle['idLibro']]);
                }

                $precio = (float)$detalle['precio'];
                $cantidad = (int)$detalle['cantidad'];
                $subtotal = $precio * $cantidad;
                $total += $subtotal;

                $productosInfo[] = [
                    'idLibro' => $detalle['idLibro'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal
                ];
            }

            // Insertar compra principal
            $stmt = $this->db->prepare(
                "INSERT INTO tcompra (Folio, idProveedor, Fecha, Total) 
                 VALUES (?, ?, NOW(), ?)"
            );
            if (!$stmt->execute([$folio, $datosCompra['idProveedor'], $total])) {
                throw new Exception("Error al guardar la compra principal");
            }
            $idCompra = $this->db->lastInsertId();

            // Insertar detalles
            foreach ($productosInfo as $producto) {
                $stmt = $this->db->prepare(
                    "INSERT INTO tcompradet (idCompra, idLibro, Cantidad, PrecioCompra, Subtotal) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                if (!$stmt->execute([
                    $idCompra,
                    $producto['idLibro'],
                    $producto['cantidad'],
                    $producto['precio'],
                    $producto['subtotal']
                ])) {
                    throw new Exception("Error al guardar detalle de compra");
                }

                // Actualizar stock y precio en TLibro (considerando triggers)
                $stmt = $this->db->prepare(
                    "UPDATE tlibro 
                     SET Stock = COALESCE(Stock, 0) + ?, 
                         Precio = ?
                     WHERE id_Libro = ?"
                );
                if (!$stmt->execute([
                    $producto['cantidad'],
                    $producto['precio'],
                    $producto['idLibro']
                ])) {
                    throw new Exception("Error al actualizar inventario");
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'folio' => $folio,
                'idCompra' => $idCompra,
                'total' => number_format($total, 2),
                'mensaje' => 'Compra registrada exitosamente'
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                try {
                    $this->db->rollBack();
                } catch (PDOException $rollbackEx) {
                    error_log("Error al hacer rollback: " . $rollbackEx->getMessage());
                }
            }
            error_log("Error en registrarCompra: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    public function handleAction($action, $data = []) {
        header('Content-Type: application/json');
        
        try {
            if (session_status() === PHP_SESSION_NONE) @session_start();
            if (!isset($_SESSION['SISTEMA'])) {
                throw new Exception("Acceso no autorizado. Inicie sesión.");
            }
            
            switch ($action) {
                case 'generarFolio':
                    $result = $this->generarFolio();
                    break;
                    
                case 'registrarCompra':
                    if (empty($data['datos']) || empty($data['detalles'])) {
                        throw new Exception("Datos incompletos para la compra");
                    }
                    
                    $datos = json_decode($data['datos'], true, 512, JSON_THROW_ON_ERROR);
                    $detalles = json_decode($data['detalles'], true, 512, JSON_THROW_ON_ERROR);
                    
                    $result = $this->registrarCompra($datos, $detalles);
                    break;
                    
                default:
                    throw new Exception("Acción no válida: " . htmlspecialchars($action));
            }
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Error en handleAction: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'Conexion.php';
    $compraManager = new PCompraF($GLOBALS['conexion']);
    $compraManager->handleAction($_POST['action'], $_POST);
}
?>