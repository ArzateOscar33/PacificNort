<?php
class EstadosModel extends Query
{
    public function listar()
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE estatus = 1
                ORDER BY id_estado DESC";
        return $this->selectAll($sql);
    }
    public function registrar($nombre)
    {
        $sql = "INSERT INTO estados (nombre_estado) VALUES (?)";
        return $this->insertar($sql, [$nombre]);
    }
      public function existe($nombre)
    {
        $sql = "SELECT id_estado
                FROM estados
                WHERE estatus = 1 AND LOWER(nombre_estado) = LOWER(?)";
        return $this->select($sql, [$nombre]);
    }
    public function actualizar($id, $nombre)
    {
        $sql = "UPDATE estados
                SET nombre_estado = ?
                WHERE id_estado = ?";
        return $this->save($sql, [$nombre, $id]);
    }
        public function eliminar($id)
    {
        $sql = "UPDATE estados SET estatus = 0 WHERE id_estado = ?";
        return $this->save($sql, [$id]);
    }
    public function obtener($id)
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE id_estado = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }
 
    public function buscar($termino)
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE estatus = 1 AND LOWER(nombre_estado) LIKE ?
                ORDER BY id_estado DESC";   // <-- FIX aquí
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }



    
}