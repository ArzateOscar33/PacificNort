<?php
class tipos_eventos_logisticosModel extends Query
{
    public function listar()
    {
        $sql = "SELECT t.id_tipo_evento, t.nombre, t.id_tipo_operacion,
                       o.nombre_operacion
                FROM tipos_evento_logistico t
                LEFT JOIN tipos_operacion o ON o.id_tipo_operacion = t.id_tipo_operacion
                WHERE t.estatus = 1
                ORDER BY t.id_tipo_evento DESC";
        return $this->selectAll($sql);
    }

    public function obtener($id)
    {
        $sql = "SELECT t.id_tipo_evento, t.nombre, t.id_tipo_operacion
                FROM tipos_evento_logistico t
                WHERE t.id_tipo_evento = ? AND t.estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function existe($nombre, $tipo_operacion_id, $excluirId = null)
    {
        $params = [mb_strtolower($nombre, 'UTF-8')];

        $sql = "SELECT id_tipo_evento
                FROM tipos_evento_logistico
                WHERE estatus = 1
                  AND LOWER(nombre) = ?
                  AND ".($tipo_operacion_id === '' || $tipo_operacion_id === null ? "id_tipo_operacion IS NULL" : "id_tipo_operacion = ?");

        if ($tipo_operacion_id !== '' && $tipo_operacion_id !== null) {
            $params[] = $tipo_operacion_id;
        }

        if (!empty($excluirId)) {
            $sql .= " AND id_tipo_evento <> ?";
            $params[] = $excluirId;
        }
        return $this->select($sql, $params);
    }

    public function registrar($nombre, $tipo_operacion_id)
    {
        $sql = "INSERT INTO tipos_evento_logistico (nombre, id_tipo_operacion)
                VALUES (?, ?)";
        // si quieres permitir NULL cuando no se selecciona, cámbialo en el controlador
        return $this->insertar($sql, [$nombre, $tipo_operacion_id]);
    }

    public function actualizar($id, $nombre, $tipo_operacion_id)
    {
        $sql = "UPDATE tipos_evento_logistico
                SET nombre = ?, id_tipo_operacion = ?
                WHERE id_tipo_evento = ?";
        return $this->save($sql, [$nombre, $tipo_operacion_id, $id]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE tipos_evento_logistico SET estatus = 0 WHERE id_tipo_evento = ?";
        return $this->save($sql, [$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT t.id_tipo_evento, t.nombre, t.id_tipo_operacion, o.nombre_operacion
                FROM tipos_evento_logistico t
                LEFT JOIN tipos_operacion o ON o.id_tipo_operacion = t.id_tipo_operacion
                WHERE t.estatus = 1
                  AND (LOWER(t.nombre) LIKE ? OR LOWER(o.nombre_operacion) LIKE ?)
                ORDER BY t.id_tipo_evento DESC";
        $like = "%".mb_strtolower($termino, 'UTF-8')."%";
        return $this->selectAll($sql, [$like, $like]);
    }

    public function listarTiposOperacionActivas()
    {
        $sql = "SELECT id_tipo_operacion, nombre_operacion
                FROM tipos_operacion
                WHERE estatus = 1
                ORDER BY nombre_operacion";
        return $this->selectAll($sql);
    }
}
