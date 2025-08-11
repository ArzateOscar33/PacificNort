<?php
class tipos_eventos_logisticosModel extends Query
{
    public function listar()
    {
        $sql = "SELECT * FROM tipos_evento_logistico WHERE estatus = 1 ORDER BY id_tipo_evento DESC";
        return $this->selectAll($sql);
    }


    public function obtener($id)
    {
        $sql = "SELECT id_tipo_evento, nombre
                FROM tipos_evento_logistico
                WHERE id_tipo_evento = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function existe($nombre)
    {
        $sql = "SELECT id_tipo_evento
                FROM tipos_evento_logistico
                WHERE estatus = 1 AND LOWER(nombre) = LOWER(?)";
        return $this->select($sql, [$nombre]);
    }

    public function registrar($nombre)
    {
        $sql = "INSERT INTO tipos_evento_logistico (nombre)
                VALUES (?)";
        return $this->insertar($sql, [$nombre]);
    }
        public function actualizar($id, $nombre)
    {
        $sql = "UPDATE tipos_evento_logistico
                SET nombre = ?
                WHERE id_tipo_evento = ?";
        return $this->save($sql, [$nombre, $id]);
    }
        public function eliminar($id)
    {
        $sql = "UPDATE tipos_evento_logistico SET estatus = 0 WHERE id_tipo_evento = ?";
        return $this->save($sql, [$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT id_tipo_evento, nombre 
                FROM tipos_evento_logistico
                WHERE estatus = 1 AND LOWER(nombre) LIKE ?
                ORDER BY id_tipo_evento DESC";
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }
     
}
