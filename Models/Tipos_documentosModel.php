<?php
class Tipos_documentosModel extends Query
{
    public function listar()
    {
        $sql = "SELECT * FROM tipos_documento WHERE activo = 1 ORDER BY id_tipo_documento DESC";
        return $this->selectAll($sql);
    }
    
    public function obtener($id)
    {
        $sql = "SELECT id_tipo_documento, clave, nombre, descripcion, aplica_sobre
                FROM tipos_documento
                WHERE id_tipo_documento = ? AND activo = 1";
        return $this->select($sql, [$id]);
    }

    public function existe($nombre)
    {
        $sql = "SELECT id_tipo_documento
                FROM tipos_documento
                WHERE activo = 1 AND LOWER(clave) = LOWER(?)";
        return $this->select($sql, [$nombre]);
    }

    public function registrar($clave, $nombre, $descripcion, $aplicaSobre)
    {
        $sql = "INSERT INTO tipos_documento (clave, nombre, descripcion, aplica_sobre,obligatorio_por_defecto)
                VALUES (?,?,?,?,1)";
        return $this->insertar($sql, [$clave, $nombre, $descripcion, $aplicaSobre]);
    }
    public function actualizar($id,$clave, $nombre, $descripcion, $aplicaSobre )
    {
        $sql = "UPDATE tipos_documento
                SET nombre = ? , descripcion = ?, aplica_sobre = ?,clave = ?
                WHERE id_tipo_documento = ?";
        return $this->save($sql, [$nombre, $descripcion, $aplicaSobre,$clave, $id]);
    }
    public function eliminar($id)
    {
        $sql = "UPDATE tipos_documento SET activo = 0 WHERE id_tipo_documento = ?";
        return $this->save($sql, [$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT id_tipo_documento, clave, nombre, descripcion, aplica_sobre 
                FROM tipos_documento
                WHERE activo = 1 AND LOWER(clave) LIKE ?
                ORDER BY id_tipo_documento DESC";
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }
 
}


