<?php
class LibroF {
    private $db;
    
    public function __construct($pdo) {
        $this->db = $pdo;
    }
    
    public function obtenerLibrosCompletos() {
        try {
            $query = "SELECT l.*, 
                     a.Nombre AS NombreAutor, 
                     e.Nombre AS NombreEditorial, 
                     g.Nombre AS NombreGenero, 
                     s.Nombre AS NombreSaga
                     FROM tlibro l
                     LEFT JOIN tautor a ON l.idAutor = a.id_Autor
                     LEFT JOIN teditorial e ON l.idEditorial = e.id_Editorial
                     LEFT JOIN tgenero g ON l.idGenero = g.id_Genero
                     LEFT JOIN tsaga s ON l.idSaga = s.id_Saga
                     WHERE l.eliminado = 0
                     ORDER BY l.Titulo";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerLibrosCompletos: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarLibros($termino) {
        try {
            // Iniciamos registro de depuración
            $debugInfo = [
                'termino_original' => $termino,
                'pasos' => [],
                'query_final' => '',
                'parametros' => [],
                'resultados' => []
            ];
    
            $termino = trim($termino);
            $debugInfo['termino_trimmed'] = $termino;
            $debugInfo['pasos'][] = 'Término trimmeado';
    
            if (empty($termino)) {
                $debugInfo['pasos'][] = 'Término vacío - devolviendo todos los libros';
                $debugInfo['resultados'] = $this->obtenerLibrosCompletos();
                $this->registrarDepuracion($debugInfo);
                return $debugInfo['resultados'];
            }
    
            // Preparamos el término de búsqueda
            $terminoBusqueda = "%" . preg_replace('/\s+/', '%', $termino) . "%";
            $debugInfo['termino_busqueda'] = $terminoBusqueda;
            $debugInfo['pasos'][] = 'Término de búsqueda preparado';
    
            $query = "SELECT l.*, 
                     a.Nombre AS NombreAutor, 
                     e.Nombre AS NombreEditorial, 
                     g.Nombre AS NombreGenero, 
                     s.Nombre AS NombreSaga
                     FROM tlibro l
                     LEFT JOIN tautor a ON l.idAutor = a.id_Autor AND a.eliminado = 0
                     LEFT JOIN teditorial e ON l.idEditorial = e.id_Editorial AND e.eliminado = 0
                     LEFT JOIN tgenero g ON l.idGenero = g.id_Genero AND g.eliminado = 0
                     LEFT JOIN tsaga s ON l.idSaga = s.id_Saga AND s.eliminado = 0
                     WHERE l.eliminado = 0 AND (
                         l.Titulo LIKE :termino COLLATE utf8mb4_general_ci
                         OR l.Codigo LIKE :termino COLLATE utf8mb4_general_ci
                         OR (l.idAutor IS NOT NULL AND a.Nombre LIKE :termino COLLATE utf8mb4_general_ci)
                         OR (l.idEditorial IS NOT NULL AND e.Nombre LIKE :termino COLLATE utf8mb4_general_ci)
                         OR (l.idGenero IS NOT NULL AND g.Nombre LIKE :termino COLLATE utf8mb4_general_ci)
                         OR (l.idSaga IS NOT NULL AND s.Nombre LIKE :termino COLLATE utf8mb4_general_ci)
                     )
                     ORDER BY l.Titulo";
    
            $debugInfo['query_final'] = $query;
            $debugInfo['parametros'][':termino'] = $terminoBusqueda;
            $debugInfo['pasos'][] = 'Consulta SQL preparada';
    
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':termino', $terminoBusqueda, PDO::PARAM_STR);
            $stmt->execute();
    
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $debugInfo['resultados'] = $resultados;
            $debugInfo['pasos'][] = 'Consulta ejecutada - ' . count($resultados) . ' resultados';
            
            // Registramos información de depuración
            $this->registrarDepuracion($debugInfo);
            
            return $resultados;
            
        } catch (PDOException $e) {
            $errorMsg = "Error en buscarLibros: " . $e->getMessage();
            error_log($errorMsg);
            
            // Registramos el error también en la depuración
            if (isset($debugInfo)) {
                $debugInfo['error'] = $errorMsg;
                $this->registrarDepuracion($debugInfo);
            }
            
            return [];
        }
    }
    
    private function registrarDepuracion($debugInfo) {
        // Guardamos en el log del servidor
        error_log("DEBUG BUSQUEDA: " . print_r($debugInfo, true));
        
        // También guardamos en sesión para mostrar al usuario (opcional)
        $_SESSION['debug_busqueda'] = $debugInfo;
    }
    
