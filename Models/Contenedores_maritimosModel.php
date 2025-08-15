<?php
class Contenedores_maritimosModel extends Query
{
    /* ===== LISTAR ===== */
    public function listar()
    {
        $sql = "SELECT 
                    c.id_contenedor_maritimo AS id_contenedor,
                    c.numero_contenedor,
                    c.tipo, 
                    c.observaciones,
                    c.estatus
                FROM contenedores_maritimos c
                WHERE c.estatus = 1
                ORDER BY c.id_contenedor_maritimo DESC";
        return $this->selectAll($sql);
    }

 

    /* ===== OBTENER UNO ===== */
    public function obtener($id_contenedor_maritimo)
    {
        $sql = "SELECT 
                    c.id_contenedor_maritimo AS id_contenedor,
                    c.numero_contenedor,
                    c.tipo,
                    c.observaciones,
                    c.estatus
                FROM contenedores_maritimos c 
                WHERE c.id_contenedor_maritimo = ?
                LIMIT 1";
        return $this->select($sql, [$id_contenedor_maritimo]);
    }

    /* ===== DUPLICADOS POR NÚMERO ===== */
    public function existeNumero($numero_contenedor)
    {
        $sql = "SELECT id_contenedor_maritimo AS id_contenedor, estatus
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$numero_contenedor]);
    }

    public function existeNumeroOtro($numero_contenedor, $id_contenedor)
    {
        $sql = "SELECT id_contenedor_maritimo AS id_contenedor, estatus
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                  AND id_contenedor_maritimo <> ?
                LIMIT 1";
        return $this->select($sql, [$numero_contenedor, $id_contenedor]);
    }

    /* ===== CRUD ===== */
    public function registrar($numero_contenedor, $tipo, $observaciones)
    {
        // permite NULL mientras migras; si ya es NOT NULL en DB, asegúrate que venga un id válido
        $sql = "INSERT INTO contenedores_maritimos
                    (numero_contenedor, tipo, naviera_id, observaciones, estatus)
                VALUES (?,?,?,1)";
        return $this->insertar($sql, [$numero_contenedor, $tipo, $observaciones]);
    }

    public function reactivar($id_contenedor, $numero_contenedor, $tipo, $observaciones)
    {
        $sql = "UPDATE contenedores_maritimos
                SET numero_contenedor = ?, 
                    tipo = ?, 
                    observaciones = ?, 
                    estatus = 1
                WHERE id_contenedor_maritimo = ?";
        return $this->save($sql, [$numero_contenedor, $tipo, $observaciones, $id_contenedor]);
    }

    public function actualizar($id_contenedor, $numero_contenedor, $tipo, $observaciones)
    {
        $sql = "UPDATE contenedores_maritimos
                SET numero_contenedor = ?, 
                    tipo = ?,  
                    observaciones = ?
                WHERE id_contenedor_maritimo = ?";
        return $this->save($sql, [$numero_contenedor, $tipo, $observaciones, $id_contenedor]);
    }

    public function eliminar($id_contenedor)
    {
        $sql = "UPDATE contenedores_maritimos
                SET estatus = 0
                WHERE id_contenedor_maritimo = ?";
        return $this->save($sql, [$id_contenedor]);
    }

public function buscar($termino)
{
    $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
    $sql = "SELECT
                c.id_contenedor_maritimo AS id_contenedor,
                c.numero_contenedor,
                c.tipo, 
                c.observaciones
            FROM contenedores_maritimos c 
            WHERE c.estatus = 1
              AND (
                    LOWER(c.numero_contenedor) LIKE ?
                 OR LOWER(c.tipo)              LIKE ?
                 OR LOWER(IFNULL(c.observaciones,'')) LIKE ?
              )
            ORDER BY c.id_contenedor_maritimo DESC
            LIMIT 100";
    return $this->selectAll($sql, [$needle, $needle, $needle]);  
}

}
