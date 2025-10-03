<?php
class Operaciones_maritimo_ferro_trazabilidadModel extends Query
{




    /**
     * Sugerencias para el input "Operación Ferroviaria"
     * Busca por número de operación FO, número de ferro/caja o nombre de cliente.
     * Usa la vista para evitar múltiples JOINs en el autocomplete.
     */
    public function buscarOperacionesFerroSugerencias(string $term, int $limit = 10): array
    {
        $like = '%' . trim($term) . '%';
        $limit = max(1, min(50, (int)$limit)); // bound seguro

        // NOTA: Algunos drivers no aceptan bind para LIMIT.
        // Si tu PDO no emula prepares, interpolamos $limit ya saneado.
        $sql = "
            SELECT id_operacion_ferro, numero_operacion, numero_ferro, cliente_nombre
            FROM vista_operaciones_ferroviarias_completa
            WHERE numero_operacion LIKE ?
               OR numero_ferro LIKE ?
               OR cliente_nombre LIKE ?
            ORDER BY id_operacion_ferro DESC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$like, $like, $like]) ?: [];
    }




    /**
     * Encabezado/básicos de la operación ferroviaria seleccionada:
     * - FO: id, numero_operacion, fecha, comentarios, estatus_id
     * - Cliente principal: id/nombre
     * - Ferro/caja vinculado: id_fisico/numero_ferro
     */
    public function getOperacionFerroBasicos(int $operacionFerroId): ?array
    {
        $sql = "
            SELECT 
                of.id_operacion_ferro,
                of.numero_operacion,
                of.fecha,
                of.comentarios,
                of.estatus_id,
                of.cliente_id,
                c.nombre AS cliente_nombre,
                of.contenedor_fisico_id,
                cf.numero_ferro
            FROM operaciones_ferroviarias AS of
            LEFT JOIN clientes AS c           ON c.id_cliente = of.cliente_id
            LEFT JOIN contenedores_fisicos cf ON cf.id_fisico  = of.contenedor_fisico_id
            WHERE of.id_operacion_ferro = ?
            LIMIT 1
        ";
        $row = $this->select($sql, [$operacionFerroId]);
        return $row ?: null;
    }

    /**
     * Ferro/caja vinculado a la operación (por si lo necesitas por separado).
     */
    public function getFerroDeOperacionFerro(int $operacionFerroId): ?array
    {
        $sql = "
            SELECT 
                cf.id_fisico,
                cf.numero_ferro
            FROM operaciones_ferroviarias of
            INNER JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id
            WHERE of.id_operacion_ferro = ?
            LIMIT 1
        ";
        $row = $this->select($sql, [$operacionFerroId]);
        return $row ?: null;
    }

    /**
     * Clientes involucrados en la operación FO:
     *  - Cliente principal de la FO (of.cliente_id)
     *  - Clientes de las operaciones marítimas enlazadas vía contenedor_maritimo_ferro -> cmo -> operaciones.cliente_id
     */
    public function getClientesDeOperacionFerro(int $operacionFerroId): array
    {
        $sql = "
            SELECT DISTINCT c.id_cliente, c.nombre
            FROM (
                SELECT of.cliente_id AS cliente_id
                FROM operaciones_ferroviarias of
                WHERE of.id_operacion_ferro = ?

                UNION

                SELECT o.cliente_id
                FROM contenedor_maritimo_ferro cmf
                INNER JOIN contenedores_maritimos_operacion cmo 
                        ON cmo.id = cmf.cont_maritimo_operacion_id
                INNER JOIN operaciones o 
                        ON o.id_operacion = cmo.operacion_id
                WHERE cmf.operacion_ferro_id = ?
                  AND o.cliente_id IS NOT NULL
            ) x
            INNER JOIN clientes c ON c.id_cliente = x.cliente_id
            ORDER BY c.nombre
        ";
        return $this->selectAll($sql, [$operacionFerroId, $operacionFerroId]) ?: [];
    }

    /**
     * Paquete listo para llenar tu modal:
     *  - operacion: básicos FO
     *  - ferro: id/numero_ferro
     *  - clientes: chips con id/nombre
     */
    public function getDatosModalTrazabilidad(int $operacionFerroId): array
    {
        $hdr      = $this->getOperacionFerroBasicos($operacionFerroId);
        $ferro    = $hdr
            ? ['id_fisico' => $hdr['contenedor_fisico_id'], 'numero_ferro' => $hdr['numero_ferro']]
            : $this->getFerroDeOperacionFerro($operacionFerroId);
        $clientes = $this->getClientesDeOperacionFerro($operacionFerroId);

        return [
            'operacion' => $hdr,
            'ferro'     => $ferro,
            'clientes'  => $clientes,
        ];
    }


    /* ============================
     * LUGARES (CIUDADES / PUERTOS)
     * ============================ */

    /** Sugerencias de ciudades activas por nombre */
    public function buscarCiudades(string $term, int $limit = 10): array
    {
        $like  = '%' . trim($term) . '%';
        $limit = max(1, min(50, (int)$limit));
        $sql = "
            SELECT 
                c.id_ciudad      AS id,
                c.nombre_ciudad  AS nombre,
                'ciudad'         AS tipo
            FROM ciudades c
            WHERE c.estatus = 1
              AND c.nombre_ciudad LIKE ?
            ORDER BY c.nombre_ciudad ASC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    /** Sugerencias de puertos activos por nombre (incluye ciudad si quieres mostrarla) */
    public function buscarPuertos(string $term, int $limit = 10): array
    {
        $like  = '%' . trim($term) . '%';
        $limit = max(1, min(50, (int)$limit));
        $sql = "
            SELECT 
                p.id_puerto AS id,
                p.nombre    AS nombre,
                'puerto'    AS tipo
            FROM puertos p
            WHERE p.estatus = 1
              AND p.nombre LIKE ?
            ORDER BY p.nombre ASC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    /**
     * Sugerencias unificadas de lugares (ciudades + puertos).
     * Útil para el autosuggest de Origen/Destino en un solo input.
     */
    public function buscarLugares(string $term, int $limit = 10): array
    {
        $limit = max(1, min(50, (int)$limit));

        // Para repartir el límite aproximado entre ambos (50/50)
        $limC  = max(1, (int)floor($limit / 2));
        $limP  = $limit - $limC;

        $ciudades = $this->buscarCiudades($term, $limC);

        // Mezcla simple (puedes mejorar priorizando coincidencias exactas)
        return array_values(array_merge($ciudades));
    }

    /* =================
     * TRANSPORTISTAS
     * ================= */

    /**
     * Transportistas activos filtrados por término y tipo.
     * Para trazabilidad ferroviaria es común usar tipo='ferroviario'.
     * Acepta: 'terrestre' | 'maritimo' | 'ferroviario'
     */
    public function buscarTransportistas(string $term, string $tipo = 'ferroviario', int $limit = 10): array
    {
        $like  = '%' . trim($term) . '%';
        $tipo  = in_array($tipo, ['terrestre', 'maritimo', 'ferroviario'], true) ? $tipo : 'ferroviario';
        $limit = max(1, min(50, (int)$limit));

        $sql = "
            SELECT 
                t.id_transportista AS id,
                t.nombre           AS nombre,
                t.tipo             AS tipo
            FROM transportistas t
            WHERE t.estatus = 1
              AND t.tipo = ?
              AND t.nombre LIKE ?
            ORDER BY t.nombre ASC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$tipo, $like]) ?: [];
    }

    /** Azúcar sintáctica para ferroviarios (default del módulo) */
    public function buscarTransportistasFerro(string $term, int $limit = 10): array
    {
        return $this->buscarTransportistas($term, 'ferroviario', $limit);
    }
    //REGISTRAR
    public function crearRutaFerro(int $operacionFerroId, int $contenedorFisicoId, ?string $comentario = null): int
    {
        $sql = "INSERT INTO rutas_ferro (operacion_ferro_id, contenedor_fisico_id, comentario)
            VALUES (?, ?, ?)";
        return (int)$this->insertar($sql, [$operacionFerroId, $contenedorFisicoId, $comentario]);
    }


    /***********************
     * TRAMOS (CRUD mínimo)
     ***********************/
    public function getTramosPorRuta(int $rutaId): array
    {
        $sql = "SELECT id_tramo, ruta_id, orden, origen_id, destino_id, transportista_id, monto, comentario
              FROM rutas_ferro_tramos
             WHERE ruta_id = ? AND estatus = 1
             ORDER BY orden ASC, id_tramo ASC";
        return $this->selectAll($sql, [$rutaId]) ?: [];
    }

    public function insertarTramoRutaFerro(
        int $rutaId,
        int $orden,
        int $origenId,
        int $destinoId,
        int $transportistaId,
        float $monto,
        ?string $comentario = null
    ): int {
        $sql = "INSERT INTO rutas_ferro_tramos
               (ruta_id, orden, origen_id, destino_id, transportista_id, monto, comentario)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        return (int)$this->insertar($sql, [
            $rutaId,
            $orden,
            $origenId,
            $destinoId,
            $transportistaId,
            $monto,
            $comentario
        ]);
    }

    public function actualizarTramoRutaFerro(
        int $idTramo,
        int $orden,
        int $origenId,
        int $destinoId,
        int $transportistaId,
        float $monto,
        ?string $comentario = null
    ): int {
        $sql = "UPDATE rutas_ferro_tramos
               SET orden = ?, origen_id = ?, destino_id = ?, transportista_id = ?, monto = ?, comentario = ?
             WHERE id_tramo = ?
             LIMIT 1";
        return $this->save($sql, [
            $orden,
            $origenId,
            $destinoId,
            $transportistaId,
            $monto,
            $comentario,
            $idTramo
        ]);
    }

    public function eliminarTramosPorIds(array $ids): int
    {
        if (empty($ids)) return 1;
        // build IN (?, ?, ?)
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM rutas_ferro_tramos WHERE id_tramo IN ($marks)";
        return $this->save($sql, array_map('intval', $ids));
    }

    /*********************************
     * COSTOS — Transporte (tipo 23)
     *********************************/
    public function insertarCostoTransporteFerro(
        int $operacionFerroId,
        float $monto,
        ?string $comentario,
        int $creadoPorUsuarioId,
        int $tipoMovimientoId = 23,
        int $estatus = 1
    ): int {
        $sql = "INSERT INTO costos_operacion_ferro
               (operacion_ferro_id, tipo_movimiento_id, monto, comentario, creado_por, estatus)
            VALUES (?, ?, ?, ?, ?, ?)";
        return (int)$this->insertar($sql, [
            $operacionFerroId,
            $tipoMovimientoId,
            $monto,
            $comentario,
            $creadoPorUsuarioId,
            $estatus
        ]);
    }



    //LISTAR PAGINADO
  public function listarRutasFerroCatalogo(
    string $q = '',
    ?string $desde = null,
    ?string $hasta = null,
    int $page = 1,
    int $perPage = 10
): array {
    $page    = max(1, (int)$page);
    $perPage = max(1, min(200, (int)$perPage));
    $offset  = ($page - 1) * $perPage;

    $buscar  = trim($q) !== '' ? '%' . trim($q) . '%' : null;
    $useDate = (!empty($desde) || !empty($hasta));

    // Agregados SOLO con tramos activos
    $sqlAggTramos = "
        SELECT 
            t.ruta_id,
            COUNT(*)                 AS tramos_count,
            COALESCE(SUM(t.monto),0) AS costo_acumulado,
            MAX(t.created_at)        AS t_max_updated
        FROM rutas_ferro_tramos t
        WHERE t.estatus = 1
        GROUP BY t.ruta_id
    ";

    // Primer tramo (de tramos activos)
    $sqlFirst = "
        SELECT tt.ruta_id, tt.id_tramo, tt.origen_id
        FROM rutas_ferro_tramos tt
        INNER JOIN (
            SELECT ruta_id, MIN(orden) AS min_orden
            FROM rutas_ferro_tramos
            WHERE estatus = 1
            GROUP BY ruta_id
        ) x ON x.ruta_id = tt.ruta_id AND x.min_orden = tt.orden
        WHERE tt.estatus = 1
    ";

    // Último tramo (de tramos activos)
    $sqlLast = "
        SELECT tt.ruta_id, tt.id_tramo, tt.destino_id
        FROM rutas_ferro_tramos tt
        INNER JOIN (
            SELECT ruta_id, MAX(orden) AS max_orden
            FROM rutas_ferro_tramos
            WHERE estatus = 1
            GROUP BY ruta_id
        ) x ON x.ruta_id = tt.ruta_id AND x.max_orden = tt.orden
        WHERE tt.estatus = 1
    ";

    $sqlClientesOp = "
        SELECT 
            z.operacion_ferro_id,
            GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS clientes
        FROM (
            SELECT ofx.id_operacion_ferro AS operacion_ferro_id, ofx.cliente_id
            FROM operaciones_ferroviarias ofx

            UNION ALL

            SELECT cmf.operacion_ferro_id, o.cliente_id
            FROM contenedor_maritimo_ferro cmf
            INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
            INNER JOIN operaciones o ON o.id_operacion = cmo.operacion_id
        ) z
        INNER JOIN clientes c ON c.id_cliente = z.cliente_id
        GROUP BY z.operacion_ferro_id
    ";

    // Base: IMPORTANTE -> rf.estatus = 1 (solo rutas activas)
    $sqlBase = "
        FROM rutas_ferro rf
        INNER JOIN operaciones_ferroviarias ofe 
                ON ofe.id_operacion_ferro = rf.operacion_ferro_id
        LEFT JOIN  contenedores_fisicos cf 
                ON cf.id_fisico = rf.contenedor_fisico_id

        LEFT JOIN ($sqlAggTramos) agg  ON agg.ruta_id  = rf.id_ruta
        LEFT JOIN ($sqlFirst)     f    ON f.ruta_id    = rf.id_ruta
        LEFT JOIN ($sqlLast)      l    ON l.ruta_id    = rf.id_ruta

        LEFT JOIN ciudades cori ON cori.id_ciudad = f.origen_id
        LEFT JOIN ciudades cdes ON cdes.id_ciudad = l.destino_id

        LEFT JOIN ($sqlClientesOp) cli ON cli.operacion_ferro_id = ofe.id_operacion_ferro
        WHERE rf.estatus = 1
    ";

    $fechaOrden = "COALESCE(agg.t_max_updated, rf.updated_at, rf.created_at)";

    // WHERE dinámico (se añade encima de rf.estatus=1 ya presente en $sqlBase)
    $wheres = [];
    $paramsCount = [];
    $paramsData  = [];

    if ($buscar !== null) {
        $wheres[] = "(ofe.numero_operacion LIKE ? 
                  OR cf.numero_ferro LIKE ?
                  OR cli.clientes LIKE ?
                  OR cori.nombre_ciudad LIKE ?
                  OR cdes.nombre_ciudad LIKE ?)";
        array_push($paramsCount, $buscar, $buscar, $buscar, $buscar, $buscar);
        array_push($paramsData,  $buscar, $buscar, $buscar, $buscar, $buscar);
    }

    if ($useDate) {
        if (!empty($desde)) {
            $wheres[] = "DATE($fechaOrden) >= ?";
            $paramsCount[] = $desde;
            $paramsData[]  = $desde;
        }
        if (!empty($hasta)) {
            $wheres[] = "DATE($fechaOrden) <= ?";
            $paramsCount[] = $hasta;
            $paramsData[]  = $hasta;
        }
    }

    $whereSqlExtra = count($wheres) ? (" AND " . implode(" AND ", $wheres)) : "";

    // TOTAL (ya incluye rf.estatus=1 en $sqlBase)
    $sqlTotal = "SELECT COUNT(*) AS total " . $sqlBase . $whereSqlExtra;
    $rowTot   = $this->select($sqlTotal, $paramsCount);
    $total    = $rowTot ? (int)$rowTot['total'] : 0;

    // DATA
    $sqlData = "
        SELECT
            rf.id_ruta                  AS ferro_ruta_id,
            ofe.id_operacion_ferro     AS operacion_id,
            ofe.numero_operacion       AS operacion_numero,
            cf.id_fisico               AS ferro_id,
            cf.numero_ferro            AS ferro_nombre,

            cli.clientes               AS cliente,

            cori.nombre_ciudad         AS origen_inicial,
            cdes.nombre_ciudad         AS destino_actual,

            COALESCE(agg.tramos_count, 0)     AS tramos_count,
            COALESCE(agg.costo_acumulado, 0)  AS costo_acumulado,

            $fechaOrden                 AS updated_at
        " . $sqlBase . $whereSqlExtra . "
        ORDER BY $fechaOrden DESC, rf.id_ruta DESC
        LIMIT $perPage OFFSET $offset
    ";
    $rows = $this->selectAll($sqlData, $paramsData) ?: [];

    $from = ($total === 0) ? 0 : ($offset + 1);
    $to   = ($total === 0) ? 0 : min($total, $offset + $perPage);

    return [
        'ok'    => true,
        'total' => $total,
        'from'  => $from,
        'to'    => $to,
        'data'  => $rows
    ];
}


    // === HEADER de la ruta (solo lectura para el modal de edición)
    public function getRutaFerroHeaderByRutaId(int $rutaId): ?array
    {
        $sql = "
        SELECT 
            rf.id_ruta,
            rf.operacion_ferro_id,
            rf.contenedor_fisico_id,
            rf.comentario AS comentario_ruta,
            ofe.numero_operacion,
            cf.numero_ferro
        FROM rutas_ferro rf
        INNER JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = rf.operacion_ferro_id
        LEFT JOIN contenedores_fisicos cf       ON cf.id_fisico          = rf.contenedor_fisico_id
        WHERE rf.id_ruta = ?
        LIMIT 1
    ";
        $row = $this->select($sql, [$rutaId]);
        return $row ?: null;
    }

    // === Clientes (todos) por operacion_ferro_id (para chips, solo lectura)
    public function getClientesPorOperacionFerroId(int $operacionFerroId): array
    {
        $sql = "
        SELECT DISTINCT c.id_cliente, c.nombre
        FROM (
            SELECT ofx.cliente_id
            FROM operaciones_ferroviarias ofx
            WHERE ofx.id_operacion_ferro = ?

            UNION

            SELECT o.cliente_id
            FROM contenedor_maritimo_ferro cmf
            INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
            INNER JOIN operaciones o ON o.id_operacion = cmo.operacion_id
            WHERE cmf.operacion_ferro_id = ?
              AND o.cliente_id IS NOT NULL
        ) x
        INNER JOIN clientes c ON c.id_cliente = x.cliente_id
        ORDER BY c.nombre
    ";
        return $this->selectAll($sql, [$operacionFerroId, $operacionFerroId]) ?: [];
    }

    // === Tramos con nombres (para pintar tabla en edición)
    public function getTramosPorRutaConNombres(int $rutaId): array
    {
        $sql = "
        SELECT 
            t.id_tramo,
            t.ruta_id,
            t.orden,
            t.origen_id,
            co.nombre_ciudad   AS origen_nombre,
            t.destino_id,
            cd.nombre_ciudad   AS destino_nombre,
            t.transportista_id,
            tr.nombre          AS transportista_nombre,
            t.monto,
            t.comentario,
            t.created_at
        FROM rutas_ferro_tramos t
        LEFT JOIN ciudades       co ON co.id_ciudad = t.origen_id
        LEFT JOIN ciudades       cd ON cd.id_ciudad = t.destino_id
        LEFT JOIN transportistas tr ON tr.id_transportista = t.transportista_id
        WHERE t.ruta_id = ? AND t.estatus = 1
        ORDER BY t.orden ASC, t.id_tramo ASC
    ";
        return $this->selectAll($sql, [$rutaId]) ?: [];
    }
 /**************************************************************
 * DIFERENCIAL (inserta/actualiza/elimina)
 * - Solo registra COSTO en INSERT (no en UPDATE)
 * - Evita colisiones de orden asignando MAX(orden)+1 para cada nuevo tramo
 **************************************************************/
