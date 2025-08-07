<?php
class Tipos_operacionModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getTiposOperacion()
    {
        $sql = "SELECT id_tipo_operacion, nombre_operacion
                FROM tipos_operacion
                WHERE estatus = 1
                ORDER BY id_tipo_operacion DESC";
        return $this->selectAll($sql);
    }

    public function registrarTipoOperacion($nombre)
    {
        $sql = "INSERT INTO tipos_operacion (nombre_operacion, estatus) VALUES (?, 1)";
        return $this->insertar($sql, [$nombre]);
    }

    public function existeTipoOperacion($nombre)
    {
        $sql = "SELECT id_tipo_operacion FROM tipos_operacion WHERE LOWER(nombre_operacion) = LOWER(?) AND estatus = 1";
        return $this->select($sql, [$nombre]);
    }

    public function getTipoOperacion($id)
    {
        $sql = "SELECT id_tipo_operacion, nombre_operacion FROM tipos_operacion WHERE id_tipo_operacion = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function actualizarTipoOperacion($id, $nombre)
    {
        $sql = "UPDATE tipos_operacion SET nombre_operacion = ? WHERE id_tipo_operacion = ?";
        return $this->save($sql, [$nombre, $id]);
    }

    public function eliminarTipoOperacion($id)
    {
        $sql = "UPDATE tipos_operacion SET estatus = 0 WHERE id_tipo_operacion = ?";
        return $this->save($sql, [$id]);
    }

    public function buscarTipoOperacion($termino)
    {
        $sql = "SELECT id_tipo_operacion, nombre_operacion 
                FROM tipos_operacion 
                WHERE nombre_operacion LIKE ? AND estatus = 1";
        $param = ["%$termino%"];
        return $this->selectAll($sql, $param);
    }

}
