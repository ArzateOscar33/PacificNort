<?php
class EstatusModel extends Query
{
    public function listar()
    {
        $sql = "SELECT id_estatus, nombre, color_hex
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

    public function registrar($nombre, $color_hex)
    {
        $sql = "INSERT INTO estatus (nombre, color_hex) VALUES (?, ?)";
        return $this->insertar($sql, [$nombre, $color_hex]);
    }

    public function actualizar($id, $nombre, $color_hex)
    {
        $sql = "UPDATE estatus
                SET nombre = ?, color_hex = ?
                WHERE id_estatus = ?";
        return $this->save($sql, [$nombre, $color_hex, $id]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE estatus SET estatus = 0 WHERE id_estatus = ?";
        return $this->save($sql, [$id]);
    }

    public function obtener($id)
    {
        $sql = "SELECT id_estatus, nombre, color_hex
                FROM estatus
                WHERE id_estatus = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT id_estatus, nombre, color_hex
                FROM estatus
                WHERE estatus = 1 AND LOWER(nombre) LIKE ?
                ORDER BY id_estatus DESC";
        return $this->selectAll($sql, ["%" . mb_strtolower($termino, 'UTF-8') . "%"]);
    }
}
