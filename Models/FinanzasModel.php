<?php
class FinanzasModel extends Query
{
    public function catalogoClientes(): array
    {
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function getBrokers(): array
    {
        $sql = "SELECT id_broker, nombre
                FROM brokers
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function getTransportistas(): array
    {
        $sql = "SELECT id_transportista, nombre
                FROM transportistas
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function listarDestinos(): array
    {
        $sql = "SELECT id_ciudad, nombre_ciudad
                FROM ciudades
                WHERE estatus = 1
                ORDER BY nombre_ciudad";
        return $this->selectAll($sql) ?: [];
    }

    public function listarCategoriasCostos(): array
    {
        $sql = "SELECT id_categoria, nombre
                FROM tipos_movimiento_categorias
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function buscarClientes(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                  AND nombre LIKE ?
                ORDER BY nombre
                LIMIT 10";
        return $this->selectAll($sql, ["%{$term}%"]) ?: [];
    }

    private function isValidDate(string $date): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    private function buildWhereMaritimo(array $filters): array
    {
        $where = " WHERE st.tipo_operacion_id = 11
         AND co.estatus = 1
         AND tm.estatus = 1
         AND LOWER(tm.tipo) = 'gasto' ";
        $args = [];

        // Origen: si el filtro pide solo PARTIDA, excluir esta fuente
        $origenTipo = $this->normalizeOrigen($filters['origen_tipo'] ?? '');
        if ($origenTipo === 'PARTIDA/DOMESTICO') {
            $where .= " AND 1 = 0 ";
            return ['sql' => $where, 'args' => $args];
        }

        // Cliente
        $clienteId = (int)($filters['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $where .= " AND o.cliente_id = ? ";
            $args[] = $clienteId;
        }

        // Fechas
        [$fi, $ff] = $this->parseFechas($filters);
        if ($fi !== '' && $ff !== '') {
            $where .= " AND DATE(co.fecha_creacion) BETWEEN ? AND ? ";
            $args[] = $fi;
            $args[] = $ff;
        } elseif ($fi !== '') {
            $where .= " AND DATE(co.fecha_creacion) >= ? ";
            $args[] = $fi;
        } elseif ($ff !== '') {
            $where .= " AND DATE(co.fecha_creacion) <= ? ";
            $args[] = $ff;
        }

        // Broker
        $brokerId = (int)($filters['broker_id'] ?? 0);
        if ($brokerId > 0) {
            $where .= " AND bro.broker_id = ? ";
            $args[] = $brokerId;
        }

        // =====================================================
        // Transportista marítimo (operación principal)
        // Compatibilidad:
        // - transportista_maritimo_id
        // - transportista_id (legacy)
        // =====================================================
        $transportistaMaritimoId = 0;
        if (isset($filters['transportista_maritimo_id'])) {
            $transportistaMaritimoId = (int)$filters['transportista_maritimo_id'];
        } elseif (isset($filters['transportista_id'])) {
            $transportistaMaritimoId = (int)$filters['transportista_id'];
        }

        if ($transportistaMaritimoId > 0) {
            $where .= " AND o.transportista_id = ? ";
            $args[] = $transportistaMaritimoId;
        }

        // =====================================================
        // Transportista ferro/caja
        // =====================================================
        $transportistaFerroId = (int)($filters['transportista_ferro_id'] ?? 0);
        if ($transportistaFerroId > 0) {
            $where .= " AND EXISTS (
            SELECT 1
            FROM contenedores_maritimos_operacion cmoF
            INNER JOIN contenedor_maritimo_ferro cmfF
                ON cmfF.cont_maritimo_operacion_id = cmoF.id
               AND cmfF.estatus = 1
            INNER JOIN operaciones_ferroviarias ofF
                ON ofF.id_operacion_ferro = cmfF.operacion_ferro_id
            WHERE cmoF.operacion_id = o.id_operacion
              AND ofF.transportista_id = ?
        ) ";
            $args[] = $transportistaFerroId;
        }

        // Pagado
        $pagado = $filters['pagado'] ?? '';
        if ($pagado !== '' && in_array($pagado, ['0', '1', 0, 1], true)) {
            $where .= " AND co.Pagado = ? ";
            $args[] = (int)$pagado;
        }

        // Categoría
        $categoriaId = (int)($filters['categoria_id'] ?? 0);
        if ($categoriaId > 0) {
            $where .= " AND tm.categoria_id = ? ";
            $args[] = $categoriaId;
        }

        // =====================================================
        // Búsqueda libre:
        // - operación
        // - cliente
        // - concepto
        // - comentario
        // - contenedor marítimo
        // - ferro/caja
        // - transportista ferro/caja
        // =====================================================
        $raw = trim((string)($filters['term'] ?? ''));
        if ($raw !== '') {
            $terms = array_values(array_filter(array_map(
                fn($t) => mb_strtolower(trim($t), 'UTF-8'),
                explode(',', $raw)
            ), fn($t) => $t !== ''));
            $terms = array_slice($terms, 0, 5);

            foreach ($terms as $t) {
                $needle = '%' . $t . '%';

                $where .= " AND (
                LOWER(COALESCE(o.numero_operacion, '')) LIKE ?
                OR LOWER(COALESCE(cl.nombre, '')) LIKE ?
                OR LOWER(COALESCE(tm.nombre, '')) LIKE ?
                OR LOWER(COALESCE(co.comentario, '')) LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM contenedores_maritimos_operacion cmo2
                    INNER JOIN contenedores_maritimos cm2
                        ON cm2.id_contenedor_maritimo = cmo2.contenedor_maritimo_id
                    WHERE cmo2.operacion_id = o.id_operacion
                      AND LOWER(COALESCE(cm2.numero_contenedor, '')) LIKE ?
                )
                OR EXISTS (
                    SELECT 1
                    FROM contenedores_maritimos_operacion cmo3
                    INNER JOIN contenedor_maritimo_ferro cmf3
                        ON cmf3.cont_maritimo_operacion_id = cmo3.id
                       AND cmf3.estatus = 1
                    INNER JOIN contenedores_fisicos cf3
                        ON cf3.id_fisico = cmf3.contenedor_fisico_id
                    LEFT JOIN operaciones_ferroviarias of3
                        ON of3.id_operacion_ferro = cmf3.operacion_ferro_id
                    LEFT JOIN transportistas tf3
                        ON tf3.id_transportista = of3.transportista_id
                    WHERE cmo3.operacion_id = o.id_operacion
                      AND (
                          LOWER(COALESCE(cf3.numero_ferro, '')) LIKE ?
                          OR LOWER(COALESCE(tf3.nombre, '')) LIKE ?
                      )
                )
            ) ";

                array_push(
                    $args,
                    $needle, // o.numero_operacion
                    $needle, // cl.nombre
                    $needle, // tm.nombre
                    $needle, // co.comentario
                    $needle, // contenedor marítimo
                    $needle, // ferro/caja
                    $needle  // transportista ferro
                );
            }
        }

        return ['sql' => $where, 'args' => $args];
    }

    private function buildWherePartida(array $filters): array
    {
        $where = " WHERE cop.estatus = 1
                 AND f.estatus = 1
                 AND tm.estatus = 1
                 AND LOWER(tm.tipo) = 'gasto' ";
        $args = [];

        // Origen: si el filtro pide solo MARITIMO, excluir esta fuente
        $origenTipo = $this->normalizeOrigen($filters['origen_tipo'] ?? '');
        if ($origenTipo === 'MARITIMO-FERRO') {
            $where .= " AND 1 = 0 ";
            return ['sql' => $where, 'args' => $args];
        }

        // Broker: partida no tiene broker -> si se filtra por broker, excluir
        $brokerId = (int)($filters['broker_id'] ?? 0);
        if ($brokerId > 0) {
            $where .= " AND 1 = 0 ";
            return ['sql' => $where, 'args' => $args];
        }

        // Cliente
        $clienteId = (int)($filters['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $where .= " AND f.cliente_id = ? ";
            $args[] = $clienteId;
        }

        // Fechas
        [$fi, $ff] = $this->parseFechas($filters);
        if ($fi !== '' && $ff !== '') {
            $where .= " AND DATE(cop.fecha_creacion) BETWEEN ? AND ? ";
            $args[] = $fi;
            $args[] = $ff;
        } elseif ($fi !== '') {
            $where .= " AND DATE(cop.fecha_creacion) >= ? ";
            $args[] = $fi;
        } elseif ($ff !== '') {
            $where .= " AND DATE(cop.fecha_creacion) <= ? ";
            $args[] = $ff;
        }

        // En PARTIDA/DOMESTICO el filtro aplicable es el de caja/ferro
        $transportistaFerroId = (int)($filters['transportista_ferro_id'] ?? 0);
        if ($transportistaFerroId > 0) {
            $where .= " AND env.transportista_id = ? ";
            $args[] = $transportistaFerroId;
        }

        // Pagado
        $pagado = $filters['pagado'] ?? '';
        if ($pagado !== '' && in_array($pagado, ['0', '1', 0, 1], true)) {
            $where .= " AND cop.pagado = ? ";
            $args[] = (int)$pagado;
        }

        // Categoría
        $categoriaId = (int)($filters['categoria_id'] ?? 0);
        if ($categoriaId > 0) {
            $where .= " AND tm.categoria_id = ? ";
            $args[] = $categoriaId;
        }

        // Búsqueda libre
        [$termWhere, $termArgs] = $this->buildTermWhere(
            $filters['term'] ?? '',
            [
                'f.numero_factura',
                'cl.nombre',
                'cf.numero_ferro',
                'tm.nombre',
                'cop.comentario',
                'env.transportistas',
                'env.estatuses'
            ]
        );
        $where .= $termWhere;
        $args   = array_merge($args, $termArgs);

        return ['sql' => $where, 'args' => $args];
    }

    /**
     * Normaliza el filtro de origen_tipo.
     * Devuelve 'MARITIMO-FERRO', 'PARTIDA/DOMESTICO', o '' (todos).
     */
    private function normalizeOrigen(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';
        $norm = mb_strtoupper($raw, 'UTF-8');
        $norm = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $norm);
        $norm = preg_replace('/\s+/', '', $norm);
        if (in_array($norm, ['PARTIDA/DOMESTICO', 'PARTIDA-DOMESTICO'], true)) return 'PARTIDA/DOMESTICO';
        if (in_array($norm, ['MARITIMO-FERRO', 'MARITIMOFERRO'], true))        return 'MARITIMO-FERRO';
        return '';
    }

    /**
     * Parsea y valida las fechas de los filtros.
     * @return array [fecha_inicio, fecha_fin]  (strings '' si inválidos)
     */
    private function parseFechas(array $filters): array
    {
        $fi = trim((string)($filters['fecha_inicio'] ?? ''));
        $ff = trim((string)($filters['fecha_fin']    ?? ''));
        if ($fi !== '' && !$this->isValidDate($fi)) $fi = '';
        if ($ff !== '' && !$this->isValidDate($ff)) $ff = '';
        if ($fi !== '' && $ff !== '' && $fi > $ff) [$fi, $ff] = [$ff, $fi];
        return [$fi, $ff];
    }

    /**
     * Construye la parte AND (...LIKE...) para búsqueda libre.
     * $columns: columnas reales de la tabla (con alias de tabla, ej: 'o.numero_operacion')
     */
    private function buildTermWhere(string $raw, array $columns): array
    {
        $raw = trim($raw);
        if ($raw === '') return ['', []];

        $terms = array_values(array_filter(array_map(
            fn($t) => mb_strtolower(trim($t), 'UTF-8'),
            explode(',', $raw)
        ), fn($t) => $t !== ''));
        $terms = array_slice($terms, 0, 5);

        $where = '';
        $args  = [];

        foreach ($terms as $t) {
            $needle = '%' . $t . '%';
            $parts  = array_map(
                fn($col) => "LOWER(COALESCE({$col}, '')) LIKE ?",
                $columns
            );
            $where .= " AND (" . implode(' OR ', $parts) . ") ";
            foreach ($columns as $_) {
                $args[] = $needle;
            }
        }

        return [$where, $args];
    }

    // ================================================================
    //  SQL BASE POR FUENTE (sin WHERE — se agrega dinámicamente)
    // ================================================================

    private function sqlMaritimo(): string
    {
        return "
SELECT
    CONCAT('MAR-', co.id_costo_operacion)                         AS registro_id,
    'MARITIMO-FERRO'                                              AS origen_tipo,
    1                                                             AS origen_orden,
    o.id_operacion                                                AS origen_id,
    co.id_costo_operacion                                         AS costo_id,
    COALESCE(co.fecha_creacion, o.eta)                            AS fecha_base,
    o.numero_operacion                                            AS referencia,
    cl.id_cliente,
    COALESCE(cl.nombre, 'Sin cliente')                            AS cliente,
    COALESCE(cont.contenedores, 'No aplica')                      AS contenedor,
    COALESCE(fer.ferros, 'No aplica')                             AS ferro_caja,

    o.transportista_id,
    COALESCE(tr.nombre, 'No aplica')                              AS transportista,

    COALESCE(fer.transportista_ferro_id, 0)                       AS transportista_ferro_id,
    COALESCE(fer.transportistas_ferro, 'No aplica')               AS transportista_ferro,

    bro.broker_id,
    COALESCE(bro.brokers, 'No aplica')                            AS broker,
    COALESCE(e.nombre, 'Sin estatus')                             AS estatus_operacion,
    COALESCE(DATE_FORMAT(o.cita_puerto, '%Y-%m-%d'), 'No aplica') AS cita_puerto,
    CASE
        WHEN COALESCE(o.isf, 0) = 1 THEN 'Sí'
        ELSE 'No'
    END                                                           AS isf,
    tm.categoria_id,
    COALESCE(tmc.nombre, '')                                      AS categoria,
    tm.id_tipo_movimiento                                         AS tipo_movimiento_id,
    tm.nombre                                                     AS concepto,
    tm.moneda,
    COALESCE(co.monto, 0)                                         AS monto,
    COALESCE(co.Pagado, 0)                                        AS pagado,
    COALESCE(co.comentario, '')                                   AS comentario

FROM operaciones o
LEFT JOIN subtipos_operacion st
    ON st.id_subtipo = o.subtipo_operacion_id
LEFT JOIN clientes cl
    ON cl.id_cliente = o.cliente_id
LEFT JOIN estatus e
    ON e.id_estatus = o.estatus_id
LEFT JOIN transportistas tr
    ON tr.id_transportista = o.transportista_id
INNER JOIN costos_operacion co
    ON co.operacion_id = o.id_operacion
INNER JOIN tipos_movimiento tm
    ON tm.id_tipo_movimiento = co.tipo_movimiento_id
LEFT JOIN tipos_movimiento_categorias tmc
    ON tmc.id_categoria = tm.categoria_id
   AND tmc.estatus = 1
LEFT JOIN (
    SELECT
        cmo.operacion_id,
        GROUP_CONCAT(
            DISTINCT cm.numero_contenedor
            ORDER BY cm.numero_contenedor SEPARATOR ', '
        ) AS contenedores
    FROM contenedores_maritimos_operacion cmo
    INNER JOIN contenedores_maritimos cm
        ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
    GROUP BY cmo.operacion_id
) cont
    ON cont.operacion_id = o.id_operacion
LEFT JOIN (
    SELECT
        cmo.operacion_id,
        MAX(ofe.transportista_id) AS transportista_ferro_id,
        GROUP_CONCAT(
            DISTINCT cf.numero_ferro
            ORDER BY cf.numero_ferro SEPARATOR ', '
        ) AS ferros,
        GROUP_CONCAT(
            DISTINCT COALESCE(tf.nombre, 'Sin transportista')
            ORDER BY tf.nombre SEPARATOR ', '
        ) AS transportistas_ferro
    FROM contenedores_maritimos_operacion cmo
    INNER JOIN contenedor_maritimo_ferro cmf
        ON cmf.cont_maritimo_operacion_id = cmo.id
       AND cmf.estatus = 1
    INNER JOIN contenedores_fisicos cf
        ON cf.id_fisico = cmf.contenedor_fisico_id
    LEFT JOIN operaciones_ferroviarias ofe
        ON ofe.id_operacion_ferro = cmf.operacion_ferro_id
    LEFT JOIN transportistas tf
        ON tf.id_transportista = ofe.transportista_id
    GROUP BY cmo.operacion_id
) fer
    ON fer.operacion_id = o.id_operacion
LEFT JOIN (
    SELECT
        ob.operacion_id,
        MAX(ob.broker_id) AS broker_id,
        GROUP_CONCAT(
            DISTINCT b.nombre
            ORDER BY b.nombre SEPARATOR ', '
        ) AS brokers
    FROM operacion_brokers ob
    INNER JOIN brokers b
        ON b.id_broker = ob.broker_id
    GROUP BY ob.operacion_id
) bro
    ON bro.operacion_id = o.id_operacion
";
    }

    private function sqlPartida(): string
    {
        return "
    SELECT
        CONCAT('PAR-', cop.id_costo_operacion_partida)                AS registro_id,
        'PARTIDA/DOMESTICO'                                           AS origen_tipo,
        2                                                             AS origen_orden,
        f.id_factura                                                  AS origen_id,
        cop.id_costo_operacion_partida                                AS costo_id,
        COALESCE(cop.fecha_creacion, f.creado_en)                     AS fecha_base,
        f.numero_factura                                              AS referencia,
        cl.id_cliente,
        COALESCE(cl.nombre, 'Sin cliente')                            AS cliente,
        'No aplica'                                                   AS contenedor,
        COALESCE(cf.numero_ferro, 'No aplica')                        AS ferro_caja,

        env.transportista_id                                          AS transportista_id,
        COALESCE(env.transportistas, 'No aplica')                     AS transportista,

        env.transportista_id                                          AS transportista_ferro_id,
        COALESCE(env.transportistas, 'No aplica')                     AS transportista_ferro,

        NULL                                                          AS broker_id,
        'No aplica'                                                   AS broker,
        COALESCE(env.estatuses, 'Sin envío')                          AS estatus_operacion,
        'No aplica'                                                   AS cita_puerto,
        'No aplica'                                                   AS isf,
        tm.categoria_id,
        COALESCE(tmc.nombre, '')                                      AS categoria,
        tm.id_tipo_movimiento                                         AS tipo_movimiento_id,
        tm.nombre                                                     AS concepto,
        tm.moneda,
        COALESCE(cop.monto, 0)                                        AS monto,
        COALESCE(cop.pagado, 0)                                       AS pagado,
        COALESCE(cop.comentario, '')                                  AS comentario

    FROM costos_operacion_partida cop
    INNER JOIN op_partida_facturas f
        ON f.id_factura = cop.factura_id
    LEFT JOIN clientes cl
        ON cl.id_cliente = f.cliente_id
    LEFT JOIN contenedores_fisicos cf
        ON cf.id_fisico = cop.contenedor_fisico_id
    INNER JOIN tipos_movimiento tm
        ON tm.id_tipo_movimiento = cop.tipo_movimiento_id
    LEFT JOIN tipos_movimiento_categorias tmc
        ON tmc.id_categoria = tm.categoria_id
       AND tmc.estatus = 1
    LEFT JOIN (
        SELECT
            d.factura_id,
            e.contenedor_fisico_id,
            MAX(e.transportista_id) AS transportista_id,
            GROUP_CONCAT(
                DISTINCT COALESCE(t.nombre, 'Sin transportista')
                ORDER BY t.nombre SEPARATOR ', '
            ) AS transportistas,
            GROUP_CONCAT(
                DISTINCT COALESCE(e.estatus_envio, 'Sin estatus')
                ORDER BY e.estatus_envio SEPARATOR ', '
            ) AS estatuses
        FROM operaciones_partida_envio_detalle d
        INNER JOIN operaciones_partida_envios e
            ON e.id_envio = d.envio_id
           AND e.estatus = 1
        LEFT JOIN transportistas t
            ON t.id_transportista = e.transportista_id
        WHERE d.estatus = 1
        GROUP BY d.factura_id, e.contenedor_fisico_id
    ) env
        ON env.factura_id = cop.factura_id
       AND env.contenedor_fisico_id = cop.contenedor_fisico_id
    ";
    }
    // ================================================================
    //  LISTAR PAGINADO
    //  Ejecuta las dos fuentes por separado y combina en PHP.
    //  Elimina el patrón FROM (UNION ALL) base que algunos frameworks
    //  no soportan correctamente con selectAll().
    // ================================================================
    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, (int)$page);
        $perPage = (int)$perPage;
        if ($perPage <= 0) $perPage = 25;

        $isAll  = ($perPage >= 10000000);
        $offset = ($page - 1) * $perPage;

        // WHERE de cada fuente
        $wMar = $this->buildWhereMaritimo($filters);
        $wPar = $this->buildWherePartida($filters);

        // =========================
        // COUNT MARITIMO
        // =========================
        $sqlCountMar = "SELECT COUNT(*) AS total_rows
                FROM operaciones o
                LEFT JOIN clientes cl
                    ON cl.id_cliente = o.cliente_id
                LEFT JOIN estatus e
                    ON e.id_estatus = o.estatus_id
                LEFT JOIN transportistas tr
                    ON tr.id_transportista = o.transportista_id
                INNER JOIN costos_operacion co
                    ON co.operacion_id = o.id_operacion
                INNER JOIN tipos_movimiento tm
                    ON tm.id_tipo_movimiento = co.tipo_movimiento_id
                LEFT JOIN subtipos_operacion st
                    ON st.id_subtipo = o.subtipo_operacion_id
                LEFT JOIN (
                    SELECT
                        ob.operacion_id,
                        MAX(ob.broker_id) AS broker_id
                    FROM operacion_brokers ob
                    GROUP BY ob.operacion_id
                ) bro
                    ON bro.operacion_id = o.id_operacion
                {$wMar['sql']}";

        // =========================
        // COUNT PARTIDA
        // =========================
        $sqlCountPar = "SELECT COUNT(*) AS total_rows
                FROM costos_operacion_partida cop
                INNER JOIN op_partida_facturas f
                    ON f.id_factura = cop.factura_id
                LEFT JOIN clientes cl
                    ON cl.id_cliente = f.cliente_id
                LEFT JOIN contenedores_fisicos cf
                    ON cf.id_fisico = cop.contenedor_fisico_id
                INNER JOIN tipos_movimiento tm
                    ON tm.id_tipo_movimiento = cop.tipo_movimiento_id
                LEFT JOIN (
                    SELECT
                        d.factura_id,
                        e.contenedor_fisico_id,
                        MAX(e.transportista_id) AS transportista_id,
                        GROUP_CONCAT(
                            DISTINCT COALESCE(t.nombre, 'Sin transportista')
                            ORDER BY t.nombre SEPARATOR ', '
                        ) AS transportistas,
                        GROUP_CONCAT(
                            DISTINCT COALESCE(e.estatus_envio, 'Sin estatus')
                            ORDER BY e.estatus_envio SEPARATOR ', '
                        ) AS estatuses
                    FROM operaciones_partida_envio_detalle d
                    INNER JOIN operaciones_partida_envios e
                        ON e.id_envio = d.envio_id
                       AND e.estatus = 1
                    LEFT JOIN transportistas t
                        ON t.id_transportista = e.transportista_id
                    WHERE d.estatus = 1
                    GROUP BY d.factura_id
                ) env
                    ON env.factura_id = cop.factura_id
                   AND env.contenedor_fisico_id = cop.contenedor_fisico_id
                {$wPar['sql']}";

        $cntMar = $this->selectAll($sqlCountMar, $wMar['args']) ?: [];
        $cntPar = $this->selectAll($sqlCountPar, $wPar['args']) ?: [];

        $totalMar  = (int)($cntMar[0]['total_rows'] ?? 0);
        $totalPar  = (int)($cntPar[0]['total_rows'] ?? 0);
        $totalRows = $totalMar + $totalPar;

        if ($totalRows <= 0) {
            return [
                'rows'        => [],
                'total'       => 0,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => 1,
                'meta'        => [
                    'total_rows'      => 0,
                    'total_ops'       => 0,
                    'total_conceptos' => 0,
                    'pendientes'      => [],
                    'pagados'         => [],
                ],
            ];
        }

        // =========================
        // DATA
        // =========================
        $sqlDataMar = $this->sqlMaritimo() . $wMar['sql'];
        $sqlDataPar = $this->sqlPartida()  . $wPar['sql'];

        $rowsMar = $this->selectAll($sqlDataMar, $wMar['args']);
        if ($rowsMar === false) {
            throw new Exception('Falló sqlDataMar');
        }

        $rowsPar = $this->selectAll($sqlDataPar, $wPar['args']);
        if ($rowsPar === false) {
            throw new Exception('Falló sqlDataPar');
        }

        $all = array_merge($rowsMar ?: [], $rowsPar ?: []);

        // Orden base de renglones
        usort($all, function ($a, $b) {
            $fd = strcmp((string)($b['fecha_base'] ?? ''), (string)($a['fecha_base'] ?? ''));
            if ($fd !== 0) return $fd;

            $od = (int)($a['origen_orden'] ?? 0) - (int)($b['origen_orden'] ?? 0);
            if ($od !== 0) return $od;

            return (int)($b['costo_id'] ?? 0) - (int)($a['costo_id'] ?? 0);
        });

        // =========================
        // AGRUPAR POR OPERACION + CATEGORIA
        // =========================
        $grouped = $this->agruparCostosPorOperacionYCategoria($all);

        $totalGroups = count($grouped);

        // Paginación por operación padre
        $rows = $isAll ? $grouped : array_slice($grouped, $offset, $perPage);

        // =========================
        // META (sobre universo completo)
        // =========================
        $pend           = [];
        $pag            = [];
        $totalConceptos = 0;

        foreach ($all as $r) {
            $mon    = (string)($r['moneda'] ?? '');
            $monto  = (float)($r['monto'] ?? 0);
            $pagado = (int)($r['pagado'] ?? 0);

            $totalConceptos++;

            if (!isset($pend[$mon])) {
                $pend[$mon] = 0.0;
                $pag[$mon]  = 0.0;
            }

            if ($pagado === 1) {
                $pag[$mon] += $monto;
            } else {
                $pend[$mon] += $monto;
            }
        }

        return [
            'rows'        => $rows,
            'total'       => $totalGroups,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $isAll ? 1 : max(1, (int)ceil($totalGroups / $perPage)),
            'meta'        => [
                'total_rows'      => $totalGroups,
                'total_ops'       => $totalGroups,
                'total_conceptos' => $totalConceptos,
                'pendientes'      => $pend,
                'pagados'         => $pag,
            ],
        ];
    }
    public function listarCategorias(): array
    {
        $sql = "SELECT id_categoria, nombre
                FROM tipos_movimiento_categorias
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    // ================================================================
    //  DIAGNÓSTICO — llama a /Finanzas/diagnostico para ver qué pasa
    //  BORRAR después de confirmar que funciona.
    // ================================================================
    public function diagnostico(): array
    {
        $tests = [];

        // Test 1: ¿selectAll funciona con query simple?
        $r = $this->selectAll("SELECT COUNT(*) AS total_rows FROM costos_operacion WHERE estatus = 1");
        $tests['test1_costos_count']   = $r;
        $tests['test1_valor']          = $r[0]['total_rows'] ?? 'NO_KEY';

        // Test 2: ¿operaciones con tipo_operacion_id=11?
        $r2 = $this->selectAll("SELECT COUNT(*) AS total_rows FROM operaciones WHERE tipo_operacion_id = 11");
        $tests['test2_operaciones_11'] = $r2;
        $tests['test2_valor']          = $r2[0]['total_rows'] ?? 'NO_KEY';

        // Test 3: ¿JOIN básico funciona?
        $r3 = $this->selectAll("SELECT COUNT(*) AS total_rows
            FROM operaciones o
            INNER JOIN costos_operacion co ON co.operacion_id = o.id_operacion
            INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            LEFT JOIN subtipos_operacion st
    ON st.id_subtipo = o.subtipo_operacion_id

WHERE st.tipo_operacion_id = 11
              AND co.estatus = 1
              AND tm.estatus = 1
              AND LOWER(tm.tipo) = 'gasto'");
        $tests['test3_join_basico'] = $r3;
        $tests['test3_valor']       = $r3[0]['total_rows'] ?? 'NO_KEY';

        // Test 4: ¿selectAll con parámetro funciona?
        $r4 = $this->selectAll("SELECT COUNT(*) AS total_rows FROM operaciones WHERE tipo_operacion_id = ?", [11]);
        $tests['test4_con_param']  = $r4;
        $tests['test4_valor']      = $r4[0]['total_rows'] ?? 'NO_KEY';

        return $tests;
    }

    private function agruparCostosPorOperacionYCategoria(array $rows): array
    {
        $groups = [];

        foreach ($rows as $r) {
            $groupKey = (string)($r['origen_tipo'] ?? '') . '|' . (string)($r['origen_id'] ?? 0);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'group_key'              => $groupKey,
                    'origen_tipo'            => (string)($r['origen_tipo'] ?? ''),
                    'origen_orden'           => (int)($r['origen_orden'] ?? 0),
                    'origen_id'              => (int)($r['origen_id'] ?? 0),
                    'referencia'             => (string)($r['referencia'] ?? ''),
                    'cliente_id'             => (int)($r['id_cliente'] ?? 0),
                    'cliente'                => (string)($r['cliente'] ?? 'Sin cliente'),
                    'contenedor'             => (string)($r['contenedor'] ?? 'No aplica'),
                    'ferro_caja'             => (string)($r['ferro_caja'] ?? 'No aplica'),

                    'transportista_id'       => (int)($r['transportista_id'] ?? 0),
                    'transportista'          => (string)($r['transportista'] ?? 'No aplica'),

                    'transportista_ferro_id' => (int)($r['transportista_ferro_id'] ?? 0),
                    'transportista_ferro'    => (string)($r['transportista_ferro'] ?? 'No aplica'),

                    'broker_id'              => isset($r['broker_id']) ? (int)$r['broker_id'] : null,
                    'broker'                 => (string)($r['broker'] ?? 'No aplica'),
                    'estatus_operacion'      => (string)($r['estatus_operacion'] ?? 'Sin estatus'),
                    'cita_puerto'            => (string)($r['cita_puerto'] ?? 'No aplica'),
                    'isf'                    => (string)($r['isf'] ?? 'No'),
                    'fecha_base'             => (string)($r['fecha_base'] ?? ''),
                    'total'                  => 0.0,
                    'pendiente'              => 0.0,
                    'pagado_total'           => 0.0,
                    'categorias'             => [],
                ];
            }

            $catId   = (int)($r['categoria_id'] ?? 0);
            $catName = trim((string)($r['categoria'] ?? ''));
            if ($catName === '') {
                $catName = 'Sin categoría';
            }

            $catKey = (string)$catId . '|' . $catName;

            if (!isset($groups[$groupKey]['categorias'][$catKey])) {
                $groups[$groupKey]['categorias'][$catKey] = [
                    'categoria_id' => $catId,
                    'categoria'    => $catName,
                    'total'        => 0.0,
                    'pendiente'    => 0.0,
                    'pagado_total' => 0.0,
                    'conceptos'    => [],
                ];
            }

            $monto  = (float)($r['monto'] ?? 0);
            $pagado = (int)($r['pagado'] ?? 0);

            $concepto = [
                'registro_id'         => (string)($r['registro_id'] ?? ''),
                'costo_id'            => (int)($r['costo_id'] ?? 0),
                'tipo_movimiento_id'  => (int)($r['tipo_movimiento_id'] ?? 0),
                'concepto'            => (string)($r['concepto'] ?? ''),
                'moneda'              => (string)($r['moneda'] ?? ''),
                'monto'               => $monto,
                'pagado'              => $pagado,
                'comentario'          => (string)($r['comentario'] ?? ''),
                'fecha_base'          => (string)($r['fecha_base'] ?? ''),
            ];

            $groups[$groupKey]['categorias'][$catKey]['conceptos'][] = $concepto;
            $groups[$groupKey]['categorias'][$catKey]['total'] += $monto;
            $groups[$groupKey]['total'] += $monto;

            if ($pagado === 1) {
                $groups[$groupKey]['categorias'][$catKey]['pagado_total'] += $monto;
                $groups[$groupKey]['pagado_total'] += $monto;
            } else {
                $groups[$groupKey]['categorias'][$catKey]['pendiente'] += $monto;
                $groups[$groupKey]['pendiente'] += $monto;
            }
        }

        // Ordenar conceptos dentro de cada categoría
        foreach ($groups as &$g) {
            foreach ($g['categorias'] as &$cat) {
                usort($cat['conceptos'], function ($a, $b) {
                    return (int)($b['costo_id'] ?? 0) - (int)($a['costo_id'] ?? 0);
                });
            }
            unset($cat);

            // Convertir categorías asociativas a arreglo normal
            $g['categorias'] = array_values($g['categorias']);

            // Ordenar categorías por nombre
            usort($g['categorias'], function ($a, $b) {
                return strcmp(
                    mb_strtolower((string)($a['categoria'] ?? ''), 'UTF-8'),
                    mb_strtolower((string)($b['categoria'] ?? ''), 'UTF-8')
                );
            });
        }
        unset($g);

        // Convertir grupos asociativos a arreglo normal
        $groups = array_values($groups);

        // Orden final por fecha_base DESC, origen_orden ASC, origen_id DESC
        usort($groups, function ($a, $b) {
            $fd = strcmp((string)($b['fecha_base'] ?? ''), (string)($a['fecha_base'] ?? ''));
            if ($fd !== 0) return $fd;

            $od = (int)($a['origen_orden'] ?? 0) - (int)($b['origen_orden'] ?? 0);
            if ($od !== 0) return $od;

            return (int)($b['origen_id'] ?? 0) - (int)($a['origen_id'] ?? 0);
        });

        return $groups;
    }
}
