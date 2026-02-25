<?php

class Operaciones_maritimo_ferro_costos_contenedorModel extends Query
{
    /**
     * A partir de ahora, TODOS los costos se asocian a la operación marítima maestra:
     * - tabla_costos: costos_operacion
     * - fk: operacion_id
     * - tabla_operaciones: operaciones
     * - tipo_operacion_id (para filtrar tipos_movimiento): 11 (AJUSTA si aplica)
     */
    private function getOperacionId(array $f): int
    {
        // Ya no soportamos FO como fuente. Solo operacion_id (marítima).
        return (int)($f['operacion_id'] ?? 0);
    }

    public function buscarOperacionesCombinadasPorTerm(string $term): array
    {
        // NOTA: si en el front todavía se llama así, lo dejamos para compatibilidad.
        $term = trim($term);
        if ($term === '') return [];

        $sql = "SELECT 
                    o.id_operacion     AS id,
                    o.numero_operacion AS numero_operacion,
                    c.nombre           AS cliente,
                    'MF'               AS fuente
                FROM operaciones o
                LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
                WHERE o.tipo_operacion_id = 11
                  AND o.numero_operacion LIKE ?
                ORDER BY o.numero_operacion DESC
                LIMIT 20";

        try {
            return $this->selectAll($sql, ["%{$term}%"]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function obtenerTiposMovimientoActivosPorFuente(string $fuente = 'MF'): array
    {
        // Compatibilidad: ignoramos $fuente y devolvemos los de marítimo.
        return $this->obtenerTiposMovimientoActivos();
    }

    public function obtenerTiposMovimientoActivos(): array
    {
        // AJUSTA tipo_operacion_id si tu catálogo de tipos_movimiento usa otro id para marítimo
        $sql = "SELECT id_tipo_movimiento, nombre, UPPER(moneda) AS moneda
                FROM tipos_movimiento
                WHERE estatus = 1 AND tipo_operacion_id = 1
                ORDER BY nombre ASC";
        try {
            return $this->selectAll($sql, []) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function contarCostosCombinados(array $f = []): int
    {
        $opId = $this->getOperacionId($f);
        if ($opId <= 0) return 0;

        $buscar      = trim((string)($f['buscar'] ?? ''));
        $moneda      = strtoupper(trim((string)($f['moneda'] ?? ''))); // 'PESOS'|'DLLS'|''
        $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $w = ["c.operacion_id = ?"];
        $p = [$opId];

        if ($soloActivos) {
            $w[] = "c.estatus = 1";
        }
        if ($buscar !== '') {
            $w[] = "(tm.nombre LIKE ? OR c.comentario LIKE ? OR o.numero_operacion LIKE ?)";
            array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
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
                FROM costos_operacion c
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                LEFT JOIN operaciones o ON o.id_operacion = c.operacion_id
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

        $opId = $this->getOperacionId($f);
        if ($opId <= 0) return [];

        $buscar      = trim((string)($f['buscar'] ?? ''));
        $moneda      = strtoupper(trim((string)($f['moneda'] ?? '')));
        $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $w = ["c.operacion_id = ?"];
        $p = [$opId];

        if ($soloActivos) {
            $w[] = "c.estatus = 1";
        }
        if ($buscar !== '') {
            $w[] = "(tm.nombre LIKE ? OR c.comentario LIKE ? OR o.numero_operacion LIKE ?)";
            array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
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
                'OPERACION'              AS origen,
                NULL                     AS contenedor_id,
                NULL                     AS contenedor,
                c.id_costo_operacion     AS row_id,
                o.id_operacion           AS operacion_id,
                o.numero_operacion       AS numero_operacion,
                tm.id_tipo_movimiento    AS tipo_movimiento_id,
                tm.nombre                AS concepto,
                LOWER(tm.tipo)           AS naturaleza,
                UPPER(tm.moneda)         AS moneda,
                c.monto                  AS monto,
                c.comentario             AS comentario,
                c.fecha_creacion         AS fecha,
                'MF'                     AS fuente,
                c.pagado                 AS pagado
            FROM costos_operacion c
            LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
            LEFT JOIN operaciones o ON o.id_operacion = c.operacion_id
            WHERE " . implode(' AND ', $w) . "
            ORDER BY c.fecha_creacion DESC, c.id_costo_operacion DESC
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
        $opId = $this->getOperacionId($f);
        if ($opId <= 0) {
            return [
                'total_operacion'           => 0.0,
                'total_contenedores'        => 0.0,
                'total_general'             => 0.0,
                'total_abonos_operacion'    => 0.0,
                'total_abonos_contenedores' => 0.0,
            ];
        }

        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $sqlG = "SELECT COALESCE(SUM(c.monto),0) AS total
                 FROM costos_operacion c
                 INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                 WHERE c.operacion_id = ?
                 " . ($soloActivos ? "AND c.estatus=1 " : "") . "
                 AND tm.tipo='gasto'";
        $rowG = $this->select($sqlG, [$opId]);
        $totalOp = (float)($rowG['total'] ?? 0);

        $sqlA = "SELECT COALESCE(SUM(c.monto),0) AS total
                 FROM costos_operacion c
                 INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                 WHERE c.operacion_id = ?
                 " . ($soloActivos ? "AND c.estatus=1 " : "") . "
                 AND tm.tipo='abono'";
        $rowA = $this->select($sqlA, [$opId]);
        $totalAbOp = (float)($rowA['total'] ?? 0);

        return [
            'total_operacion'           => $totalOp,
            'total_contenedores'        => 0.0,
            'total_general'             => $totalOp,
            'total_abonos_operacion'    => $totalAbOp,
            'total_abonos_contenedores' => 0.0,
        ];
    }

    public function abonosCombinadosDetallado(array $f = []): array
    {
        $opId = $this->getOperacionId($f);
        if ($opId <= 0) {
            return [
                'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
            ];
        }

        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $sql = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(c.monto),0) AS total
                FROM costos_operacion c
                INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                WHERE c.operacion_id = ?
                " . ($soloActivos ? "AND c.estatus=1 " : "") . "
                AND tm.tipo='abono'
                GROUP BY UPPER(tm.moneda)";
        $rows = $this->selectAll($sql, [$opId]) ?: [];

        $op = ['PESOS' => 0.0, 'DLLS' => 0.0];
        foreach ($rows as $r) {
            $m = strtoupper((string)($r['moneda'] ?? ''));
            $t = (float)($r['total'] ?? 0);
            if ($m === 'PESOS') $op['PESOS'] += $t;
            if ($m === 'DLLS')  $op['DLLS']  += $t;
        }

        return [
            'operacion'    => $op,
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }

    public function totalesCostosCombinadosDetallado(array $f = []): array
    {
        $opId = $this->getOperacionId($f);
        if ($opId <= 0) {
            return [
                'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
            ];
        }

        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $sql = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(c.monto),0) AS total
                FROM costos_operacion c
                INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                WHERE c.operacion_id = ?
                " . ($soloActivos ? "AND c.estatus=1 " : "") . "
                AND tm.tipo='gasto'
                GROUP BY UPPER(tm.moneda)";
        $rows = $this->selectAll($sql, [$opId]) ?: [];

        $op = ['PESOS' => 0.0, 'DLLS' => 0.0];
        foreach ($rows as $r) {
            $m = strtoupper((string)($r['moneda'] ?? ''));
            $t = (float)($r['total'] ?? 0);
            if ($m === 'PESOS') $op['PESOS'] += $t;
            if ($m === 'DLLS')  $op['DLLS']  += $t;
        }

        return [
            'operacion'    => $op,
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }

    public function insertarCostoOperacionCombinado(array $d): int
    {
        $opId = $this->getOperacionId($d);
        if ($opId <= 0) return 0;

        $pagado = isset($d['pagado']) ? (int)$d['pagado'] : 0;
        $pagado = ($pagado === 1) ? 1 : 0;

        $sql = "INSERT INTO costos_operacion
            (operacion_id, tipo_movimiento_id, monto, comentario, pagado, estatus, fecha_creacion)
            VALUES (?, ?, ?, ?, ?, 1, NOW())";

        return (int)$this->insertar($sql, [
            $opId,
            (int)($d['tipo_movimiento_id'] ?? 0),
            (float)($d['monto'] ?? 0),
            (string)($d['comentario'] ?? ''),
            $pagado
        ]);
    }

    public function actualizarCostoOperacionCombinado(int $id, array $d): bool
    {
        $sets   = [];
        $params = [];

        if (array_key_exists('tipo_movimiento_id', $d)) {
            $sets[] = "tipo_movimiento_id = ?";
            $params[] = (int)$d['tipo_movimiento_id'];
        }
        if (array_key_exists('monto', $d)) {
            $sets[] = "monto = ?";
            $params[] = (float)$d['monto'];
        }
        if (array_key_exists('comentario', $d)) {
            $sets[] = "comentario = ?";
            $params[] = (string)$d['comentario'];
        }

        // ✅ NUEVO
        if (array_key_exists('pagado', $d)) {
            $val = ((int)$d['pagado'] === 1) ? 1 : 0;
            $sets[] = "pagado = ?";
            $params[] = $val;
        }

        if (empty($sets)) return false;

        $sql = "UPDATE costos_operacion
            SET " . implode(', ', $sets) . "
            WHERE id_costo_operacion = ?
            LIMIT 1";
        $params[] = $id;

        return $this->save($sql, $params) === 1;
    }

    public function obtenerCostoOperacionCombinado(int $id): ?array
    {
        $sql = "SELECT 
                    c.id_costo_operacion AS row_id,
                    c.operacion_id       AS operacion_id,
                    o.numero_operacion   AS numero_operacion,
                    c.tipo_movimiento_id,
                    tm.nombre            AS concepto,
                    UPPER(tm.moneda)     AS moneda,
                    c.monto,
                    c.comentario,
                    c.estatus,
                    c.fecha_creacion     AS fecha,
                    'MF'                 AS fuente,
                    c.pagado             AS pagado
                FROM costos_operacion c
                LEFT JOIN operaciones o      ON o.id_operacion = c.operacion_id
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
                WHERE c.id_costo_operacion = ?
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function desactivarCostoOperacionCombinado(int $id): bool
    {
        $sql = "UPDATE costos_operacion SET estatus = 0 WHERE id_costo_operacion = ? LIMIT 1";
        return $this->save($sql, [$id]) === 1;
    }

    public function reactivarCostoOperacionCombinado(int $id): bool
    {
        $sql = "UPDATE costos_operacion SET estatus = 1 WHERE id_costo_operacion = ? LIMIT 1";
        return $this->save($sql, [$id]) === 1;
    }

    public function obtenerTipoMovimiento(int $id): ?array
    {
        $sql = "SELECT id_tipo_movimiento, UPPER(moneda) AS moneda, LOWER(tipo) AS tipo, nombre
                FROM tipos_movimiento
                WHERE id_tipo_movimiento = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function obtenerContenedorLigado(array $f = []): ?array
    {
        $opId = $this->getOperacionId($f);
        if ($opId <= 0) return null;

        try {
            // MF: operacion_id -> contenedores_maritimos_operacion -> contenedores_maritimos.numero_contenedor
            $sql = "SELECT 
                        cmo.id                    AS puente_id,
                        cm.id_contenedor_maritimo AS contenedor_maritimo_id,
                        cm.numero_contenedor      AS numero
                    FROM contenedores_maritimos_operacion cmo
                    LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                    WHERE cmo.operacion_id = ?
                    ORDER BY cmo.id ASC
                    LIMIT 1";
            $row = $this->select($sql, [$opId]) ?: null;
            if (!$row || empty($row['numero'])) return null;

            return [
                'fuente'   => 'MF',
                'numero'   => $row['numero'],
                'tipo'     => 'MARITIMO',
                'ids'      => [
                    'contenedor_maritimo_id' => (int)$row['contenedor_maritimo_id'],
                    'puente_id'              => (int)$row['puente_id'],
                    'operacion_id'           => $opId,
                ],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
