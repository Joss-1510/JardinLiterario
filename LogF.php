<?php
class Log {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Obtiene todos los registros de log con paginación
     */
    public function getAll($limit = 20, $page = 1) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                u.Nombre as usuario_nombre,  -- Solo el nombre de usuario
                DATE_FORMAT(l.Fecha, '%d/%m/%Y %H:%i:%s') as fecha_formateada
            FROM tlog l
            LEFT JOIN tusuario u ON l.idUsuario = u.id_Usuario  -- JOIN simplificado
            ORDER BY l.Fecha DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filtra logs por acción
     */
    public function getByAction($action, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                u.Nombre as usuario_nombre
            FROM tlog l
            LEFT JOIN tusuario u ON l.idUsuario = u.id_Usuario
            WHERE l.Accion = :action
            ORDER BY l.Fecha DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Búsqueda en logs
     */
    public function search($query, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                u.Nombre as usuario_nombre
            FROM tlog l
            LEFT JOIN tusuario u ON l.idUsuario = u.id_Usuario
            WHERE l.Detalle LIKE CONCAT('%', :query, '%')
            ORDER BY l.Fecha DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':query', $query, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conteo total de registros
     */
    public function getTotalCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tlog");
        return (int)$stmt->fetchColumn();
    }
}
?>