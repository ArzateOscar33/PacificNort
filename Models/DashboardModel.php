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
        SUM(CASE WHEN e.estatus = 1 THEN 1 ELSE 0 END) AS hechos,
        COUNT(*) AS total
      FROM eventos_logisticos e
      JOIN operaciones o ON o.id_operacion = e.operacion_id
      WHERE o.estatus_id IN (1,5,9)
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

public function chartCostosPorSemanaSubtipo(int $semanas = 8, string $moneda = 'MXN', float $tc = 17.0): array
{
    // Nota: si tu tabla de movimientos se llama distinto, ajusta "tipos_movimiento"
    $sql = "
      WITH costos_unificados AS (
        -- Costos a nivel OPERACIÓN
        SELECT
          DATE_FORMAT(DATE_SUB(co.fecha_creacion, INTERVAL WEEKDAY(co.fecha_creacion) DAY), '%Y-%m-%d') AS week_start,
          o.subtipo_id,
          so.prefijo_codigo,
          so.nombre,
          CASE
            WHEN ? = 'MXN' THEN CASE WHEN tm.moneda = 'DLLS'  THEN co.monto * ? ELSE co.monto END
            ELSE                CASE WHEN tm.moneda = 'PESOS' THEN co.monto / ? ELSE co.monto END
          END AS monto_conv
        FROM costos_operacion co
        JOIN operaciones o         ON o.id_operacion = co.operacion_id
        JOIN subtipos_operacion so ON so.id_subtipo  = o.subtipo_id
        JOIN tipos_movimiento tm   ON tm.id_tipo_movimiento = co.tipo_movimiento_id
        WHERE co.estatus = 1
          AND co.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)

        UNION ALL

        -- Costos a nivel CONTENEDOR
        SELECT
          DATE_FORMAT(DATE_SUB(cco.fecha_creacion, INTERVAL WEEKDAY(cco.fecha_creacion) DAY), '%Y-%m-%d') AS week_start,
          o.subtipo_id,
          so.prefijo_codigo,
          so.nombre,
          CASE
            WHEN ? = 'MXN' THEN CASE WHEN tm.moneda = 'DLLS'  THEN cco.monto * ? ELSE cco.monto END
            ELSE                CASE WHEN tm.moneda = 'PESOS' THEN cco.monto / ? ELSE cco.monto END
          END AS monto_conv
        FROM costos_contenedor_operacion cco
        JOIN contenedores_operacion coo ON coo.id_contenedor = cco.contenedor_operacion_id
        JOIN operaciones o              ON o.id_operacion    = coo.operacion_id
        JOIN subtipos_operacion so      ON so.id_subtipo     = o.subtipo_id
        JOIN tipos_movimiento tm        ON tm.id_tipo_movimiento = cco.tipo_movimiento_id
        WHERE cco.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
      )
      SELECT
        week_start,
        subtipo_id,
        prefijo_codigo,
        nombre,
        ROUND(SUM(monto_conv), 2) AS total
      FROM costos_unificados
      GROUP BY week_start, subtipo_id, prefijo_codigo, nombre
      ORDER BY week_start ASC, nombre ASC;
    ";

    // Parámetros en el orden en el que se usan en la consulta
    return $this->selectAll($sql, [
      $moneda, $tc, $tc, $semanas,
      $moneda, $tc, $tc, $semanas
    ]);
}





}
