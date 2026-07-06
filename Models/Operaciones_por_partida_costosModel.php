<?php

class Operaciones_por_partida_costosModel extends Query
{
    /**
     * IMPORTANTE:
     * - Los costos viven en: costos_operacion_partida
     * - FK principal: factura_id
     * - La relación factura -> ferro para envíos de partida NO está en
     *   operaciones_partida_envios directamente por factura_id.
     * - La relación correcta es:
     *      op_partida_facturas
     *        -> operaciones_partida_envio_detalle (factura_id)
     *        -> operaciones_partida_envios (envio_id)
     *        -> contenedores_fisicos (contenedor_fisico_id)
     */

    public function __construct()
    {
        parent::__construct();
    }

    private function getFacturaId(array $f): int
    {
        return (int)($f['factura_id'] ?? 0);
    }

    private function getContenedorFisicoId(array $f): int
    {
        return (int)($f['contenedor_fisico_id'] ?? 0);
    }

    private function getTiposOperacionMovimientoIds(): array
    {
        // Compatibilidad con tus catálogos actuales
        return [1, 11];
    }

    private function buildInPlaceholders(array $items): string
    {
        return implode(', ', array_fill(0, count($items), '?'));
    }

    public function buscarOperacionesCombinadasPorTerm(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];

        $sql = "SELECT
                    f.id_factura                          AS id,
                    f.numero_factura                      AS numero_operacion,
                    COALESCE(c.nombre, 'Sin cliente')    AS cliente,
                    COALESCE(f.proveedor, '')            AS proveedor,
                    COALESCE(b.nombre, '')               AS bodega,
                    'PARTIDA'                            AS fuente
                FROM op_partida_facturas f
                LEFT JOIN clientes c ON c.id_cliente = f.cliente_id
                LEFT JOIN bodegas b  ON b.id_bodega = f.bodega_id
                WHERE f.estatus = 1
                  AND (
                        f.numero_factura LIKE ?
                        OR c.nombre LIKE ?
                        OR f.proveedor LIKE ?
                        OR b.nombre LIKE ?
                      )
                ORDER BY f.id_factura DESC
                LIMIT 20";

        $like = "%{$term}%";

