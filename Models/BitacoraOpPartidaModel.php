<?php
class BitacoraOpPartidaModel extends Query
{
    public function crear(
        ?int $usuarioId,
        string $modulo,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?string $detalle = null
    ) {
        $modulo  = trim($modulo);
        $accion  = trim($accion);
        $entidad = trim($entidad);
        $detalle = $detalle !== null ? trim($detalle) : null;

        $sql = "INSERT INTO bitacora (usuario_id, modulo, accion, entidad, entidad_id, detalle)
                VALUES (?, ?, ?, ?, ?, ?)";

        return $this->insertar($sql, [
            $usuarioId,
            $modulo,
            $accion,
            $entidad,
            $entidadId,
            $detalle
        ]);
    }

    public function desc(string $entidad, string $evento, array $info = []): string
    {
        $base = strtoupper($entidad) . " {$evento}";
        if (!empty($info)) {
            $kv = [];
            foreach ($info as $k => $v) {
                $kv[] = "{$k}={$v}";
            }
            $base .= " (" . implode(", ", $kv) . ")";
        }
        return $base;
    }

    public function contarLogs(array $filtros = [])
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) LIKE ?)";
            $params[] = '%' . $filtros['usuario'] . '%';
            $params[] = '%' . $filtros['usuario'] . '%';
        }

        if (!empty($filtros['entidad'])) {
            $where[] = "(
                b.entidad LIKE ?
                OR CAST(b.entidad_id AS CHAR) LIKE ?
                OR b.detalle LIKE ?
            )";
            $params[] = '%' . $filtros['entidad'] . '%';
            $params[] = '%' . $filtros['entidad'] . '%';
            $params[] = '%' . $filtros['entidad'] . '%';
        }

        if (!empty($filtros['modulo'])) {
            $where[] = "b.modulo = ?";
            $params[] = $filtros['modulo'];
        }

        if (!empty($filtros['accion'])) {
            $where[] = "b.accion = ?";
            $params[] = $filtros['accion'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(b.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(b.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql = "SELECT COUNT(*) AS total
                FROM bitacora b
                LEFT JOIN usuarios u ON u.id_usuario = b.usuario_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $row = $this->select($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    public function obtenerLogs(array $filtros = [])
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) LIKE ?)";
            $params[] = '%' . $filtros['usuario'] . '%';
            $params[] = '%' . $filtros['usuario'] . '%';
        }

        if (!empty($filtros['entidad'])) {
            $where[] = "(
                b.entidad LIKE ?
                OR CAST(b.entidad_id AS CHAR) LIKE ?
                OR b.detalle LIKE ?
            )";
            $params[] = '%' . $filtros['entidad'] . '%';
            $params[] = '%' . $filtros['entidad'] . '%';
            $params[] = '%' . $filtros['entidad'] . '%';
        }

        if (!empty($filtros['modulo'])) {
            $where[] = "b.modulo = ?";
            $params[] = $filtros['modulo'];
        }

        if (!empty($filtros['accion'])) {
            $where[] = "b.accion = ?";
            $params[] = $filtros['accion'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(b.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(b.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql = "SELECT
                    b.id_bitacora,
                    b.modulo,
                    b.accion,
                    b.entidad,
                    b.entidad_id,
                    b.detalle,
                    b.fecha,
                    b.usuario_id,
                    COALESCE(
                        NULLIF(u.nombre, ''),
                        NULLIF(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')), ' '),
                        'Sistema'
                    ) AS usuario_nombre
                FROM bitacora b
                LEFT JOIN usuarios u ON u.id_usuario = b.usuario_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY b.fecha DESC, b.id_bitacora DESC";

        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $sql .= " LIMIT " . (int)$filtros['offset'] . ", " . (int)$filtros['limit'];
        }

        return $this->selectAll($sql, $params);
    }
}
