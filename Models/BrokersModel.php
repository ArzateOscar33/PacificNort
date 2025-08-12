<?php
class BrokersModel extends Query
{
  public function listar(){
    $sql="SELECT *from brokers
            WHERE estatus=1";
    return $this->selectAll($sql);
  }
 public function existeNombre($nombre)
    {
        $sql = "SELECT id_broker, estatus
                FROM brokers
                WHERE LOWER(nombre) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }
 
    public function registrar($nombre, $contacto)
    {
        $sql = "INSERT INTO brokers (nombre, contacto, estatus)
                VALUES (?,?,1)";
        return $this->insertar($sql, [$nombre, $contacto]);
    }

    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                   nombre,contacto
                FROM  brokers
                WHERE estatus = 1
                AND (
                        LOWER(nombre)        LIKE ?
                    OR LOWER(contacto)     LIKE ?
                     
                )
                ORDER BY id_broker DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle]);
    }
       public function obtenerBroker($id)
    {
        $sql = "SELECT id_broker as id,nombre,contacto FROM brokers WHERE id_broker = ? AND estatus=1"; 
        return $this->select($sql, [$id]);
    }
    public function actualizar($id_broker, $nombre, $direccion)
    {
        $sql = "UPDATE brokers
                SET nombre = ?, contacto = ?
                WHERE id_broker = ?";
        return $this->save($sql, [$nombre, $direccion, $id_broker]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE brokers SET estatus = 0 WHERE id_broker = ?";
        return $this->save($sql, [$id]);
    }
}
