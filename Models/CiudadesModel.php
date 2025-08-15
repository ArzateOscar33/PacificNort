<?php
class CiudadesModel extends Query
{
    /**
     * Lista paginada con filtros:
     * - $q: texto libre (por nombre_ciudad o nombre_estado)
     * - $estadoId: filtra por estado específico
     * Devuelve: ['rows' => [...], 'total' => int]
     */
    public function listarPaginado(int $offset, int $limit, ?string $q = null, $estadoId = null): array
    {
        $offset = max(0, (int)$offset);
        $limit  = in_array((int)$limit, [25, 50], true) ? (int)$limit : 25;

        $where  = "c.estatus = 1";
        $params = [];

        if ($q !== null && $q !== '') {
            $needle = "%" . mb_strtolower($q, 'UTF-8') . "%";
            $where .= " AND (LOWER(c.nombre_ciudad) LIKE ? OR LOWER(e.nombre_estado) LIKE ?)";
            $params[] = $needle;
            $params[] = $needle;
        }

        if ($estadoId !== null && $estadoId !== '' && (int)$estadoId > 0) {
            $where .= " AND c.estado_id = ?";
            $params[] = (int)$estadoId;
        }

        $sql = "SELECT 
                    c.id_ciudad,
                    c.nombre_ciudad,
                    c.estado_id,
                    e.nombre_estado AS estado
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
                WHERE $where
                ORDER BY c.id_ciudad DESC
                LIMIT $limit OFFSET $offset";
        $rows = $this->selectAll($sql, $params);

        $sqlCount = "SELECT COUNT(*) AS total
                     FROM ciudades c
                     INNER JOIN estados e ON c.estado_id = e.id_estado
                     WHERE $where";
        $count = $this->select($sqlCount, $params);

        return [
            'rows'  => $rows ?: [],
            'total' => (int)($count['total'] ?? 0),
        ];
    }

    /**
     * Sugerencias (sin paginar, máx 100).
     * Opcional: filtra por estado_id si se pasa.
     */
    public function buscar($termino, $estadoId = null)
    {
        $needle = "%" . mb_strtolower((string)$termino, 'UTF-8') . "%";
        $params = [$needle];

        $extra = "";
        if ($estadoId !== null && $estadoId !== '' && (int)$estadoId > 0) {
            $extra   = " AND c.estado_id = ?";
            $params[] = (int)$estadoId;
        }

        $sql = "SELECT 
                    c.id_ciudad,
                    c.nombre_ciudad,
                    c.estado_id,
                    e.nombre_estado AS estado
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
                WHERE c.estatus = 1
                  AND LOWER(c.nombre_ciudad) LIKE ?
                  $extra
                ORDER BY c.id_ciudad DESC
                LIMIT 100";
        return $this->selectAll($sql, $params);
    }

    /* =======================
       Métodos existentes / compat
       ======================= */

    public function listar()
    {
        $sql = "SELECT c.id_ciudad, c.nombre_ciudad, e.nombre_estado AS estado, c.estado_id
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
                WHERE c.estatus = 1
                ORDER BY c.id_ciudad DESC"; // <--- FIX: antes estaba ORDER BY id_estado
        return $this->selectAll($sql);
    }

    public function listarEstados()
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE estatus = 1
                ORDER BY id_estado DESC";
        return $this->selectAll($sql);
    }

    public function registrar($nombre, $id_estado)
    {
        $sql = "INSERT INTO ciudades (nombre_ciudad, estado_id, estatus) VALUES (?, ?, 1)";
        return $this->insertar($sql, [$nombre, $id_estado]);
    }

    /**
     * Duplicado por (nombre_ciudad, estado_id) en activos
     */
    public function existe($nombre, $id_estado)
    {
        $sql = "SELECT id_ciudad
                FROM ciudades
                WHERE estatus = 1
                  AND LOWER(nombre_ciudad) = LOWER(?)
                  AND estado_id = ?
                LIMIT 1";
        return $this->select($sql, [$nombre, (int)$id_estado]);
    }

    /**
     * Duplicado en edición por (nombre_ciudad, estado_id)
     */
    public function existeEnOtro($nombre, $id_estado, $id_ciudad)
    {
        $sql = "SELECT id_ciudad
                FROM ciudades
                WHERE estatus = 1
                  AND LOWER(nombre_ciudad) = LOWER(?)
                  AND estado_id = ?
                  AND id_ciudad <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, (int)$id_estado, (int)$id_ciudad]);
    }

    public function actualizar($id, $nombre, $estado)
    {
        $sql = "UPDATE ciudades
                   SET nombre_ciudad = ?,
                       estado_id     = ?
                 WHERE id_ciudad = ?";
        return $this->save($sql, [$nombre, $estado, $id]);
    }

    public function obtener($id)
    {
        $sql = "SELECT id_ciudad, nombre_ciudad, estado_id
                FROM ciudades
                WHERE id_ciudad = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE ciudades SET estatus = 0 WHERE id_ciudad = ?";
        return $this->save($sql, [$id]);
    }

    // Compat: búsqueda simple por nombre (ya mejorada arriba con estado_id opcional)
    public function buscarCompat($termino)
    {
        $sql = "SELECT c.id_ciudad, c.nombre_ciudad, e.nombre_estado AS estado, c.estado_id
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
                WHERE c.estatus = 1 AND LOWER(c.nombre_ciudad) LIKE ?
                ORDER BY c.id_ciudad DESC";
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }

    // Compat: filtrar existente (se mantiene, pero ya cubierto por listarPaginado)
    public function filtrar($term, $estadoId)
    {
        $sql = "SELECT c.id_ciudad, c.nombre_ciudad, e.nombre_estado AS estado, c.estado_id
                FROM ciudades c
                INNER JOIN estados e ON c.estado_id = e.id_estado
                WHERE c.estatus = 1";
        $params = [];

        if ($term !== '') {
            $sql .= " AND LOWER(c.nombre_ciudad) LIKE ?";
            $params[] = "%".mb_strtolower($term, 'UTF-8')."%";
        }
        if ($estadoId !== '') {
            $sql .= " AND c.estado_id = ?";
            $params[] = $estadoId;
        }

        $sql .= " ORDER BY c.id_ciudad DESC";
        return $this->selectAll($sql, $params);
    }
}
