<?php

class Operaciones_maritimo_ferro_costos_clientesModel extends Query
{
    public function __construct()
    {
        parent::__construct();
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




    /**
     * Listado paginado por OPERACIÓN (cada operación trae N conceptos).
     * Filtros:
     * - cliente_id (obligatorio)
     * - fecha_inicio / fecha_fin (DATE(o.eta))
     * - broker_id (EXISTS operacion_brokers)
     * - transportista_id (o.transportista_id)
     * - pagado (co.Pagado 0/1)
     * - term (operación / contenedor / concepto / comentario)
     */
    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, (int)$page);
        $perPage = (int)$perPage;
        if ($perPage <= 0) $perPage = 25;

        // "Todos" (tu convención)
        $isAll  = ($perPage >= 10000000);
        $offset = ($page - 1) * $perPage;

        // =========================
        // Cliente: 0 => "Todos" (NO filtra)
        // =========================
        $clienteId = isset($filters['cliente_id']) ? (int)$filters['cliente_id'] : 0;

        // =========================
        // WHERE + ARGS (base)
        // =========================
        // Respeta tu catálogo: tipo_operacion_id IN (11)
        $where = "WHERE st.tipo_operacion_id IN (11)
          AND co.estatus = 1
          AND tm.estatus = 1
          AND LOWER(tm.tipo) = 'gasto'";
        $args  = [];

        // Cliente opcional
        if ($clienteId > 0) {
            $where .= " AND o.cliente_id = ? ";
            $args[] = $clienteId;
        }

        // --- Fechas por ETA ---
        $fi = trim((string)($filters['fecha_inicio'] ?? ''));
        $ff = trim((string)($filters['fecha_fin'] ?? ''));

        $isDate = static function (string $d): bool {
            return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        };

        if ($fi !== '' && !$isDate($fi)) $fi = '';
        if ($ff !== '' && !$isDate($ff)) $ff = '';
        if ($fi !== '' && $ff !== '' && $fi > $ff) {
            [$fi, $ff] = [$ff, $fi];
        }

        if ($fi !== '' && $ff !== '') {
            $where .= " AND DATE(o.eta) BETWEEN ? AND ? ";
            array_push($args, $fi, $ff);
        } elseif ($fi !== '') {
            $where .= " AND DATE(o.eta) >= ? ";
            $args[] = $fi;
        } elseif ($ff !== '') {
            $where .= " AND DATE(o.eta) <= ? ";
            $args[] = $ff;
        }

