<?php
class TransportistasModel extends Query
{
    /* ===== LISTAR ===== */
    public function listar()
    {
        $sql = "SELECT 
                    id_transportista,
                    nombre,
                    tipo,
                    estatus
                FROM transportistas
                WHERE estatus = 1
                ORDER BY id_transportista DESC";
        return $this->selectAll($sql);
    }

    /* ===== EXISTENCIAS / DUPLICADOS ===== */
    // ¿Existe un transportista con (nombre, tipo)?
    public function existeNombreTipo($nombre, $tipo)
    {
        $sql = "SELECT id_transportista, estatus
                FROM transportistas
                WHERE LOWER(nombre) = LOWER(?) AND tipo = ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $tipo]);
    }

    // ¿Existe otro (distinto id) con el mismo (nombre, tipo)?
    public function existeNombreTipoOtro($nombre, $tipo, $id_transportista)
    {
        $sql = "SELECT id_transportista, estatus
                FROM transportistas
                WHERE LOWER(nombre) = LOWER(?) 
                  AND tipo = ?
                  AND id_transportista <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $tipo, $id_transportista]);
    }

    /* ===== CRUD ===== */
    public function registrar($nombre, $tipo)
    {
        $sql = "INSERT INTO transportistas (nombre, tipo, estatus)
                VALUES (?,?,1)";
        return $this->insertar($sql, [$nombre, $tipo]);
    }

    public function obtenerTransportista($id)
    {
        $sql = "SELECT 
                    id_transportista,
                    nombre,
                    tipo
                FROM transportistas
                WHERE id_transportista = ?
                LIMIT 1";
        return $this->select($sql, [$id]);
    }

    public function actualizar($id_transportista, $nombre, $tipo)
    {
        $sql = "UPDATE transportistas
                SET nombre = ?, tipo = ?
                WHERE id_transportista = ?";
        return $this->save($sql, [$nombre, $tipo, $id_transportista]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE transportistas
                SET estatus = 0
                WHERE id_transportista = ?";
        return $this->save($sql, [$id]);
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                    id_transportista,
                    nombre,
                    tipo
                FROM transportistas
                WHERE estatus = 1
                  AND (
                        LOWER(nombre) LIKE ?
                     OR LOWER(tipo)   LIKE ?
                  )
                ORDER BY id_transportista DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle]);
    }
}
