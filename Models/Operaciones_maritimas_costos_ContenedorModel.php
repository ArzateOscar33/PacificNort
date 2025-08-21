<?php
class Operaciones_maritimas_costos_ContenedorModel extends Query
{
  public function listarCostosPaginado(int $page, int $perPage, array $f = []): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        $buscar = trim($f['buscar'] ?? '');
        $moneda = trim($f['moneda'] ?? '');             // 'PESOS' | 'DLLS' | ''
        $tipoId = (int)($f['tipo_movimiento_id'] ?? 0); // id_tipo_movimiento

        $where  = [];
        $params = [];

        if ($buscar !== '') {
            $where[] = "(cte.nombre LIKE ? OR o.numero_operacion LIKE ? OR cf.numero_ferro LIKE ? OR tm.nombre LIKE ?)";
            $like = "%{$buscar}%";
            array_push($params, $like, $like, $like, $like);
        }
        if ($moneda !== '') {
            $where[] = "tm.moneda = ?";
            $params[] = $moneda;
        }
        if ($tipoId > 0) {
            $where[] = "cco.tipo_movimiento_id = ?";
            $params[] = $tipoId;
        }

        $sql = "SELECT
        cco.id_costo_contenedor,
        o.id_operacion,
        o.numero_operacion,
        cf.numero_ferro           AS contenedor,
        tm.id_tipo_movimiento,
        tm.nombre                 AS concepto,
        tm.moneda,
        cco.monto,
        cco.comentario,
        cco.fecha_creacion
            FROM costos_contenedor_operacion cco
            LEFT JOIN contenedores_operacion  co  ON co.id_contenedor   = cco.contenedor_operacion_id
            LEFT JOIN operaciones             o   ON o.id_operacion     = co.operacion_id
            LEFT JOIN contenedores_fisicos    cf  ON cf.id_fisico       = co.id_fisico
            LEFT JOIN tipos_movimiento        tm  ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
            LEFT JOIN clientes                cte ON cte.id_cliente      = co.cliente_id";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            // IMPORTANTE: interpolar LIMIT/OFFSET como enteros validados
            $sql .= " ORDER BY cco.fecha_creacion DESC, cco.id_costo_contenedor DESC
                    LIMIT {$perPage} OFFSET {$offset}";

            try {
                $rows = $this->selectAll($sql, $params);
                return is_array($rows) ? $rows : [];
            } catch (\Throwable $e) {
                // Opcional: error_log("listarCostosPaginado: " . $e->getMessage());
                return [];
            }
    }

    public function contarCostos(array $f = []): int
    {
        $buscar = trim($f['buscar'] ?? '');
        $moneda = trim($f['moneda'] ?? '');
        $tipoId = (int)($f['tipo_movimiento_id'] ?? 0);

        $where  = [];
        $params = [];

        if ($buscar !== '') {
            $where[] = "(cte.nombre LIKE ? OR o.numero_operacion LIKE ? OR cf.numero_ferro LIKE ? OR tm.nombre LIKE ?)";
            $like = "%{$buscar}%";
            array_push($params, $like, $like, $like, $like);
        }
        if ($moneda !== '') {
            $where[] = "tm.moneda = ?";
            $params[] = $moneda;
        }
        if ($tipoId > 0) {
            $where[] = "cco.tipo_movimiento_id = ?";
            $params[] = $tipoId;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM costos_contenedor_operacion cco
                LEFT JOIN contenedores_operacion  co  ON co.id_contenedor   = cco.contenedor_operacion_id
                LEFT JOIN operaciones             o   ON o.id_operacion     = co.operacion_id
                LEFT JOIN contenedores_fisicos    cf  ON cf.id_fisico       = co.id_fisico
                LEFT JOIN tipos_movimiento        tm  ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
                LEFT JOIN clientes                cte ON cte.id_cliente      = co.cliente_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        try {
            $row = $this->select($sql, $params);
            return (int)($row['total'] ?? 0);
        } catch (\Throwable $e) {
            // Opcional: error_log("contarCostos: " . $e->getMessage());
            return 0;
        }
    }

        /**
     * Catálogo de tipos de costo (tipos_movimiento) activos.
     * Devuelve: id_tipo_movimiento, nombre, moneda, estatus
     */
    public function catalogoTiposMovimiento(): array
    {
        $sql = "SELECT id_tipo_movimiento, nombre, moneda, estatus
                FROM tipos_movimiento
                WHERE estatus = 1
                AND UPPER(tipo) = 'GASTO'
                ORDER BY nombre ASC";
        try {
            $rows = $this->selectAll($sql);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Busca operaciones por término (número de operación o cliente).
     * @param string $term
     * @param int $limit
     * Devuelve: id_operacion, numero_operacion, cliente_id, cliente
     */
    public function buscarOperaciones(string $term, int $limit = 8): array
    {
        $term = trim($term);
        $params = [];
        $where  = "";

        if ($term !== "") {
            $where = " AND (o.numero_operacion LIKE ? OR cte.nombre LIKE ?)";
            $like  = "%{$term}%";
            $params[] = $like;
            $params[] = $like;
        }

        // Solo operaciones que tengan al menos un contenedor_físico ligado vía contenedores_operacion
        $limit = max(1, min(20, $limit));
        $sql = "
            SELECT DISTINCT
                o.id_operacion,
                o.numero_operacion,
                cte.id_cliente  AS cliente_id,
                cte.nombre      AS cliente
            FROM operaciones o
            LEFT JOIN clientes cte ON cte.id_cliente = o.cliente_id
            WHERE EXISTS (
                SELECT 1
                FROM contenedores_operacion co
                INNER JOIN contenedores_fisicos cf
                        ON cf.id_fisico = co.id_fisico
                WHERE co.operacion_id = o.id_operacion
            )
            {$where}
            ORDER BY o.numero_operacion DESC, o.id_operacion DESC
            LIMIT {$limit}
        ";

        try {
            $rows = $this->selectAll($sql, $params);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            return [];
        }
    }


    /**
     * Busca contenedores físicos dentro de una operación (por número parcial).
     * Importante: devuelve el id del contenedor EN OPERACIÓN (co.id_contenedor),
     * que es el que necesitas para guardar en costos_contenedor_operacion.contenedor_operacion_id
     *
     * @param int $operacionId  (obligatorio)
     * @param string $term      (parcial de numero_ferro)
     * @param int $limit
     * Devuelve: contenedor_operacion_id, numero_ferro, id_fisico
     */
    public function buscarContenedoresPorOperacion(int $operacionId, string $term = "", int $limit = 10): array
    {
        if ($operacionId <= 0) return [];

        $term = trim($term);
        $where = "WHERE co.operacion_id = ?";
        $params = [$operacionId];

        if ($term !== "") {
            $where .= " AND cf.numero_ferro LIKE ?";
            $params[] = "%{$term}%";
        }

        $limit = max(1, min(30, $limit));

        $sql = "SELECT
                    co.id_contenedor           AS contenedor_operacion_id,
                    cf.id_fisico,
                    cf.numero_ferro
                FROM contenedores_operacion co
                LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
                {$where}
                ORDER BY cf.numero_ferro ASC
                LIMIT {$limit}";
        try {
            $rows = $this->selectAll($sql, $params);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            return [];
        }
    }


}
