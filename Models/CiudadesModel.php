<?php
class CiudadesModel extends Query
{
    public function listar()
    {
        $sql = "SELECT c.id_ciudad, c.nombre_ciudad, e.nombre_estado as estado
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
 
                WHERE c.estatus = 1
                ORDER BY id_estado DESC";
        return $this->selectAll($sql);
    }

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

     
    public function filtrar($term, $estadoId)
{
    $sql = "SELECT c.id_ciudad, c.nombre_ciudad, e.nombre_estado AS estado
            FROM ciudades c
            INNER JOIN estados e ON c.estado_id = e.id_estado
            WHERE c.estatus = 1";
    $params = [];

    if ($term !== '') {
        $sql .= " AND LOWER(c.nombre_ciudad) LIKE ?";
        $params[] = "%".mb_strtolower($term, 'UTF-8')."%";
    }
    if ($estadoId !== '') {
        $sql .= " AND c.estado_id = ?";
        $params[] = $estadoId;
    }

    $sql .= " ORDER BY c.id_ciudad DESC";
    return $this->selectAll($sql, $params);
}
 
}