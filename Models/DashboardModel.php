<?php
class DashboardModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    // Operaciones marítimas activas (tabla operaciones)
    public function kpiOperacionesActivas(): int
    {
        $sql = "SELECT COUNT(*) AS n
                FROM operaciones
                WHERE estatus_id IN (1,5,9)"; // Pendiente, En Revisión, Abierta
        $row = $this->select($sql);
        return $row ? (int)$row['n'] : 0;
    }

    // ✅ NUEVO: Operaciones FO (ferro/terrestres) activas (tabla operaciones_ferroviarias)
    public function kpiOperacionesFOActivas(): int
    {
        $sql = "SELECT COUNT(*) AS n
                FROM operaciones_ferroviarias
                WHERE estatus_id IN (1,5,9)"; // mismos estatus activos que marítimo
        $row = $this->select($sql);
        return $row ? (int)$row['n'] : 0;
    }

    public function kpiContenedoresActivos(): int
    {
        $sql = "
      SELECT
        COALESCE((
          SELECT COUNT(*)
          FROM contenedores_operacion co
          JOIN operaciones o ON o.id_operacion = co.operacion_id
          WHERE co.estatus = 1 AND o.estatus_id IN (1,5,9)
        ),0)
        +
        COALESCE((
          SELECT COUNT(*)
          FROM contenedores_maritimos_operacion cmo
          JOIN operaciones o ON o.id_operacion = cmo.operacion_id
          WHERE o.estatus_id IN (1,5,9)
        ),0) AS total
    ";
        $row = $this->select($sql);
        return $row ? (int)$row['total'] : 0;
    }

    public function kpiEventosHechosTotal(): array
    {
        $sql = "
SELECT
  SUM(CASE WHEN e.estatus = 1 THEN 1 ELSE 0 END)                           AS hechos,
  SUM(CASE WHEN e.estatus IN (0,1) THEN 1 ELSE 0 END)                       AS total
FROM eventos_logisticos e
JOIN operaciones o ON o.id_operacion = e.operacion_id
WHERE o.estatus_id IN (1,5,9);
    ";
        $row = $this->select($sql);
        return [
            'hechos' => (int)($row['hechos'] ?? 0),
            'total'  => (int)($row['total'] ?? 0),
        ];
    }

    // Nº de clientes distintos con operaciones activas
    public function kpiClientesActivos(): int
    {
        $sql = "SELECT COUNT(DISTINCT o.cliente_id) AS n
            FROM operaciones o
            WHERE o.estatus_id IN (1,5,9) AND o.cliente_id IS NOT NULL";
        $row = $this->select($sql);
        return $row ? (int)$row['n'] : 0;
    }

    // Nº de operaciones activas con ETA en los próximos N días (default 7)
    public function kpiOpsProximasETA(int $dias = 7): int
    {
        $sql = "SELECT COUNT(*) AS n
            FROM operaciones o
            WHERE o.estatus_id IN (1,5,9)
              AND o.eta IS NOT NULL
              AND DATE(o.eta) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
        $row = $this->select($sql, [$dias]);
        return $row ? (int)$row['n'] : 0;
    }

    public function chartOpsPorSubtipo(): array
    {
        $sql = "
        SELECT 
            s.id_subtipo,
            s.nombre,
            s.prefijo_codigo,
            COUNT(*) AS total
        FROM operaciones o
        INNER JOIN subtipos_operacion s 
            ON s.id_subtipo = o.subtipo_operacion_id
        WHERE o.estatus_id IN (1,5,9)            -- activos: Pendiente / En revisión / Abierta
        GROUP BY s.id_subtipo, s.nombre, s.prefijo_codigo
        ORDER BY total DESC
    ";
        return $this->selectAll($sql);
    }

    public function chartPuntualidadEntregasSemana(int $semanas = 8): array
    {
        $sql = "
      SELECT
        DATE_FORMAT(DATE_SUB(e.fecha, INTERVAL WEEKDAY(e.fecha) DAY), '%Y-%m-%d') AS semana_inicio,
        DATE_FORMAT(DATE_ADD(DATE_SUB(e.fecha, INTERVAL WEEKDAY(e.fecha) DAY), INTERVAL 6 DAY), '%Y-%m-%d') AS semana_fin,
        DATE_FORMAT(e.fecha, '%x-W%v') AS semana_iso,
        SUM(CASE WHEN e.fecha <= COALESCE(d.arribo_sd, o.eta) THEN 1 ELSE 0 END) AS a_tiempo,
        SUM(CASE WHEN e.fecha  > COALESCE(d.arribo_sd, o.eta) THEN 1 ELSE 0 END) AS tarde,
        ROUND(AVG(DATEDIFF(e.fecha, COALESCE(d.arribo_sd, o.eta))), 2) AS retraso_prom_dias
      FROM eventos_logisticos e
      JOIN operaciones o ON o.id_operacion = e.operacion_id
      LEFT JOIN detalles_logisticos d ON d.operacion_id = o.id_operacion
      WHERE e.estatus = 1
        AND e.tipo_evento_id IN (6, 10)   -- 6=Entrega Cargado (Terrestre), 10=Entrega (Marítimo)
        AND e.fecha >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
        AND COALESCE(d.arribo_sd, o.eta) IS NOT NULL
      GROUP BY
        DATE_SUB(e.fecha, INTERVAL WEEKDAY(e.fecha) DAY),
        DATE_FORMAT(e.fecha, '%x-W%v')
      ORDER BY semana_inicio ASC
    ";
        return $this->selectAll($sql, [$semanas]);
    }

    public function costosVsAbonosPorMes(
        int $meses = 12,
        string $monedaDestino = 'MXN',
        float $tcUsdMxn = 17.00
    ): array {
        $sql = "
    SELECT
      DATE_FORMAT(mes, '%Y-%m') AS anio_mes,
      ROUND(SUM(CASE WHEN tipo = 'GASTO' THEN monto_conv ELSE 0 END), 2)  AS gastos,
      ROUND(SUM(CASE WHEN tipo = 'ABONO' THEN monto_conv ELSE 0 END), 2)  AS abonos
    FROM (
      -- --------- Operación ----------
      SELECT
        DATE_FORMAT(coo.fecha_creacion, '%Y-%m-01') AS mes,
        tm.tipo AS tipo,  -- 'GASTO' | 'ABONO'
        CASE
          WHEN ? = 'MXN' THEN
            CASE WHEN tm.moneda = 'DLLS'  THEN coo.monto * ? ELSE coo.monto END
          ELSE
            CASE WHEN tm.moneda = 'PESOS' THEN coo.monto / ? ELSE coo.monto END
        END AS monto_conv
      FROM costos_operacion coo
      JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = coo.tipo_movimiento_id
      WHERE coo.fecha_creacion >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL ? MONTH)
        AND coo.estatus = 1

      UNION ALL

      -- --------- Contenedor ---------
      SELECT
        DATE_FORMAT(cco.fecha_creacion, '%Y-%m-01') AS mes,
        tm.tipo AS tipo,
        CASE
          WHEN ? = 'MXN' THEN
            CASE WHEN tm.moneda = 'DLLS'  THEN cco.monto * ? ELSE cco.monto END
          ELSE
            CASE WHEN tm.moneda = 'PESOS' THEN cco.monto / ? ELSE cco.monto END
        END AS monto_conv
      FROM costos_contenedor_operacion cco
      JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
      WHERE cco.fecha_creacion >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL ? MONTH)
    ) t
    GROUP BY anio_mes
    ORDER BY anio_mes ASC
  ";

        return $this->selectAll($sql, [
            $monedaDestino, $tcUsdMxn, $tcUsdMxn, $meses,
            $monedaDestino, $tcUsdMxn, $tcUsdMxn, $meses
        ]);
    }

    // Timeline ETD→ETA
    public function timelineETD_ETA(int $dias = 60): array
    {
        $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            o.etd,
            o.eta,
            d.arribo_sd,
            COALESCE(d.arribo_sd, o.eta) AS llegada_real,
            o.estatus_id,
            s.nombre          AS estatus_nombre,
            st.prefijo_codigo AS subtipo_prefijo,
            st.nombre         AS subtipo_nombre
        FROM operaciones o
        LEFT JOIN detalles_logisticos   d  ON d.operacion_id = o.id_operacion
        LEFT JOIN subtipos_operacion    st ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN estatus               s  ON s.id_estatus = o.estatus_id
        WHERE o.estatus_id IN (1,5,9)
          AND o.etd IS NOT NULL
          AND (o.eta IS NOT NULL OR d.arribo_sd IS NOT NULL)
          AND (
                o.etd BETWEEN DATE_SUB(CURDATE(), INTERVAL ? DAY)
                           AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             OR COALESCE(d.arribo_sd, o.eta) BETWEEN DATE_SUB(CURDATE(), INTERVAL ? DAY)
                                                AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
          )
        ORDER BY o.etd ASC
    ";

        return $this->selectAll($sql, [$dias, $dias, $dias, $dias]) ?: [];
    }

    public function alertasFinalizadaSinEntrega(int $estatusFinalizadaId, int $limit = 20): array
    {
        $limit = max(1, (int)$limit);

        $sql = "
          SELECT 
            o.id_operacion,
            o.numero_operacion,
            o.eta,
            o.etd,
            c.nombre AS cliente
          FROM operaciones o
          LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
          WHERE o.estatus_id = ?
            AND NOT EXISTS (
              SELECT 1
              FROM eventos_logisticos e
              WHERE e.operacion_id = o.id_operacion
                AND e.estatus = 1
                AND e.tipo_evento_id IN (6,10)
            )
          ORDER BY COALESCE(o.eta, o.etd) DESC
          LIMIT {$limit}";

        $rows = $this->selectAll($sql, [$estatusFinalizadaId]);
        return is_array($rows) ? $rows : [];
    }

    public function alertasEtaProximasOVencidas(int $window = 7, int $past = 7, int $limit = 50): array
    {
        $limit  = max(1, (int)$limit);
        $window = max(0, (int)$window);
        $past   = max(0, (int)$past);

        $sql = "
      SELECT
        o.id_operacion,
        o.numero_operacion,
        c.nombre AS cliente,
        DATE(o.eta) AS eta_fecha,
        DATEDIFF(DATE(o.eta), CURDATE()) AS dias_restantes
      FROM operaciones o
      LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
      WHERE o.estatus_id IN (1,5,9)
        AND o.eta IS NOT NULL
        AND DATEDIFF(DATE(o.eta), CURDATE()) BETWEEN -? AND ?
      ORDER BY dias_restantes ASC, o.eta ASC
      LIMIT {$limit}";

        $rows = $this->selectAll($sql, [$past, $window]);
        return is_array($rows) ? $rows : [];
    }


        /**
     * Obtiene los IDs de estatus por nombre (comparación case-insensitive).
     * Ej: ["BODEGA TJ","BODEGA SD"]
     */
