<?php
class PermisosModel extends Query
{
    /* ===== LISTADOS ===== */
    public function listar()
    {
        $sql = "SELECT 
                    po.id_permiso,
                    u.id_usuario,
                    CONCAT_WS(' ', u.nombre, u.apellido) AS usuario,
                    t.id_tipo_operacion,
                    t.nombre_operacion AS tipo_operacion
                FROM permisos_operacion po
                INNER JOIN usuarios u        ON u.id_usuario = po.usuario_id
                INNER JOIN tipos_operacion t ON t.id_tipo_operacion = po.tipo_operacion_id
                WHERE po.estatus = 1
                  AND u.estatus  = 1
                  AND t.estatus  = 1
                ORDER BY po.id_permiso DESC";
        return $this->selectAll($sql);
    }

    public function listarUsuariosActivos()
    {
        $sql = "SELECT id_usuario, CONCAT_WS(' ', nombre, apellido) AS nombre
                FROM usuarios
                WHERE estatus = 1
                ORDER BY nombre ASC";
        return $this->selectAll($sql);
    }

    public function listarTiposOperacionActivos()
    {
        $sql = "SELECT id_tipo_operacion, nombre_operacion
                FROM tipos_operacion
                WHERE estatus = 1
                ORDER BY nombre_operacion ASC";
        return $this->selectAll($sql);
    }

    /* ===== OBTENER / EXISTENCIAS ===== */
    public function obtener($id_permiso)
    {
        $sql = "SELECT 
                    po.id_permiso, po.usuario_id, po.tipo_operacion_id, po.estatus,
                    CONCAT_WS(' ', u.nombre, u.apellido) AS usuario,
                    t.nombre_operacion
                FROM permisos_operacion po
                INNER JOIN usuarios u        ON u.id_usuario = po.usuario_id
                INNER JOIN tipos_operacion t ON t.id_tipo_operacion = po.tipo_operacion_id
                WHERE po.id_permiso = ?
                LIMIT 1";
        return $this->select($sql, [$id_permiso]);
    }

    public function existePair($usuario_id, $tipo_operacion_id)
    {
        $sql = "SELECT id_permiso, estatus
                FROM permisos_operacion
                WHERE usuario_id = ? AND tipo_operacion_id = ?
                LIMIT 1";
        return $this->select($sql, [$usuario_id, $tipo_operacion_id]);
    }

    public function existePairOtro($usuario_id, $tipo_operacion_id, $id_permiso)
    {
        $sql = "SELECT id_permiso, estatus
                FROM permisos_operacion
                WHERE usuario_id = ? AND tipo_operacion_id = ? AND id_permiso <> ?
                LIMIT 1";
        return $this->select($sql, [$usuario_id, $tipo_operacion_id, $id_permiso]);
    }

    /* ===== CRUD ===== */
    public function registrar($usuario_id, $tipo_operacion_id)
    {
        // Reactivar si existe apagado / evitar duplicado si ya activo
        $existe = $this->existePair($usuario_id, $tipo_operacion_id);
        if ($existe) {
            if ((int)$existe['estatus'] === 1) {
                return ['ok' => false, 'msg' => 'El permiso ya está asignado'];
            }
            $sql = "UPDATE permisos_operacion
                    SET estatus = 1, actualizado_en = NOW()
                    WHERE id_permiso = ?";
            $ok = $this->save($sql, [$existe['id_permiso']]);
            return $ok ? ['ok' => true, 'reactivado' => true] : ['ok' => false, 'msg' => 'No se pudo reactivar'];
        }

        $sql = "INSERT INTO permisos_operacion (usuario_id, tipo_operacion_id, estatus)
                VALUES (?,?,1)";
        $id = $this->insertar($sql, [$usuario_id, $tipo_operacion_id]);
        return $id ? ['ok' => true, 'id' => $id] : ['ok' => false, 'msg' => 'No se pudo asignar'];
    }

    public function actualizar($id_permiso, $usuario_id, $tipo_operacion_id)
    {
        // Evitar colisión con otro permiso igual
        $otro = $this->existePairOtro($usuario_id, $tipo_operacion_id, $id_permiso);
        if ($otro && (int)$otro['estatus'] === 1) {
            return ['ok' => false, 'msg' => 'Ya existe otro permiso con esa combinación'];
        }

        // Si existe otro igual pero estatus=0, podrías optar por mover/merge; aquí solo bloqueamos.
        $sql = "UPDATE permisos_operacion
                SET usuario_id = ?, tipo_operacion_id = ?, actualizado_en = NOW()
                WHERE id_permiso = ?";
        $ok = $this->save($sql, [$usuario_id, $tipo_operacion_id, $id_permiso]);
        return $ok ? ['ok' => true] : ['ok' => false, 'msg' => 'No se pudo actualizar'];
    }

    public function eliminar($id_permiso)
    {
        $sql = "UPDATE permisos_operacion
                SET estatus = 0, actualizado_en = NOW()
                WHERE id_permiso = ?";
        return $this->save($sql, [$id_permiso]);
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                    po.id_permiso,
                    CONCAT_WS(' ', u.nombre, u.apellido) AS usuario,
                    t.nombre_operacion AS tipo_operacion
                FROM permisos_operacion po
                INNER JOIN usuarios u        ON u.id_usuario = po.usuario_id
                INNER JOIN tipos_operacion t ON t.id_tipo_operacion = po.tipo_operacion_id
                WHERE po.estatus = 1 AND u.estatus = 1 AND t.estatus = 1
                  AND (
                        LOWER(u.nombre)           LIKE ?
                     OR LOWER(u.apellido)         LIKE ?
                     OR LOWER(u.correo)           LIKE ?
                     OR LOWER(t.nombre_operacion) LIKE ?
                  )
                ORDER BY po.id_permiso DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle, $needle, $needle]);
    }
}
