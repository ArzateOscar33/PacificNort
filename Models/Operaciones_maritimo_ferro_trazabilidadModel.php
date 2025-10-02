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
        $tipo  = in_array($tipo, ['terrestre','maritimo','ferroviario'], true) ? $tipo : 'ferroviario' ;
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
             WHERE ruta_id = ?
             ORDER BY orden ASC, id_tramo ASC";
    return $this->selectAll($sql, [$rutaId]) ?: [];
}

public function insertarTramoRutaFerro(
    int $rutaId, int $orden, int $origenId, int $destinoId,
    int $transportistaId, float $monto, ?string $comentario = null
): int {
    $sql = "INSERT INTO rutas_ferro_tramos
               (ruta_id, orden, origen_id, destino_id, transportista_id, monto, comentario)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    return (int)$this->insertar($sql, [
        $rutaId, $orden, $origenId, $destinoId, $transportistaId, $monto, $comentario
    ]);
}

public function actualizarTramoRutaFerro(
    int $idTramo, int $orden, int $origenId, int $destinoId,
    int $transportistaId, float $monto, ?string $comentario = null
): int {
    $sql = "UPDATE rutas_ferro_tramos
               SET orden = ?, origen_id = ?, destino_id = ?, transportista_id = ?, monto = ?, comentario = ?
             WHERE id_tramo = ?
             LIMIT 1";
    return $this->save($sql, [
        $orden, $origenId, $destinoId, $transportistaId, $monto, $comentario, $idTramo
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
    int $operacionFerroId, float $monto, ?string $comentario, int $creadoPorUsuarioId,
    int $tipoMovimientoId = 23, int $estatus = 1
): int {
    $sql = "INSERT INTO costos_operacion_ferro
               (operacion_ferro_id, tipo_movimiento_id, monto, comentario, creado_por, estatus)
            VALUES (?, ?, ?, ?, ?, ?)";
    return (int)$this->insertar($sql, [
        $operacionFerroId, $tipoMovimientoId, $monto, $comentario, $creadoPorUsuarioId, $estatus
    ]);
}

/**************************************************************
 * Guardado DIFERENCIAL de tramos + costo por tramo (transacc.)
 * - Solo ciudades en origen/destino (IDs válidos y > 0)
 * - orden llega desde el “carrito” (no hay reordenamiento)
 * - Transacción TOTAL (todo o nada)
 * - Diferencial:
 *    • con id_tramo > 0 => UPDATE
 *    • sin id_tramo     => INSERT
 *    • tramos existentes que no vienen => DELETE
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
        $idTramo        = isset($t['id_tramo']) ? (int)$t['id_tramo'] : 0;
        $orden          = isset($t['orden']) ? (int)$t['orden'] : $n; // viene del carrito
        $origenId       = (int)($t['origen_id'] ?? 0);
        $destinoId      = (int)($t['destino_id'] ?? 0);
        $transportistaId= (int)($t['transportista_id'] ?? 0);
        $monto          = (float)($t['monto'] ?? 0);
        $comentario     = isset($t['comentario']) ? trim((string)$t['comentario']) : null;

        // Validaciones mínimas (si falla una, abortaremos toda la transacción)
        if ($origenId <= 0 || $destinoId <= 0 || $transportistaId <= 0) {
            return ['ok'=>false, 'msg'=>"Tramo #$n inválido (IDs requeridos)."];
        }

        $tramos[] = compact('idTramo','orden','origenId','destinoId','transportistaId','monto','comentario');
        if ($idTramo > 0) $idsEnPayload[] = $idTramo;
    }

    // 2) Cargar existentes para diferencial
    $existentes = $this->getTramosPorRuta($rutaId);
    $idsExistentes = array_map(fn($r)=>(int)$r['id_tramo'], $existentes);

    $aEliminar = array_values(array_diff($idsExistentes, $idsEnPayload)); // los que ya no vienen

    // 3) Transacción TOTAL
    $this->save("START TRANSACTION", []);

    // 3.1) Eliminar “faltantes”
    if (!empty($aEliminar)) {
        $okDel = $this->eliminarTramosPorIds($aEliminar);
        if ($okDel !== 1) {
            $this->save("ROLLBACK", []);
            return ['ok'=>false, 'msg'=>'No fue posible eliminar tramos removidos.'];
        }
        // Nota: no borramos costos históricos (no hay FK directa);
        // si deseas “reversar costos” podemos añadir una marca o un endpoint específico.
    }

    // 3.2) Upsert (update/insert) + costo por tramo
    $insertados = 0; $actualizados = 0; $costos = 0;

    foreach ($tramos as $t) {
        if ($t['idTramo'] > 0) {
            // UPDATE
            $ok = $this->actualizarTramoRutaFerro(
                $t['idTramo'], $t['orden'], $t['origenId'], $t['destinoId'],
                $t['transportistaId'], $t['monto'], $t['comentario']
            );
            if ($ok !== 1) {
                $this->save("ROLLBACK", []);
                return ['ok'=>false,'msg'=>'No fue posible actualizar un tramo.'];
            }
            $actualizados++;

            // Costo por actualización (registra el monto vigente como costo nuevo)
            $idCosto = $this->insertarCostoTransporteFerro(
                $operacionFerroId, $t['monto'], $t['comentario'], $creadoPorUsuarioId, 23, 1
            );
            if ($idCosto <= 0) {
                $this->save("ROLLBACK", []);
                return ['ok'=>false,'msg'=>'Tramo actualizado, pero falló el costo asociado.'];
            }
            $costos++;
        } else {
            // INSERT
            $nuevoId = $this->insertarTramoRutaFerro(
                $rutaId, $t['orden'], $t['origenId'], $t['destinoId'],
                $t['transportistaId'], $t['monto'], $t['comentario']
            );
            if ($nuevoId <= 0) {
                $this->save("ROLLBACK", []);
                return ['ok'=>false,'msg'=>'No fue posible insertar un tramo.'];
            }
            $insertados++;

            // Costo por inserción
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

    // 3.3) Listo
    $this->save("COMMIT", []);
    return [
        'ok'          => true,
        'insertados'  => $insertados,
        'actualizados'=> $actualizados,
        'eliminados'  => count($aEliminar),
        'costos'      => $costos
    ];
}


//LISTAR PAGINADO
public function listarRutasFerroCatalogo(
    string $q = '',
    ?string $desde = null,
    ?string $hasta = null,
    int $page = 1,
    int $perPage = 10
): array
{
    $page    = max(1, (int)$page);
    $perPage = max(1, min(200, (int)$perPage));
    $offset  = ($page - 1) * $perPage;

    // Normalizar filtros
    $buscar = trim($q) !== '' ? '%' . trim($q) . '%' : null;
    $useDate = (!empty($desde) || !empty($hasta));

    // --- Subquery: agregados de tramos (count, sum, max_updated)
    // FIX: rutas_ferro_tramos NO tiene updated_at; usar created_at.
    $sqlAggTramos = "
        SELECT 
            t.ruta_id,
            COUNT(*)                 AS tramos_count,
            COALESCE(SUM(t.monto),0) AS costo_acumulado,
            MAX(t.created_at)        AS t_max_updated
        FROM rutas_ferro_tramos t
        GROUP BY t.ruta_id
    ";

    // --- Subquery: primer tramo por ruta (para origen)
    $sqlFirst = "
        SELECT tt.ruta_id, tt.id_tramo, tt.origen_id
        FROM rutas_ferro_tramos tt
        INNER JOIN (
            SELECT ruta_id, MIN(orden) AS min_orden
            FROM rutas_ferro_tramos
            GROUP BY ruta_id
        ) x ON x.ruta_id = tt.ruta_id AND x.min_orden = tt.orden
    ";

    // --- Subquery: último tramo por ruta (para destino)
    $sqlLast = "
        SELECT tt.ruta_id, tt.id_tramo, tt.destino_id
        FROM rutas_ferro_tramos tt
        INNER JOIN (
            SELECT ruta_id, MAX(orden) AS max_orden
            FROM rutas_ferro_tramos
            GROUP BY ruta_id
        ) x ON x.ruta_id = tt.ruta_id AND x.max_orden = tt.orden
    ";

    // --- Subquery: clientes (TODOS) por operación_ferro
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

    // --- SQL base con joins y agregados
    $sqlBase = "
        FROM rutas_ferro rf
        INNER JOIN operaciones_ferroviarias ofe 
                ON ofe.id_operacion_ferro = rf.operacion_ferro_id
        LEFT JOIN contenedores_fisicos cf 
                ON cf.id_fisico = rf.contenedor_fisico_id

        LEFT JOIN ($sqlAggTramos) agg  ON agg.ruta_id  = rf.id_ruta
        LEFT JOIN ($sqlFirst)     f    ON f.ruta_id    = rf.id_ruta
        LEFT JOIN ($sqlLast)      l    ON l.ruta_id    = rf.id_ruta

        LEFT JOIN ciudades cori ON cori.id_ciudad = f.origen_id     -- origen inicial
        LEFT JOIN ciudades cdes ON cdes.id_ciudad = l.destino_id    -- destino actual

        LEFT JOIN ($sqlClientesOp) cli ON cli.operacion_ferro_id = ofe.id_operacion_ferro
    ";

    // Campo de fecha efectiva para filtro/orden
    $fechaOrden = "COALESCE(agg.t_max_updated, rf.updated_at, rf.created_at)";

    // --- WHERE dinámico
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

    $whereSql = count($wheres) ? ("WHERE " . implode(" AND ", $wheres)) : "";

    // --- TOTAL
    $sqlTotal = "SELECT COUNT(*) AS total " . $sqlBase . " " . $whereSql;
    $rowTot = $this->select($sqlTotal, $paramsCount);
    $total = $rowTot ? (int)$rowTot['total'] : 0;

    // --- DATA
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
        " . $sqlBase . "
        $whereSql
        ORDER BY $fechaOrden DESC, rf.id_ruta DESC
        LIMIT $perPage OFFSET $offset
    ";
    $rows = $this->selectAll($sqlData, $paramsData) ?: [];

    // Rango mostrado
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


}
