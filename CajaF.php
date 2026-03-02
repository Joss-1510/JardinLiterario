<?php
require_once 'Conexion.php';

class CajaF {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function cajaAbierta() {
        try {
            $stmt = $this->db->prepare("
                SELECT id_Caja FROM tcaja 
                WHERE Cierre IS NULL 
                AND DATE(Fecha) = CURDATE()
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Verificación de caja: " . ($result ? "Abierta (ID: ".$result['id_Caja'].")" : "Cerrada"));
            
            return (bool)$result;
        } catch (PDOException $e) {
            error_log("Error en cajaAbierta(): " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadoCaja() {
        $stmt = $this->db->prepare("
            SELECT * FROM tcaja 
            WHERE DATE(Fecha) = CURDATE()
            ORDER BY Fecha DESC 
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function abrirCaja($montoApertura) {
        $monto = (float)$montoApertura;
        if ($monto <= 0) {
            throw new Exception("El monto de apertura debe ser mayor a cero");
        }

        $stmt = $this->db->prepare("
            INSERT INTO tcaja 
            (Fecha, Apertura, Cierre, FechaHora, idUsuario)
            VALUES 
            (CURDATE(), :monto, NULL, NOW(), :idUsuario)
        ");
        $stmt->bindValue(':monto', $monto, PDO::PARAM_STR);
        $stmt->bindValue(':idUsuario', $_SESSION['SISTEMA']['id'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cerrarCaja($montoCierre) {
        $monto = (float)$montoCierre;
        if ($monto <= 0) {
            throw new Exception("El monto de cierre debe ser mayor a cero");
        }

        $stmt = $this->db->prepare("
            UPDATE tcaja SET 
                Cierre = :monto,
                FechaHora = NOW()
            WHERE Cierre IS NULL
            AND DATE(Fecha) = CURDATE()
        ");
        $stmt->bindValue(':monto', $monto, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function obtenerVentasDelDia() {
        $stmt = $this->db->prepare("
            SELECT IFNULL(SUM(Total), 0) as total 
            FROM tventa 
            WHERE DATE(Fecha) = CURDATE()
        ");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function calcularCierreAutomatico() {
        $estadoCaja = $this->obtenerEstadoCaja();
        
        if (!$estadoCaja || $estadoCaja['Cierre'] !== null) {
            return false;
        }
        
        $ventas = $this->obtenerVentasDelDia();
        
        return [
            'apertura' => $estadoCaja['Apertura'],
            'ventas' => $ventas,
            'total' => $estadoCaja['Apertura'] + $ventas
        ];
    }

    public function obtenerHistorial($limit = 30) {
        $stmt = $this->db->prepare("
            SELECT * FROM tcaja 
            ORDER BY Fecha DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'Conexion.php';
    session_start();
    header('Content-Type: application/json');
    
    try {
        $cajaManager = new CajaF($GLOBALS['conexion']);
        
        switch ($_POST['action']) {
            case 'abrirCaja':
                if (isset($_POST['monto'])) {
                    echo json_encode(['success' => $cajaManager->abrirCaja($_POST['monto'])]);
                }
                break;
                
            case 'cerrarCaja':
                if (isset($_POST['monto'])) {
                    echo json_encode(['success' => $cajaManager->cerrarCaja($_POST['monto'])]);
                }
                break;
                
            case 'verificarCaja':
                echo json_encode(['abierta' => $cajaManager->cajaAbierta()]);
                break;
                
            case 'obtenerEstado':
                echo json_encode($cajaManager->obtenerEstadoCaja());
                break;
                
            case 'calcularCierre':
                echo json_encode($cajaManager->calcularCierreAutomatico());
                break;
                
            case 'obtenerVentas':
                echo json_encode(['ventas' => $cajaManager->obtenerVentasDelDia()]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>