<?php
class ForwardersModel extends Query
{
    /* ===== LISTAR ===== */
    public function listar()
    {
        $sql = "SELECT 
                    id_forwarder,
                    nombre,
                    contacto,
                    estatus
                FROM forwarders
                WHERE estatus = 1
                ORDER BY id_forwarder DESC";
        return $this->selectAll($sql);
    }

    /* ===== EXISTENCIAS / DUPLICADOS ===== */
    public function existeNombre($nombre)
    {
        $sql = "SELECT id_forwarder, estatus
                FROM forwarders
                WHERE LOWER(nombre) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }

    public function existeNombreOtro($nombre, $id_forwarder)
    {
        $sql = "SELECT id_forwarder, estatus
                FROM forwarders
                WHERE LOWER(nombre) = LOWER(?)
                  AND id_forwarder <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $id_forwarder]);
    }

    /* ===== CRUD ===== */
    public function registrar($nombre, $contacto)
    {
        $sql = "INSERT INTO forwarders (nombre, contacto, estatus)
                VALUES (?,?,1)";
        return $this->insertar($sql, [$nombre, $contacto]);
    }

    public function reactivar($id_forwarder, $nombre, $contacto)
    {
        $sql = "UPDATE forwarders
                SET nombre = ?, contacto = ?, estatus = 1
                WHERE id_forwarder = ?";
        return $this->save($sql, [$nombre, $contacto, $id_forwarder]);
    }

    public function obtener($id_forwarder)
    {
        $sql = "SELECT 
                    id_forwarder,
                    nombre,
                    contacto,
                    estatus
                FROM forwarders
                WHERE id_forwarder = ?
                LIMIT 1";
        return $this->select($sql, [$id_forwarder]);
    }

    public function actualizar($id_forwarder, $nombre, $contacto)
    {
        $sql = "UPDATE forwarders
                SET nombre = ?, contacto = ?
                WHERE id_forwarder = ?";
        return $this->save($sql, [$nombre, $contacto, $id_forwarder]);
    }

    public function eliminar($id_forwarder)
    {
        $sql = "UPDATE forwarders
                SET estatus = 0
                WHERE id_forwarder = ?";
        return $this->save($sql, [$id_forwarder]);
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                    id_forwarder,
                    nombre,
                    contacto
                FROM forwarders
                WHERE estatus = 1
                  AND (
                        LOWER(nombre)   LIKE ?
                     OR LOWER(contacto) LIKE ?
                  )
                ORDER BY id_forwarder DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle]);
    }
}
