<?php
class VentaDetalleF {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public function obtenerVentasAgrupadas($limit = 20, $page = 1, $busqueda = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM vistaventasagrupadas";
        
        // Agregar condición de búsqueda si se proporciona
        if (!empty($busqueda)) {
            $sql .= " WHERE (Folio LIKE :busqueda OR Cliente LIKE :busqueda OR Vendedor LIKE :busqueda OR Fecha LIKE :busqueda)";
        }
        
        $sql .= " ORDER BY Fecha DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        if (!empty($busqueda)) {
            $stmt->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene detalles de una venta específica (productos vendidos)
     */
    public function obtenerDetallesVenta($idVenta) {
        $stmt = $this->db->prepare("
            SELECT l.Titulo AS Producto, vd.Cantidad, vd.PrecioUnitario, vd.Subtotal
            FROM tventadet vd
            JOIN tlibro l ON vd.idLibro = l.id_Libro
            WHERE vd.idVenta = :idVenta
        ");
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el conteo total de ventas (con filtro de búsqueda)
     */
    public function getTotalCount($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM tventa";
        
        if (!empty($busqueda)) {
            $sql = "SELECT COUNT(*) as total FROM VistaVentasAgrupadas 
                    WHERE (Folio LIKE :busqueda OR Cliente LIKE :busqueda OR Vendedor LIKE :busqueda)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene datos para el ticket
     */
    public function obtenerDatosTicket($idVenta) {
        // Obtener datos de la venta
        $stmt = $this->db->prepare("
            SELECT v.*, c.Nombre AS Cliente, u.Nombre AS Vendedor, 
                   fp.Descripcion AS FormaPago
            FROM tventa v
            LEFT JOIN tcliente c ON v.idCliente = c.id_Cliente
            LEFT JOIN tusuario u ON v.idUsuario = u.id_Usuario
            LEFT JOIN tformapago fp ON v.idFormaPago = fp.id_FormaPago
            WHERE v.id_Venta = :idVenta
        ");
        $stmt->bindParam(':idVenta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            return false;
        }

        // Obtener detalles de la venta
        $detalles = $this->obtenerDetallesVenta($idVenta);

        return [
            'venta' => $venta,
            'detalles' => $detalles
        ];
    }
}