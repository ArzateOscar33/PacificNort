<?php
class Operaciones_maritimo_ferro_costos_contenedorModel extends Query
{
// Dentro de Operaciones_maritimo_ferro_costos_contenedorModel
private function resolveFuente(array $f): array
{
    // 'F' por defecto para mantener compatibilidad si no te mandan la fuente
    $fuente = strtoupper(trim((string)($f['fuente'] ?? 'F')));
    if ($fuente !== 'MF') $fuente = 'F';

    if ($fuente === 'F') {
        return [
            'fuente'            => 'F',
            'tabla_costos'      => 'costos_operacion_ferro',
            'pk_costo'          => 'id_costo_ferro',
            'fk_operacion'      => 'operacion_ferro_id',
            'tabla_operaciones' => 'operaciones_ferroviarias',
            'pk_operacion'      => 'id_operacion_ferro',
            'num_operacion'     => 'numero_operacion',
            'tipo_operacion_id' => 2,   // para filtrar tipos_movimiento
        ];
    }

    // MF
    return [
        'fuente'            => 'MF',
        'tabla_costos'      => 'costos_operacion',
        'pk_costo'          => 'id_costo_operacion',
        'fk_operacion'      => 'operacion_id',
        'tabla_operaciones' => 'operaciones',
        'pk_operacion'      => 'id_operacion',
        'num_operacion'     => 'numero_operacion',
        'tipo_operacion_id' => 1,     // para filtrar tipos_movimiento
    ];
}
private function getOperacionId(array $f, string $fuente, string $fkName): int
{
    // Compatibilidad: si viene 'operacion_ferro_id' úsalo; si viene 'operacion_id', también.
    // Para MF se espera 'operacion_id'. Para F se espera 'operacion_ferro_id'.
    if ($fuente === 'F') {
        return (int)($f['operacion_ferro_id'] ?? $f['operacion_id'] ?? 0);
    }
    // MF
    return (int)($f['operacion_id'] ?? $f['operacion_ferro_id'] ?? 0);
}
public function buscarOperacionesCombinadasPorTerm(string $term): array
{
    $term = trim($term);
    if ($term === '') return [];

    // FO
    $sqlF = "SELECT 
                ofv.id_operacion_ferro AS id,
                ofv.numero_operacion   AS numero_operacion,
                c.nombre               AS cliente,
                'F'                    AS fuente
             FROM operaciones_ferroviarias ofv
             LEFT JOIN clientes c ON c.id_cliente = ofv.cliente_id
             WHERE ofv.tipo_operacion_id = 2
               AND ofv.numero_operacion LIKE ?
             ORDER BY ofv.numero_operacion ASC
             LIMIT 20";

    // MF
    $sqlMF = "SELECT 
                o.id_operacion       AS id,
                o.numero_operacion   AS numero_operacion,
                c.nombre             AS cliente,
                'MF'                 AS fuente
              FROM operaciones o
              LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
              WHERE o.tipo_operacion_id = 11
                AND o.numero_operacion LIKE ?
              ORDER BY o.numero_operacion ASC
              LIMIT 20";

    try {
        $rowsF  = $this->selectAll($sqlF,  ["%{$term}%"]) ?: [];
        $rowsMF = $this->selectAll($sqlMF, ["%{$term}%"]) ?: [];
        // Puedes mezclar y si quieres limitar a 20 totales, haz un array_slice
        return array_merge($rowsF, $rowsMF);
    } catch (\Throwable $e) {
        return [];
    }
}
public function obtenerTiposMovimientoActivosPorFuente(string $fuente = 'F'): array
{
    $cfg = $this->resolveFuente(['fuente' => $fuente]);
    $sql = "SELECT id_tipo_movimiento, nombre, UPPER(moneda) AS moneda
            FROM tipos_movimiento
            WHERE estatus = 1 AND tipo_operacion_id = ?
            ORDER BY nombre ASC";
    try {
        return $this->selectAll($sql, [$cfg['tipo_operacion_id']]) ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}

// (Opcional) Mantén la firma vieja apuntando a FO:
public function obtenerTiposMovimientoActivos(): array
{
    return $this->obtenerTiposMovimientoActivosPorFuente('F');
}
public function contarCostosCombinados(array $f = []): int
{
    $cfg = $this->resolveFuente($f);
    $opId = $this->getOperacionId($f, $cfg['fuente'], $cfg['fk_operacion']);
    if ($opId <= 0) return 0;

    $buscar      = trim((string)($f['buscar'] ?? ''));
    $moneda      = strtoupper(trim((string)($f['moneda'] ?? ''))); // 'PESOS'|'DLLS'|''
    $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    $aliasCostos = 'c';
    $aliasTipos  = 'tm';
    $aliasOps    = 'op';

    $w = ["{$aliasCostos}.{$cfg['fk_operacion']} = ?"];
    $p = [$opId];

    if ($soloActivos) { $w[] = "{$aliasCostos}.estatus = 1"; }
    if ($buscar !== '') {
        $w[] = "({$aliasTipos}.nombre LIKE ? OR {$aliasCostos}.comentario LIKE ? OR {$aliasOps}.{$cfg['num_operacion']} LIKE ?)";
        array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
    }
    if ($moneda === 'PESOS' || $moneda === 'DLLS') {
        $w[] = "UPPER({$aliasTipos}.moneda) = ?";
        $p[] = $moneda;
    }
    if ($tipoId > 0) {
        $w[] = "{$aliasCostos}.tipo_movimiento_id = ?";
        $p[] = $tipoId;
    }

    $sql = "SELECT COUNT(*) AS total
            FROM {$cfg['tabla_costos']} {$aliasCostos}
            LEFT JOIN tipos_movimiento {$aliasTipos} ON {$aliasTipos}.id_tipo_movimiento = {$aliasCostos}.tipo_movimiento_id
            LEFT JOIN {$cfg['tabla_operaciones']} {$aliasOps} ON {$aliasOps}.{$cfg['pk_operacion']} = {$aliasCostos}.{$cfg['fk_operacion']}
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

    $cfg = $this->resolveFuente($f);
    $opId = $this->getOperacionId($f, $cfg['fuente'], $cfg['fk_operacion']);
    if ($opId <= 0) return [];

    $buscar      = trim((string)($f['buscar'] ?? ''));
    $moneda      = strtoupper(trim((string)($f['moneda'] ?? '')));
    $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    $c = 'c'; $tm = 'tm'; $op = 'op';

    $w = ["{$c}.{$cfg['fk_operacion']} = ?"];
    $p = [$opId];

    if ($soloActivos) { $w[] = "{$c}.estatus = 1"; }
    if ($buscar !== '') {
        $w[] = "({$tm}.nombre LIKE ? OR {$c}.comentario LIKE ? OR {$op}.{$cfg['num_operacion']} LIKE ?)";
        array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
    }
    if ($moneda === 'PESOS' || $moneda === 'DLLS') {
        $w[] = "UPPER({$tm}.moneda) = ?";
        $p[] = $moneda;
    }
    if ($tipoId > 0) {
        $w[] = "{$c}.tipo_movimiento_id = ?";
        $p[] = $tipoId;
    }

    $sql = "
        SELECT
            'OPERACION'                        AS origen,
            NULL                               AS contenedor_id,
            NULL                               AS contenedor,
            {$c}.{$cfg['pk_costo']}            AS row_id,
            {$op}.{$cfg['pk_operacion']}       AS operacion_id,         -- clave normalizada
            {$op}.{$cfg['num_operacion']}      AS numero_operacion,
            {$tm}.id_tipo_movimiento           AS tipo_movimiento_id,
            {$tm}.nombre                       AS concepto,
            LOWER({$tm}.tipo)                  AS naturaleza,  -- 'gasto' | 'abono'
            UPPER({$tm}.moneda)                AS moneda,
            {$c}.monto                         AS monto,
            {$c}.comentario                    AS comentario,
            {$c}.fecha_creacion                AS fecha,
            '{$cfg['fuente']}'                 AS fuente                 -- MUY ÚTIL EN EL FRONT
        FROM {$cfg['tabla_costos']} {$c}
        LEFT JOIN tipos_movimiento {$tm} ON {$tm}.id_tipo_movimiento = {$c}.tipo_movimiento_id
        LEFT JOIN {$cfg['tabla_operaciones']} {$op} ON {$op}.{$cfg['pk_operacion']} = {$c}.{$cfg['fk_operacion']}
        WHERE " . implode(' AND ', $w) . "
        ORDER BY {$c}.fecha_creacion DESC, {$c}.{$cfg['pk_costo']} DESC
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
    $cfg = $this->resolveFuente($f);
    $opId = $this->getOperacionId($f, $cfg['fuente'], $cfg['fk_operacion']);
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

    // GASTOS
    $sqlG = "SELECT COALESCE(SUM(c.monto),0) AS total
             FROM {$cfg['tabla_costos']} c
             INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
             WHERE c.{$cfg['fk_operacion']} = ? " . ($soloActivos ? "AND c.estatus=1 " : "") . " AND tm.tipo='gasto'";
    $rowG = $this->select($sqlG, [$opId]);
    $totalOp = (float)($rowG['total'] ?? 0);

    // ABONOS
    $sqlA = "SELECT COALESCE(SUM(c.monto),0) AS total
             FROM {$cfg['tabla_costos']} c
             INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
             WHERE c.{$cfg['fk_operacion']} = ? " . ($soloActivos ? "AND c.estatus=1 " : "") . " AND tm.tipo='abono'";
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
    $cfg = $this->resolveFuente($f);
    $opId = $this->getOperacionId($f, $cfg['fuente'], $cfg['fk_operacion']);
    if ($opId <= 0) {
        return [
            'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(c.monto),0) AS total
              FROM {$cfg['tabla_costos']} c
              INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
              WHERE c.{$cfg['fk_operacion']} = ? " . ($soloActivos ? "AND c.estatus=1 " : "") . " AND tm.tipo='abono'
              GROUP BY UPPER(tm.moneda)";
    $rowsOp = $this->selectAll($sqlOp, [$opId]) ?: [];
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

public function totalesCostosCombinadosDetallado(array $f = []): array
{
    $cfg = $this->resolveFuente($f);
    $opId = $this->getOperacionId($f, $cfg['fuente'], $cfg['fk_operacion']);
    if ($opId <= 0) {
        return [
            'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(c.monto),0) AS total
              FROM {$cfg['tabla_costos']} c
              INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id
              WHERE c.{$cfg['fk_operacion']} = ? " . ($soloActivos ? "AND c.estatus=1 " : "") . " AND tm.tipo='gasto'
              GROUP BY UPPER(tm.moneda)";
    $rowsOp = $this->selectAll($sqlOp, [$opId]) ?: [];
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
public function insertarCostoOperacionCombinado(array $d): int
{
    $cfg = $this->resolveFuente($d);
    $opId = $this->getOperacionId($d, $cfg['fuente'], $cfg['fk_operacion']);
    if ($opId <= 0) return 0;

    $sql = "INSERT INTO {$cfg['tabla_costos']}
            ({$cfg['fk_operacion']}, tipo_movimiento_id, monto, comentario, estatus, fecha_creacion)
            VALUES (?, ?, ?, ?, 1, NOW())";
    return (int)$this->insertar($sql, [
        $opId,
        (int)($d['tipo_movimiento_id'] ?? 0),
        (float)($d['monto'] ?? 0),
        (string)($d['comentario'] ?? '')
    ]);
}

public function actualizarCostoOperacionCombinado(int $id, array $d): bool
{
    $cfg = $this->resolveFuente($d);
    $sets   = [];
    $params = [];

    if (array_key_exists('tipo_movimiento_id', $d)) { $sets[] = "tipo_movimiento_id = ?"; $params[] = (int)$d['tipo_movimiento_id']; }
    if (array_key_exists('monto', $d))              { $sets[] = "monto = ?";              $params[] = (float)$d['monto']; }
    if (array_key_exists('comentario', $d))         { $sets[] = "comentario = ?";         $params[] = (string)$d['comentario']; }
    if (empty($sets)) return false;

    $sql = "UPDATE {$cfg['tabla_costos']}
            SET " . implode(', ', $sets) . "
            WHERE {$cfg['pk_costo']} = ?
            LIMIT 1";
    $params[] = $id;

    return $this->save($sql, $params) === 1;
}

public function obtenerCostoOperacionCombinado(int $id, string $fuente = 'F'): ?array
{
    $cfg = $this->resolveFuente(['fuente' => $fuente]);
    $sql = "SELECT 
                c.{$cfg['pk_costo']}         AS row_id,
                c.{$cfg['fk_operacion']}     AS operacion_id,
                op.{$cfg['num_operacion']}   AS numero_operacion,
                c.tipo_movimiento_id,
                tm.nombre                    AS concepto,
                UPPER(tm.moneda)             AS moneda,
                c.monto,
                c.comentario,
                c.estatus,
                c.fecha_creacion             AS fecha,
                '{$cfg['fuente']}'           AS fuente
            FROM {$cfg['tabla_costos']} c
            LEFT JOIN {$cfg['tabla_operaciones']} op ON op.{$cfg['pk_operacion']} = c.{$cfg['fk_operacion']}
            LEFT JOIN tipos_movimiento tm          ON tm.id_tipo_movimiento = c.tipo_movimiento_id
            WHERE c.{$cfg['pk_costo']} = ?
            LIMIT 1";
    $row = $this->select($sql, [$id]);
    return $row ?: null;
}

public function desactivarCostoOperacionCombinado(int $id, string $fuente = 'F'): bool
{
    $cfg = $this->resolveFuente(['fuente' => $fuente]);
    $sql = "UPDATE {$cfg['tabla_costos']} SET estatus = 0 WHERE {$cfg['pk_costo']} = ? LIMIT 1";
    return $this->save($sql, [$id]) === 1;
}

public function reactivarCostoOperacionCombinado(int $id, string $fuente = 'F'): bool
{
    $cfg = $this->resolveFuente(['fuente' => $fuente]);
    $sql = "UPDATE {$cfg['tabla_costos']} SET estatus = 1 WHERE {$cfg['pk_costo']} = ? LIMIT 1";
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
    $cfg = $this->resolveFuente($f);
    $opId = $this->getOperacionId($f, $cfg['fuente'], $cfg['fk_operacion']);
    if ($opId <= 0) return null;

    try {
        if ($cfg['fuente'] === 'F') {
            // FO: operacion_ferro_id -> contenedor_maritimo_ferro -> contenedores_fisicos.numero_ferro
            $sql = "SELECT 
                        cf.id_fisico              AS contenedor_fisico_id,
                        cf.numero_ferro           AS numero,
                        cmf.id                    AS puente_id
                    FROM contenedor_maritimo_ferro cmf
                    LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = cmf.contenedor_fisico_id
                    WHERE cmf.operacion_ferro_id = ? AND cmf.estatus = 1
                    ORDER BY cmf.fecha_asignacion DESC
                    LIMIT 1";
            $row = $this->select($sql, [$opId]) ?: null;
            if (!$row || empty($row['numero'])) return null;
            return [
                'fuente'   => 'F',
                'numero'   => $row['numero'],
                'tipo'     => 'FERRO',
                'ids'      => [
                    'contenedor_fisico_id' => (int)$row['contenedor_fisico_id'],
                    'puente_id'            => (int)$row['puente_id'],
                    'operacion_ferro_id'   => $opId,
                ],
            ];
        } else {
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
        }
    } catch (\Throwable $e) {
        return null;
    }
}

}
 