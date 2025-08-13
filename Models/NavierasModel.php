<?php
class NavierasModel extends Query
{
    /* ===== LISTAR ===== */
    public function listar()
    {
        $sql = "SELECT 
                    id_naviera,
                    nombre,
                    contacto,
                    estatus
                FROM navieras
                WHERE estatus = 1
                ORDER BY id_naviera DESC";
        return $this->selectAll($sql);
    }

    /* ===== EXISTENCIAS / DUPLICADOS ===== */
    public function existeNombre($nombre)
    {
        $sql = "SELECT id_naviera, estatus
                FROM navieras
                WHERE LOWER(nombre) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }

    public function existeNombreOtro($nombre, $id_naviera)
    {
        $sql = "SELECT id_naviera, estatus
                FROM navieras
                WHERE LOWER(nombre) = LOWER(?)
                  AND id_naviera <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $id_naviera]);
    }

    /* ===== CRUD ===== */
    public function registrar($nombre, $contacto)
    {
        $sql = "INSERT INTO navieras (nombre, contacto, estatus)
                VALUES (?,?,1)";
        return $this->insertar($sql, [$nombre, $contacto]);
    }

    public function obtenerNaviera($id)
    {
        $sql = "SELECT 
                    id_naviera AS id_naviera,
                    nombre,
                    contacto
                FROM navieras
                WHERE id_naviera = ?
                LIMIT 1";
        return $this->select($sql, [$id]);
    }

    public function actualizar($id_naviera, $nombre, $contacto)
    {
        $sql = "UPDATE navieras
                SET nombre = ?, contacto = ?
                WHERE id_naviera = ?";
        return $this->save($sql, [$nombre, $contacto, $id_naviera]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE navieras
                SET estatus = 0
                WHERE id_naviera = ?";
        return $this->save($sql, [$id]);
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                    id_naviera,
                    nombre,
                    contacto
                FROM navieras
                WHERE estatus = 1
                  AND (
                        LOWER(nombre)   LIKE ?
                     OR LOWER(contacto) LIKE ?
                  )
                ORDER BY id_naviera DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle]);
    }
}
