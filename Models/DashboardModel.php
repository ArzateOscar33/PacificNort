<?php
class DashboardModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    public function kpiOperacionesActivas(): int
    {
        $sql = "SELECT COUNT(*) AS n
                FROM operaciones
                WHERE estatus_id IN (1,5,9)"; // Pendiente, En Revisión, Abierta
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
  SUM(CASE WHEN e.estatus IN (0,1) THEN 1 ELSE 0 END)                       AS total -- 👈 ajusta IN(...)
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
  // $monedaDestino: 'MXN' | 'USD'
  // $tcUsdMxn: MXN por 1 USD
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

// Devuelve operaciones activas con su ventana ETD→ETA y llegada real (arribo_sd si existe)
// $dias: ventana alrededor de hoy para limitar el resultado (ej. 60 días)
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

    // OJO: hay 4 ? en este WHERE (dos para o.etd y dos para COALESCE(...))
    // Si usas solo 3, fallará. Aquí pasamos los 4.
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
                AND e.tipo_evento_id IN (6,10) -- Entrega (ajusta si tus IDs son otros)
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
      WHERE o.estatus_id IN (1,5,9)         -- activas
        AND o.eta IS NOT NULL
        AND DATEDIFF(DATE(o.eta), CURDATE()) BETWEEN -? AND ?
      ORDER BY dias_restantes ASC, o.eta ASC
      LIMIT {$limit}";

    $rows = $this->selectAll($sql, [$past, $window]);
    return is_array($rows) ? $rows : [];
}





}
