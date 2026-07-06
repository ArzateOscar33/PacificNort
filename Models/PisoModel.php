<?php

class PisoModel extends Query
{
    public function listarEnPisoPaginado(array $filters = [], int $page = 1, int $per_page = 10): array
    {
        $argsBuscar = [];

        // ---- Búsqueda libre (cliente / contenedor / operación) ----
        $term   = isset($filters['term']) ? trim(mb_strtolower($filters['term'], 'UTF-8')) : '';
        $buscar = ($term !== '');
        $needle = $buscar ? "%{$term}%" : null;

        $whereBusq = $buscar
            ? " AND (
                    LOWER(COALESCE(cli.nombre,''))            LIKE ?
                 OR LOWER(COALESCE(cm.numero_contenedor,''))  LIKE ?
                 OR LOWER(COALESCE(o.numero_operacion,''))    LIKE ?
               )"
            : "";

        if ($buscar) {
            array_push($argsBuscar, $needle, $needle, $needle);
        }

        // ---- Filtro por bodega (estatus.nombre) ----
        $bodega = isset($filters['bodega']) ? trim($filters['bodega']) : '';
        $argsBodega = [];
        $whereBodega = "";

        if ($bodega !== '' && in_array($bodega, ['BODEGA MX', 'BODEGA USA'], true)) {
            $whereBodega = " AND es.nombre = ? ";
            $argsBodega[] = $bodega;
        }

        // IMPORTANTE: por ahora NO usamos fechas hasta confirmar el nombre real del campo
        $allArgs = array_merge($argsBuscar, $argsBodega);

        // Subquery base (idéntica a tu SQL validado)
        $sub = "
            SELECT
                cmo.id AS cmo_id,
                o.id_operacion,
                o.numero_operacion,
                COALESCE(cli.nombre,'') AS cliente,
                cm.numero_contenedor,
                COALESCE(es.nombre,'') AS bodega,
                COALESCE(cmo.bultos,0) AS bultos_totales,
                COALESCE(SUM(COALESCE(cmf.bultos_asignados,0)),0) AS bultos_enviados,
                GREATEST(
                    COALESCE(cmo.bultos,0) - COALESCE(SUM(COALESCE(cmf.bultos_asignados,0)),0),
                    0
                ) AS bultos_restantes
            FROM contenedores_maritimos_operacion cmo
            INNER JOIN operaciones o
                ON o.id_operacion = cmo.operacion_id
            LEFT JOIN clientes cli
                ON cli.id_cliente = o.cliente_id
            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            LEFT JOIN estatus es
                ON es.id_estatus = o.estatus_id
            LEFT JOIN contenedor_maritimo_ferro cmf
                ON cmf.cont_maritimo_operacion_id = cmo.id
            WHERE es.nombre IN ('BODEGA MX','BODEGA USA')
            {$whereBusq}
            {$whereBodega}
            GROUP BY
                cmo.id, o.id_operacion, o.numero_operacion,
                cli.nombre, cm.numero_contenedor, es.nombre, cmo.bultos
        HAVING GREATEST(
        COALESCE(cmo.bultos,0) - COALESCE(SUM(COALESCE(cmf.bultos_asignados,0)),0),
        0
    ) > 0
        ";

        // TOTAL
        $rowCount = $this->select("SELECT COUNT(*) AS total FROM ({$sub}) t", $allArgs);
        $total = (int)($rowCount['total'] ?? 0);

        // Paginación
        $per_page    = max(1, min($per_page, 200));
        $total_pages = max(1, (int)ceil($total / $per_page));
        $page        = max(1, min($page, $total_pages));
        $offset      = ($page - 1) * $per_page;

        // DATA (orden igual a tu SQL probado)
        $sqlData = "{$sub}
            ORDER BY id_operacion DESC, numero_contenedor ASC
            LIMIT {$per_page} OFFSET {$offset}";
        $data = $this->selectAll($sqlData, $allArgs) ?: [];

        // Badges sobre conjunto filtrado
        $bad = $this->select("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN bodega = 'BODEGA MX' THEN 1 ELSE 0 END) AS total_tj,
                SUM(CASE WHEN bodega = 'BODEGA USA' THEN 1 ELSE 0 END) AS total_sd
            FROM ({$sub}) x
        ", $allArgs) ?: [];

        return [
            'data' => $data,
            'meta' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total_pages,
            ],
            'badges' => [
                'total' => (int)($bad['total'] ?? 0),
                'tj'    => (int)($bad['total_tj'] ?? 0),
                'sd'    => (int)($bad['total_sd'] ?? 0),
            ],
        ];
    }
}
