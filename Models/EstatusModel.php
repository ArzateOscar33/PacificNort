<?php
class EstatusModel extends Query
{
    public function listar()
    {
        $sql = "SELECT id_estatus, nombre
                FROM estatus
                WHERE estatus = 1
                ORDER BY id_estatus DESC";
        return $this->selectAll($sql);
    }

    public function existe($nombre)
    {
        $sql = "SELECT id_estatus
                FROM estatus
                WHERE estatus = 1 AND LOWER(nombre) = LOWER(?)";
        return $this->select($sql, [$nombre]);
    }

    public function registrar($nombre)
    {
        $sql = "INSERT INTO estatus (nombre) VALUES (?)";
        return $this->insertar($sql, [$nombre]);
    }

    public function actualizar($id, $nombre)
    {
        $sql = "UPDATE estatus
                SET nombre = ?
                WHERE id_estatus = ?";
        return $this->save($sql, [$nombre, $id]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE estatus SET estatus = 0 WHERE id_estatus = ?";
        return $this->save($sql, [$id]);
    }

    public function obtener($id)
    {
        $sql = "SELECT id_estatus, nombre
                FROM estatus
                WHERE id_estatus = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }
 
    public function buscar($termino)
    {
        $sql = "SELECT id_estatus, nombre
                FROM estatus
                WHERE estatus = 1 AND LOWER(nombre) LIKE ?
                ORDER BY id_estatus DESC";
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }
}