public function getEstatusIdsByNombre(array $nombres): array
{
    $nombres = array_values(array_filter(array_map('trim', $nombres)));
    if (empty($nombres)) return [];

    $placeholders = implode(',', array_fill(0, count($nombres), '?'));

    $sql = "SELECT id_estatus
            FROM estatus
            WHERE UPPER(TRIM(nombre)) IN ($placeholders)";

    $params = array_map(fn($x) => mb_strtoupper(trim($x), 'UTF-8'), $nombres);
    $rows = $this->selectAll($sql, $params);

    if (!is_array($rows)) return [];
    return array_map('intval', array_column($rows, 'id_estatus'));
}


    /**
     * Obtiene IDs de subtipos que correspondan a "Lázaro" sin hardcodear.
     * Busca por clave, nombre o prefijo.
     */
    public function getSubtipoLazaroIds(): array
    {
        $sql = "SELECT id_subtipo
                FROM subtipos_operacion
                WHERE UPPER(clave) LIKE '%LAZARO%'
                   OR UPPER(nombre) LIKE '%LÁZARO%'
                   OR UPPER(nombre) LIKE '%LAZARO%'
                   OR UPPER(prefijo_codigo) IN ('LC','LAZ','LAZARO')";
        $rows = $this->selectAll($sql);
        if (!is_array($rows)) return [];
        return array_map('intval', array_column($rows, 'id_subtipo'));
    }

    /**
     * Crea una lista de placeholders para IN() y devuelve [sql_in, params]
     */
    private function buildIn(array $values): array
    {
        $values = array_values(array_filter($values, fn($v)=>$v!==null && $v!==''));
        if (empty($values)) return ['(NULL)', []];
        $ph = '(' . implode(',', array_fill(0, count($values), '?')) . ')';
        return [$ph, $values];
    }
    /**
     * KPI: Operaciones activas SIN ISF (isf=0), excluyendo subtipo Lázaro.
     */
    public function kpiOperacionesSinISF(): int
    {
        $lazaroIds = $this->getSubtipoLazaroIds();
        [$inLazaro, $paramsL] = $this->buildIn($lazaroIds);

        // Si no encontró Lázaro, no excluimos nada (pero idealmente sí existirá).
        $sql = "
            SELECT COUNT(*) AS n
            FROM operaciones o
            WHERE o.estatus_id IN (1,5,9)
              AND (o.isf IS NULL OR o.isf = 0)
              AND (
                    " . (empty($lazaroIds) ? "1=1" : "o.subtipo_operacion_id NOT IN $inLazaro") . "
                  )
        ";

        $row = $this->select($sql, $paramsL);
        return $row ? (int)$row['n'] : 0;
    }

        /**
     * KPI: Operaciones activas SIN cita en puerto (cita_puerto NULL),
     * excluyendo subtipo Lázaro.
     */
    public function kpiOperacionesSinCitaPuerto(): int
    {
        $lazaroIds = $this->getSubtipoLazaroIds();
        [$inLazaro, $paramsL] = $this->buildIn($lazaroIds);

        $sql = "
            SELECT COUNT(*) AS n
            FROM operaciones o
            WHERE o.estatus_id IN (1,5,9)
              AND o.cita_puerto IS NULL
              AND (
                    " . (empty($lazaroIds) ? "1=1" : "o.subtipo_operacion_id NOT IN $inLazaro") . "
                  )
        ";

        $row = $this->select($sql, $paramsL);
        return $row ? (int)$row['n'] : 0;
    }

        /**
     * KPI: Operaciones activas con cita_puerto dentro de los próximos N días,
     * excluyendo subtipo Lázaro.
     */
    public function kpiCitaPuertoProxima(int $dias = 5): int
    {
        $dias = max(0, (int)$dias);

        $lazaroIds = $this->getSubtipoLazaroIds();
        [$inLazaro, $paramsL] = $this->buildIn($lazaroIds);

        $sql = "
            SELECT COUNT(*) AS n
            FROM operaciones o
            WHERE o.estatus_id IN (1,5,9)
              AND o.cita_puerto IS NOT NULL
              AND DATE(o.cita_puerto) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
              AND (
                    " . (empty($lazaroIds) ? "1=1" : "o.subtipo_operacion_id NOT IN $inLazaro") . "
                  )
        ";

        $params = array_merge([$dias], $paramsL);
        $row = $this->select($sql, $params);
        return $row ? (int)$row['n'] : 0;
    }

        /**
     * Alertas: ETA próximas a vencer (0..window días).
     */
    public function alertasEtaProximas(int $window = 7, int $limit = 50): array
    {
        $window = max(0, (int)$window);
        $limit  = max(1, (int)$limit);

        $sql = "
            SELECT
              o.id_operacion,
              o.numero_operacion,
              c.nombre AS cliente,
              DATE(o.eta) AS eta_fecha,
              DATEDIFF(DATE(o.eta), CURDATE()) AS dias_restantes
            FROM operaciones o
            LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
            WHERE o.estatus_id IN (1,5,9)
              AND o.eta IS NOT NULL
              AND DATEDIFF(DATE(o.eta), CURDATE()) BETWEEN 0 AND ?
            ORDER BY dias_restantes ASC, o.eta ASC
            LIMIT {$limit}
        ";

        $rows = $this->selectAll($sql, [$window]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Alertas: ETA ya vencidas (-past..-1 días).
     */
    public function alertasEtaVencidas(int $past = 7, int $limit = 50): array
    {
        $past  = max(0, (int)$past);
        $limit = max(1, (int)$limit);

        $sql = "
            SELECT
              o.id_operacion,
              o.numero_operacion,
              c.nombre AS cliente,
              DATE(o.eta) AS eta_fecha,
              DATEDIFF(DATE(o.eta), CURDATE()) AS dias_restantes
            FROM operaciones o
            LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
            WHERE o.estatus_id IN (1,5,9)
              AND o.eta IS NOT NULL
              AND DATEDIFF(DATE(o.eta), CURDATE()) BETWEEN -? AND -1
            ORDER BY dias_restantes ASC, o.eta ASC
            LIMIT {$limit}
        ";

        $rows = $this->selectAll($sql, [$past]);
        return is_array($rows) ? $rows : [];
    }
// ✅ NUEVO: KPI FO en tránsito (estatus = EN CAMINO)
// Cuenta operaciones_ferroviarias cuyo estatus sea "EN CAMINO"
public function kpiOperacionesFOEnCamino(): int
{
    // 1) Intento exacto (tolerante a TRIM por el cambio de arriba)
    $ids = $this->getEstatusIdsByNombre([
        'CAMINO A DESTINO',
        'EN CAMINO',
        'EN TRANSITO',
        'EN TRÁNSITO'
    ]);

    // 2) Fallback por LIKE si no encontró nada (por variantes raras)
    if (empty($ids)) {
        $sqlIds = "
            SELECT id_estatus
            FROM estatus
            WHERE UPPER(TRIM(nombre)) LIKE '%CAMINO%'
               OR UPPER(TRIM(nombre)) LIKE '%TRANSIT%'
               OR UPPER(TRIM(nombre)) LIKE '%TRÁNSIT%'
        ";
        $rows = $this->selectAll($sqlIds);
        $ids = is_array($rows) ? array_map('intval', array_column($rows, 'id_estatus')) : [];
    }

    if (empty($ids)) return 0;

    [$in, $params] = $this->buildIn($ids);

    $sql = "
        SELECT COUNT(*) AS n
        FROM operaciones_ferroviarias
        WHERE estatus_id IN $in
    ";

    $row = $this->select($sql, $params);
    return $row ? (int)$row['n'] : 0;
}

/**
 * KPI: Contenedores en Bodega (BODEGA TJ + BODEGA SD)
 * Suma:
 *  - contenedores_operacion (cajas/FO) activos (co.estatus=1) cuya operación esté en Bodega
 *  - contenedores_maritimos_operacion (marítimos) cuya operación esté en Bodega
 */
public function kpiContenedoresEnBodega(): int
{
    $bodegaIds = $this->getEstatusIdsByNombre(['BODEGA TJ', 'BODEGA SD']);
    if (empty($bodegaIds)) return 0;

    [$inBodega, $params] = $this->buildIn($bodegaIds);

    $sql = "
        SELECT
          COALESCE((
            SELECT COUNT(*)
            FROM contenedores_operacion co
            JOIN operaciones o ON o.id_operacion = co.operacion_id
            WHERE co.estatus = 1
              AND o.estatus_id IN {$inBodega}
          ),0)
          +
          COALESCE((
            SELECT COUNT(*)
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o ON o.id_operacion = cmo.operacion_id
            WHERE o.estatus_id IN {$inBodega}
          ),0) AS total
    ";

    $row = $this->select($sql, $params);
    return $row ? (int)$row['total'] : 0;
}
public function kpiContenedoresEnBodegaDetalle(): array
{
    $idTJ = $this->getEstatusIdsByNombre(['BODEGA TJ']);
    $idSD = $this->getEstatusIdsByNombre(['BODEGA SD']);

    $tj = !empty($idTJ) ? (int)$this->countContenedoresByEstatusOperacion($idTJ[0]) : 0;
    $sd = !empty($idSD) ? (int)$this->countContenedoresByEstatusOperacion($idSD[0]) : 0;

    return ['tj' => $tj, 'sd' => $sd, 'total' => ($tj + $sd)];
}

private function countContenedoresByEstatusOperacion(int $estatusId): int
{
    $sql = "
        SELECT
          COALESCE((
            SELECT COUNT(*)
            FROM contenedores_operacion co
            JOIN operaciones o ON o.id_operacion = co.operacion_id
            WHERE co.estatus = 1 AND o.estatus_id = ?
          ),0)
          +
          COALESCE((
            SELECT COUNT(*)
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o ON o.id_operacion = cmo.operacion_id
            WHERE o.estatus_id = ?
          ),0) AS total
    ";
    $row = $this->select($sql, [$estatusId, $estatusId]);
    return $row ? (int)$row['total'] : 0;
}

}
