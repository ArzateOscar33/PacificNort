<?php

class Operaciones_por_partida_costosModel extends Query
{
    /**
     * IMPORTANTE:
     * - Ahora los costos viven en: costos_operacion_partida
     * - FK principal: factura_id
     * - Entidad raíz: op_partida_facturas
     *

     *
     * Si después creas un tipo_operacion_id exclusivo para partida, solo cambia
     * el arreglo de getTiposOperacionMovimientoIds().
     */

    private function getFacturaId(array $f): int
    {
        return (int)($f['factura_id'] ?? 0);
    }

    private function getTiposOperacionMovimientoIds(): array
    {
        // Ajustable.
        // En tu dump existen 1=Maritimo y 11=Maritimo-Ferroviario.
        // Como comentaste que los costos son los mismos o muy similares,
        // usamos ambos catálogos por compatibilidad.
        return [1, 11];
    }

    private function buildInPlaceholders(array $items): string
    {
        return implode(', ', array_fill(0, count($items), '?'));
    }

    public function buscarOperacionesCombinadasPorTerm(string $term): array
    {
        // Se conserva el nombre por compatibilidad con el front,
        // pero ahora realmente busca FACTURAS de operaciones por partida.
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
        // Compatibilidad con tu front
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

        $buscar      = trim((string)($f['buscar'] ?? ''));
        $moneda      = strtoupper(trim((string)($f['moneda'] ?? '')));
        $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $w = ["c.factura_id = ?"];
        $p = [$facturaId];

        if ($soloActivos) {
            $w[] = "c.estatus = 1";
        }

        if ($buscar !== '') {
            $w[] = "(
                        tm.nombre LIKE ?
                        OR c.comentario LIKE ?
                        OR f.numero_factura LIKE ?
                        OR cli.nombre LIKE ?
                        OR f.proveedor LIKE ?
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

        $buscar      = trim((string)($f['buscar'] ?? ''));
        $moneda      = strtoupper(trim((string)($f['moneda'] ?? '')));
        $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos = array_key_exists('solo_activos', $f) ? (bool)$f['solo_activos'] : true;

        $w = ["c.factura_id = ?"];
        $p = [$facturaId];

        if ($soloActivos) {
            $w[] = "c.estatus = 1";
        }

        if ($buscar !== '') {
            $w[] = "(
                        tm.nombre LIKE ?
                        OR c.comentario LIKE ?
                        OR f.numero_factura LIKE ?
                        OR cli.nombre LIKE ?
                        OR f.proveedor LIKE ?
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

        $sql = "
            SELECT
                'FACTURA'                                AS origen,
                NULL                                     AS contenedor_id,
                NULL                                     AS contenedor,
                c.id_costo_operacion_partida            AS row_id,
                c.factura_id                            AS factura_id,
                f.numero_factura                        AS numero_operacion,
                f.numero_factura                        AS numero_factura,
                COALESCE(cli.nombre, 'Sin cliente')     AS cliente,
                COALESCE(f.proveedor, '')               AS proveedor,
                COALESCE(b.nombre, '')                  AS bodega,
                tm.id_tipo_movimiento                   AS tipo_movimiento_id,
                tm.nombre                               AS concepto,
                LOWER(tm.tipo)                          AS naturaleza,
                UPPER(tm.moneda)                        AS moneda,
                c.monto                                 AS monto,
                c.comentario                            AS comentario,
                c.fecha_creacion                        AS fecha,
                'PARTIDA'                               AS fuente,
                c.pagado                                AS pagado
            FROM costos_operacion_partida c
            LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
            LEFT JOIN op_partida_facturas f ON f.id_factura = c.factura_id
            LEFT JOIN clientes cli ON cli.id_cliente = f.cliente_id
            LEFT JOIN bodegas b ON b.id_bodega = f.bodega_id
            WHERE " . implode(' AND ', $w) . "
            ORDER BY c.fecha_creacion DESC, c.id_costo_operacion_partida DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

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
        if ($facturaId <= 0) return 0;

        $tipoMovimientoId = (int)($d['tipo_movimiento_id'] ?? 0);
        $monto            = (float)($d['monto'] ?? 0);
        $comentario       = trim((string)($d['comentario'] ?? ''));
        $pagado           = ((int)($d['pagado'] ?? 0) === 1) ? 1 : 0;

        if ($tipoMovimientoId <= 0 || $monto <= 0) {
            return 0;
        }

        $sql = "INSERT INTO costos_operacion_partida
                    (factura_id, tipo_movimiento_id, monto, comentario, pagado, estatus, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, 1, NOW())";

        return (int)$this->insertar($sql, [
            $facturaId,
            $tipoMovimientoId,
            $monto,
            $comentario,
            $pagado
        ]);
    }

    public function actualizarCostoOperacionCombinado(int $id, array $d): bool
    {
        $sets   = [];
        $params = [];

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
                    f.numero_factura                     AS numero_operacion,
                    f.numero_factura                     AS numero_factura,
                    COALESCE(cli.nombre, 'Sin cliente')  AS cliente,
                    COALESCE(f.proveedor, '')            AS proveedor,
                    COALESCE(b.nombre, '')               AS bodega,
                    c.tipo_movimiento_id,
                    tm.nombre                            AS concepto,
                    LOWER(tm.tipo)                       AS naturaleza,
                    UPPER(tm.moneda)                     AS moneda,
                    c.monto,
                    c.comentario,
                    c.estatus,
                    c.fecha_creacion                     AS fecha,
                    'PARTIDA'                            AS fuente,
                    c.pagado                             AS pagado
                FROM costos_operacion_partida c
                LEFT JOIN op_partida_facturas f ON f.id_factura = c.factura_id
                LEFT JOIN clientes cli ON cli.id_cliente = f.cliente_id
                LEFT JOIN bodegas b ON b.id_bodega = f.bodega_id
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
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

    public function obtenerContenedorLigado(array $f = []): ?array
    {
        // En operación por partida no hay un único contenedor maestro equivalente al MF.
        // Lo más cercano es obtener el primer envío ligado a la factura y de ahí el ferro/caja.
        $facturaId = $this->getFacturaId($f);
        if ($facturaId <= 0) return null;

        try {
            $sql = "SELECT
                        e.id_envio,
                        cf.id_fisico,
                        cf.numero_ferro AS numero
                    FROM op_partida_envios e
                    INNER JOIN contenedores_fisicos cf ON cf.id_fisico = e.id_fisico
                    WHERE e.factura_id = ?
                      AND e.estatus = 1
                    ORDER BY e.id_envio ASC
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
                    f.fecha_factura,
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
}
