<?php
class PuertosModel extends Query
{
    public function listar()
    {
        $sql = "SELECT p.id_puerto, p.nombre, c.nombre_ciudad 
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
 
                WHERE p.estatus = 1
                ORDER BY id_puerto DESC";
        return $this->selectAll($sql);
    }
    public function listarCiudades()
    {
        $sql = "SELECT id_ciudad, nombre_ciudad
                FROM ciudades
                WHERE estatus = 1
                ORDER BY id_ciudad DESC";
        return $this->selectAll($sql);
    }
    public function registrar($nombre,$id_ciudad)
    {
        $sql = "INSERT INTO puertos (nombre, ciudad_id) VALUES (?, ?)";
        return $this->insertar($sql, [$nombre,$id_ciudad]);
    } 
      public function existe($nombre)
    {
        $sql = "SELECT id_puerto
                FROM puertos
                WHERE estatus = 1 AND LOWER(nombre) = LOWER(?)";
        return $this->select($sql, [$nombre]);
    }   
    public function existePorNombreCiudad($nombre, $ciudadId)
{
    $sql = "SELECT id_puerto
            FROM puertos
            WHERE estatus = 1 AND LOWER(nombre) = LOWER(?) AND ciudad_id = ?";
    return $this->select($sql, [$nombre, $ciudadId]);
}

public function existeOtro($nombre, $ciudadId, $idPuerto)
{
    $sql = "SELECT id_puerto
            FROM puertos
            WHERE estatus = 1
              AND LOWER(nombre) = LOWER(?)
              AND ciudad_id = ?
              AND id_puerto <> ?";
    return $this->select($sql, [$nombre, $ciudadId, $idPuerto]);
}

    public function actualizar($id, $nombre,$ciudad)
    {
        $sql = "UPDATE puertos
                SET nombre = ?,
                    ciudad_id = ?
                WHERE id_puerto = ?";
        return $this->save($sql, [$nombre,$ciudad,$id]);
    }
    public function obtener($id)
    {
        $sql = "SELECT id_puerto, nombre, ciudad_id
                FROM puertos
                WHERE id_puerto = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }
    public function eliminar($id)
    {
        $sql = "UPDATE puertos SET estatus = 0 WHERE id_puerto = ?";
        return $this->save($sql, [$id]);
    }
    public function buscar($termino)
    {
        $sql = "SELECT p.id_puerto, p.nombre, c.nombre_ciudad 
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
 
                WHERE p.estatus = 1 AND LOWER(p.nombre) LIKE ?
                ORDER BY id_puerto DESC";   
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }

public function filtrar($term, $ciudadId)
{
    $sql = "SELECT p.id_puerto, p.nombre, c.nombre_ciudad
            FROM puertos p
            INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
            WHERE p.estatus = 1";
    $params = [];

    if ($term !== '') {
        $sql .= " AND LOWER(p.nombre) LIKE ?";
        $params[] = "%".mb_strtolower($term, 'UTF-8')."%";
    }
    if ($ciudadId !== '') {
        $sql .= " AND p.ciudad_id = ?";
        $params[] = $ciudadId;
    }

    $sql .= " ORDER BY p.id_puerto DESC";
    return $this->selectAll($sql, $params);
}

}
/*
    public function listarEstados()
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE estatus = 1
                ORDER BY id_estado DESC";
        return $this->selectAll($sql);
    }
        public function registrar($nombre,$id_estado)
    {
        $sql = "INSERT INTO ciudades (nombre_ciudad, estado_id) VALUES (?, ?)";
        return $this->insertar($sql, [$nombre,$id_estado]);
    } 
      public function existe($nombre)
    {
        $sql = "SELECT id_ciudad
                FROM ciudades
                WHERE estatus = 1 AND LOWER(nombre_ciudad) = LOWER(?)";
        return $this->select($sql, [$nombre]);
    }
    public function actualizar($id, $nombre,$estado)
    {
        $sql = "UPDATE ciudades
                SET nombre_ciudad = ?,
                    estado_id = ?
                WHERE id_ciudad = ?";
        return $this->save($sql, [$nombre,$estado,$id]);
    }
    public function obtener($id)
    {
        $sql = "SELECT id_ciudad, nombre_ciudad, estado_id
                FROM ciudades
                WHERE id_ciudad = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }
    public function eliminar($id)
    {
        $sql = "UPDATE ciudades SET estatus = 0 WHERE id_ciudad = ?";
        return $this->save($sql, [$id]);
    }
    public function buscar($termino)
    {
        $sql = "SELECT c.id_ciudad, c.nombre_ciudad, e.nombre_estado as estado
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
 
                WHERE c.estatus = 1 AND LOWER(c.nombre_ciudad) LIKE ?
                ORDER BY id_ciudad DESC";   
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }

     
    
 */
 