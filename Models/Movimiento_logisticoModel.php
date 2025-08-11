<?php
class Movimiento_logisticoModel extends Query
{
    public function listar()
    {
        $sql = "SELECT * FROM tipos_movimiento WHERE estatus = 1 ORDER BY id_tipo_movimiento DESC";
        return $this->selectAll($sql);
    }

    public function registrar($nombre, $tipo, $moneda)
    {
        $sql = "INSERT INTO tipos_movimiento (nombre, tipo, moneda, estatus) VALUES (?, ?, ?, 1)";
        $datos = [$nombre, $tipo, $moneda];
        return $this->insertar($sql, $datos);
    }

    public function existeMovimiento($nombre)
    {
        $sql = "SELECT id_tipo_movimiento FROM tipos_movimiento WHERE LOWER(nombre) = LOWER(?) AND estatus = 1";
        return $this->select($sql, [$nombre]);
    }

    public function obtener($id)
    {
        $sql = "SELECT * FROM tipos_movimiento WHERE id_tipo_movimiento = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function actualizar($id, $nombre, $tipo, $moneda)
    {
        $sql = "UPDATE tipos_movimiento SET nombre = ?, tipo = ?, moneda = ? WHERE id_tipo_movimiento = ?";
        $datos = [$nombre, $tipo, $moneda, $id];
        return $this->save($sql, $datos);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE tipos_movimiento SET estatus = 0 WHERE id_tipo_movimiento = ?";
        return $this->save($sql, [$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda
                FROM tipos_movimiento
                WHERE estatus = 1 AND LOWER(nombre) LIKE ?";
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }

    // ✅ NUEVO: filtro combinado
    public function filtrar($term, $tipo, $moneda)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda
                FROM tipos_movimiento
                WHERE estatus = 1";
        $params = [];

        if ($term !== '') {
            $sql .= " AND LOWER(nombre) LIKE ?";
            $params[] = "%".mb_strtolower($term, 'UTF-8')."%";
        }
        if ($tipo !== '') {
            $sql .= " AND tipo = ?";
            $params[] = $tipo; // 'gasto' | 'abono'
        }
        if ($moneda !== '') {
            $sql .= " AND moneda = ?";
            $params[] = $moneda; // 'PESOS' | 'DLLS'
        }

        $sql .= " ORDER BY id_tipo_movimiento DESC";
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
}
