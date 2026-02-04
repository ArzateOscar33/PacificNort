<?php
class Operaciones_maritimas_eventosModel extends Query
{
    // Tipos de evento que representan "entrega" (los que ya usabas)
    private const TIPOS_ENTREGA = [6, 10]; // 6=Entrega Terrestre, 10=Entrega Marítimo

    /* ======================================================================
     *  LISTADO MATRIZ (similar a Operaciones_maritimo_ferro_eventos_marModel)
     * ====================================================================== */

    /**
     * Listado paginado de parejas Operación + Contenedor marítimo
     * en formato matriz (una fila por contenedor, columnas dinámicas por tipo de evento).
     */
    public function listarEventosMarPaginado(
        int $page,
        int $perPage,
        ?int $opId = null,
        ?int $contMarOpId = null,
        string $q = ''
    ): array {
        $perPage = min(100, max(1, $perPage));
        $offset  = max(0, ($page - 1) * $perPage);

        // ---- Filtros base (solo operaciones MARÍTIMAS)
        $where  = ["o.tipo_operacion_id = 1"]; // 1 = Marítimo
        $params = [];

        // Excluir operaciones Cancelado(6) o Finalizada(7)
        $where[] = "o.estatus_id NOT IN (6, 7)";

        if (!empty($opId)) {
            $where[]  = "o.id_operacion = ?";
            $params[] = (int)$opId;
        }

        if (!empty($contMarOpId)) {
            $where[]  = "cmo.id = ?";
            $params[] = (int)$contMarOpId; // contenedores_maritimos_operacion.id
        }

        if ($q !== '') {
            $like = '%'.mb_strtolower($q, 'UTF-8').'%';
            $where[] = "("
                     . "LOWER(o.numero_operacion) LIKE ? "
                     . "OR LOWER(cm.numero_contenedor) LIKE ?"
                     . ")";
            array_push($params, $like, $like);
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ---- 1) TOTAL de renglones (parejas op + cmo) independientemente de eventos
        $sqlCount = "
            SELECT COUNT(*) AS total_rows
            FROM (
                SELECT o.id_operacion, cmo.id
                FROM contenedores_maritimos_operacion cmo
                JOIN operaciones o ON o.id_operacion = cmo.operacion_id
                JOIN contenedores_maritimos cm 
                     ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                    AND cm.estatus = 1
                $whereSql
                GROUP BY o.id_operacion, cmo.id
            ) t
        ";
        $rowCount  = $this->select($sqlCount, $params);
        $totalRows = $rowCount ? (int)$rowCount['total_rows'] : 0;

        // ---- 2) Página de parejas (op + cmo)
        $sqlPairs = "
            SELECT 
                o.id_operacion,
                o.numero_operacion   AS operacion,
                cmo.id               AS cmo_id,
                cm.numero_contenedor AS contenedor
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o ON o.id_operacion = cmo.operacion_id
            JOIN contenedores_maritimos cm 
                 ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                AND cm.estatus = 1
            $whereSql
            GROUP BY o.id_operacion, cmo.id, operacion, contenedor
            ORDER BY operacion DESC, contenedor ASC
            LIMIT $perPage OFFSET $offset
        ";
        $rowsPairs = $this->selectAll($sqlPairs, $params) ?: [];

        if (empty($rowsPairs)) {
            return [
                'rows'     => [],
                'total'    => $totalRows,
                'page'     => $page,
                'per_page' => $perPage
            ];
        }

        // ---- 3) Traer eventos con LEFT JOIN partiendo de la página de parejas
        $cmoIds = array_column($rowsPairs, 'cmo_id');
        $in     = implode(',', array_fill(0, count($cmoIds), '?'));
        $paramsEvt = $cmoIds;

        $sqlPageWithEvents = "
            SELECT
                p.id_operacion,
                p.operacion,
                p.cmo_id,
                p.contenedor,
                e.id_evento,
                e.operacion_id,
                e.cont_maritimo_operacion_id,
                e.tipo_evento_id,
                te.nombre AS evento,
                e.fecha,
                e.comentario
            FROM (
                SELECT 
                    o.id_operacion,
                    o.numero_operacion   AS operacion,
                    cmo.id               AS cmo_id,
                    cm.numero_contenedor AS contenedor
                FROM contenedores_maritimos_operacion cmo
                JOIN operaciones o ON o.id_operacion = cmo.operacion_id
                JOIN contenedores_maritimos cm 
                     ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                    AND cm.estatus = 1
                WHERE cmo.id IN ($in)
                GROUP BY o.id_operacion, cmo.id, operacion, contenedor
            ) p
            LEFT JOIN eventos_logisticos e
                   ON e.estatus = 1
                  AND e.cont_maritimo_operacion_id = p.cmo_id
            LEFT JOIN tipos_evento_logistico te
                   ON te.id_tipo_evento = e.tipo_evento_id
            ORDER BY p.operacion DESC, p.contenedor ASC, e.tipo_evento_id ASC, e.fecha DESC

        ";
        $rows = $this->selectAll($sqlPageWithEvents, $paramsEvt) ?: [];

        // ---- 4) Normalizar salida
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id_evento'                  => isset($r['id_evento']) ? (int)$r['id_evento'] : null,
                'operacion_id'               => (int)$r['id_operacion'],
                'cont_maritimo_operacion_id' => (int)$r['cmo_id'],
                'tipo_evento_id'             => isset($r['tipo_evento_id']) ? (int)$r['tipo_evento_id'] : null,
                'evento'                     => (string)($r['evento'] ?? ''),
                'fecha'                      => (string)($r['fecha'] ?? ''),
                'comentario'                 => (string)($r['comentario'] ?? ''),
                'operacion'                  => (string)$r['operacion'],
                'contenedor'                 => (string)$r['contenedor'],
            ];
        }

        return [
            'rows'     => $out,
            'total'    => $totalRows,
            'page'     => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Catálogo de eventos MARÍTIMOS para construir columnas dinámicas.
     * (id_tipo_operacion = 1)
     */
    public function listarEventosMaritimosParaColumnas(): array
    {
        $sql = "SELECT 
                    te.id_tipo_evento AS id, 
                    te.nombre
                FROM tipos_evento_logistico te
                WHERE te.estatus = 1
                  AND te.id_tipo_operacion = 1  -- 1 = Marítimo
                ORDER BY te.id_tipo_evento ASC";

        $rows = $this->selectAll($sql);
        if (!is_array($rows)) return [];

        foreach ($rows as &$r) {
            $r['key'] = $this->slugEvento($r['nombre']);
        }
        return $rows;
    }

    /** Convierte 'Cita en puerto' => 'cita_en_puerto' */
    private function slugEvento(string $nombre): string
    {
        $s = mb_strtolower(trim($nombre), 'UTF-8');
        $s = strtr($s, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
            'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ÿ'=>'y','ñ'=>'n'
        ]);
        $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
        $s = preg_replace('/_+/', '_', $s);
        return trim($s, '_');
    }

    /* ======================================================================
     *  AUTOCOMPLETES (OPERACIÓN MARÍTIMA + CONTENEDOR MARÍTIMO)
     * ====================================================================== */

    /** Operaciones MARÍTIMAS (id_tipo_operacion = 1) con conteo de contenedores */
    public function buscarOperacionesMaritimas(string $term, int $limit = 10): array
    {
        $limit  = max(1, (int)$limit);
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

        $sql = "
            SELECT 
                o.id_operacion                       AS id,
                o.numero_operacion                   AS label,
                COUNT(DISTINCT cmo.id)               AS maritimos,
                GROUP_CONCAT(
                    DISTINCT cm.numero_contenedor
                    ORDER BY cm.numero_contenedor SEPARATOR ', '
                )                                     AS contenedores
            FROM operaciones o
            LEFT JOIN contenedores_maritimos_operacion cmo
                   ON cmo.operacion_id = o.id_operacion
            LEFT JOIN contenedores_maritimos cm
                   ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                  AND cm.estatus = 1
            WHERE o.tipo_operacion_id = 1
              AND LOWER(o.numero_operacion) LIKE ?
            GROUP BY o.id_operacion, o.numero_operacion
            ORDER BY o.numero_operacion ASC
            LIMIT $limit
        ";

        $rows = $this->selectAll($sql, [$needle]);
        return is_array($rows) ? $rows : [];
    }

    /** Contenedores MARÍTIMOS ligados a una operación (por operacion_id) */
    public function buscarContenedoresMarDeOperacion(
        int $operacionId,
        string $term = '',
        int $limit = 15
    ): array {
        $limit  = max(1, (int)$limit);
        $params = [$operacionId];
        $filtro = '';

        if ($term !== '') {
            $filtro = " AND LOWER(cm.numero_contenedor) LIKE ? ";
            $params[] = '%'.mb_strtolower($term, 'UTF-8').'%';
        }

        $sql = "
            SELECT 
                cmo.id               AS id,          -- contenedores_maritimos_operacion.id
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

    /** Catálogo de tipos de evento MARÍTIMOS (para combos) */
    public function listarTiposEventoMaritimo(): array
    {
        $sql = "SELECT id_tipo_evento, nombre
                FROM tipos_evento_logistico
                WHERE estatus = 1
                  AND id_tipo_operacion = 1   -- marítimo
                ORDER BY nombre ASC";
        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }

    /** Primer contenedor marítimo de una operación (útil para autoseleccionar) */
    public function getContenedorMaritimoDeOperacion(int $operacionId): ?array
    {
        if ($operacionId <= 0) return null;

        $sql = "
            SELECT 
                cmo.id               AS id,
                cm.numero_contenedor AS label
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm 
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            JOIN operaciones o
              ON o.id_operacion = cmo.operacion_id
            WHERE cmo.operacion_id = ?
              AND o.tipo_operacion_id = 1   -- Marítimo
              AND cm.estatus = 1
            ORDER BY cmo.id ASC
            LIMIT 1
        ";
        $row = $this->select($sql, [$operacionId]);
        return $row ?: null;
    }

    /* ======================================================================
     *  DUPLICADOS
     * ====================================================================== */

    public function existeEventoMaritimoDuplicado(
        int $contMaritimoOperacionId,
        int $tipoEventoId,
        ?int $excluirId = null
    ): bool {
        if ($contMaritimoOperacionId <= 0 || $tipoEventoId <= 0) return false;

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

    /* ======================================================================
     *  REGISTRAR / ACTUALIZAR / OBTENER / ELIMINAR
     * ====================================================================== */

    /** Registrar evento LOGÍSTICO MARÍTIMO con validaciones similares al módulo MF */
    public function registrar(array $data, int $idUsuario): int
    {
        $operacionId = (int)($data['operacion_id'] ?? 0);
        $cmoId       = (int)($data['cont_maritimo_operacion_id'] ?? 0);
        $tipoEvtId   = (int)($data['tipo_evento_id'] ?? 0);
        $fecha       = (string)($data['fecha'] ?? '');
        $comentario  = $data['comentario'] ?? null;

        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return 0;
        }

        // 1) Validar que la operación sea MARÍTIMA (id_tipo_operacion = 1)
        $rowOp = $this->select("
            SELECT id_operacion 
              FROM operaciones 
             WHERE id_operacion = ? 
               AND tipo_operacion_id = 1 
             LIMIT 1
        ", [$operacionId]);
        if (!$rowOp) return 0;

        // 2) Validar que el CMO pertenezca a la operación y contenedor esté ACTIVO
        $rowCMO = $this->select("
            SELECT cmo.id
              FROM contenedores_maritimos_operacion cmo
              JOIN contenedores_maritimos cm 
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
             WHERE cmo.id = ?
               AND cmo.operacion_id = ?
               AND cm.estatus = 1
             LIMIT 1
        ", [$cmoId, $operacionId]);
        if (!$rowCMO) return 0;

        // 3) Validar que el tipo de evento sea MARÍTIMO (id_tipo_operacion = 1) y esté activo
        $rowEvt = $this->select("
            SELECT id_tipo_evento
              FROM tipos_evento_logistico
             WHERE id_tipo_evento = ?
               AND id_tipo_operacion = 1
               AND estatus = 1
             LIMIT 1
        ", [$tipoEvtId]);
        if (!$rowEvt) return 0;

        // 4) Evitar duplicados (misma pareja cmo.id + tipo_evento_id, activo)
        if ($this->existeEventoMaritimoDuplicado($cmoId, $tipoEvtId)) {
            return 0;
        }

        // 5) Insertar
        $sqlIns = "INSERT INTO eventos_logisticos
                   (operacion_id, cont_maritimo_operacion_id, tipo_evento_id, fecha, comentario, creado_por)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$operacionId, $cmoId, $tipoEvtId, $fecha, $comentario, ($idUsuario ?: null)];

        return (int)$this->insertar($sqlIns, $params);
    }

    public function actualizar(array $data): bool
    {
        $idEvento   = (int)($data['id_evento'] ?? 0);
        $operacionId= (int)($data['operacion_id'] ?? 0);
        $cmoId      = (int)($data['cont_maritimo_operacion_id'] ?? 0);
        $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
        $fecha      = (string)($data['fecha'] ?? '');
        $comentario = $data['comentario'] ?? null;

        if ($idEvento <= 0 || $operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return false;
        }

        // Misma serie de validaciones que en registrar:
        $rowOp = $this->select("
            SELECT id_operacion 
              FROM operaciones 
             WHERE id_operacion = ? 
               AND tipo_operacion_id = 1 
             LIMIT 1
        ", [$operacionId]);
        if (!$rowOp) return false;

        $rowCMO = $this->select("
            SELECT cmo.id
              FROM contenedores_maritimos_operacion cmo
              JOIN contenedores_maritimos cm 
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
             WHERE cmo.id = ?
               AND cmo.operacion_id = ?
               AND cm.estatus = 1
             LIMIT 1
        ", [$cmoId, $operacionId]);
        if (!$rowCMO) return false;

        $rowEvt = $this->select("
            SELECT id_tipo_evento
              FROM tipos_evento_logistico
             WHERE id_tipo_evento = ?
               AND id_tipo_operacion = 1
               AND estatus = 1
             LIMIT 1
        ", [$tipoEvtId]);
        if (!$rowEvt) return false;

        if ($this->existeEventoMaritimoDuplicado($cmoId, $tipoEvtId, $idEvento)) {
            return false;
        }

        $sql = "UPDATE eventos_logisticos
                   SET operacion_id = ?,
                       cont_maritimo_operacion_id = ?,
                       tipo_evento_id = ?,
                       fecha = ?,
                       comentario = ?
                 WHERE id_evento = ?
                   AND estatus = 1";
        $params = [$operacionId, $cmoId, $tipoEvtId, $fecha, $comentario, $idEvento];

        return (bool)$this->save($sql, $params);
    }

    /** Obtener un evento por clave (op + cmo + tipo de evento) */
    public function obtenerEventoPorClave(
        int $operacionId,
        int $cmoId,
        int $tipoEventoId
    ): ?array {
        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEventoId <= 0) return null;

        $sql = "SELECT 
                    e.id_evento, e.operacion_id, e.cont_maritimo_operacion_id,
                    e.tipo_evento_id, e.fecha, e.comentario
                FROM eventos_logisticos e
                WHERE e.estatus = 1
                  AND e.operacion_id = ?
                  AND e.cont_maritimo_operacion_id = ?
                  AND e.tipo_evento_id = ?
                LIMIT 1";
        $row = $this->select($sql, [$operacionId, $cmoId, $tipoEventoId]);
        return $row ?: null;
    }

    /** Baja lógica de un evento marítimo */
    public function eliminar(int $idEvento): bool
    {
        if ($idEvento <= 0) return false;
        $sql = "UPDATE eventos_logisticos
                   SET estatus = 0
                 WHERE id_evento = ?
                 LIMIT 1";
        return (bool)$this->save($sql, [$idEvento]);
    }

    /* ======================================================================
     *  MÉTODOS EXTRA QUE YA TENÍAS (LOS CONSERVO)
     * ====================================================================== */

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
