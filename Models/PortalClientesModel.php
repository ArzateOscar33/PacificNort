<?php

class PortalClientesModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lista operaciones marítimas/LBMF (tabla operaciones) filtradas por cliente.
     * Retorna: ['rows'=>[], 'total'=>int]
     */
    public function listarOperacionesCliente(array $filtros): array
    {
        $clienteId = (int)($filtros['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            return ['rows' => [], 'total' => 0];
        }

        $search   = trim((string)($filtros['search'] ?? ''));
        $tipoClave = trim((string)($filtros['tipo'] ?? ''));      // "MAR" | "LBMF" | ""
        $estatus  = (int)($filtros['estatus'] ?? 0);              // 0 = todos
        $etaIni   = trim((string)($filtros['eta_ini'] ?? ''));    // YYYY-MM-DD
        $etaFin   = trim((string)($filtros['eta_fin'] ?? ''));    // YYYY-MM-DD

        $page     = max(1, (int)($filtros['page'] ?? 1));
        $pageSize = (int)($filtros['page_size'] ?? 15);
        if (!in_array($pageSize, [15, 30, 50], true)) $pageSize = 15;

        $offset = ($page - 1) * $pageSize;

        $where = " WHERE o.cliente_id = :cliente_id ";
        $params = ['cliente_id' => $clienteId];

        // Tipo (subtipo clave)
        if ($tipoClave !== '') {
            $where .= " AND st.clave = :tipo_clave ";
            $params['tipo_clave'] = $tipoClave;
        }

        // Estatus
        if ($estatus > 0) {
            $where .= " AND o.estatus_id = :estatus_id ";
            $params['estatus_id'] = $estatus;
        }

        // Rango ETA
        if ($etaIni !== '') {
            $where .= " AND o.eta >= :eta_ini ";
            $params['eta_ini'] = $etaIni;
        }
        if ($etaFin !== '') {
            $where .= " AND o.eta <= :eta_fin ";
            $params['eta_fin'] = $etaFin;
        }

        // Search (operación, BL, contenedor)
        if ($search !== '') {
            $where .= " AND (
                o.numero_operacion LIKE :q
                OR o.numero_bl LIKE :q
                OR cm.numero_contenedor LIKE :q
            ) ";
            $params['q'] = '%' . $search . '%';
        }

        // Total (para paginación)
        $sqlTotal = "
            SELECT COUNT(DISTINCT o.id_operacion) AS total
            FROM operaciones o
            LEFT JOIN subtipos_operacion st
                   ON st.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN estatus e
                   ON e.id_estatus = o.estatus_id
            LEFT JOIN contenedores_maritimos_operacion cmo
                   ON cmo.operacion_id = o.id_operacion
            LEFT JOIN contenedores_maritimos cm
                   ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            $where
        ";
        $rowTotal = $this->select($sqlTotal, $params);
        $total = $rowTotal ? (int)$rowTotal['total'] : 0;

        // Rows
        $sql = "
            SELECT
                o.id_operacion,
                o.numero_operacion,
                o.numero_bl,
                o.etd,
                o.eta,
                o.estatus_id,
                e.nombre AS estatus,

                st.clave  AS tipo_clave,
                st.nombre AS tipo_nombre,

                GROUP_CONCAT(DISTINCT cm.numero_contenedor ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
            FROM operaciones o
            LEFT JOIN subtipos_operacion st
                ON st.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN estatus e
                ON e.id_estatus = o.estatus_id
            LEFT JOIN contenedores_maritimos_operacion cmo
                ON cmo.operacion_id = o.id_operacion
            LEFT JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            $where
            GROUP BY o.id_operacion
            ORDER BY o.id_operacion DESC
            LIMIT $pageSize OFFSET $offset
        ";

        $rows = $this->selectAll($sql, $params) ?: [];

        return ['rows' => $rows, 'total' => $total];
    }

    public function obtenerDetalleMaritima(int $clienteId, int $operacionId): ?array
    {
        if ($clienteId <= 0 || $operacionId <= 0) return null;

        $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            o.numero_bl,
            o.etd,
            o.eta,
            o.estatus_id,
            e.nombre AS estatus,

            st.clave  AS tipo_clave,
            st.nombre AS tipo_nombre,

            COALESCE(c.nombre,'') AS cliente,

            COALESCE(nv.nombre,'') AS naviera,
            COALESCE(pu.nombre,'') AS puerto,

            COALESCE(o.notas,'') AS comentario, 

            GROUP_CONCAT(DISTINCT cm.numero_contenedor ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
        FROM operaciones o
        LEFT JOIN clientes c
               ON c.id_cliente = o.cliente_id
        LEFT JOIN subtipos_operacion st
               ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN estatus e
               ON e.id_estatus = o.estatus_id
        LEFT JOIN navieras nv
               ON nv.id_naviera = o.naviera_id
        LEFT JOIN puertos pu
               ON pu.id_puerto = st.puerto_arribo_default_id
        LEFT JOIN contenedores_maritimos_operacion cmo
               ON cmo.operacion_id = o.id_operacion
        LEFT JOIN contenedores_maritimos cm
               ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE o.id_operacion = :op_id
          AND o.cliente_id = :cliente_id
        GROUP BY o.id_operacion
        LIMIT 1
    ";

        $row = $this->select($sql, [
            'op_id' => $operacionId,
            'cliente_id' => $clienteId
        ]);

        return $row ?: null;
    }


    /**
     * Eventos de operación (solo lectura) si pertenece al cliente.
     * Ajusta nombres de columnas si tu tabla varía.
     */
    public function listarEventosOperacion(int $clienteId, int $operacionId): array
    {
        if ($clienteId <= 0 || $operacionId <= 0) return [];

        // Seguridad: validamos pertenencia (evita filtrar eventos de otra cuenta)
        $check = $this->select(
            "SELECT 1 AS ok FROM operaciones WHERE id_operacion = :op_id AND cliente_id = :cliente_id LIMIT 1",
            ['op_id' => $operacionId, 'cliente_id' => $clienteId]
        );
        if (!$check) return [];

        $sql = "
            SELECT
                e.id_evento,
                e.fecha,
                te.nombre AS evento,
                COALESCE(e.comentario,'') AS comentario
            FROM eventos_logisticos e
            LEFT JOIN tipos_evento_logistico te
                   ON te.id_tipo_evento = e.tipo_evento_id
            WHERE e.operacion_id = :op_id
              AND e.estatus = 1
            ORDER BY e.fecha DESC, e.id_evento DESC
        ";

        return $this->selectAll($sql, ['op_id' => $operacionId]) ?: [];
    }

    /**
     * Wrapper: detalle + eventos (ideal para endpoint JSON del modal)
     */
    public function obtenerDetalleMaritimaConEventos(int $clienteId, int $operacionId): array
    {
        $detalle = $this->obtenerDetalleMaritima($clienteId, $operacionId);
        if (!$detalle) {
            return ['ok' => false, 'msg' => 'Operación no encontrada o sin acceso.'];
        }

        $eventos = $this->listarEventosOperacion($clienteId, $operacionId);

        return [
            'ok' => true,
            'detalle' => $detalle,
            'eventos' => $eventos,
        ];
    }
}