        try {
            return $this->selectAll($sql, [$like, $like, $like, $like]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function obtenerTiposMovimientoActivosPorFuente(string $fuente = 'PARTIDA'): array
    {
        return $this->obtenerTiposMovimientoActivos();
    }

    public function obtenerTiposMovimientoActivos(): array
    {
        $tipoOperacionIds = $this->getTiposOperacionMovimientoIds();
        $placeholders = $this->buildInPlaceholders($tipoOperacionIds);

        $sql = "SELECT
                    id_tipo_movimiento,
                    nombre,
                    LOWER(tipo) AS tipo,
                    UPPER(moneda) AS moneda
                FROM tipos_movimiento
                WHERE estatus = 1
                  AND tipo_operacion_id IN ($placeholders)
                ORDER BY nombre ASC";

        try {
            return $this->selectAll($sql, $tipoOperacionIds) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function contarCostosCombinados(array $f = []): int
    {
        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) return 0;

        $contenedorFisicoId = $this->getContenedorFisicoId($f);
        $buscar             = trim((string)($f['buscar'] ?? ''));
        $moneda             = strtoupper(trim((string)($f['moneda'] ?? '')));
        $tipoId             = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos        = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $w = ["c.factura_id = ?"];
        $p = [$facturaId];

        if ($contenedorFisicoId > 0) {
            $w[] = "c.contenedor_fisico_id = ?";
            $p[] = $contenedorFisicoId;
        }

        if ($soloActivos) {
            $w[] = "c.estatus = 1";
        }

        if ($buscar !== '') {
            $w[] = "(
                    tm.nombre LIKE ?
                    OR c.comentario LIKE ?
                    OR f.numero_factura LIKE ?
                    OR cli.nombre LIKE ?
                    OR cf.numero_ferro LIKE ?
                )";
            array_push($p, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
        }

        if ($moneda === 'PESOS' || $moneda === 'DLLS') {
            $w[] = "UPPER(tm.moneda) = ?";
            $p[] = $moneda;
        }

        if ($tipoId > 0) {
            $w[] = "c.tipo_movimiento_id = ?";
            $p[] = $tipoId;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM costos_operacion_partida c
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                LEFT JOIN op_partida_facturas f ON f.id_factura = c.factura_id
                LEFT JOIN clientes cli ON cli.id_cliente = f.cliente_id
                LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = c.contenedor_fisico_id
                WHERE " . implode(' AND ', $w);

        try {
            $row = $this->select($sql, $p);
            return (int)($row['total'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function listarCostosCombinadosPaginado(int $page, int $perPage, array $f = []): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) return [];

        $contenedorFisicoId = $this->getContenedorFisicoId($f);
        $buscar             = trim((string)($f['buscar'] ?? ''));
        $moneda             = strtoupper(trim((string)($f['moneda'] ?? '')));
        $tipoId             = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos        = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $w = ["c.factura_id = ?"];
        $p = [$facturaId];

        if ($contenedorFisicoId > 0) {
            $w[] = "c.contenedor_fisico_id = ?";
            $p[] = $contenedorFisicoId;
        }

        if ($soloActivos) {
            $w[] = "c.estatus = 1";
        }

        if ($buscar !== '') {
            $w[] = "(
                    tm.nombre LIKE ?
                    OR c.comentario LIKE ?
                    OR f.numero_factura LIKE ?
                    OR cli.nombre LIKE ?
                    OR cf.numero_ferro LIKE ?
                )";
            array_push($p, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
        }

        if ($moneda === 'PESOS' || $moneda === 'DLLS') {
            $w[] = "UPPER(tm.moneda) = ?";
            $p[] = $moneda;
        }

        if ($tipoId > 0) {
            $w[] = "c.tipo_movimiento_id = ?";
            $p[] = $tipoId;
        }

        $sql = "SELECT
                    'FACTURA'                             AS origen,
                    c.contenedor_fisico_id               AS contenedor_id,
                    c.contenedor_fisico_id               AS contenedor_fisico_id,
                    cf.numero_ferro                      AS contenedor,
                    cf.numero_ferro                      AS numero_ferro,
                    c.id_costo_operacion_partida         AS row_id,
                    c.factura_id                         AS factura_id,
                    f.numero_factura                     AS numero_operacion,
                    f.numero_factura                     AS numero_factura,
                    COALESCE(cli.nombre, 'Sin cliente')  AS cliente,
                    COALESCE(f.proveedor, '')            AS proveedor,
                    COALESCE(b.nombre, '')               AS bodega,
                    tm.id_tipo_movimiento                AS tipo_movimiento_id,
                    tm.nombre                            AS concepto,
                    LOWER(tm.tipo)                       AS naturaleza,
                    UPPER(tm.moneda)                     AS moneda,
                    c.monto                              AS monto,
                    c.comentario                         AS comentario,
                    c.fecha_creacion                     AS fecha,
                    'PARTIDA'                            AS fuente,
                    c.pagado                             AS pagado
                FROM costos_operacion_partida c
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                LEFT JOIN op_partida_facturas f ON f.id_factura = c.factura_id
                LEFT JOIN clientes cli ON cli.id_cliente = f.cliente_id
                LEFT JOIN bodegas b ON b.id_bodega = f.bodega_id
                LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = c.contenedor_fisico_id
                WHERE " . implode(' AND ', $w) . "
                ORDER BY c.fecha_creacion DESC, c.id_costo_operacion_partida DESC
                LIMIT {$perPage} OFFSET {$offset}";

        try {
            $rows = $this->selectAll($sql, $p);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function totalesCostosCombinados(array $f = []): array
    {
        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) {
            return [
                'total_operacion'           => 0.0,
                'total_contenedores'        => 0.0,
                'total_general'             => 0.0,
                'total_abonos_operacion'    => 0.0,
                'total_abonos_contenedores' => 0.0,
            ];
        }

        $soloActivos = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $sqlG = "SELECT COALESCE(SUM(c.monto),0) AS total
                 FROM costos_operacion_partida c
                 INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                 WHERE c.factura_id = ?
                 " . ($soloActivos ? "AND c.estatus = 1 " : "") . "
                 AND LOWER(tm.tipo) = 'gasto'";
        $rowG = $this->select($sqlG, [$facturaId]);
        $totalFactura = (float)($rowG['total'] ?? 0);

        $sqlA = "SELECT COALESCE(SUM(c.monto),0) AS total
                 FROM costos_operacion_partida c
                 INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                 WHERE c.factura_id = ?
                 " . ($soloActivos ? "AND c.estatus = 1 " : "") . "
                 AND LOWER(tm.tipo) = 'abono'";
        $rowA = $this->select($sqlA, [$facturaId]);
        $totalAbonosFactura = (float)($rowA['total'] ?? 0);

        return [
            'total_operacion'           => $totalFactura,
            'total_contenedores'        => 0.0,
            'total_general'             => $totalFactura,
            'total_abonos_operacion'    => $totalAbonosFactura,
            'total_abonos_contenedores' => 0.0,
        ];
    }

    public function abonosCombinadosDetallado(array $f = []): array
    {
        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) {
            return [
                'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
            ];
        }

        $soloActivos = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $sql = "SELECT
                    UPPER(tm.moneda) AS moneda,
                    COALESCE(SUM(c.monto),0) AS total
                FROM costos_operacion_partida c
                INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                WHERE c.factura_id = ?
                " . ($soloActivos ? "AND c.estatus = 1 " : "") . "
                  AND LOWER(tm.tipo) = 'abono'
                GROUP BY UPPER(tm.moneda)";
        $rows = $this->selectAll($sql, [$facturaId]) ?: [];

        $factura = ['PESOS' => 0.0, 'DLLS' => 0.0];
        foreach ($rows as $r) {
            $m = strtoupper((string)($r['moneda'] ?? ''));
            $t = (float)($r['total'] ?? 0);
            if ($m === 'PESOS') $factura['PESOS'] += $t;
            if ($m === 'DLLS')  $factura['DLLS']  += $t;
        }

        return [
            'operacion'    => $factura,
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }

    public function totalesCostosCombinadosDetallado(array $f = []): array
    {
        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) {
            return [
                'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
            ];
        }

        $soloActivos = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $sql = "SELECT
                    UPPER(tm.moneda) AS moneda,
                    COALESCE(SUM(c.monto),0) AS total
                FROM costos_operacion_partida c
                INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                WHERE c.factura_id = ?
                " . ($soloActivos ? "AND c.estatus = 1 " : "") . "
                  AND LOWER(tm.tipo) = 'gasto'
                GROUP BY UPPER(tm.moneda)";
        $rows = $this->selectAll($sql, [$facturaId]) ?: [];

        $factura = ['PESOS' => 0.0, 'DLLS' => 0.0];
        foreach ($rows as $r) {
            $m = strtoupper((string)($r['moneda'] ?? ''));
            $t = (float)($r['total'] ?? 0);
            if ($m === 'PESOS') $factura['PESOS'] += $t;
            if ($m === 'DLLS')  $factura['DLLS']  += $t;
        }

        return [
            'operacion'    => $factura,
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }

    public function insertarCostoOperacionCombinado(array $d): int
    {
        $facturaId = $this->getFacturaId($d);
        $contenedorFisicoId = $this->getContenedorFisicoId($d);

        if ($facturaId <= 0 || $contenedorFisicoId <= 0) return 0;
        if (!$this->existeFerroEnFactura($facturaId, $contenedorFisicoId)) return 0;

        $tipoMovimientoId = (int)($d['tipo_movimiento_id'] ?? 0);
        $monto            = (float)($d['monto'] ?? 0);
        $comentario       = trim((string)($d['comentario'] ?? ''));
        $pagado           = ((int)($d['pagado'] ?? 0) === 1) ? 1 : 0;

        if ($tipoMovimientoId <= 0 || $monto <= 0) {
            return 0;
        }

        $sql = "INSERT INTO costos_operacion_partida
                    (factura_id, contenedor_fisico_id, tipo_movimiento_id, monto, comentario, pagado, estatus, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";

        return (int)$this->insertar($sql, [
            $facturaId,
            $contenedorFisicoId,
            $tipoMovimientoId,
            $monto,
            $comentario,
            $pagado
        ]);
    }

    /**
     * Valida si un ferro realmente está ligado a una factura mediante
     * operaciones_partida_envio_detalle -> operaciones_partida_envios.
     */
    public function existeFerroEnFactura(int $facturaId, int $contenedorFisicoId): bool
    {
        if ($facturaId <= 0 || $contenedorFisicoId <= 0) {
            return false;
        }

        $sql = "SELECT 1
                FROM operaciones_partida_envio_detalle d
                INNER JOIN operaciones_partida_envios e
                    ON e.id_envio = d.envio_id
                WHERE d.factura_id = ?
                  AND e.contenedor_fisico_id = ?
                  AND d.estatus <> 0
                  AND e.estatus <> 0
                LIMIT 1";

        try {
            $row = $this->select($sql, [$facturaId, $contenedorFisicoId]);
            return !empty($row);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function actualizarCostoOperacionCombinado(int $id, array $d): bool
    {
        $actual = $this->obtenerCostoOperacionCombinado($id);
        if (!$actual) return false;

        $nuevoContenedorFisicoId = array_key_exists('contenedor_fisico_id', $d)
            ? (int)$d['contenedor_fisico_id']
            : (int)$actual['contenedor_fisico_id'];

        $facturaId = (int)$actual['factura_id'];

        if ($nuevoContenedorFisicoId > 0 && !$this->existeFerroEnFactura($facturaId, $nuevoContenedorFisicoId)) {
            return false;
        }

        $sets   = [];
        $params = [];

        if (array_key_exists('contenedor_fisico_id', $d)) {
            $sets[]   = "contenedor_fisico_id = ?";
            $params[] = $nuevoContenedorFisicoId;
        }

        if (array_key_exists('tipo_movimiento_id', $d)) {
            $sets[]   = "tipo_movimiento_id = ?";
            $params[] = (int)$d['tipo_movimiento_id'];
        }

        if (array_key_exists('monto', $d)) {
            $sets[]   = "monto = ?";
            $params[] = (float)$d['monto'];
        }

        if (array_key_exists('comentario', $d)) {
            $sets[]   = "comentario = ?";
            $params[] = trim((string)$d['comentario']);
        }

        if (array_key_exists('pagado', $d)) {
            $sets[]   = "pagado = ?";
            $params[] = ((int)$d['pagado'] === 1) ? 1 : 0;
        }

        if (empty($sets)) return false;

        $sql = "UPDATE costos_operacion_partida
                SET " . implode(', ', $sets) . "
                WHERE id_costo_operacion_partida = ?
                LIMIT 1";
        $params[] = $id;

        return $this->save($sql, $params) === 1;
    }

    public function obtenerCostoOperacionCombinado(int $id): ?array
    {
        $sql = "SELECT
                    c.id_costo_operacion_partida         AS row_id,
                    c.factura_id                         AS factura_id,
                    c.contenedor_fisico_id              AS contenedor_fisico_id,
                    cf.numero_ferro                     AS contenedor,
                    cf.numero_ferro                     AS numero_ferro,
                    f.numero_factura                    AS numero_operacion,
                    f.numero_factura                    AS numero_factura,
                    COALESCE(cli.nombre, 'Sin cliente') AS cliente,
                    COALESCE(f.proveedor, '')           AS proveedor,
                    COALESCE(b.nombre, '')              AS bodega,
                    c.tipo_movimiento_id,
                    tm.nombre                           AS concepto,
                    LOWER(tm.tipo)                      AS naturaleza,
                    UPPER(tm.moneda)                    AS moneda,
                    c.monto,
                    c.comentario,
                    c.estatus,
                    c.fecha_creacion                    AS fecha,
                    'PARTIDA'                           AS fuente,
                    c.pagado                            AS pagado
                FROM costos_operacion_partida c
                LEFT JOIN op_partida_facturas f ON f.id_factura = c.factura_id
                LEFT JOIN clientes cli ON cli.id_cliente = f.cliente_id
                LEFT JOIN bodegas b ON b.id_bodega = f.bodega_id
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = c.contenedor_fisico_id
                WHERE c.id_costo_operacion_partida = ?
                LIMIT 1";

        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function desactivarCostoOperacionCombinado(int $id): bool
    {
        $sql = "UPDATE costos_operacion_partida
                SET estatus = 0
                WHERE id_costo_operacion_partida = ?
                LIMIT 1";
        return $this->save($sql, [$id]) === 1;
    }

    public function reactivarCostoOperacionCombinado(int $id): bool
    {
        $sql = "UPDATE costos_operacion_partida
                SET estatus = 1
                WHERE id_costo_operacion_partida = ?
                LIMIT 1";
        return $this->save($sql, [$id]) === 1;
    }

    public function obtenerTipoMovimiento(int $id): ?array
    {
        $sql = "SELECT
                    id_tipo_movimiento,
                    UPPER(moneda) AS moneda,
                    LOWER(tipo) AS tipo,
                    nombre
                FROM tipos_movimiento
                WHERE id_tipo_movimiento = ?
                  AND estatus = 1
                LIMIT 1";

        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    /**
     * Devuelve el primer ferro ligado a la factura,
     * usando el detalle de envío como puente real.
     */
    public function obtenerContenedorLigado(array $f = []): ?array
    {
        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) return null;

        try {
            $sql = "SELECT
                        e.id_envio,
                        cf.id_fisico,
                        cf.numero_ferro AS numero
                    FROM operaciones_partida_envio_detalle d
                    INNER JOIN operaciones_partida_envios e
                        ON e.id_envio = d.envio_id
                    INNER JOIN contenedores_fisicos cf
                        ON cf.id_fisico = e.contenedor_fisico_id
                    WHERE d.factura_id = ?
                      AND d.estatus <> 0
                      AND e.estatus <> 0
                    ORDER BY e.fecha_envio DESC, e.id_envio DESC
                    LIMIT 1";

            $row = $this->select($sql, [$facturaId]) ?: null;
            if (!$row || empty($row['numero'])) return null;

            return [
                'fuente' => 'PARTIDA',
                'numero' => $row['numero'],
                'tipo'   => 'FERRO',
                'ids'    => [
                    'factura_id'           => $facturaId,
                    'envio_id'             => (int)$row['id_envio'],
                    'contenedor_fisico_id' => (int)$row['id_fisico'],
                ],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function obtenerFacturaPartida(int $facturaId): ?array
    {
        $sql = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.fecha_recibido AS fecha_factura,
                    f.cliente_id,
                    COALESCE(cli.nombre, 'Sin cliente') AS cliente,
                    f.bodega_id,
                    COALESCE(b.nombre, '') AS bodega,
                    COALESCE(f.proveedor, '') AS proveedor,
                    f.estatus
                FROM op_partida_facturas f
                LEFT JOIN clientes cli ON cli.id_cliente = f.cliente_id
                LEFT JOIN bodegas b ON b.id_bodega = f.bodega_id
                WHERE f.id_factura = ?
                LIMIT 1";

        $row = $this->select($sql, [$facturaId]);
        return $row ?: null;
    }

    /**
     * Este método es el importante para llenar tu SELECT de ferros.
     * Busca todos los ferros usados por los productos de la factura.
     */
    public function obtenerFerrosPorFactura(int $facturaId): array
    {
        if ($facturaId <= 0) return [];

        $sql = "SELECT
                    e.contenedor_fisico_id                 AS contenedor_fisico_id,
                    cf.id_fisico                           AS id_fisico,
                    cf.numero_ferro                        AS numero,
                    cf.numero_ferro                        AS numero_ferro,
                    COUNT(DISTINCT e.id_envio)             AS total_envios,
                    COUNT(DISTINCT d.producto_id)          AS total_productos,
                    COALESCE(SUM(d.cajas_enviadas), 0)     AS total_cajas_enviadas,
                    MAX(e.fecha_envio)                     AS ultima_fecha_envio
                FROM operaciones_partida_envio_detalle d
                INNER JOIN operaciones_partida_envios e
                    ON e.id_envio = d.envio_id
                INNER JOIN contenedores_fisicos cf
                    ON cf.id_fisico = e.contenedor_fisico_id
                WHERE d.factura_id = ?
                  AND d.estatus <> 0
                  AND e.estatus <> 0
                GROUP BY
                    e.contenedor_fisico_id,
                    cf.id_fisico,
                    cf.numero_ferro
                ORDER BY
                    cf.numero_ferro ASC";

        try {
            return $this->selectAll($sql, [$facturaId]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Opcional pero útil para tu vista:
     * devuelve los productos de una factura y en qué ferro(s) fueron enviados.
     */
    public function obtenerProductosYFerrosPorFactura(int $facturaId): array
    {
        if ($facturaId <= 0) return [];

        $sql = "SELECT
                    d.producto_id,
                    p.descripcion,
                    p.upc,
                    cf.id_fisico                           AS contenedor_fisico_id,
                    cf.numero_ferro,
                    e.id_envio,
                    e.fecha_envio,
                    e.estatus_envio,
                    d.cajas_enviadas
                FROM operaciones_partida_envio_detalle d
                INNER JOIN operaciones_partida_envios e
                    ON e.id_envio = d.envio_id
                INNER JOIN contenedores_fisicos cf
                    ON cf.id_fisico = e.contenedor_fisico_id
                LEFT JOIN op_partida_productos p
                    ON p.id_producto = d.producto_id
                WHERE d.factura_id = ?
                  AND d.estatus <> 0
                  AND e.estatus <> 0
                ORDER BY p.descripcion ASC, cf.numero_ferro ASC, e.fecha_envio DESC";

        try {
            return $this->selectAll($sql, [$facturaId]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
