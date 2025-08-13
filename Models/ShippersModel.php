<?php
class ShippersModel extends Query
{
    /* ===== LISTAR ===== */
    public function listar()
    {
        $sql = "SELECT 
                    id_shipper,
                    nombre,
                    contacto,
                    direccion,
                    estatus
                FROM shippers
                WHERE estatus = 1
                ORDER BY id_shipper DESC";
        return $this->selectAll($sql);
    }

    /* ===== EXISTENCIAS / DUPLICADOS ===== */
    public function existeNombre($nombre)
    {
        $sql = "SELECT id_shipper, estatus
                FROM shippers
                WHERE LOWER(nombre) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }

    public function existeNombreOtro($nombre, $id_shipper)
    {
        $sql = "SELECT id_shipper, estatus
                FROM shippers
                WHERE LOWER(nombre) = LOWER(?)
                  AND id_shipper <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $id_shipper]);
    }

    /* ===== CRUD ===== */
    public function registrar($nombre, $contacto, $direccion)
    {
        $sql = "INSERT INTO shippers (nombre, contacto, direccion, estatus)
                VALUES (?,?,?,1)";
        return $this->insertar($sql, [$nombre, $contacto, $direccion]);
    }

    public function obtenerShipper($id)
    {
        $sql = "SELECT 
                    id_shipper,
                    nombre,
                    contacto,
                    direccion
                FROM shippers
                WHERE id_shipper = ?
                LIMIT 1";
        return $this->select($sql, [$id]);
    }

    public function actualizar($id_shipper, $nombre, $contacto, $direccion)
    {
        $sql = "UPDATE shippers
                SET nombre = ?, contacto = ?, direccion = ?
                WHERE id_shipper = ?";
        return $this->save($sql, [$nombre, $contacto, $direccion, $id_shipper]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE shippers
                SET estatus = 0
                WHERE id_shipper = ?";
        return $this->save($sql, [$id]);
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                    id_shipper,
                    nombre,
                    contacto,
                    direccion
                FROM shippers
                WHERE estatus = 1
                  AND (
                        LOWER(nombre)    LIKE ?
                     OR LOWER(contacto)  LIKE ?
                     OR LOWER(direccion) LIKE ?
                  )
                ORDER BY id_shipper DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle, $needle]);
    }
}
