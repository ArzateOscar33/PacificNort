<?php

class Operaciones_maritimo_ferro_costos_combinadosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Autocomplete: sugerencias de contenedores marítimos
     */
    public function sugerenciasContenedores(string $term = '', int $limit = 10): array
    {
        $term  = trim($term);
        $limit = max(1, min(50, (int)$limit));
        if ($term === '') return [];

        $sql = "SELECT
                    cm.id_contenedor_maritimo AS id,
                    cm.numero_contenedor      AS text
                FROM contenedores_maritimos cm
                WHERE cm.estatus = 1
                  AND cm.numero_contenedor LIKE ?
                ORDER BY cm.numero_contenedor ASC
                LIMIT {$limit}";

        return $this->selectAll($sql, ['%' . $term . '%']);
    }

    /**
     * Lista costos combinados por contenedor (Marítimo + FO)
     * - Solo gastos (tm.tipo='gasto')
     * - Fechas por operaciones.cita_puerto
     * - Incluye cliente + contenedor
     */
    public function listarCostosCombinadosPaginado(array $filters = [], int $page = 1, int $per_page = 10): array
    {
        $contenedor   = isset($filters['contenedor']) ? trim($filters['contenedor']) : '';
        $term         = isset($filters['term']) ? trim($filters['term']) : '';
        $fecha_ini    = isset($filters['fecha_ini']) ? trim($filters['fecha_ini']) : '';
        $fecha_fin    = isset($filters['fecha_fin']) ? trim($filters['fecha_fin']) : '';
        $moneda_vista = isset($filters['moneda_vista']) ? strtoupper(trim($filters['moneda_vista'])) : 'MXN';
        $tc           = isset($filters['tc']) ? (float)$filters['tc'] : 17.00;

        $page     = max(1, (int)$page);
        $per_page = max(1, min(100, (int)$per_page));
        $offset   = ($page - 1) * $per_page;

        if ($contenedor === '') {
            return [
                'data' => [],
                'meta' => ['total'=>0,'page'=>$page,'per_page'=>$per_page,'total_pages'=>0],
                'totals' => ['total_pesos'=>0,'total_dlls'=>0,'total_mxn'=>0,'total_usd'=>0]
            ];
        }

        if ($tc <= 0) $tc = 1;
        if ($moneda_vista !== 'USD') $moneda_vista = 'MXN';

        // ---------------- Filtros sobre dataset combinado ----------------
        $whereExtra  = "";
        $paramsExtra = [];

        // term: concepto / comentario / cliente (ya que lo agregaste a tabla)
        if ($term !== '') {
            $whereExtra .= " AND (
                LOWER(t.concepto) LIKE ?
                OR LOWER(IFNULL(t.comentario,'')) LIKE ?
                OR LOWER(IFNULL(t.cliente,'')) LIKE ?
            )";
            $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
            $paramsExtra[] = $like;
            $paramsExtra[] = $like;
            $paramsExtra[] = $like;
        }

        // fechas: por cita_puerto (ya viene en t.cita_puerto)
        if ($fecha_ini !== '' && $fecha_fin !== '') {
            $whereExtra .= " AND (DATE(t.cita_puerto) BETWEEN ? AND ?)";
            $paramsExtra[] = $fecha_ini;
            $paramsExtra[] = $fecha_fin;
        } elseif ($fecha_ini !== '') {
            $whereExtra .= " AND (DATE(t.cita_puerto) >= ?)";
            $paramsExtra[] = $fecha_ini;
        } elseif ($fecha_fin !== '') {
            $whereExtra .= " AND (DATE(t.cita_puerto) <= ?)";
            $paramsExtra[] = $fecha_fin;
        }

        /**
         * ======================= CLIENTE JOIN =======================
         * Si tus tablas difieren, AJUSTA AQUÍ:
         * - operaciones.cliente_id
         * - clientes.id_cliente
         * - clientes.nombre (o razon_social)
         *
         * Por defecto asumo: clientes(id_cliente, nombre)
         */
        $clienteSelectMar = "IFNULL(cli.nombre,'') AS cliente";
        $clienteJoinMar   = "LEFT JOIN clientes cli ON cli.id_cliente = o.cliente_id";

        // Para FO: intentamos cliente directo en operaciones_ferroviarias (si existe),
        // y si no, lo derivamos desde la(s) operación(es) marítimas ligadas.
        $clienteSelectFo = "
            COALESCE(
                IFNULL(clif.nombre, NULL),
                (
                    SELECT MAX(cli2.nombre)
                    FROM contenedor_maritimo_ferro cmf5
                    JOIN contenedores_maritimos_operacion cmo5 ON cmo5.id = cmf5.cont_maritimo_operacion_id
                    JOIN operaciones o5 ON o5.id_operacion = cmo5.operacion_id
                    LEFT JOIN clientes cli2 ON cli2.id_cliente = o5.cliente_id
                    WHERE cmf5.operacion_ferro_id = ofe.id_operacion_ferro
                      AND cmf5.contenedor_maritimo_id = cm.id_contenedor_maritimo
                      AND cmf5.estatus = 1
                ),
                ''
            ) AS cliente
        ";
        $clienteJoinFo = "LEFT JOIN clientes clif ON clif.id_cliente = ofe.cliente_id";

        // ---------------- UNION (MAR + FO) ----------------
        $unionSql = "
            SELECT
                'MARITIMO' AS origen,
                {$clienteSelectMar},
                cm.numero_contenedor AS contenedor,
                o.cita_puerto AS cita_puerto,

                o.numero_operacion AS operacion_maritima,

                (
                    SELECT GROUP_CONCAT(DISTINCT ofo.numero_operacion ORDER BY ofo.numero_operacion SEPARATOR ', ')
                    FROM contenedores_maritimos_operacion cmo2
                    JOIN contenedor_maritimo_ferro cmf2
                      ON cmf2.cont_maritimo_operacion_id = cmo2.id
                     AND cmf2.estatus = 1
                    JOIN operaciones_ferroviarias ofo
                      ON ofo.id_operacion_ferro = cmf2.operacion_ferro_id
                    WHERE cmo2.operacion_id = o.id_operacion
                      AND cmo2.contenedor_maritimo_id = cm.id_contenedor_maritimo
                ) AS operacion_terrestre,

                tm.nombre AS concepto,
                co.monto  AS monto,
                tm.moneda AS moneda_origen,
                co.comentario AS comentario,
                co.fecha_creacion AS fecha_creacion

            FROM costos_operacion co
            JOIN operaciones o
              ON o.id_operacion = co.operacion_id
            {$clienteJoinMar}
            JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            JOIN contenedores_maritimos_operacion cmo
              ON cmo.operacion_id = o.id_operacion
            JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id

            WHERE cm.numero_contenedor = ?
              AND co.estatus = 1
              AND tm.tipo = 'gasto'

            UNION ALL

            SELECT
                'FO' AS origen,
                {$clienteSelectFo},
                cm.numero_contenedor AS contenedor,

                (
                    SELECT MAX(o3.cita_puerto)
                    FROM contenedor_maritimo_ferro cmf4
                    JOIN contenedores_maritimos_operacion cmo4 ON cmo4.id = cmf4.cont_maritimo_operacion_id
                    JOIN operaciones o3 ON o3.id_operacion = cmo4.operacion_id
                    WHERE cmf4.operacion_ferro_id = ofe.id_operacion_ferro
                      AND cmf4.contenedor_maritimo_id = cm.id_contenedor_maritimo
                      AND cmf4.estatus = 1
                ) AS cita_puerto,

                (
                    SELECT GROUP_CONCAT(DISTINCT o2.numero_operacion ORDER BY o2.numero_operacion SEPARATOR ', ')
                    FROM contenedor_maritimo_ferro cmf3
                    JOIN contenedores_maritimos_operacion cmo3 ON cmo3.id = cmf3.cont_maritimo_operacion_id
                    JOIN operaciones o2 ON o2.id_operacion = cmo3.operacion_id
                    WHERE cmf3.operacion_ferro_id = ofe.id_operacion_ferro
                      AND cmf3.contenedor_maritimo_id = cm.id_contenedor_maritimo
                      AND cmf3.estatus = 1
                ) AS operacion_maritima,

                ofe.numero_operacion AS operacion_terrestre,

                tm.nombre AS concepto,
                cof.monto AS monto,
                tm.moneda AS moneda_origen,
                cof.comentario AS comentario,
                cof.fecha_creacion AS fecha_creacion

            FROM costos_operacion_ferro cof
            JOIN operaciones_ferroviarias ofe
              ON ofe.id_operacion_ferro = cof.operacion_ferro_id
            {$clienteJoinFo}
            JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = cof.tipo_movimiento_id
            JOIN contenedor_maritimo_ferro cmf
              ON cmf.operacion_ferro_id = ofe.id_operacion_ferro
             AND cmf.estatus = 1
            JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id

            WHERE cm.numero_contenedor = ?
              AND cof.estatus = 1
              AND tm.tipo = 'gasto'
        ";

        // ---------------- DATA ----------------
        $sqlData = "
            SELECT
                t.origen,
                t.cliente,
                t.contenedor,
                t.cita_puerto,
                t.operacion_maritima,
                t.operacion_terrestre,
                t.concepto,
                t.monto,
                t.moneda_origen,
                t.comentario,
                t.fecha_creacion,

                CASE WHEN t.moneda_origen = 'PESOS' THEN t.monto ELSE (t.monto * ?) END AS monto_mxn,
                CASE WHEN t.moneda_origen = 'DLLS'  THEN t.monto ELSE (t.monto / ?) END AS monto_usd,

                CASE
                  WHEN ? = 'MXN' THEN
                    (CASE WHEN t.moneda_origen = 'PESOS' THEN t.monto ELSE (t.monto * ?) END)
                  ELSE
                    (CASE WHEN t.moneda_origen = 'DLLS' THEN t.monto ELSE (t.monto / ?) END)
                END AS monto_vista

            FROM ({$unionSql}) t
            WHERE 1=1
            {$whereExtra}
            ORDER BY t.cita_puerto DESC, t.fecha_creacion DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";

        // ---------------- COUNT ----------------
        $sqlCount = "
            SELECT COUNT(*) AS total
            FROM ({$unionSql}) t
            WHERE 1=1
            {$whereExtra}
        ";

        // ---------------- TOTALS ----------------
        $sqlTotals = "
            SELECT
                SUM(CASE WHEN t.moneda_origen='PESOS' THEN t.monto ELSE 0 END) AS total_pesos,
                SUM(CASE WHEN t.moneda_origen='DLLS'  THEN t.monto ELSE 0 END) AS total_dlls,
                SUM(CASE WHEN t.moneda_origen='PESOS' THEN t.monto ELSE (t.monto * ?) END) AS total_mxn,
                SUM(CASE WHEN t.moneda_origen='DLLS'  THEN t.monto ELSE (t.monto / ?) END) AS total_usd
            FROM ({$unionSql}) t
            WHERE 1=1
            {$whereExtra}
        ";

        // union pide contenedor 2 veces
        $paramsUnion = [$contenedor, $contenedor];

        $paramsData = array_merge(
            [$tc, $tc, $moneda_vista, $tc, $tc],
            $paramsUnion,
            $paramsExtra
        );

        $paramsCount = array_merge($paramsUnion, $paramsExtra);

        $paramsTotals = array_merge([$tc, $tc], $paramsUnion, $paramsExtra);

        $rowCount = $this->select($sqlCount, $paramsCount);
        $total = $rowCount ? (int)$rowCount['total'] : 0;
        $total_pages = (int)ceil($total / $per_page);

        $data = $this->selectAll($sqlData, $paramsData);

        $tot = $this->select($sqlTotals, $paramsTotals);
        $totals = [
            'total_pesos' => $tot && $tot['total_pesos'] !== null ? (float)$tot['total_pesos'] : 0,
            'total_dlls'  => $tot && $tot['total_dlls']  !== null ? (float)$tot['total_dlls']  : 0,
            'total_mxn'   => $tot && $tot['total_mxn']   !== null ? (float)$tot['total_mxn']   : 0,
            'total_usd'   => $tot && $tot['total_usd']   !== null ? (float)$tot['total_usd']   : 0,
        ];

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages
            ],
            'totals' => $totals
        ];
    }
}
