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
                st.clave AS tipo_clave,
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
}