    public function obtenerSugerenciasBusqueda($termino) {
        try {
            $query = "SELECT l.Titulo AS label, l.id_Libro AS value 
                     FROM tlibro l
                     LEFT JOIN tautor a ON l.idAutor = a.id_Autor
                     WHERE l.eliminado = 0 AND l.Titulo LIKE :termino
                     ORDER BY l.Titulo
                     LIMIT 10";
            
            $stmt = $this->db->prepare($query);
            $terminoBusqueda = "%" . trim($termino) . "%";
            $stmt->bindParam(':termino', $terminoBusqueda, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerSugerenciasBusqueda: " . $e->getMessage());
            return [];
        }
    }

    public function create($table, $data) {
        try {
            $this->db->beginTransaction();
            
            if (isset($_SESSION['SISTEMA']['id'])) {
                $this->db->exec("SET @usuario_id = " . $_SESSION['SISTEMA']['id']);
            }

            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($data as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : 
                            (is_float($value) ? PDO::PARAM_STR : 
                            (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                $stmt->bindValue(":$key", $value, $paramType);
            }
            
            $stmt->execute();
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Error al guardar los datos: " . $e->getMessage());
        }
    }
    
    public function read($table, $conditions = [], $limit = null) {
        try {
            $where = [];
            if ($table === 'tlibro') {
                $where[] = "eliminado = 0";
            }
            
            if (!empty($conditions)) {
                foreach ($conditions as $key => $value) {
                    $where[] = "$key = :$key";
                }
            }
            
            $query = "SELECT * FROM $table";
            if (!empty($where)) {
                $query .= " WHERE " . implode(" AND ", $where);
            }
            if ($limit) {
                $query .= " LIMIT $limit";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($conditions);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al leer registros: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($table, $data, $conditions) {
        try {
            $this->db->beginTransaction();
            
            if (isset($_SESSION['SISTEMA']['id'])) {
                $this->db->exec("SET @usuario_id = " . $_SESSION['SISTEMA']['id']);
            }
    
            $set = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $set[] = "$key = :set_$key";
                $params[":set_$key"] = $value;
            }
            
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :cond_$key";
                $params[":cond_$key"] = $value;
            }
            
            $query = "UPDATE $table SET " . implode(", ", $set) . " WHERE " . implode(" AND ", $where);
            $stmt = $this->db->prepare($query);
            
            $stmt->execute($params);
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Error al actualizar: " . $e->getMessage());
        }
    }
    
    public function delete($table, $conditions) {
        try {
            $this->db->beginTransaction();
            
            if (isset($_SESSION['SISTEMA']['id'])) {
                $this->db->exec("SET @usuario_id = " . $_SESSION['SISTEMA']['id']);
            }

            if ($table === 'tlibro') {
                $query = "UPDATE $table SET eliminado = 1 WHERE ";
                $where = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "$key = :$key";
                }
                $query .= implode(" AND ", $where);
            } else {
                $query = "DELETE FROM $table WHERE ";
                $where = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "$key = :$key";
                }
                $query .= implode(" AND ", $where);
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($conditions);
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Error al eliminar el libro: " . $e->getMessage());
        }
    }

    public function obtenerLibrosEliminados() {
        try {
            $query = "SELECT l.*, 
                     a.Nombre AS NombreAutor, 
                     e.Nombre AS NombreEditorial, 
                     g.Nombre AS NombreGenero, 
                     s.Nombre AS NombreSaga
                     FROM tlibro l
                     LEFT JOIN tautor a ON l.idAutor = a.id_Autor
                     LEFT JOIN teditorial e ON l.idEditorial = e.id_Editorial
                     LEFT JOIN tgenero g ON l.idGenero = g.id_Genero
                     LEFT JOIN tsaga s ON l.idSaga = s.id_Saga
                     WHERE l.eliminado = 1
                     ORDER BY l.Titulo";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerLibrosEliminados: " . $e->getMessage());
            return [];
        }
    }

    public function restaurarLibro($idLibro) {
        try {
            $this->db->beginTransaction();
            
            if (isset($_SESSION['SISTEMA']['id'])) {
                $this->db->exec("SET @usuario_id = " . $_SESSION['SISTEMA']['id']);
            }

            $query = "UPDATE tlibro SET eliminado = 0 WHERE id_Libro = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $idLibro, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Error al restaurar el libro: " . $e->getMessage());
        }
    }
}
?>