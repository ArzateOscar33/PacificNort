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
                 ORDER BY id_tipo_evento ASC";
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

    public function listarEventosFOPaginado(
        int $page,
        int $perPage,
        ?int $opId = null,         // puede ser operacion_id (marítima) o id_operacion_ferro (legacy)
        ?int $ferroId = null,      // contenedores_fisicos.id_fisico
        string $q = '',
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {

        $perPage = min(100, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        // Solo ferros activos
        $where[] = "cf.estatus = 1";

        // Excluir Cancelado/Finalizada tanto en marítima como en FO (segmento)
        $where[] = "o.estatus_id  NOT IN (13, 7)";
        $where[] = "of.estatus_id NOT IN (13, 7)";

        // Filtro: opId puede venir como marítima (o.id_operacion) o como FO (of.id_operacion_ferro)
        if ($opId) {
            $where[] = "(o.id_operacion = ? OR of.id_operacion_ferro = ?)";
            $params[] = $opId;
            $params[] = $opId;
        }

        if ($ferroId) {
            $where[] = "cf.id_fisico = ?";
            $params[] = $ferroId;
        }

        // 🔥 Búsqueda: ahora incluye contenedor marítimo + ubicación actual
        if ($q !== '') {
            $like = '%' . mb_strtolower(trim($q), 'UTF-8') . '%';
            $where[] = "("
                . "LOWER(o.numero_operacion) LIKE ? "
                . "OR LOWER(of.numero_operacion) LIKE ? "
                . "OR LOWER(cf.numero_ferro) LIKE ? "
                . "OR LOWER(cl.nombre) LIKE ? "
                . "OR LOWER(tr.nombre) LIKE ? "
                . "OR LOWER(ci.nombre_ciudad) LIKE ? "
                . "OR LOWER(COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '')) LIKE ? "
                . "OR LOWER(COALESCE(ua.ubicacion_actual, '')) LIKE ? "
                . ")";
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // --- Subquery: Ubicación actual = último evento (por fecha) del par (op_ferro_id + contenedor_fisico_id)
        // Nota: si hay empates de fecha, puede escoger cualquiera del mismo día; normalmente no pasa.
        $sqlUbicacion = "
        SELECT
            e.operacion_ferro_id,
            e.contenedor_fisico_id,
            te.nombre AS ubicacion_actual,
            e.fecha   AS ubicacion_fecha
        FROM eventos_ferroviarios e
        JOIN (
            SELECT operacion_ferro_id, contenedor_fisico_id, MAX(fecha) AS max_fecha
            FROM eventos_ferroviarios
            WHERE estatus = 1
            GROUP BY operacion_ferro_id, contenedor_fisico_id
        ) mx
          ON mx.operacion_ferro_id = e.operacion_ferro_id
         AND mx.contenedor_fisico_id = e.contenedor_fisico_id
         AND mx.max_fecha = e.fecha
        LEFT JOIN tipos_evento_logistico te
          ON te.id_tipo_evento = e.tipo_evento_id
        WHERE e.estatus = 1
    ";

        // 1) TOTAL parejas (Marítima + Ferro)
        $sqlCount = "
        SELECT COUNT(*) AS total_rows
        FROM (
            SELECT o.id_operacion, cf.id_fisico
            FROM operacion_ferro_operacion ofo
            JOIN operaciones o
              ON o.id_operacion = ofo.operacion_id
            JOIN operaciones_ferroviarias of
              ON of.id_operacion_ferro = ofo.operacion_ferro_id
            JOIN contenedores_fisicos cf
              ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1

            LEFT JOIN clientes cl
              ON cl.id_cliente = o.cliente_id
            LEFT JOIN transportistas tr
              ON tr.id_transportista = of.transportista_id
            LEFT JOIN ciudades ci
              ON ci.id_ciudad = of.destino_id

            -- ✅ Contenedor marítimo (por cmf.contenedor_maritimo_id o cmf.cont_maritimo_operacion_id)
            LEFT JOIN contenedor_maritimo_ferro cmf
              ON cmf.operacion_ferro_id = of.id_operacion_ferro
             AND cmf.contenedor_fisico_id = cf.id_fisico
             AND cmf.estatus = 1
            LEFT JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id
            LEFT JOIN contenedores_maritimos_operacion cmo
              ON cmo.id = cmf.cont_maritimo_operacion_id
            LEFT JOIN contenedores_maritimos cm2
              ON cm2.id_contenedor_maritimo = cmo.contenedor_maritimo_id

            -- ✅ Ubicación actual (último evento)
            LEFT JOIN ( $sqlUbicacion ) ua
              ON ua.operacion_ferro_id = of.id_operacion_ferro
             AND ua.contenedor_fisico_id = cf.id_fisico

            {$whereSql}
            GROUP BY o.id_operacion, cf.id_fisico
        ) t
    ";

        $rowCount  = $this->select($sqlCount, $params);
        $totalRows = $rowCount ? (int)$rowCount['total_rows'] : 0;

        // 2) Página: parejas (Marítima + Ferro) + campos extra
        $sqlRows = "
        SELECT
            o.id_operacion                 AS operacion_id,
            o.numero_operacion             AS operacion_maritima,

            -- ✅ nueva col 2
            COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '') AS contenedor_maritimo,

            cl.nombre                      AS cliente,

            -- ✅ ya NO estatus de marítima
            COALESCE(ua.ubicacion_actual, 'SIN REGISTRAR') AS ubicacion_actual,

            of.id_operacion_ferro          AS op_ferro_id,
            of.numero_operacion            AS operacion_ferro,

            cf.id_fisico                   AS ferro_id,
            cf.numero_ferro                AS ferro,

            ci.nombre_ciudad               AS destino,
            tr.nombre                      AS transportista

        FROM operacion_ferro_operacion ofo
        JOIN operaciones o
          ON o.id_operacion = ofo.operacion_id
        JOIN operaciones_ferroviarias of
          ON of.id_operacion_ferro = ofo.operacion_ferro_id
        JOIN contenedores_fisicos cf
          ON cf.id_fisico = of.contenedor_fisico_id AND cf.estatus = 1

        LEFT JOIN clientes cl
          ON cl.id_cliente = o.cliente_id

        LEFT JOIN ciudades ci
          ON ci.id_ciudad = of.destino_id
        LEFT JOIN transportistas tr
          ON tr.id_transportista = of.transportista_id

        -- ✅ Contenedor marítimo
        LEFT JOIN contenedor_maritimo_ferro cmf
          ON cmf.operacion_ferro_id = of.id_operacion_ferro
         AND cmf.contenedor_fisico_id = cf.id_fisico
         AND cmf.estatus = 1
        LEFT JOIN contenedores_maritimos cm
          ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id
        LEFT JOIN contenedores_maritimos_operacion cmo
          ON cmo.id = cmf.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm2
          ON cm2.id_contenedor_maritimo = cmo.contenedor_maritimo_id

        -- ✅ Ubicación actual
        LEFT JOIN ( $sqlUbicacion ) ua
          ON ua.operacion_ferro_id = of.id_operacion_ferro
         AND ua.contenedor_fisico_id = cf.id_fisico

        {$whereSql}
        GROUP BY o.id_operacion, cf.id_fisico, op_ferro_id, operacion_maritima, operacion_ferro, ferro
        ORDER BY o.id_operacion DESC, ferro DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";

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
        $opFerroIds = array_values(array_unique(array_column($rowsPairs, 'op_ferro_id')));
        $fxIds      = array_values(array_unique(array_column($rowsPairs, 'ferro_id')));

        $inOps = implode(',', array_fill(0, count($opFerroIds), '?'));
        $inFxs = implode(',', array_fill(0, count($fxIds), '?'));

        $paramsEvt = array_merge($opFerroIds, $fxIds);

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
        LEFT JOIN tipos_evento_logistico te
          ON te.id_tipo_evento = e.tipo_evento_id
        WHERE e.estatus = 1
          AND e.operacion_ferro_id IN ($inOps)
          AND e.contenedor_fisico_id IN ($inFxs)
    ";

        $rowsEvts = $this->selectAll($sqlEvts, $paramsEvt) ?: [];

        // 4) Mapa por par (op_ferro_id + ferro_id) para pegar los extras
        $byPair = [];
        foreach ($rowsPairs as $r) {
            $key = (int)$r['op_ferro_id'] . '_' . (int)$r['ferro_id'];
            $byPair[$key] = $r;
        }

        $out = [];

        foreach ($rowsEvts as $e) {
            $key = (int)$e['operacion_ferro_id'] . '_' . (int)$e['contenedor_fisico_id'];
            if (!isset($byPair[$key])) continue;

            $p = $byPair[$key];

            $out[] = [
                'id_evento'            => (int)$e['id_evento'],
                'operacion_ferro_id'   => (int)$e['operacion_ferro_id'],
                'contenedor_fisico_id' => (int)$e['contenedor_fisico_id'],
                'tipo_evento_id'       => (int)$e['tipo_evento_id'],
                'evento'               => (string)($e['evento'] ?? ''),
                'fecha'                => (string)($e['fecha'] ?? ''),
                'comentario'           => (string)($e['comentario'] ?? ''),

                // ✅ CAMPOS PARA LA TABLA
                'operacion_id'         => (int)($p['operacion_id'] ?? 0),
                'operacion_maritima'   => (string)($p['operacion_maritima'] ?? ''),
                'contenedor_maritimo'  => (string)($p['contenedor_maritimo'] ?? ''),
                'cliente'              => (string)($p['cliente'] ?? ''),
                'ubicacion_actual'     => (string)($p['ubicacion_actual'] ?? 'SIN REGISTRAR'),
                'destino'              => (string)($p['destino'] ?? ''),
                'transportista'        => (string)($p['transportista'] ?? ''),

                // legacy/útil
                'operacion'            => (string)($p['operacion_ferro'] ?? ''),
                'ferro'                => (string)($p['ferro'] ?? ''),
            ];
        }

        // 5) Rellena pares sin evento para que sigan apareciendo en la tabla
        $pairsConEvento = array_unique(array_map(
            fn($r) => (int)$r['operacion_ferro_id'] . '_' . (int)$r['contenedor_fisico_id'],
            $out
        ));

        foreach ($rowsPairs as $r) {
            $key = (int)$r['op_ferro_id'] . '_' . (int)$r['ferro_id'];
            if (!in_array($key, $pairsConEvento, true)) {
                $out[] = [
                    'id_evento'            => null,
                    'operacion_ferro_id'   => (int)$r['op_ferro_id'],
                    'contenedor_fisico_id' => (int)$r['ferro_id'],
                    'tipo_evento_id'       => null,
                    'evento'               => null,
                    'fecha'                => null,
                    'comentario'           => null,

                    // ✅ CAMPOS PARA LA TABLA
                    'operacion_id'         => (int)($r['operacion_id'] ?? 0),
                    'operacion_maritima'   => (string)($r['operacion_maritima'] ?? ''),
                    'contenedor_maritimo'  => (string)($r['contenedor_maritimo'] ?? ''),
                    'cliente'              => (string)($r['cliente'] ?? ''),
                    'ubicacion_actual'     => (string)($r['ubicacion_actual'] ?? 'SIN REGISTRAR'),
                    'destino'              => (string)($r['destino'] ?? ''),
                    'transportista'        => (string)($r['transportista'] ?? ''),

                    // legacy
                    'operacion'            => (string)($r['operacion_ferro'] ?? ''),
                    'ferro'                => (string)($r['ferro'] ?? ''),
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
        $comentario = $data['comentario'] ?? null;

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
        $comentario = $data['comentario'] ?? null;

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
        if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) return null;

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
