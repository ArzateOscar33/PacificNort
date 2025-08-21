<?php
class Movimiento_logisticoModel extends Query
{
    public function listar()
    {
        $sql = "SELECT tm.id_tipo_movimiento, tm.nombre, tm.tipo, tm.moneda, tm.tipo_operacion_id,
                    toper.nombre_operacion AS categoria
                FROM tipos_movimiento tm
                LEFT JOIN tipos_operacion toper ON toper.id_tipo_operacion = tm.tipo_operacion_id
                WHERE tm.estatus = 1
                ORDER BY tm.id_tipo_movimiento DESC";
        return $this->selectAll($sql);
    }

    public function registrar($nombre, $tipo, $moneda, $tipo_operacion_id)
    {
        $sql = "INSERT INTO tipos_movimiento (nombre, tipo, moneda, tipo_operacion_id, estatus)
                VALUES (?, ?, ?, ?, 1)";
        $datos = [$nombre, $tipo, $moneda, $tipo_operacion_id ?: null];
        return $this->insertar($sql, $datos);
    }


    public function existeMovimiento($nombre)
    {
        $sql = "SELECT id_tipo_movimiento FROM tipos_movimiento WHERE LOWER(nombre) = LOWER(?) AND estatus = 1";
        return $this->select($sql, [$nombre]);
    }

    public function obtener($id)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda, tipo_operacion_id
                FROM tipos_movimiento
                WHERE id_tipo_movimiento = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }


    public function actualizar($id, $nombre, $tipo, $moneda, $tipo_operacion_id)
    {
        $sql = "UPDATE tipos_movimiento
                SET nombre = ?, tipo = ?, moneda = ?, tipo_operacion_id = ?
                WHERE id_tipo_movimiento = ?";
        $datos = [$nombre, $tipo, $moneda, $tipo_operacion_id ?: null, $id];
        return $this->save($sql, $datos);
    }


    public function eliminar($id)
    {
        $sql = "UPDATE tipos_movimiento SET estatus = 0 WHERE id_tipo_movimiento = ?";
        return $this->save($sql, [$id]);
    }

    public function buscar($termino)
{
    $sql = "SELECT tm.id_tipo_movimiento, tm.nombre, tm.tipo, tm.moneda, tm.tipo_operacion_id,
                   toper.nombre_operacion AS categoria
            FROM tipos_movimiento tm
            LEFT JOIN tipos_operacion toper ON toper.id_tipo_operacion = tm.tipo_operacion_id
            WHERE tm.estatus = 1 AND LOWER(tm.nombre) LIKE ?
            ORDER BY tm.id_tipo_movimiento DESC";
    $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
    return $this->selectAll($sql, $param);
}

    public function filtrar($term, $tipo, $moneda, $categoria)
    {
        $sql = "SELECT tm.id_tipo_movimiento, tm.nombre, tm.tipo, tm.moneda, tm.tipo_operacion_id,
                    toper.nombre_operacion AS categoria
                FROM tipos_movimiento tm
                LEFT JOIN tipos_operacion toper ON toper.id_tipo_operacion = tm.tipo_operacion_id
                WHERE tm.estatus = 1";
        $params = [];

        if ($term !== '') {
            $sql .= " AND LOWER(tm.nombre) LIKE ?";
            $params[] = "%".mb_strtolower($term, 'UTF-8')."%";
        }
        if ($tipo !== '') {
            $sql .= " AND tm.tipo = ?";
            $params[] = $tipo;
        }
        if ($moneda !== '') {
            $sql .= " AND tm.moneda = ?";
            $params[] = $moneda;
        }
        if ($categoria !== '') {
            $sql .= " AND tm.tipo_operacion_id = ?";
            $params[] = (int)$categoria;
        }

        $sql .= " ORDER BY tm.id_tipo_movimiento DESC";
        return $this->selectAll($sql, $params);
    }

    // (Opcional) Métodos previos de filtro individual
    public function buscarFiltroTipo($tipo)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda
                FROM tipos_movimiento
                WHERE tipo = ? AND estatus = 1";
        return $this->selectAll($sql, [$tipo]);
    }

    public function buscarFiltroMoneda($moneda)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda
                FROM tipos_movimiento
                WHERE moneda = ? AND estatus = 1";
        return $this->selectAll($sql, [$moneda]);
    }
    public function catalogoTiposOperacion()
{
    $sql = "SELECT id_tipo_operacion, nombre_operacion
            FROM tipos_operacion
            WHERE estatus = 1
            ORDER BY id_tipo_operacion ASC";
    return $this->selectAll($sql);
}

}
