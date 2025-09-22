<?php
class Operaciones_maritimas_costos_operacionModel extends Query
{
 /**
 * Cuenta el total de filas combinadas (operación + contenedores) para paginar.
 * Filtros: operacion_id, buscar, moneda('PESOS'|'DLLS'|''), tipo_movimiento_id, origen(''|'OPERACION'|'CONTENEDOR'), solo_activos
 */
public function contarCostosCombinados(array $f = []): int
{
    $operacionId = (int)($f['operacion_id'] ?? 0);
    if ($operacionId <= 0) return 0;

    $buscar      = trim((string)($f['buscar'] ?? ''));
    $moneda      = strtoupper(trim((string)($f['moneda'] ?? ''))); // 'PESOS'|'DLLS'|''
    $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
    $origen      = strtoupper(trim((string)($f['origen'] ?? ''))); // ''|'OPERACION'|'CONTENEDOR'
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // Si pidieran explícitamente contenedores, devuelve 0
    if ($origen === 'CONTENEDOR') return 0;

    $w  = ["co.operacion_id = ?"];
    $p  = [$operacionId];

    if ($soloActivos) {
        $w[] = "co.estatus = 1";
    }
    if ($buscar !== '') {
        $w[] = "(tm.nombre LIKE ? OR co.comentario LIKE ? OR o.numero_operacion LIKE ?)";
        array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
    }
    if ($moneda === 'PESOS' || $moneda === 'DLLS') {
        $w[] = "UPPER(tm.moneda) = ?";
        $p[] = $moneda;
    }
    if ($tipoId > 0) {
        $w[] = "co.tipo_movimiento_id = ?";
        $p[] = $tipoId;
    }

    $sql = "SELECT COUNT(*) AS total
            FROM costos_operacion co
            LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            LEFT JOIN operaciones o       ON o.id_operacion       = co.operacion_id
            WHERE " . implode(' AND ', $w);

    try {
        $row = $this->select($sql, $p);
        return (int)($row['total'] ?? 0);
    } catch (\Throwable $e) {
        return 0;
    }
}


/**
 * Lista filas combinadas (operación + contenedores) con paginación.
 * Devuelve columnas normalizadas para una sola tabla en el frontend.
 */
public function listarCostosCombinadosPaginado(int $page, int $perPage, array $f = []): array
{
    $page    = max(1, $page);
    $perPage = max(1, min(200, $perPage));
    $offset  = ($page - 1) * $perPage;

    $operacionId = (int)($f['operacion_id'] ?? 0);
    if ($operacionId <= 0) return [];

    $buscar      = trim((string)($f['buscar'] ?? ''));
    $moneda      = strtoupper(trim((string)($f['moneda'] ?? '')));
    $tipoId      = (int)($f['tipo_movimiento_id'] ?? ($f['tipo'] ?? 0));
    $origen      = strtoupper(trim((string)($f['origen'] ?? '')));
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // Si pidieran explícitamente contenedores, devuelve []
    if ($origen === 'CONTENEDOR') return [];

    $w  = ["co.operacion_id = ?"];
    $p  = [$operacionId];

    if ($soloActivos) {
        $w[] = "co.estatus = 1";
    }
    if ($buscar !== '') {
        $w[] = "(tm.nombre LIKE ? OR co.comentario LIKE ? OR o.numero_operacion LIKE ?)";
        array_push($p, "%$buscar%", "%$buscar%", "%$buscar%");
    }
    if ($moneda === 'PESOS' || $moneda === 'DLLS') {
        $w[] = "UPPER(tm.moneda) = ?";
        $p[] = $moneda;
    }
    if ($tipoId > 0) {
        $w[] = "co.tipo_movimiento_id = ?";
        $p[] = $tipoId;
    }

    $sql = "
        SELECT
            'OPERACION'                 AS origen,
            NULL                        AS contenedor_id,
            NULL                        AS contenedor,
            co.id_costo_operacion       AS row_id,
            o.id_operacion              AS operacion_id,
            o.numero_operacion          AS numero_operacion,
            tm.id_tipo_movimiento       AS tipo_movimiento_id,
            tm.nombre                   AS concepto,
            LOWER(tm.tipo)              AS naturaleza, 
            UPPER(tm.moneda)            AS moneda,
            co.monto                    AS monto,
            co.comentario               AS comentario,
            co.fecha_creacion           AS fecha
        FROM costos_operacion co
        LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
        LEFT JOIN operaciones o       ON o.id_operacion       = co.operacion_id
        WHERE " . implode(' AND ', $w) . "
        ORDER BY co.fecha_creacion DESC, co.id_costo_operacion DESC
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
    $operacionId = (int)($f['operacion_id'] ?? 0);
    if ($operacionId <= 0) {
        return [
            'total_operacion'    => 0.0,
            'total_contenedores' => 0.0,
            'total_general'      => 0.0,
            'total_abonos_operacion'    => 0.0,
            'total_abonos_contenedores' => 0.0,
        ];
    }
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // GASTOS de operación
    $sqlOpGasto = "SELECT COALESCE(SUM(co.monto),0) AS total
                   FROM costos_operacion co
                   INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
                   WHERE co.operacion_id = ? " . ($soloActivos ? "AND co.estatus=1 " : "") . " AND tm.tipo='gasto'";
    $rowOpG = $this->select($sqlOpGasto, [$operacionId]);
    $totalOp = (float)($rowOpG['total'] ?? 0);

    // ABONOS de operación
    $sqlOpAbono = "SELECT COALESCE(SUM(co.monto),0) AS total
                   FROM costos_operacion co
                   INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
                   WHERE co.operacion_id = ? " . ($soloActivos ? "AND co.estatus=1 " : "") . " AND tm.tipo='abono'";
    $rowOpA = $this->select($sqlOpAbono, [$operacionId]);
    $totalAbOp = (float)($rowOpA['total'] ?? 0);

    return [
        'total_operacion'    => $totalOp,
        'total_contenedores' => 0.0,                 // <- ya no contamos contenedores
        'total_general'      => $totalOp,            // <- igual a operación
        'total_abonos_operacion'    => $totalAbOp,
        'total_abonos_contenedores' => 0.0,          // <- cero
    ];
}

public function abonosCombinadosDetallado(array $f = []): array
{
    $operacionId = (int)($f['operacion_id'] ?? 0);
    if ($operacionId <= 0) {
        return [
            'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(co.monto),0) AS total
              FROM costos_operacion co
              INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
              WHERE co.operacion_id = ? " . ($soloActivos ? "AND co.estatus=1 " : "") . " AND tm.tipo='abono'
              GROUP BY UPPER(tm.moneda)";
    $rowsOp = $this->selectAll($sqlOp, [$operacionId]) ?: [];
    $op = ['PESOS'=>0.0, 'DLLS'=>0.0];
    foreach ($rowsOp as $r) {
        $m = strtoupper((string)($r['moneda'] ?? ''));
        $t = (float)($r['total'] ?? 0);
        if ($m === 'PESOS') $op['PESOS'] += $t;
        if ($m === 'DLLS')  $op['DLLS']  += $t;
    }

    return [
        'operacion'    => $op,
        'contenedores' => ['PESOS'=>0.0, 'DLLS'=>0.0], // <- cero
    ];
}

// En tu Model:
public function obtenerTipoMovimiento(int $id): ?array {
  $sql = "SELECT id_tipo_movimiento, UPPER(moneda) AS moneda, LOWER(tipo) AS tipo, nombre
          FROM tipos_movimiento
          WHERE id_tipo_movimiento = ? AND estatus = 1
          LIMIT 1";
  $row = $this->select($sql, [$id]);
  return $row ?: null;
}

 public function buscarOperacionesPorTerm(string $term): array
{
    $term = trim($term);
    if ($term === '') return [];

    $sql = "SELECT 
                o.id_operacion, 
                o.numero_operacion, 
                c.nombre AS cliente
            FROM operaciones o
            LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
            WHERE o.tipo_operacion_id = 1              -- solo marítimas
              AND o.numero_operacion LIKE ?
            ORDER BY o.numero_operacion ASC
            LIMIT 20";

    try {
        return $this->selectAll($sql, ["%{$term}%"]) ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}


public function totalesCostosCombinadosDetallado(array $f = []): array
{
    $operacionId = (int)($f['operacion_id'] ?? 0);
    if ($operacionId <= 0) {
        return [
            'operacion'    => ['PESOS' => 0.0, 'DLLS' => 0.0],
            'contenedores' => ['PESOS' => 0.0, 'DLLS' => 0.0],
        ];
    }
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // SOLO GASTOS por moneda (nivel operación)
    $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(co.monto),0) AS total
              FROM costos_operacion co
              INNER JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
              WHERE co.operacion_id = ? " . ($soloActivos ? "AND co.estatus = 1 " : "") . " AND tm.tipo='gasto'
              GROUP BY UPPER(tm.moneda)";
    $rowsOp = $this->selectAll($sqlOp, [$operacionId]) ?: [];
    $op = ['PESOS'=>0.0, 'DLLS'=>0.0];
    foreach ($rowsOp as $r) {
        $m = strtoupper((string)($r['moneda'] ?? ''));
        $t = (float)($r['total'] ?? 0);
        if ($m === 'PESOS') $op['PESOS'] += $t;
        if ($m === 'DLLS')  $op['DLLS']  += $t;
    }

    return [
        'operacion'    => $op,
        'contenedores' => ['PESOS'=>0.0, 'DLLS'=>0.0], // <- cero
    ];
}



public function obtenerTiposMovimientoActivos(): array
{
    $sql = "SELECT 
                id_tipo_movimiento, 
                nombre, 
                UPPER(moneda) AS moneda
            FROM tipos_movimiento
            WHERE estatus = 1 and tipo_operacion_id=1
            ORDER BY nombre ASC";
        try {
            return $this->selectAll($sql) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
}

// === CRUD básico para costos a NIVEL OPERACIÓN ===

/**
 * Inserta un costo de operación.
 * Tabla esperada: costos_operacion(id_costo_operacion PK AI, operacion_id, tipo_movimiento_id, monto, comentario, estatus, fecha_creacion)
 * Nota: la MONEDA se deriva de tipos_movimiento.moneda; no se guarda aquí.
 * @return int ID insertado (0 si falla)
 */
public function insertarCostoOperacion(array $d): int
{
    $sql = "INSERT INTO costos_operacion
            (operacion_id, tipo_movimiento_id, monto, comentario, estatus, fecha_creacion)
            VALUES (?, ?, ?, ?, 1, NOW())";

    return (int)$this->insertar($sql, [
        (int)($d['operacion_id'] ?? 0),
        (int)($d['tipo_movimiento_id'] ?? 0),
        (float)($d['monto'] ?? 0),
        (string)($d['comentario'] ?? '')
    ]);
}

/**
 * Actualiza campos de un costo de operación.
 * @return bool true si actualizó, false si no
 */
public function actualizarCostoOperacion(int $id, array $d): bool
{
    // Construcción dinámica SOLO de los campos permitidos
    $sets   = [];
    $params = [];

    if (array_key_exists('tipo_movimiento_id', $d)) { $sets[] = "tipo_movimiento_id = ?"; $params[] = (int)$d['tipo_movimiento_id']; }
    if (array_key_exists('monto', $d))              { $sets[] = "monto = ?";              $params[] = (float)$d['monto']; }
    if (array_key_exists('comentario', $d))         { $sets[] = "comentario = ?";         $params[] = (string)$d['comentario']; }

    if (empty($sets)) return false;

    $sql = "UPDATE costos_operacion
            SET " . implode(', ', $sets) . "
            WHERE id_costo_operacion = ?
            LIMIT 1";
    $params[] = $id;

    return $this->save($sql, $params) === 1;
}

/**
 * Obtiene un costo de operación (join para traer número de operación y la moneda del tipo).
 * @return array|null
 */
public function obtenerCostoOperacion(int $id): ?array
{
    $sql = "SELECT 
                co.id_costo_operacion   AS row_id,
                co.operacion_id,
                o.numero_operacion,
                co.tipo_movimiento_id,
                tm.nombre               AS concepto,
                UPPER(tm.moneda)        AS moneda,
                co.monto,
                co.comentario,
                co.estatus,
                co.fecha_creacion       AS fecha
            FROM costos_operacion co
            LEFT JOIN operaciones o       ON o.id_operacion = co.operacion_id
            LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            WHERE co.id_costo_operacion = ?
            LIMIT 1";

    $row = $this->select($sql, [$id]);
    return $row ?: null;
}
public function desactivarCostoOperacion(int $id): bool
{
    $sql = "UPDATE costos_operacion SET estatus = 0 WHERE id_costo_operacion = ? LIMIT 1";
    return $this->save($sql, [$id]) === 1;
}

public function reactivarCostoOperacion(int $id): bool
{
    $sql = "UPDATE costos_operacion SET estatus = 1 WHERE id_costo_operacion = ? LIMIT 1";
    return $this->save($sql, [$id]) === 1;
}


}
