<?php
class Operaciones_maritimo_ferro_costos_contenedorModel extends Query
{
    /**
     * Cuenta filas (solo NIVEL OPERACIÓN FERRO) para paginar.
     * Filtros: operacion_ferro_id, buscar, moneda('PESOS'|'DLLS'|''), tipo_movimiento_id, solo_activos
     */
    public function contarCostosCombinados(array $f = []): int
    {
        $opFerroId   = (int)($f['operacion_ferro_id'] ?? $f['operacion_id'] ?? 0);
        if ($opFerroId <= 0) return 0;

        $buscar      = trim((string)($f['buscar'] ?? ''));
        $moneda      = strtoupper(trim((string)($f['moneda'] ?? ''))); // 'PESOS'|'DLLS'|''
        $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $w = ["cf.operacion_ferro_id = ?"];
        $p = [$opFerroId];

        if ($soloActivos) {
            $w[] = "cf.estatus = 1";
        }
        if ($buscar !== '') {
            $w[] = "(tm.nombre LIKE ? OR cf.comentario LIKE ? OR ofv.numero_operacion LIKE ?)";
            array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
        }
        if ($moneda === 'PESOS' || $moneda === 'DLLS') {
            $w[] = "UPPER(tm.moneda) = ?";
            $p[] = $moneda;
        }
        if ($tipoId > 0) {
            $w[] = "cf.tipo_movimiento_id = ?";
            $p[] = $tipoId;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM costos_operacion_ferro cf
                LEFT JOIN tipos_movimiento tm      ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
                LEFT JOIN operaciones_ferroviarias ofv ON ofv.id_operacion_ferro = cf.operacion_ferro_id
                WHERE " . implode(' AND ', $w);

        try {
            $row = $this->select($sql, $p);
            return (int)($row['total'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Lista (solo NIVEL OPERACIÓN FERRO) con paginación.
     * Normaliza columnas a un único formato de tabla para el front.
     */
    public function listarCostosCombinadosPaginado(int $page, int $perPage, array $f = []): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        $opFerroId   = (int)($f['operacion_ferro_id'] ?? $f['operacion_id'] ?? 0);
        if ($opFerroId <= 0) return [];

        $buscar      = trim((string)($f['buscar'] ?? ''));
        $moneda      = strtoupper(trim((string)($f['moneda'] ?? '')));
        $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $w = ["cf.operacion_ferro_id = ?"];
        $p = [$opFerroId];

        if ($soloActivos) {
            $w[] = "cf.estatus = 1";
        }
        if ($buscar !== '') {
            $w[] = "(tm.nombre LIKE ? OR cf.comentario LIKE ? OR ofv.numero_operacion LIKE ?)";
            array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
        }
        if ($moneda === 'PESOS' || $moneda === 'DLLS') {
            $w[] = "UPPER(tm.moneda) = ?";
            $p[] = $moneda;
        }
        if ($tipoId > 0) {
            $w[] = "cf.tipo_movimiento_id = ?";
            $p[] = $tipoId;
        }

        $sql = "
            SELECT
                'OPERACION'               AS origen,
                NULL                      AS contenedor_id,
                NULL                      AS contenedor,
                cf.id_costo_ferro         AS row_id,
                ofv.id_operacion_ferro    AS operacion_ferro_id,
                ofv.numero_operacion      AS numero_operacion,
                tm.id_tipo_movimiento     AS tipo_movimiento_id,
                tm.nombre                 AS concepto,
                LOWER(tm.tipo)            AS naturaleza,  -- 'gasto' | 'abono'
                UPPER(tm.moneda)          AS moneda,
                cf.monto                  AS monto,
                cf.comentario             AS comentario,
                cf.fecha_creacion         AS fecha
            FROM costos_operacion_ferro cf
            LEFT JOIN tipos_movimiento tm      ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
            LEFT JOIN operaciones_ferroviarias ofv ON ofv.id_operacion_ferro = cf.operacion_ferro_id
            WHERE " . implode(' AND ', $w) . "
            ORDER BY cf.fecha_creacion DESC, cf.id_costo_ferro DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        try {
            $rows = $this->selectAll($sql, $p);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Totales (solo operación ferro) */
    public function totalesCostosCombinados(array $f = []): array
    {
        $opFerroId = (int)($f['operacion_ferro_id'] ?? $f['operacion_id'] ?? 0);
        if ($opFerroId <= 0) {
            return [
                'total_operacion'           => 0.0,
                'total_contenedores'        => 0.0, // no aplica aquí
                'total_general'             => 0.0,
                'total_abonos_operacion'    => 0.0,
                'total_abonos_contenedores' => 0.0,
            ];
        }
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        // GASTOS (operación ferro)
        $sqlG = "SELECT COALESCE(SUM(cf.monto),0) AS total
                 FROM costos_operacion_ferro cf
                 INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
                 WHERE cf.operacion_ferro_id = ? " . ($soloActivos ? "AND cf.estatus=1 " : "") . " AND tm.tipo='gasto'";
        $rowG = $this->select($sqlG, [$opFerroId]);
        $totalOp = (float)($rowG['total'] ?? 0);

        // ABONOS (operación ferro)
        $sqlA = "SELECT COALESCE(SUM(cf.monto),0) AS total
                 FROM costos_operacion_ferro cf
                 INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
                 WHERE cf.operacion_ferro_id = ? " . ($soloActivos ? "AND cf.estatus=1 " : "") . " AND tm.tipo='abono'";
        $rowA = $this->select($sqlA, [$opFerroId]);
        $totalAbOp = (float)($rowA['total'] ?? 0);

        return [
            'total_operacion'           => $totalOp,
            'total_contenedores'        => 0.0,
            'total_general'             => $totalOp,
            'total_abonos_operacion'    => $totalAbOp,
            'total_abonos_contenedores' => 0.0,
        ];
    }

    /** Abonos por moneda (operación ferro) */
    public function abonosCombinadosDetallado(array $f = []): array
    {
        $opFerroId = (int)($f['operacion_ferro_id'] ?? $f['operacion_id'] ?? 0);
        if ($opFerroId <= 0) {
            return [
                'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
            ];
        }
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(cf.monto),0) AS total
                  FROM costos_operacion_ferro cf
                  INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
                  WHERE cf.operacion_ferro_id = ? " . ($soloActivos ? "AND cf.estatus=1 " : "") . " AND tm.tipo='abono'
                  GROUP BY UPPER(tm.moneda)";
        $rowsOp = $this->selectAll($sqlOp, [$opFerroId]) ?: [];
        $op = ['PESOS'=>0.0, 'DLLS'=>0.0];
        foreach ($rowsOp as $r) {
            $m = strtoupper((string)($r['moneda'] ?? ''));
            $t = (float)($r['total'] ?? 0);
            if ($m === 'PESOS') $op['PESOS'] += $t;
            if ($m === 'DLLS')  $op['DLLS']  += $t;
        }

        return [
            'operacion'    => $op,
            'contenedores' => ['PESOS'=>0.0, 'DLLS'=>0.0],
        ];
    }

    /** Gastos por moneda (operación ferro) */
    public function totalesCostosCombinadosDetallado(array $f = []): array
    {
        $opFerroId = (int)($f['operacion_ferro_id'] ?? $f['operacion_id'] ?? 0);
        if ($opFerroId <= 0) {
            return [
                'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
                'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
            ];
        }
        $soloActivos = (bool)($f['solo_activos'] ?? true);

        $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(cf.monto),0) AS total
                  FROM costos_operacion_ferro cf
                  INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
                  WHERE cf.operacion_ferro_id = ? " . ($soloActivos ? "AND cf.estatus=1 " : "") . " AND tm.tipo='gasto'
                  GROUP BY UPPER(tm.moneda)";
        $rowsOp = $this->selectAll($sqlOp, [$opFerroId]) ?: [];
        $op = ['PESOS'=>0.0, 'DLLS'=>0.0];
        foreach ($rowsOp as $r) {
            $m = strtoupper((string)($r['moneda'] ?? ''));
            $t = (float)($r['total'] ?? 0);
            if ($m === 'PESOS') $op['PESOS'] += $t;
            if ($m === 'DLLS')  $op['DLLS']  += $t;
        }

        return [
            'operacion'    => $op,
            'contenedores' => ['PESOS'=>0.0, 'DLLS'=>0.0],
        ];
    }

    /** Trae un tipo de movimiento (id, moneda, tipo, nombre) */
    public function obtenerTipoMovimiento(int $id): ?array
    {
        $sql = "SELECT id_tipo_movimiento, UPPER(moneda) AS moneda, LOWER(tipo) AS tipo, nombre
                FROM tipos_movimiento
                WHERE id_tipo_movimiento = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    /** Tipos de movimiento activos específicos de FERRO (tipo_operacion_id=2) */
    public function obtenerTiposMovimientoActivos(): array
    {
        $sql = "SELECT id_tipo_movimiento, nombre, UPPER(moneda) AS moneda
                FROM tipos_movimiento
                WHERE estatus = 1 AND tipo_operacion_id = 2
                ORDER BY nombre ASC";
        try {
            return $this->selectAll($sql) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Sugerir operaciones FERRO por número (para tu input con autocompletar) */
    public function buscarOperacionesPorTerm(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];

        $sql = "SELECT 
                    ofv.id_operacion_ferro  AS id_operacion_ferro,
                    ofv.numero_operacion    AS numero_operacion,
                    c.nombre                AS cliente
                FROM operaciones_ferroviarias ofv
                LEFT JOIN clientes c ON c.id_cliente = ofv.cliente_id
                WHERE ofv.tipo_operacion_id = 2
                  AND ofv.numero_operacion LIKE ?
                ORDER BY ofv.numero_operacion ASC
                LIMIT 20";

        try {
            return $this->selectAll($sql, ["%{$term}%"]) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // === CRUD básico para costos a NIVEL OPERACIÓN FERRO ===

    /** Inserta costo (ferro). Devuelve ID insertado. */
    public function insertarCostoOperacion(array $d): int
    {
        $sql = "INSERT INTO costos_operacion_ferro
                (operacion_ferro_id, tipo_movimiento_id, monto, comentario, estatus, fecha_creacion)
                VALUES (?, ?, ?, ?, 1, NOW())";
        return (int)$this->insertar($sql, [
            (int)($d['operacion_ferro_id'] ?? $d['operacion_id'] ?? 0),
            (int)($d['tipo_movimiento_id'] ?? 0),
            (float)($d['monto'] ?? 0),
            (string)($d['comentario'] ?? '')
        ]);
    }

    /** Actualiza campos permitidos del costo (ferro). */
    public function actualizarCostoOperacion(int $id, array $d): bool
    {
        $sets   = [];
        $params = [];

        if (array_key_exists('tipo_movimiento_id', $d)) { $sets[] = "tipo_movimiento_id = ?"; $params[] = (int)$d['tipo_movimiento_id']; }
        if (array_key_exists('monto', $d))              { $sets[] = "monto = ?";              $params[] = (float)$d['monto']; }
        if (array_key_exists('comentario', $d))         { $sets[] = "comentario = ?";         $params[] = (string)$d['comentario']; }

        if (empty($sets)) return false;

        $sql = "UPDATE costos_operacion_ferro
                SET " . implode(', ', $sets) . "
                WHERE id_costo_ferro = ?
                LIMIT 1";
        $params[] = $id;

        return $this->save($sql, $params) === 1;
    }

    /** Obtiene un costo ferro por ID (con join a operación y moneda del tipo). */
    public function obtenerCostoOperacion(int $id): ?array
    {
        $sql = "SELECT 
                    cf.id_costo_ferro        AS row_id,
                    cf.operacion_ferro_id,
                    ofv.numero_operacion,
                    cf.tipo_movimiento_id,
                    tm.nombre                AS concepto,
                    UPPER(tm.moneda)         AS moneda,
                    cf.monto,
                    cf.comentario,
                    cf.estatus,
                    cf.fecha_creacion        AS fecha
                FROM costos_operacion_ferro cf
                LEFT JOIN operaciones_ferroviarias ofv ON ofv.id_operacion_ferro = cf.operacion_ferro_id
                LEFT JOIN tipos_movimiento tm          ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
                WHERE cf.id_costo_ferro = ?
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function desactivarCostoOperacion(int $id): bool
    {
        $sql = "UPDATE costos_operacion_ferro SET estatus = 0 WHERE id_costo_ferro = ? LIMIT 1";
        return $this->save($sql, [$id]) === 1;
    }

    public function reactivarCostoOperacion(int $id): bool
    {
        $sql = "UPDATE costos_operacion_ferro SET estatus = 1 WHERE id_costo_ferro = ? LIMIT 1";
        return $this->save($sql, [$id]) === 1;
    }
}