public function guardarTramosYCostosTransaccional(
    int $operacionFerroId,
    int $rutaId,
    array $tramosPayload,
    int $creadoPorUsuarioId
): array {
    // 1) Normalizar payload
    $tramos = [];
    $idsEnPayload = [];
    $n = 0;

    foreach ($tramosPayload as $t) {
        $n++;
        $idTramo         = isset($t['id_tramo']) ? (int)$t['id_tramo'] : 0;
        $orden           = isset($t['orden']) ? (int)$t['orden'] : $n; // se ignora en INSERT para evitar colisiones
        $origenId        = (int)($t['origen_id'] ?? 0);
        $destinoId       = (int)($t['destino_id'] ?? 0);
        $transportistaId = (int)($t['transportista_id'] ?? 0);
        $monto           = (float)($t['monto'] ?? 0);
        $comentario      = isset($t['comentario']) ? trim((string)$t['comentario']) : null;

        if ($origenId <= 0 || $destinoId <= 0 || $transportistaId <= 0) {
            return ['ok'=>false, 'msg'=>"Tramo #$n inválido (IDs requeridos)."];
        }
        $tramos[] = compact('idTramo','orden','origenId','destinoId','transportistaId','monto','comentario');
        if ($idTramo > 0) $idsEnPayload[] = $idTramo;
    }

    // 2) Diferencial (qué se elimina)
    $existentes    = $this->getTramosPorRuta($rutaId);
    $idsExistentes = array_map(fn($r)=>(int)$r['id_tramo'], $existentes);
    $aEliminar     = array_values(array_diff($idsExistentes, $idsEnPayload));

    // 3) Preparar siguiente orden disponible (una sola vez)
    $rowNext  = $this->select("SELECT COALESCE(MAX(orden),0)+1 AS nexto FROM rutas_ferro_tramos WHERE ruta_id = ?", [$rutaId]);
    $nextOrden = $rowNext ? (int)$rowNext['nexto'] : 1;

    $insertados = 0; $actualizados = 0; $costos = 0;

    try {
        $this->save("START TRANSACTION", []);

        // 3.1) Eliminar faltantes (0 filas afectadas es OK; solo false es error)
        if (!empty($aEliminar)) {
            $okDel = $this->eliminarTramosPorIds($aEliminar);
            if ($okDel === false) {
                $this->save("ROLLBACK", []);
                return ['ok'=>false, 'msg'=>'No fue posible eliminar tramos removidos.'];
            }
        }

        // 3.2) Upsert
        foreach ($tramos as $t) {
            if ($t['idTramo'] > 0) {
                // UPDATE (sin costo nuevo)
                $ok = $this->actualizarTramoRutaFerro(
                    $t['idTramo'], $t['orden'], $t['origenId'], $t['destinoId'],
                    $t['transportistaId'], $t['monto'], $t['comentario']
                );
                if ($ok === false) {
                    $this->save("ROLLBACK", []);
                    return ['ok'=>false,'msg'=>'No fue posible actualizar un tramo.'];
                }
                $actualizados++;
            } else {
                // INSERT (asignar orden consecutivo para evitar colisiones) + costo
                $ordenFinal = $nextOrden++;
                $nuevoId = $this->insertarTramoRutaFerro(
                    $rutaId, $ordenFinal, $t['origenId'], $t['destinoId'],
                    $t['transportistaId'], $t['monto'], $t['comentario']
                );
                if ($nuevoId <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['ok'=>false,'msg'=>'No fue posible insertar un tramo.'];
                }
                $insertados++;

                // registrar costo SOLO en INSERT
                $idCosto = $this->insertarCostoTransporteFerro(
                    $operacionFerroId, $t['monto'], $t['comentario'], $creadoPorUsuarioId, 23, 1
                );
                if ($idCosto <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['ok'=>false,'msg'=>'Tramo insertado, pero falló el costo asociado.'];
                }
                $costos++;
            }
        }

        $this->save("COMMIT", []);
        return [
            'ok'           => true,
            'insertados'   => $insertados,
            'actualizados' => $actualizados,
            'eliminados'   => count($aEliminar),
            'costos'       => $costos
        ];
    } catch (Throwable $e) {
        $this->save("ROLLBACK", []);
        error_log('guardarTramosYCostosTransaccional: '.$e->getMessage());
        return ['ok'=>false, 'msg'=>'Error interno en la transacción.'];
    }
}
/*********************************************************
 * APPEND-ONLY (no elimina tramos que no vengan)
 * - Solo registra COSTO en INSERT (no en UPDATE)
 * - Evita colisiones de orden asignando MAX(orden)+1 por cada nuevo insert
 *********************************************************/
