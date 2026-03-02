<?php
require_once 'Conexion.php';

class CompraDetalleF {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function obtenerComprasAgrupadas($limit = 20, $page = 1) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare("
            SELECT 
                c.id_Compra,
                c.Folio,
                p.Nombre AS Proveedor,
                c.Fecha,
                c.Total,
                COUNT(cd.id_CompraDet) AS Productos
            FROM tcompra c
            JOIN tproveedor p ON c.idProveedor = p.id_Proveedor
            LEFT JOIN tcompradet cd ON c.id_Compra = cd.idCompra
            GROUP BY c.id_Compra
            ORDER BY c.Fecha DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDetallesCompra($idCompra) {
        $stmt = $this->db->prepare("
            SELECT 
                l.Titulo AS Producto,
                l.Codigo,
                cd.Cantidad,
                cd.PrecioCompra,
                cd.Subtotal
            FROM tcompradet cd
            JOIN tlibro l ON cd.idLibro = l.id_Libro
            WHERE cd.idCompra = :idCompra
        ");
        $stmt->bindParam(':idCompra', $idCompra, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tcompra");
        return (int)$stmt->fetchColumn();
    }

    public function obtenerDatosCompra($idCompra) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.Nombre AS Proveedor,
                p.RFC,
                p.Direccion,
                p.Telefono
            FROM tcompra c
            JOIN tproveedor p ON c.idProveedor = p.id_Proveedor
            WHERE c.id_Compra = :idCompra
        ");
        $stmt->bindParam(':idCompra', $idCompra, PDO::PARAM_INT);
        $stmt->execute();
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compra) {
            return false;
        }

        $detalles = $this->obtenerDetallesCompra($idCompra);

        return [
            'compra' => $compra,
            'detalles' => $detalles
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'Conexion.php';
    $compraDetalle = new CompraDetalleF($GLOBALS['conexion']);
    
    switch ($_POST['action']) {
        case 'obtenerDetalles':
            if (isset($_POST['idCompra'])) {
                $detalles = $compraDetalle->obtenerDetallesCompra($_POST['idCompra']);
                echo json_encode($detalles);
            }
            break;
            
        case 'obtenerDatosCompra':
            if (isset($_POST['idCompra'])) {
                $datos = $compraDetalle->obtenerDatosCompra($_POST['idCompra']);
                echo json_encode($datos);
            }
            break;
    }
    exit;
}
?>