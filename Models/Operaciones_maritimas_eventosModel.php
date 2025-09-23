<?php
class Operaciones_maritimas_eventosModel extends Query
{
    private const TIPOS_ENTREGA = [6, 10]; // 6=Entrega Terrestre, 10=Entrega Marítimo

    /* ===== LISTAR (SOLO MARÍTIMO) ===== */
    public function listar()
    {
        $sql = "
            SELECT
                e.id_evento,
                te.nombre               AS evento,
                e.fecha,
                o.numero_operacion      AS operacion,
                cm.numero_contenedor    AS contenedor,   -- SOLO marítimo
                e.comentario
            FROM eventos_logisticos e
            LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
            LEFT JOIN operaciones o             ON o.id_operacion   = e.operacion_id
            LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
            LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE e.estatus = 1
              AND o.tipo_operacion_id = 1        -- 1 = Marítimo
            ORDER BY e.fecha DESC, e.id_evento DESC
        ";
        return $this->selectAll($sql);
    }

    public function listarPaginado(int $page, int $perPage, ?int $opId = null, ?int $contId = null, string $q = ''): array
    {
        $perPage = min(100, max(1, $perPage));
        $offset  = max(0, ($page - 1) * $perPage);

        $where  = ["e.estatus = 1", "o.tipo_operacion_id = 1"]; // SOLO marítimo
        $params = [];

        if (!empty($opId)) {
            $where[] = "e.operacion_id = ?";
            $params[] = $opId;
        }

        // Filtro por contenedor MARÍTIMO
        if (!empty($contId)) {
            $where[] = "e.cont_maritimo_operacion_id = ?";
            $params[] = $contId;
        }

        if ($q !== '') {
            $like = '%'.mb_strtolower($q, 'UTF-8').'%';
            $where[] = "("
                     . "LOWER(te.nombre) LIKE ? "
                     . "OR LOWER(e.comentario) LIKE ? "
                     . "OR LOWER(o.numero_operacion) LIKE ? "
                     . "OR LOWER(cm.numero_contenedor) LIKE ?"
                     . ")";
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // Total
        $countSql = "
            SELECT COUNT(*) AS total
            FROM eventos_logisticos e
            LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
            LEFT JOIN operaciones o             ON o.id_operacion   = e.operacion_id
            LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
            LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            $whereSql
        ";
        $rowCount = $this->select($countSql, $params);
        $total    = $rowCount ? (int)$rowCount['total'] : 0;

        // Data (orden: Operación ↑, Contenedor ↑, Fecha ↓, Id ↓)
        $dataSql = "
            SELECT
                e.id_evento,
                te.nombre AS evento,
                e.fecha,
                o.numero_operacion AS operacion,
                cm.numero_contenedor AS contenedor,
                e.comentario
            FROM eventos_logisticos e
            LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
            LEFT JOIN operaciones o             ON o.id_operacion   = e.operacion_id
            LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
            LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            $whereSql
            ORDER BY 
                o.numero_operacion ASC,
                contenedor ASC,
                e.fecha DESC,
                e.id_evento DESC
            LIMIT $perPage OFFSET $offset
        ";
        $rows = $this->selectAll($dataSql, $params);

        return ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
    }

    /* ===== AUTOCOMPLETES ===== */

    /** Operaciones marítimas (solo cuenta contenedores marítimos) */
    public function buscarOperacionesMaritimas(string $term, int $limit = 10): array
    {
        $limit = max(1, (int)$limit);
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

        $sql = "
            SELECT 
                o.id_operacion AS id,
                o.numero_operacion AS label,
                COUNT(DISTINCT cmo.id) AS maritimos
            FROM operaciones o
            LEFT JOIN contenedores_maritimos_operacion cmo
                   ON cmo.operacion_id = o.id_operacion
            WHERE o.tipo_operacion_id = 1
              AND LOWER(o.numero_operacion) LIKE ?
            GROUP BY o.id_operacion, o.numero_operacion
            ORDER BY o.numero_operacion ASC
            LIMIT $limit
        ";

        $rows = $this->selectAll($sql, [$needle]);
        return is_array($rows) ? $rows : [];
    }

    /** Contenedores MARÍTIMOS de una operación */
    public function buscarContenedoresDeOperacion(int $operacionId, string $term = '', int $limit = 15): array
    {
        $limit  = max(1, (int)$limit);
        $params = [$operacionId];
        $filtro = '';

        if ($term !== '') {
            $filtro = " AND LOWER(cm.numero_contenedor) LIKE ? ";
            $params[] = '%'.mb_strtolower($term, 'UTF-8').'%';
        }

        $sql = "
            SELECT 
                cmo.id               AS id,
                cm.numero_contenedor AS label,
                'MARITIMO'           AS tipo
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm 
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.operacion_id = ? 
              AND cm.estatus = 1
              $filtro
            ORDER BY cm.numero_contenedor ASC
            LIMIT $limit
        ";

        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    /* ===== TIPOS DE EVENTO ===== */
    public function listarTiposEvento(): array
    {
        $sql = "SELECT id_tipo_evento, nombre 
                FROM tipos_evento_logistico 
                WHERE id_tipo_operacion = 1 AND estatus = 1
                ORDER BY nombre ASC";
        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }

    /* ===== CRUD ===== */

    public function registrar(array $data, int $idUsuario): int
    {
        // Solo marítimo: forzamos contenedor_operacion_id = NULL
        $idMaritimo = !empty($data['cont_maritimo_operacion_id']) ? (int)$data['cont_maritimo_operacion_id'] : null;

        if ($idMaritimo === null) {
            // sin contenedor marítimo no registramos
            return 0;
        }

        $sql = "INSERT INTO eventos_logisticos 
                (operacion_id, contenedor_operacion_id, cont_maritimo_operacion_id, tipo_evento_id, fecha, comentario, creado_por)
                VALUES (?, NULL, ?, ?, ?, ?, ?)";
        $params = [
            (int)$data['operacion_id'],
            $idMaritimo,
            (int)$data['tipo_evento_id'],
            $data['fecha'],                           // 'YYYY-MM-DD'
            $data['comentario'] ?? null,
            $idUsuario ?: null
        ];
        return (int)$this->insertar($sql, $params);
    }

    public function actualizar(array $data): bool
    {
        // Solo marítimo
        $sql = "UPDATE eventos_logisticos
                SET operacion_id = ?, 
                    contenedor_operacion_id = NULL,
                    cont_maritimo_operacion_id = ?, 
                    tipo_evento_id = ?, 
                    fecha = ?, 
                    comentario = ?
                WHERE id_evento = ? 
                  AND estatus = 1";
        $params = [
            (int)$data['operacion_id'],
            !empty($data['cont_maritimo_operacion_id']) ? (int)$data['cont_maritimo_operacion_id'] : null,
            (int)$data['tipo_evento_id'],
            $data['fecha'],
            $data['comentario'] ?? null,
            (int)$data['id_evento']
        ];
        return $this->save($sql, $params);
    }

    public function obtenerEvento(int $id)
    {
        $sql = "SELECT 
                    e.id_evento,
                    e.operacion_id,
                    NULL AS contenedor_operacion_id,            -- forzado a null (solo marítimo)
                    e.cont_maritimo_operacion_id,
                    e.tipo_evento_id,
                    e.fecha,
                    e.comentario,
                    o.numero_operacion AS operacion_label,
                    cm.numero_contenedor AS contenedor_label
                FROM eventos_logisticos e
                LEFT JOIN operaciones o ON o.id_operacion = e.operacion_id
                LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
                LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                WHERE e.id_evento = ? AND e.estatus = 1
                LIMIT 1";
        return $this->select($sql, [$id]);
    }

    /* Duplicados SOLO marítimos */
    public function existeEventoMaritimoDuplicado(int $contMaritimoOperacionId, int $tipoEventoId, ?int $excluirId = null): bool
    {
        $sql = "SELECT id_evento
                FROM eventos_logisticos
                WHERE estatus = 1
                  AND cont_maritimo_operacion_id = ?
                  AND tipo_evento_id = ?"
              . ($excluirId ? " AND id_evento <> ?" : "")
              . " LIMIT 1";

        $params = $excluirId
            ? [$contMaritimoOperacionId, $tipoEventoId, $excluirId]
            : [$contMaritimoOperacionId, $tipoEventoId];

        return (bool)$this->select($sql, $params);
    }

    public function desactivar(int $idEvento): bool
    {
        $sql = "UPDATE eventos_logisticos
                SET estatus = 0
                WHERE id_evento = ? AND estatus = 1";
        return $this->save($sql, [$idEvento]);
    }

    public function listarTiposEventoPorTipoOperacion(?int $tipoOperacionId): array
    {
        $params = [];
        $sql = "SELECT id_tipo_evento, nombre
                FROM tipos_evento_logistico
                WHERE estatus = 1";
        if (!is_null($tipoOperacionId)) {
            $sql .= " AND id_tipo_operacion = ?";
            $params[] = $tipoOperacionId;
        }
        $sql .= " ORDER BY nombre ASC";
        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public function existeEventoEntregaOperacion(int $operacionId): bool
    {
        if ($operacionId <= 0) return false;
        $in = implode(',', array_map('intval', self::TIPOS_ENTREGA));
        $sql = "SELECT 1
                  FROM eventos_logisticos
                 WHERE operacion_id = ?
                   AND estatus = 1
                   AND tipo_evento_id IN ($in)
                 LIMIT 1";
        $row = $this->select($sql, [$operacionId]);
        return !empty($row);
    }
}
