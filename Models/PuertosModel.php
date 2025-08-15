<?php
class PuertosModel extends Query
{
    /**
     * Lista paginada con filtros:
     * - $q: texto libre (nombre de puerto o nombre de ciudad)
     * - $ciudadId: filtra por ciudad específica
     * Devuelve: ['rows' => [...], 'total' => int]
     */
    public function listarPaginado(int $offset, int $limit, ?string $q = null, $ciudadId = null): array
    {
        $offset = max(0, (int)$offset);
        $limit  = in_array((int)$limit, [25, 50], true) ? (int)$limit : 25;

        $where  = "p.estatus = 1";
        $params = [];

        if ($q !== null && $q !== '') {
            $needle  = "%" . mb_strtolower($q, 'UTF-8') . "%";
            $where  .= " AND (LOWER(p.nombre) LIKE ? OR LOWER(c.nombre_ciudad) LIKE ?)";
            $params[] = $needle;
            $params[] = $needle;
        }

        if ($ciudadId !== null && $ciudadId !== '' && (int)$ciudadId > 0) {
            $where  .= " AND p.ciudad_id = ?";
            $params[] = (int)$ciudadId;
        }

        $sql = "SELECT 
                    p.id_puerto,
                    p.nombre,
                    p.ciudad_id,
                    c.nombre_ciudad
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
                WHERE $where
                ORDER BY p.id_puerto DESC
                LIMIT $limit OFFSET $offset";
        $rows = $this->selectAll($sql, $params);

        $sqlCount = "SELECT COUNT(*) AS total
                     FROM puertos p
                     INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
                     WHERE $where";
        $count = $this->select($sqlCount, $params);

        return [
            'rows'  => $rows ?: [],
            'total' => (int)($count['total'] ?? 0),
        ];
    }

    /**
     * Búsqueda para sugerencias (sin paginar, máx 100).
     * Opcional: filtra por ciudad_id si se proporciona.
     */
    public function buscar($termino, $ciudadId = null)
    {
        $needle = "%" . mb_strtolower((string)$termino, 'UTF-8') . "%";
        $params = [$needle];

        $extra = "";
        if ($ciudadId !== null && $ciudadId !== '' && (int)$ciudadId > 0) {
            $extra    = " AND p.ciudad_id = ?";
            $params[] = (int)$ciudadId;
        }

        $sql = "SELECT 
                    p.id_puerto,
                    p.nombre,
                    p.ciudad_id,
                    c.nombre_ciudad
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
                WHERE p.estatus = 1
                  AND LOWER(p.nombre) LIKE ?
                  $extra
                ORDER BY p.id_puerto DESC
                LIMIT 100";
        return $this->selectAll($sql, $params);
    }

    /* =======================
       Métodos existentes / compat
       ======================= */

    public function listar()
    {
        $sql = "SELECT p.id_puerto, p.nombre, p.ciudad_id, c.nombre_ciudad 
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
                WHERE p.estatus = 1
                ORDER BY p.id_puerto DESC";
        return $this->selectAll($sql);
    }

    public function listarCiudades()
    {
        $sql = "SELECT id_ciudad, nombre_ciudad
                FROM ciudades
                WHERE estatus = 1
                ORDER BY id_ciudad DESC";
        return $this->selectAll($sql);
    }

    public function registrar($nombre, $id_ciudad)
    {
        $sql = "INSERT INTO puertos (nombre, ciudad_id, estatus) VALUES (?, ?, 1)";
        return $this->insertar($sql, [$nombre, $id_ciudad]);
    }

    // Duplicado solo por nombre (compat)
    public function existe($nombre)
    {
        $sql = "SELECT id_puerto
                FROM puertos
                WHERE estatus = 1 AND LOWER(nombre) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }

    // Duplicado por (nombre, ciudad)
    public function existePorNombreCiudad($nombre, $ciudadId)
    {
        $sql = "SELECT id_puerto
                FROM puertos
                WHERE estatus = 1
                  AND LOWER(nombre) = LOWER(?)
                  AND ciudad_id = ?
                LIMIT 1";
        return $this->select($sql, [$nombre, (int)$ciudadId]);
    }

    // Duplicado en edición por (nombre, ciudad)
    public function existeOtro($nombre, $ciudadId, $idPuerto)
    {
        $sql = "SELECT id_puerto
                FROM puertos
                WHERE estatus = 1
                  AND LOWER(nombre) = LOWER(?)
                  AND ciudad_id = ?
                  AND id_puerto <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, (int)$ciudadId, (int)$idPuerto]);
    }

    public function actualizar($id, $nombre, $ciudad)
    {
        $sql = "UPDATE puertos
                SET nombre = ?,
                    ciudad_id = ?
                WHERE id_puerto = ?";
        return $this->save($sql, [$nombre, $ciudad, $id]);
    }

    public function obtener($id)
    {
        $sql = "SELECT id_puerto, nombre, ciudad_id
                FROM puertos
                WHERE id_puerto = ? AND estatus = 1
                LIMIT 1";
        return $this->select($sql, [$id]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE puertos SET estatus = 0 WHERE id_puerto = ?";
        return $this->save($sql, [$id]);
    }

    // Compat: búsqueda simple (ya cubierta por buscar($term, $ciudadId))
    public function buscarCompat($termino)
    {
        $sql = "SELECT p.id_puerto, p.nombre, c.nombre_ciudad 
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
                WHERE p.estatus = 1 AND LOWER(p.nombre) LIKE ?
                ORDER BY p.id_puerto DESC";
        $param = ["%".mb_strtolower($termino, 'UTF-8')."%"];
        return $this->selectAll($sql, $param);
    }

    // Compat: filtrar existente (cubierto por listarPaginado)
    public function filtrar($term, $ciudadId)
    {
        $sql = "SELECT p.id_puerto, p.nombre, c.nombre_ciudad, p.ciudad_id
                FROM puertos p
                INNER JOIN ciudades c ON p.ciudad_id = c.id_ciudad
                WHERE p.estatus = 1";
        $params = [];

        if ($term !== '') {
            $sql     .= " AND LOWER(p.nombre) LIKE ?";
            $params[] = "%".mb_strtolower($term, 'UTF-8')."%";
        }
        if ($ciudadId !== '') {
            $sql     .= " AND p.ciudad_id = ?";
            $params[] = $ciudadId;
        }

        $sql .= " ORDER BY p.id_puerto DESC";
        return $this->selectAll($sql, $params);
    }
}
