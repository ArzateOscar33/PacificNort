<?php
class RolesModel extends Query
{
    public function listar()
    {
        $sql = "SELECT * FROM roles";
        return $this->selectAll($sql);
    }

    public function registrar($nombre, $descripcion)
    {
        $sql = "INSERT INTO roles (nombre, descripcion) VALUES (?, ?)";
        $datos = array($nombre, $descripcion);
        return $this->save($sql, $datos);
    }
    public function existeRol($nombre)
    {
        $sql = "SELECT COUNT(*) AS total FROM roles WHERE nombre = ?";
        return $this->select($sql, [$nombre]);
    }

     function obtener($id)
    {
        $sql = "SELECT * FROM roles WHERE id_rol = ?";
        return $this->select($sql, [$id]);
    }

    public function actualizar($id, $nombre, $descripcion)
    {
        $sql = "UPDATE roles SET nombre = ?, descripcion = ? WHERE id_rol = ?";
        $datos = array($nombre, $descripcion, $id);
        return $this->save($sql, $datos);
    }
 
    public function eliminar($id)
    {
        $sql = "DELETE FROM roles WHERE id_rol = ?";
        return $this->save($sql, [$id]);
    }

    public function existeRelacionUsuarios($idRol)
    {
        $sql = "SELECT COUNT(*) AS total FROM roles_usuario WHERE rol_id = ?";
        return $this->select($sql, [$idRol]);
    }
    public function buscarRol($termino)
    {
        $sql = "SELECT * FROM roles WHERE nombre LIKE ?";
        $param = ["%$termino%"];
        return $this->selectAll($sql, $param);
    }

}
