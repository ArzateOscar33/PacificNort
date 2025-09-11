<?php
class ClientesModel extends Query
{
    public function listar()
    {
        $sql="SELECT * FROM clientes WHERE estatus = 1";
        return $this->selectAll($sql);
    }

    public function existeCorreo($correo)
    {
        $sql = "SELECT id_cliente FROM clientes WHERE estatus = 1 AND LOWER(correo) = LOWER(?)";
        return $this->select($sql, [$correo]);
    }

    // OJO: id_cliente (no id_usuario)
    public function existeCorreoOtro($correo, $idCliente)
    {
        $sql = "SELECT id_cliente 
                FROM clientes 
                WHERE estatus = 1 
                  AND LOWER(correo) = LOWER(?) 
                  AND id_cliente <> ?";
        return $this->select($sql, [$correo, $idCliente]);
    }

    public function registrarCliente($nombre, $telefono, $correo, )
    {
        $sql = "INSERT INTO clientes (nombre, telefono, correo)
                VALUES (?,?,?)";
        return $this->insertar($sql, [$nombre, $telefono, $correo]);
    }

    // OJO: actualizar en clientes y por id_cliente
    public function actualizarCliente($nombre, $telefono, $correo, $id)
    {
        $sql = "UPDATE clientes
                SET nombre = ?,  telefono = ?, correo = ?
                WHERE id_cliente = ?";
        return $this->save($sql, [$nombre, $telefono, $correo, $id]);
    }

    public function obtenerCliente($id)
    {
        $sql = "SELECT * FROM clientes WHERE id_cliente = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }
    public function eliminar($id){
        $sql="UPDATE clientes set estatus=0 where id_cliente=?";
        return $this->save($sql,[$id]);
    }
    public function buscar($termino)
{
    $needle = "%".mb_strtolower($termino, 'UTF-8')."%";

    $sql = "SELECT 
                id_cliente, nombre,  telefono, correo, 
            FROM clientes
            WHERE estatus = 1
              AND (
                    LOWER(nombre)    LIKE ?
                 
                 OR LOWER(correo)    LIKE ?
                 OR REPLACE(telefono,' ','') LIKE REPLACE(?, ' ','')
                  
              )
            ORDER BY id_cliente DESC
            LIMIT 50";

    return $this->selectAll($sql, [$needle, $needle, $needle]);
}

}