public function guardarTramosAppendOnly(
    int $operacionFerroId,
    int $rutaId,
    array $tramosPayload,
    int $creadoPorUsuarioId
): array {
    // 1) Normalizar payload
    $tramos = [];
    $n = 0;
    foreach ($tramosPayload as $t) {
        $n++;
        $idTramo         = isset($t['id_tramo']) ? (int)$t['id_tramo'] : 0;
        $orden           = isset($t['orden']) ? (int)$t['orden'] : $n; // se ignora en INSERT
        $origenId        = (int)($t['origen_id'] ?? 0);
        $destinoId       = (int)($t['destino_id'] ?? 0);
        $transportistaId = (int)($t['transportista_id'] ?? 0);
        $monto           = (float)($t['monto'] ?? 0);
        $comentario      = isset($t['comentario']) ? trim((string)$t['comentario']) : null;

        if ($origenId <= 0 || $destinoId <= 0 || $transportistaId <= 0) {
            return ['ok'=>false, 'msg'=>"Tramo #$n inválido (IDs requeridos)."];
        }
        $tramos[] = compact('idTramo','orden','origenId','destinoId','transportistaId','monto','comentario');
    }

    // 2) Preparar siguiente orden disponible (una sola vez)
    $rowNext   = $this->select("SELECT COALESCE(MAX(orden),0)+1 AS nexto FROM rutas_ferro_tramos WHERE ruta_id = ?", [$rutaId]);
    $nextOrden = $rowNext ? (int)$rowNext['nexto'] : 1;

    $insertados = 0; $actualizados = 0; $costos = 0;

    $this->save("START TRANSACTION", []);
    try {
        foreach ($tramos as $t) {
            if ($t['idTramo'] > 0) {
                // UPDATE (sin costo nuevo)
                $ok = $this->actualizarTramoRutaFerro(
                    $t['idTramo'], $t['orden'], $t['origenId'], $t['destinoId'],
                    $t['transportistaId'], $t['monto'], $t['comentario']
                );
                if ($ok !== 0 && $ok !== 1) { // false = error real
                    if ($ok === false) {
                        $this->save("ROLLBACK", []);
                        return ['ok'=>false,'msg'=>'No fue posible actualizar un tramo.'];
                    }
                }
                $actualizados++;
            } else {
                // INSERT (asignar orden consecutivo) + costo
                $ordenFinal = $nextOrden++;
                $nuevoId = $this->insertarTramoRutaFerro(
                    $rutaId, $ordenFinal, $t['origenId'], $t['destinoId'],
                    $t['transportistaId'], $t['monto'], $t['comentario']
                );
                if ($nuevoId <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['ok'=>false,'msg'=>'No fue posible insertar un tramo.'];
                }
                $insertados++;

                // costo SOLO en INSERT
                $idCosto = $this->insertarCostoTransporteFerro(
                    $operacionFerroId, $t['monto'], $t['comentario'], $creadoPorUsuarioId, 23, 1
                );
                if ($idCosto <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['ok'=>false,'msg'=>'Falló costo (insert).'];
                }
                $costos++;
            }
        }

        $this->save("COMMIT", []);
        return ['ok'=>true, 'insertados'=>$insertados, 'actualizados'=>$actualizados, 'costos'=>$costos];
    } catch (Throwable $e) {
        $this->save("ROLLBACK", []);
        error_log('guardarTramosAppendOnly: '.$e->getMessage());
        return ['ok'=>false, 'msg'=>'Error interno en la transacción.'];
    }
}


