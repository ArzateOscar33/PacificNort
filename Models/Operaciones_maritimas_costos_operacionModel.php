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
    $tipoId      = (int)($f['tipo_movimiento_id'] ?? 0);
    $origen      = strtoupper(trim((string)($f['origen'] ?? ''))); // ''|'OPERACION'|'CONTENEDOR'
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // Filtros por parte
    $wOp  = ["coo.operacion_id = ?"];  $pOp  = [$operacionId];
    $wCon = ["o.id_operacion   = ?"];  $pCon = [$operacionId];

    if ($soloActivos) {
        $wOp[] = "coo.estatus = 1";
        // cco no tiene estatus (si lo agregas, filtra aquí también)
    }
    if ($buscar !== '') {
        $wOp[]  = "(tm.nombre LIKE ? OR coo.comentario LIKE ? OR o.numero_operacion LIKE ?)";
        array_push($pOp, "%$buscar%", "%$buscar%", "%$buscar%");

        $wCon[] = "(tm.nombre LIKE ? OR cco.comentario LIKE ? OR cf.numero_ferro LIKE ? OR o.numero_operacion LIKE ?)";
        array_push($pCon, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
    }
    if ($moneda === 'PESOS' || $moneda === 'DLLS') {
        $wOp[]  = "UPPER(tm.moneda) = ?";  $pOp[]  = $moneda;
        $wCon[] = "UPPER(tm.moneda) = ?";  $pCon[] = $moneda;
    }
    if ($tipoId > 0) {
        $wOp[]  = "coo.tipo_movimiento_id = ?";  $pOp[]  = $tipoId;
        $wCon[] = "cco.tipo_movimiento_id = ?";  $pCon[] = $tipoId;
    }

    $parts  = [];
    $params = [];

    if ($origen === '' || $origen === 'OPERACION') {
        $parts[]  = "
            SELECT coo.id_costo_operacion AS row_id
            FROM costos_operacion coo
            LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = coo.tipo_movimiento_id
            LEFT JOIN operaciones o       ON o.id_operacion       = coo.operacion_id
            WHERE " . implode(' AND ', $wOp);
        $params = array_merge($params, $pOp);
    }
    if ($origen === '' || $origen === 'CONTENEDOR') {
        $parts[]  = "
            SELECT cco.id_costo_contenedor AS row_id
            FROM costos_contenedor_operacion cco
            LEFT JOIN contenedores_operacion co ON co.id_contenedor = cco.contenedor_operacion_id
            LEFT JOIN operaciones o             ON o.id_operacion   = co.operacion_id
            LEFT JOIN contenedores_fisicos cf   ON cf.id_fisico     = co.id_fisico
            LEFT JOIN tipos_movimiento tm       ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
            WHERE " . implode(' AND ', $wCon);
        $params = array_merge($params, $pCon);
    }

    if (empty($parts)) return 0;

    $sql = "SELECT COUNT(*) AS total FROM (" . implode(" UNION ALL ", $parts) . ") t";

    try {
        $row = $this->select($sql, $params);
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
    $moneda      = strtoupper(trim((string)($f['moneda'] ?? ''))); // 'PESOS'|'DLLS'|''
    $tipoId      = (int)($f['tipo_movimiento_id'] ?? 0);
    $origen      = strtoupper(trim((string)($f['origen'] ?? ''))); // ''|'OPERACION'|'CONTENEDOR'
    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // WHERE + params por parte
    $wOp  = ["co.operacion_id = ?"];   $pOp  = [$operacionId];
    $wCon = ["o.id_operacion   = ?"];  $pCon = [$operacionId];

    if ($soloActivos) {
        $wOp[] = "co.estatus = 1";
    }
    if ($buscar !== '') {
        $wOp[]  = "(tm.nombre LIKE ? OR co.comentario LIKE ? OR o.numero_operacion LIKE ?)";
        array_push($pOp, "%$buscar%", "%$buscar%", "%$buscar%");

        $wCon[] = "(tm.nombre LIKE ? OR cco.comentario LIKE ? OR cf.numero_ferro LIKE ? OR o.numero_operacion LIKE ?)";
        array_push($pCon, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
    }
    if ($moneda === 'PESOS' || $moneda === 'DLLS') {
        $wOp[]  = "UPPER(tm.moneda) = ?";  $pOp[]  = $moneda;
        $wCon[] = "UPPER(tm.moneda) = ?";  $pCon[] = $moneda;
    }
    if ($tipoId > 0) {
        $wOp[]  = "co.tipo_movimiento_id = ?";  $pOp[]  = $tipoId;
        $wCon[] = "cco.tipo_movimiento_id = ?";  $pCon[] = $tipoId;
    }

    // SELECTs normalizados
    $selOp = "
        SELECT
            'OPERACION'                 AS origen,
            NULL                        AS contenedor_id,
            NULL                        AS contenedor,
            co.id_costo_operacion       AS row_id,
            o.id_operacion              AS operacion_id,
            o.numero_operacion          AS numero_operacion,
            tm.id_tipo_movimiento       AS tipo_movimiento_id,
            tm.nombre                   AS concepto,
            UPPER(tm.moneda)            AS moneda,
            co.monto                    AS monto,
            co.comentario               AS comentario,
            co.fecha_creacion           AS fecha
        FROM costos_operacion co
        LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
        LEFT JOIN operaciones o       ON o.id_operacion       = co.operacion_id
        WHERE " . implode(' AND ', $wOp);

    $selCon = "
        SELECT
            'CONTENEDOR'                AS origen,
            co.id_contenedor            AS contenedor_id,
            cf.numero_ferro             AS contenedor,
            cco.id_costo_contenedor     AS row_id,
            o.id_operacion              AS operacion_id,
            o.numero_operacion          AS numero_operacion,
            tm.id_tipo_movimiento       AS tipo_movimiento_id,
            tm.nombre                   AS concepto,
            UPPER(tm.moneda)            AS moneda,
            cco.monto                   AS monto,
            cco.comentario              AS comentario,
            cco.fecha_creacion          AS fecha
        FROM costos_contenedor_operacion cco
        LEFT JOIN contenedores_operacion co ON co.id_contenedor = cco.contenedor_operacion_id
        LEFT JOIN operaciones o             ON o.id_operacion   = co.operacion_id
        LEFT JOIN contenedores_fisicos cf   ON cf.id_fisico     = co.id_fisico
        LEFT JOIN tipos_movimiento tm       ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
        WHERE " . implode(' AND ', $wCon);

    $parts  = [];
    $params = [];
    if ($origen === '' || $origen === 'OPERACION') {
        $parts[]  = $selOp;
        $params   = array_merge($params, $pOp);
    }
    if ($origen === '' || $origen === 'CONTENEDOR') {
        $parts[]  = $selCon;
        $params   = array_merge($params, $pCon);
    }
    if (empty($parts)) return [];

    $sql = "
        SELECT *
        FROM (" . implode(" UNION ALL ", $parts) . ") t
        ORDER BY t.fecha DESC, t.row_id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";

    try {
        $rows = $this->selectAll($sql, $params);
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
        ];
    }

    $soloActivos = (bool)($f['solo_activos'] ?? true);

    // Total de costos a NIVEL OPERACIÓN
    $whereOp  = "co.operacion_id = ?";
    $paramsOp = [$operacionId];
    if ($soloActivos) {
        $whereOp .= " AND co.estatus = 1";
    }
    $sqlOp = "SELECT COALESCE(SUM(co.monto), 0) AS total
              FROM costos_operacion co
              WHERE {$whereOp}";
    $rowOp = $this->select($sqlOp, $paramsOp);
    $totalOp = (float)($rowOp['total'] ?? 0);

    // Total de costos por CONTENEDOR ligados a la operación
    $sqlCon = "SELECT COALESCE(SUM(cco.monto), 0) AS total
               FROM costos_contenedor_operacion cco
               INNER JOIN contenedores_operacion x ON x.id_contenedor = cco.contenedor_operacion_id
               WHERE x.operacion_id = ?";
    // Si más adelante agregas estatus en costos_contenedor_operacion, filtra aquí también.
    $rowCon = $this->select($sqlCon, [$operacionId]);
    $totalCon = (float)($rowCon['total'] ?? 0);

    return [
        'total_operacion'    => $totalOp,
        'total_contenedores' => $totalCon,
        'total_general'      => $totalOp + $totalCon,
    ];
}
public function buscarOperacionesPorTerm(string $term): array
    {
        // Ajusta los nombres de tablas/columnas si en tu esquema difieren
        $sql = "SELECT 
                    o.id_operacion, 
                    o.numero_operacion, 
                    c.nombre AS cliente
                FROM operaciones o
                LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
                WHERE o.numero_operacion LIKE ?
                ORDER BY o.numero_operacion ASC
                LIMIT 20";

        try {
            return $this->selectAll($sql, ["%{$term}%"]) ?: [];
        } catch (\Throwable $e) {
            // Log si lo manejas y regresa vacío para no romper el autocompletar
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

    // --- Operación por moneda ---
    $sqlOp = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(co.monto),0) AS total
              FROM costos_operacion co
              LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = co.tipo_movimiento_id
              WHERE co.operacion_id = ?" . ($soloActivos ? " AND co.estatus = 1" : "") . "
              GROUP BY UPPER(tm.moneda)";
    $rowsOp = $this->selectAll($sqlOp, [$operacionId]) ?: [];

    $op = ['PESOS'=>0.0, 'DLLS'=>0.0];
    foreach ($rowsOp as $r) {
        $m = strtoupper((string)($r['moneda'] ?? ''));
        $t = (float)($r['total'] ?? 0);
        if ($m === 'PESOS') $op['PESOS'] += $t;
        if ($m === 'DLLS')  $op['DLLS']  += $t;
    }

    // --- Contenedores por moneda ---
    $sqlCon = "SELECT UPPER(tm.moneda) AS moneda, COALESCE(SUM(cco.monto),0) AS total
               FROM costos_contenedor_operacion cco
               LEFT JOIN contenedores_operacion x ON x.id_contenedor = cco.contenedor_operacion_id
               LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
               WHERE x.operacion_id = ?
               GROUP BY UPPER(tm.moneda)";
    $rowsCon = $this->selectAll($sqlCon, [$operacionId]) ?: [];

    $con = ['PESOS'=>0.0, 'DLLS'=>0.0];
    foreach ($rowsCon as $r) {
        $m = strtoupper((string)($r['moneda'] ?? ''));
        $t = (float)($r['total'] ?? 0);
        if ($m === 'PESOS') $con['PESOS'] += $t;
        if ($m === 'DLLS')  $con['DLLS']  += $t;
    }

    return [
        'operacion'    => $op,
        'contenedores' => $con,
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


}
