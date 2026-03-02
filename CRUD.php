<?php
class CRUD {
    protected $db;

    public function __construct($conexion) {
        if (!$conexion instanceof PDO) {
            throw new InvalidArgumentException("Se esperaba una instancia de PDO");
        }
        $this->db = $conexion;
    }

    public function create($table, $data) {
        $this->validateTableName($table);
        
        $columns = implode(", ", array_keys($data));
        $values = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($data as $key => $val) {
                $stmt->bindValue(":$key", $val);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en create: " . $e->getMessage());
            return false;
        }
    }

    public function read($table, $conditions = []) {
        $this->validateTableName($table);
        
        $sql = "SELECT * FROM $table WHERE eliminado = 0";
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", array_map(function($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($conditions as $key => $val) {
                $stmt->bindValue(":$key", $val);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en read: " . $e->getMessage());
            return [];
        }
    }

    public function update($table, $data, $conditions) {
        $this->validateTableName($table);
        
        $set = implode(", ", array_map(function($key) {
            return "$key = :set_$key";
        }, array_keys($data)));
        
        $where = implode(" AND ", array_map(function($key) {
            return "$key = :cond_$key";
        }, array_keys($conditions)));
        
        $sql = "UPDATE $table SET $set WHERE $where";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $val) {
                $stmt->bindValue(":set_$key", $val);
            }
            
            foreach ($conditions as $key => $val) {
                $stmt->bindValue(":cond_$key", $val);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en update: " . $e->getMessage());
            return false;
        }
    }

    public function delete($table, $conditions) {
        $this->validateTableName($table);
        
        $where = implode(" AND ", array_map(function($key) {
            return "$key = :$key";
        }, array_keys($conditions)));
        
        $sql = "UPDATE $table SET eliminado = 1 WHERE $where";
        
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($conditions as $key => $val) {
                $stmt->bindValue(":$key", $val);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en delete (borrado lógico): " . $e->getMessage());
            return false;
        }
    }

    protected function validateTableName($table) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new InvalidArgumentException("Nombre de tabla inválido");
        }
    }
}