        // --- Broker (por operación) ---
        $brokerId = isset($filters['broker_id']) ? (int)$filters['broker_id'] : 0;
        if ($brokerId > 0) {
            $where .= " AND EXISTS (
            SELECT 1
            FROM operacion_brokers obx
            WHERE obx.operacion_id = o.id_operacion
              AND obx.broker_id = ?
        ) ";
            $args[] = $brokerId;
        }

        // --- Transportista (de la operación) ---
        $transportistaId = isset($filters['transportista_id']) ? (int)$filters['transportista_id'] : 0;
        if ($transportistaId > 0) {
            $where .= " AND o.transportista_id = ? ";
            $args[] = $transportistaId;
        }

        // --- Pagado (por renglón) ---
        $pagado = $filters['pagado'] ?? '';
        if ($pagado !== '' && ($pagado === '0' || $pagado === '1' || $pagado === 0 || $pagado === 1)) {
            $where .= " AND co.Pagado = ? ";
            $args[] = (int)$pagado;
        }

        // --- Term (operación / contenedor / concepto / comentario) ---
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
                LOWER(o.numero_operacion) LIKE ?
                OR LOWER(tm.nombre) LIKE ?
                OR LOWER(COALESCE(co.comentario,'')) LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM contenedores_maritimos_operacion cmo2
                    JOIN contenedores_maritimos cm2
                      ON cm2.id_contenedor_maritimo = cmo2.contenedor_maritimo_id
                    WHERE cmo2.operacion_id = o.id_operacion
                      AND LOWER(cm2.numero_contenedor) LIKE ?
                )
            ) ";
                array_push($args, $needle, $needle, $needle, $needle);
            }
        }

        // =========================
        // COUNT Ops (para paginación)
        // =========================
        $sqlCount = "
        SELECT COUNT(DISTINCT o.id_operacion) AS total_ops
        FROM operaciones o
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        INNER JOIN costos_operacion co ON co.operacion_id = o.id_operacion
        INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
        $where
    ";
        $rowCount = $this->select($sqlCount, $args) ?: ['total_ops' => 0];
        $totalOps = (int)$rowCount['total_ops'];

        if ($totalOps <= 0) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 1,
                'meta' => [
                    'total_ops' => 0,
                    'total_conceptos' => 0,
                    'pendientes' => [],
                    'pagados' => [],
                ],
            ];
        }

        // =========================
        // op_ids paginados
        // =========================
        $limit = (int)$perPage;
        $off   = (int)$offset;

        $sqlOps = "
        SELECT DISTINCT o.id_operacion
        FROM operaciones o
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        INNER JOIN costos_operacion co ON co.operacion_id = o.id_operacion
        INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
        $where
        ORDER BY o.id_operacion DESC
    ";
        if (!$isAll) {
            $sqlOps .= " LIMIT $limit OFFSET $off ";
        }

        $opRows = $this->selectAll($sqlOps, $args) ?: [];
        $opIds  = array_map(fn($r) => (int)$r['id_operacion'], $opRows);

        if (empty($opIds)) {
            return [
                'rows' => [],
                'total' => $totalOps,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $isAll ? 1 : max(1, (int)ceil($totalOps / $perPage)),
                'meta' => [
                    'total_ops' => $totalOps,
                    'total_conceptos' => 0,
                    'pendientes' => [],
                    'pagados' => [],
                ],
            ];
        }

        // =========================
        // DATA (conceptos de las ops de la página)
        // =========================
        $in = implode(',', array_fill(0, count($opIds), '?'));
        $argsData = $opIds;

        $sqlData = "
        SELECT
            o.id_operacion,
            o.numero_operacion,

            /* 👇 útil cuando clienteId=0 (Todos) */
            cl.id_cliente,
            cl.nombre AS cliente,

            e.nombre AS estatus,
            o.cita_puerto,
            o.isf,
            o.eta,

            tr.nombre AS transportista,

            cont.contenedores,
            bro.brokers,

            co.id_costo_operacion,
            tm.id_tipo_movimiento,
            tm.nombre AS concepto,
            tm.moneda,
            co.monto,
            co.Pagado,
            co.comentario

        FROM operaciones o
        LEFT JOIN clientes cl       ON cl.id_cliente = o.cliente_id
        LEFT JOIN estatus e         ON e.id_estatus = o.estatus_id
        LEFT JOIN transportistas tr ON tr.id_transportista = o.transportista_id

        /* contenedores concatenados */
        LEFT JOIN (
            SELECT
                cmo.operacion_id,
                GROUP_CONCAT(DISTINCT cm.numero_contenedor
                    ORDER BY cm.numero_contenedor SEPARATOR ', '
                ) AS contenedores
            FROM contenedores_maritimos_operacion cmo
            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            GROUP BY cmo.operacion_id
        ) cont ON cont.operacion_id = o.id_operacion

        /* brokers concatenados */
        LEFT JOIN (
            SELECT
                ob.operacion_id,
                GROUP_CONCAT(DISTINCT b.nombre
                    ORDER BY b.nombre SEPARATOR ', '
                ) AS brokers
            FROM operacion_brokers ob
            INNER JOIN brokers b ON b.id_broker = ob.broker_id
            GROUP BY ob.operacion_id
        ) bro ON bro.operacion_id = o.id_operacion

        INNER JOIN costos_operacion co
            ON co.operacion_id = o.id_operacion
           AND co.estatus = 1
INNER JOIN tipos_movimiento tm
    ON tm.id_tipo_movimiento = co.tipo_movimiento_id
   AND tm.estatus = 1
   AND LOWER(tm.tipo) = 'gasto'

        WHERE o.id_operacion IN ($in)
         
        ORDER BY o.id_operacion DESC, co.id_costo_operacion DESC
    ";

        $rows = $this->selectAll($sqlData, $argsData) ?: [];

        // =========================
        // META (sobre el universo filtrado, NO solo página)
        // =========================
        $sqlMeta = "
        SELECT
            COUNT(co.id_costo_operacion) AS total_conceptos,
            tm.moneda,
            SUM(CASE WHEN co.Pagado = 0 THEN COALESCE(co.monto,0) ELSE 0 END) AS pendientes,
            SUM(CASE WHEN co.Pagado = 1 THEN COALESCE(co.monto,0) ELSE 0 END) AS pagados
        FROM operaciones o
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        INNER JOIN costos_operacion co ON co.operacion_id = o.id_operacion
        INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
        $where
        GROUP BY tm.moneda
    ";
        $metaRows = $this->selectAll($sqlMeta, $args) ?: [];

        $pend = [];
        $pag  = [];
        $totalConceptos = 0;

        foreach ($metaRows as $mr) {
            $mon = (string)($mr['moneda'] ?? '');
            $totalConceptos += (int)($mr['total_conceptos'] ?? 0);
            $pend[$mon] = (float)($mr['pendientes'] ?? 0);
            $pag[$mon]  = (float)($mr['pagados'] ?? 0);
        }

        return [
            'rows'        => $rows,
            'total'       => $totalOps,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $isAll ? 1 : max(1, (int)ceil($totalOps / $perPage)),
            'meta' => [
                'total_ops' => $totalOps,
                'total_conceptos' => $totalConceptos,
                'pendientes' => $pend,
                'pagados' => $pag,
            ],
        ];
    }
}