// 1) Helper: siguiente orden disponible para una ruta
private function getNextOrdenForRuta(int $rutaId): int {
    $row = $this->select("SELECT COALESCE(MAX(orden),0)+1 AS nexto FROM rutas_ferro_tramos WHERE ruta_id = ?", [$rutaId]);
    return $row ? (int)$row['nexto'] : 1;
}

// (Opcional) Si quieres conservar el orden del payload cuando no colisiona:
private function ordenDisponible(int $rutaId, int $orden): bool {
    $row = $this->select("SELECT 1 FROM rutas_ferro_tramos WHERE ruta_id=? AND orden=? LIMIT 1", [$rutaId, $orden]);
    return !$row; // true si NO existe
}
public function bajaLogicaRutaFerroSinRutaId(
    int $rutaId,
    int $usuarioId,
    array $transportTypeIds = [23] // <- aquí define(s) tus tipos "Transporte"
): array {
    // 0) FO (encabezado) de la ruta
    $row = $this->select(
        "SELECT operacion_ferro_id 
           FROM rutas_ferro 
          WHERE id_ruta = ? AND estatus = 1 
          LIMIT 1",
        [$rutaId]
    );
    if (!$row) {
        return ['ok'=>false, 'msg'=>'Ruta no encontrada o ya dada de baja.'];
    }
    $operacionFerroId = (int)$row['operacion_ferro_id'];

    // Build IN (?, ?, ?) para los tipos de transporte
    $transportTypeIds = array_values(array_unique(array_map('intval', $transportTypeIds)));
    if (empty($transportTypeIds)) {
        return ['ok'=>false, 'msg'=>'No se definieron tipos de movimiento de transporte.'];
    }
    $marks = implode(',', array_fill(0, count($transportTypeIds), '?'));
    $paramsTipos = $transportTypeIds; // para reuso

    try {
        $this->save("START TRANSACTION", []);

        // 1) Apagar TRAMOS de la ruta
        $ok1 = $this->save(
            "UPDATE rutas_ferro_tramos 
                SET estatus = 0 
              WHERE ruta_id = ? AND estatus = 1",
            [$rutaId]
        );
        if ($ok1 === false) {
            $this->save("ROLLBACK", []);
            return ['ok'=>false, 'msg'=>'No fue posible desactivar tramos.'];
        }

        // 2) Apagar COSTOS de TRANSPORTE de la FO (afecta todos los costos transporte de la FO)
        $paramsCostos = array_merge([$operacionFerroId], $paramsTipos);
        $ok2 = $this->save(
            "UPDATE costos_operacion_ferro 
                SET estatus = 0
              WHERE operacion_ferro_id = ?
                AND tipo_movimiento_id IN ($marks)
                AND estatus = 1",
            $paramsCostos
        );
        if ($ok2 === false) {
            $this->save("ROLLBACK", []);
            return ['ok'=>false, 'msg'=>'No fue posible desactivar costos de transporte.'];
        }

        // 3) Apagar la RUTA
        $ok3 = $this->save(
            "UPDATE rutas_ferro 
                SET estatus = 0, updated_at = NOW()
              WHERE id_ruta = ? AND estatus = 1",
            [$rutaId]
        );
        if ($ok3 === false) {
            $this->save("ROLLBACK", []);
            return ['ok'=>false, 'msg'=>'No fue posible desactivar la ruta.'];
        }

        // 4) (Opcional) Bitácora
        if ($usuarioId > 0) {
            @$this->insertarBitacora(
                $usuarioId, 'trazabilidad_ferro', 'baja_logica',
                'rutas_ferro', $rutaId, 
                'Baja lógica: ruta_ferro + tramos + costos transporte (FO='.$operacionFerroId.')'
            );
        }

        $this->save("COMMIT", []);
        return [
            'ok'    => true,
            'msg'   => 'Ruta y dependencias (tramos + costos de transporte) desactivadas.',
            // Tip: algunos drivers regresan 0/1; si quieres conteos reales, haz SELECT COUNT(*) previos.
        ];
    } catch (Throwable $e) {
        $this->save("ROLLBACK", []);
        error_log('bajaLogicaRutaFerroSinRutaId: '.$e->getMessage());
        return ['ok'=>false, 'msg'=>'Error interno en la transacción.'];
    }
}

// (Opcional) si no la tienes ya:
private function insertarBitacora(int $usuarioId, string $modulo, string $accion, string $entidad, int $entidadId, string $detalle=''): void {
    $sql = "INSERT INTO bitacora (usuario_id, modulo, accion, entidad, entidad_id, detalle) VALUES (?, ?, ?, ?, ?, ?)";
    $this->insertar($sql, [$usuarioId, $modulo, $accion, $entidad, $entidadId, $detalle]);
}


}
