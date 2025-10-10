<?php
class Operaciones_maritimo_ferro_eventos_ferModel extends Query
{
    /* ============================
       COLUMNAS (catálogo terrestre)
       ============================ */
    public function listarTiposEventoTerrestre(): array
    {
        $sql = "SELECT id_tipo_evento, nombre
                  FROM tipos_evento_logistico
                 WHERE estatus = 1
                   AND id_tipo_operacion = 2     -- TERRESTRE
                 ORDER BY nombre ASC";
        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }

    /* =======================================================
       Sugerencias de OPERACIONES FO (por número o por id)
       ======================================================= */
    public function sugerirOperacionesFO(string $term, int $limit = 8): array
    {
        $limit  = max(1, min(20, (int)$limit));
        $needle = '%' . mb_strtolower(trim($term), 'UTF-8') . '%';
        $isNum  = ctype_digit(trim($term));

        $where  = "(LOWER(of.numero_operacion) LIKE ?" . ($isNum ? " OR of.id_operacion_ferro = ?" : "") . ")";
        $params = [$needle];
        if ($isNum) $params[] = (int)$term;

        $sql = "
            SELECT 
                of.id_operacion_ferro AS id,
                of.numero_operacion   AS label,
                cf.numero_ferro       AS ferro
            FROM operaciones_ferroviarias of
            LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id
            WHERE {$where}
            ORDER BY of.numero_operacion ASC
            LIMIT {$limit}";
        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    /* ================================================================
       Contenedor(es) FERRO de una operación FO
       En FO estándar hay 1 ferro por operación (columna contenedor_fisico_id).
       ================================================================= */
    public function buscarFerrosDeOperacion(int $opFerroId, string $term = '', int $limit = 10): array
    {
        if ($opFerroId <= 0) return [];
        $limit  = max(1, (int)$limit);

        $params = [$opFerroId];
        $filtro = '';
        if ($term !== '') {
            $filtro = " AND LOWER(cf.numero_ferro) LIKE ? ";
            $params[] = '%' . mb_strtolower($term, 'UTF-8') . '%';
        }

        $sql = "
            SELECT 
                cf.id_fisico    AS id,
                cf.numero_ferro AS label,
                'FERRO'         AS tipo
            FROM operaciones_ferroviarias of
            JOIN contenedores_fisicos cf 
              ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1
            WHERE of.id_operacion_ferro = ?
              {$filtro}
            ORDER BY cf.numero_ferro ASC
            LIMIT {$limit}";
        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    /* ======================================================
       Obtener el FERRO principal de una operación FO (1:1)
       ====================================================== */
    public function getFerroDeOperacion(int $opFerroId): ?array
    {
        if ($opFerroId <= 0) return null;

        $sql = "
            SELECT 
                cf.id_fisico    AS id,
                cf.numero_ferro AS label
            FROM operaciones_ferroviarias of
            JOIN contenedores_fisicos cf 
              ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1
            WHERE of.id_operacion_ferro = ?
            LIMIT 1";
        $row = $this->select($sql, [$opFerroId]);
        return $row ?: null;
    }

    /* ===========================================================
       Paginado (FO): lista “parejas” (FO + Ferro) y sus eventos.
       Cada columna de la vista = un tipo de evento TERRESTRE.
       =========================================================== */
public function listarEventosFOPaginado(
    int $page,
    int $perPage,
    ?int $opFerroId = null,
    ?int $ferroId   = null,      // contenedores_fisicos.id_fisico
    string $q       = '',         // busca por FO o ferro
    ?string $fechaDesde = null,   // (reservado para futuro)
    ?string $fechaHasta = null    // (reservado para futuro)
): array {
    $perPage = min(100, max(1, $perPage));
    $offset  = ($page - 1) * $perPage;

    $where   = ["cf.estatus = 1"];
    $params  = [];

    // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
    // NUEVO FILTRO: excluir operaciones con estatus Cancelado(6) o Finalizada(7)
    // (catálogo 'estatus': 6=Cancelado, 7=Finalizada)
    $where[] = "of.estatus_id NOT IN (6, 7)";
    // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

    if ($opFerroId) {
        $where[] = "of.id_operacion_ferro = ?";
        $params[] = $opFerroId;
    }
    if ($ferroId) {
        $where[] = "cf.id_fisico = ?";
        $params[] = $ferroId;
    }
    if ($q !== '') {
        $like = '%' . mb_strtolower(trim($q), 'UTF-8') . '%';
        $where[] = "(LOWER(of.numero_operacion) LIKE ? OR LOWER(cf.numero_ferro) LIKE ?)";
        array_push($params, $like, $like);
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // 1) TOTAL parejas (FO + Ferro)
    $sqlCount = "
        SELECT COUNT(*) AS total_rows
        FROM (
            SELECT of.id_operacion_ferro, cf.id_fisico
            FROM operaciones_ferroviarias of
            JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1
            {$whereSql}
            GROUP BY of.id_operacion_ferro, cf.id_fisico
        ) t";
    $rowCount  = $this->select($sqlCount, $params);
    $totalRows = $rowCount ? (int)$rowCount['total_rows'] : 0;

    // 2) Página: parejas (FO + Ferro)
    $sqlRows = "
        SELECT 
            of.id_operacion_ferro              AS op_ferro_id,
            of.numero_operacion                AS operacion,
            cf.id_fisico                       AS ferro_id,
            cf.numero_ferro                    AS ferro
        FROM operaciones_ferroviarias of
        JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1
        {$whereSql}
        GROUP BY of.id_operacion_ferro, cf.id_fisico, operacion, ferro
        ORDER BY operacion ASC, ferro ASC
        LIMIT {$perPage} OFFSET {$offset}";
    $rowsPairs = $this->selectAll($sqlRows, $params) ?: [];

    if (empty($rowsPairs)) {
        return [
            'rows'     => [],
            'total'    => $totalRows,
            'page'     => $page,
            'per_page' => $perPage
        ];
    }

    // 3) Trae eventos de esos ferros en esta página
    $opIds   = array_values(array_unique(array_column($rowsPairs, 'op_ferro_id')));
    $fxIds   = array_values(array_unique(array_column($rowsPairs, 'ferro_id')));
    $inOps   = implode(',', array_fill(0, count($opIds), '?'));
    $inFxs   = implode(',', array_fill(0, count($fxIds), '?'));

    $paramsEvt = array_merge($opIds, $fxIds);
    $sqlEvts = "
        SELECT
            e.id_evento,
            e.operacion_ferro_id,
            e.contenedor_fisico_id,
            e.tipo_evento_id,
            te.nombre AS evento,
            e.fecha,
            e.comentario
        FROM eventos_ferroviarios e
        LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
        WHERE e.estatus = 1
          AND e.operacion_ferro_id IN ($inOps)
          AND e.contenedor_fisico_id IN ($inFxs)";
    $rowsEvts = $this->selectAll($sqlEvts, $paramsEvt) ?: [];

    // 4) Adjunta FO/Ferro y rellena vacíos
    $byPair = [];
    foreach ($rowsPairs as $r) {
        $key = $r['op_ferro_id'].'_'.$r['ferro_id'];
        $byPair[$key] = $r;
    }

    $out = [];
    foreach ($rowsEvts as $e) {
        $key = ((int)$e['operacion_ferro_id']).'_'.((int)$e['contenedor_fisico_id']);
        if (!isset($byPair[$key])) continue;
        $out[] = [
            'id_evento'            => (int)$e['id_evento'],
            'operacion_ferro_id'   => (int)$e['operacion_ferro_id'],
            'contenedor_fisico_id' => (int)$e['contenedor_fisico_id'],
            'tipo_evento_id'       => (int)$e['tipo_evento_id'],
            'evento'               => (string)($e['evento'] ?? ''),
            'fecha'                => (string)($e['fecha'] ?? ''),
            'comentario'           => (string)($e['comentario'] ?? ''),
            'operacion'            => (string)$byPair[$key]['operacion'],
            'ferro'                => (string)$byPair[$key]['ferro'],
        ];
    }

    $pairsConEvento = array_unique(array_map(
        fn($r) => $r['operacion_ferro_id'].'_'.$r['contenedor_fisico_id'],
        $out
    ));
    foreach ($rowsPairs as $r) {
        $key = $r['op_ferro_id'].'_'.$r['ferro_id'];
        if (!in_array($key, $pairsConEvento, true)) {
            $out[] = [
                'id_evento'            => null,
                'operacion_ferro_id'   => (int)$r['op_ferro_id'],
                'contenedor_fisico_id' => (int)$r['ferro_id'],
                'tipo_evento_id'       => null,
                'evento'               => null,
                'fecha'                => null,
                'comentario'           => null,
                'operacion'            => (string)$r['operacion'],
                'ferro'                => (string)$r['ferro'],
            ];
        }
    }

    return [
        'rows'     => $out,
        'total'    => $totalRows,
        'page'     => $page,
        'per_page' => $perPage
    ];
}


    /* ==========================================================
       Reglas de validación (FO=2, eventos TERRESTRES=2) + CRUD
       ========================================================== */

    private function existeEventoFerroDuplicado(int $opFerroId, int $ferroId, int $tipoEvtId, ?int $excluirId = null): bool
    {
        if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) return false;

        $sql = "SELECT id_evento
                  FROM eventos_ferroviarios
                 WHERE estatus = 1
                   AND operacion_ferro_id = ?
                   AND contenedor_fisico_id = ?
                   AND tipo_evento_id = ?"
             . ($excluirId ? " AND id_evento <> ?" : "")
             . " LIMIT 1";

        $params = $excluirId
            ? [$opFerroId, $ferroId, $tipoEvtId, $excluirId]
            : [$opFerroId, $ferroId, $tipoEvtId];

        return (bool)$this->select($sql, $params);
    }

    public function registrar(array $data, int $idUsuario): int
    {
        $opFerroId = (int)($data['operacion_ferro_id'] ?? 0);
        $ferroId   = (int)($data['contenedor_fisico_id'] ?? 0);
        $tipoEvtId = (int)($data['tipo_evento_id'] ?? 0);
        $fecha     = (string)($data['fecha'] ?? '');
        $comentario= $data['comentario'] ?? null;

        if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return 0;
        }

        // 1) La operación debe existir y ser FO (tipo_operacion_id=2 por tu esquema)
        $rowOp = $this->select("
            SELECT id_operacion_ferro 
              FROM operaciones_ferroviarias 
             WHERE id_operacion_ferro = ? 
             LIMIT 1", [$opFerroId]);
        if (!$rowOp) return 0; // (si quieres, valida también estatus/FO)

        // 2) El ferro debe estar activo y pertenecer a la operación (1:1 estándar)
        $rowF = $this->select("
            SELECT cf.id_fisico
              FROM operaciones_ferroviarias of
              JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1
             WHERE of.id_operacion_ferro = ?
               AND cf.id_fisico = ?
             LIMIT 1", [$opFerroId, $ferroId]);
        if (!$rowF) return 0;

        // 3) Tipo de evento debe ser TERRESTRE y activo
        $rowEvt = $this->select("
            SELECT id_tipo_evento
              FROM tipos_evento_logistico
             WHERE id_tipo_evento = ?
               AND id_tipo_operacion = 2
               AND estatus = 1
             LIMIT 1", [$tipoEvtId]);
        if (!$rowEvt) return 0;

        // 4) Evitar duplicado por (opFerroId + ferroId + tipoEvtId)
        if ($this->existeEventoFerroDuplicado($opFerroId, $ferroId, $tipoEvtId)) {
            return 0;
        }

        // 5) Insertar
        $sqlIns = "INSERT INTO eventos_ferroviarios
                   (operacion_ferro_id, contenedor_fisico_id, tipo_evento_id, fecha, comentario, creado_por)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$opFerroId, $ferroId, $tipoEvtId, $fecha, $comentario, ($idUsuario ?: null)];

        return (int)$this->insertar($sqlIns, $params);
    }

    public function actualizar(array $data): bool
    {
        $idEvento  = (int)($data['id_evento'] ?? 0);
        $opFerroId = (int)($data['operacion_ferro_id'] ?? 0);
        $ferroId   = (int)($data['contenedor_fisico_id'] ?? 0);
        $tipoEvtId = (int)($data['tipo_evento_id'] ?? 0);
        $fecha     = (string)($data['fecha'] ?? '');
        $comentario= $data['comentario'] ?? null;

        if ($idEvento <= 0 || $opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return false;
        }

        // Validaciones como en registrar
        $rowOp = $this->select("SELECT id_operacion_ferro FROM operaciones_ferroviarias WHERE id_operacion_ferro = ? LIMIT 1", [$opFerroId]);
        if (!$rowOp) return false;

        $rowF = $this->select("
            SELECT cf.id_fisico
              FROM operaciones_ferroviarias of
              JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1
             WHERE of.id_operacion_ferro = ?
               AND cf.id_fisico = ?
             LIMIT 1", [$opFerroId, $ferroId]);
        if (!$rowF) return false;

        $rowEvt = $this->select("
            SELECT id_tipo_evento
              FROM tipos_evento_logistico
             WHERE id_tipo_evento = ?
               AND id_tipo_operacion = 2
               AND estatus = 1
             LIMIT 1", [$tipoEvtId]);
        if (!$rowEvt) return false;

        if ($this->existeEventoFerroDuplicado($opFerroId, $ferroId, $tipoEvtId, $idEvento)) {
            return false;
        }

        $sql = "UPDATE eventos_ferroviarios
                   SET operacion_ferro_id = ?,
                       contenedor_fisico_id = ?,
                       tipo_evento_id = ?,
                       fecha = ?,
                       comentario = ?
                 WHERE id_evento = ?
                   AND estatus = 1";
        $params = [$opFerroId, $ferroId, $tipoEvtId, $fecha, $comentario, $idEvento];

        return (bool)$this->save($sql, $params);
    }

    public function eliminar(int $idEvento): bool
    {
        if ($idEvento <= 0) return false;
        $sql = "UPDATE eventos_ferroviarios
                   SET estatus = 0
                 WHERE id_evento = ?
                 LIMIT 1";
        return (bool)$this->save($sql, [$idEvento]);
    }

    /* ==========================================
       Obtener un evento por (FO, Ferro, TipoEvt)
       ========================================== */
    public function obtenerEventoPorClave(int $opFerroId, int $ferroId, int $tipoEvtId): ?array
    {
        if ($opFerroId<=0 || $ferroId<=0 || $tipoEvtId<=0) return null;

        $sql = "SELECT 
                    e.id_evento, e.operacion_ferro_id, e.contenedor_fisico_id,
                    e.tipo_evento_id, e.fecha, e.comentario
                FROM eventos_ferroviarios e
                WHERE e.estatus = 1
                  AND e.operacion_ferro_id = ?
                  AND e.contenedor_fisico_id = ?
                  AND e.tipo_evento_id = ?
                LIMIT 1";
        $row = $this->select($sql, [$opFerroId, $ferroId, $tipoEvtId]);
        return $row ?: null;
    }
}
