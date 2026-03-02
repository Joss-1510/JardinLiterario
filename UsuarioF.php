<?php
class UsuarioF {
    private $db;
    
    public function __construct($pdo) {
        $this->db = $pdo;
    }
    
    public function obtenerUsuariosCompletos() {
        try {
            $query = "SELECT 
                        u.id_Usuario, 
                        u.Nombre AS NombreUsuario, 
                        p.Nombre, 
                        p.Apellido, 
                        p.Email, 
                        p.Telefono, 
                        r.Nombre AS NombreRol, 
                        r.id_Rol AS idRol
                      FROM tusuario u
                      JOIN tpersona p ON u.idPersona = p.id_Persona
                      JOIN trol r ON u.idRol = r.id_Rol
                      WHERE u.eliminado = 0
                      ORDER BY p.Nombre, p.Apellido";
    
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerUsuariosCompletos: " . $e->getMessage());
            return [];
        }
    }    
    
    public function buscarUsuarios($nombre) {
        try {
            $query = "SELECT 
                        u.id_Usuario, 
                        u.Nombre AS NombreUsuario, 
                        p.Nombre, 
                        p.Apellido, 
                        p.Email, 
                        p.Telefono, 
                        r.Nombre AS NombreRol, 
                        r.id_Rol AS idRol
                      FROM tusuario u
                      JOIN tpersona p ON u.idPersona = p.id_Persona
                      JOIN trol r ON u.idRol = r.id_Rol
                      WHERE p.Nombre LIKE ? OR p.Apellido LIKE ? OR u.Nombre LIKE ?";
            
            $stmt = $this->db->prepare($query);
            $searchTerm = "%$nombre%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    public function create($table, $data) {
        try {
            if (empty($data)) {
                throw new Exception("Datos vacíos para la tabla $table");
            }
            if ($table === 'tusuario') {
                return $this->createUsuarioEspecial($data);
            }
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($query);
            foreach ($data as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(":$key", $value, $paramType);
            }
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            throw new Exception("Error al ejecutar la consulta");   
        } catch (PDOException $e) {
            error_log("Error PDO en create($table): " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        }
    }
    
    private function createUsuarioEspecial($data) {
        try {
            $query = "INSERT INTO tusuario 
                     (Nombre, Contra, idPersona, idRol) 
                      VALUES (:nombre, :contrasena, :idPersona, :idRol)";
            $stmt = $this->db->prepare($query);
            $contrasena = $data['Contra'];
            $stmt->bindValue(':nombre', $data['Nombre']);
            $stmt->bindValue(':contrasena', $contrasena);
            $stmt->bindValue(':idPersona', $data['idPersona'], PDO::PARAM_INT);
            $stmt->bindValue(':idRol', $data['idRol'], PDO::PARAM_INT);
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            throw new Exception("Error al crear usuario");
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            throw new Exception("Error de base de datos al crear usuario: " . $e->getMessage());
        }
    }
    
    public function read($table, $conditions = [], $limit = null) {
        try {
            $query = "SELECT * FROM $table";
            if (!empty($conditions)) {
                $where = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "$key = :$key";
                }
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
            $set = [];
            foreach ($data as $key => $value) {
                if ($key === 'Contra') {
                    $set[] = "Contra = :contrasena";
                    $data['contrasena'] = $value;
                    unset($data['Contra']);
                } else {
                    $set[] = "$key = :$key";
                }
            }
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :cond_$key";
                $data["cond_$key"] = $value;
            }
            $query = "UPDATE $table SET " . implode(", ", $set) . " WHERE " . implode(" AND ", $where);
            $stmt = $this->db->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error al actualizar registro: " . $e->getMessage());
            throw new Exception("Error al actualizar registro: " . $e->getMessage());
        }
    }
    
    public function delete($table, $conditions) {
        try {
            $this->db->beginTransaction();
    
            if ($table === 'tusuario') {
                // Solo marcar el usuario como eliminado, sin tocar idPersona
                $queryUsuario = "UPDATE tusuario SET eliminado = 1 WHERE ";
                $whereUsuario = [];
                foreach ($conditions as $key => $value) {
                    $whereUsuario[] = "$key = :$key";
                }
                $queryUsuario .= implode(" AND ", $whereUsuario);
                $stmtUsuario = $this->db->prepare($queryUsuario);
                $stmtUsuario->execute($conditions);
    
                // Marcar la persona como eliminada en TPersona, sin borrarla
                $queryPersona = "UPDATE tpersona SET eliminado = 1 WHERE id_Persona IN 
                                (SELECT idPersona FROM tusuario WHERE eliminado = 1)";
                $stmtPersona = $this->db->prepare($queryPersona);
                $stmtPersona->execute();
    
            } else {
                // Eliminación física solo para otras tablas sin restricciones
                $query = "DELETE FROM $table WHERE ";
                $where = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "$key = :$key";
                }
                $query .= implode(" AND ", $where);
                $stmt = $this->db->prepare($query);
                $stmt->execute($conditions);
            }
    
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception("Error al eliminar el usuario y la persona: " . $e->getMessage());
        }
    }                              

    public function obtenerUsuariosEliminados() {
        try {
            $query = "SELECT 
                        u.id_Usuario, 
                        u.Nombre AS NombreUsuario, 
                        p.Nombre, 
                        p.Apellido, 
                        p.Email, 
                        p.Telefono, 
                        r.Nombre AS NombreRol, 
                        r.id_Rol AS idRol
                      FROM tusuario u
                      LEFT JOIN tpersona p ON u.idPersona = p.id_Persona
                      LEFT JOIN trol r ON u.idRol = r.id_Rol
                      WHERE u.eliminado = 1
                      ORDER BY p.Nombre, p.Apellido";
    
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerUsuariosEliminados: " . $e->getMessage());
            return [];
        }
    }    

    public function restaurarUsuario($idUsuario) {
        try {
            $this->db->beginTransaction();
    
            // Obtener idPersona antes de restaurar
            $queryGetPersona = "SELECT idPersona FROM tusuario WHERE id_Usuario = :id";
            $stmtGetPersona = $this->db->prepare($queryGetPersona);
            $stmtGetPersona->bindParam(':id', $idUsuario, PDO::PARAM_INT);
            $stmtGetPersona->execute();
            $result = $stmtGetPersona->fetch(PDO::FETCH_ASSOC);
    
            if (!$result || empty($result['idPersona'])) {
                throw new Exception("No se encontró la persona asociada al usuario.");
            }
    
            $idPersona = $result['idPersona'];
    
            // Restaurar el usuario en TUsuario
            $queryUsuario = "UPDATE tusuario SET eliminado = 0 WHERE id_Usuario = :id";
            $stmtUsuario = $this->db->prepare($queryUsuario);
            $stmtUsuario->bindParam(':id', $idUsuario, PDO::PARAM_INT);
            $stmtUsuario->execute();
    
            // Restaurar la persona en TPersona
            $queryPersona = "UPDATE tpersona SET eliminado = 0 WHERE id_Persona = :idPersona";
            $stmtPersona = $this->db->prepare($queryPersona);
            $stmtPersona->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
            $stmtPersona->execute();
    
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en restaurarUsuario(): " . $e->getMessage());
            throw new Exception("Error al restaurar el usuario: " . $e->getMessage());
        }
    }             
}
?>