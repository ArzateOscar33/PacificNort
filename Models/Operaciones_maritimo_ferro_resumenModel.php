<?php
class Operaciones_maritimo_ferro_resumenModel extends Query
{
  /**
   * Autocomplete SOLO de operaciones Marítimo-Ferroviarias (tipo_operacion_id = 11).
   * (Ya NO incluye FO / operaciones_ferroviarias ni tipo 2).
   */
  public function buscarOperacionesMF(string $term): array
  {
    $needle = '%' . mb_strtolower(trim($term), 'UTF-8') . '%';

    $sql = "
            SELECT
              o.id_operacion     AS id,
              o.numero_operacion AS numero,
              cl.nombre          AS cliente,
              CONCAT(o.numero_operacion, ' — ', cl.nombre) AS label,
              'OP'               AS origen,
              o.tipo_operacion_id
            FROM operaciones o
            JOIN clientes cl ON cl.id_cliente = o.cliente_id
            WHERE o.tipo_operacion_id = 11
              AND (
                LOWER(o.numero_operacion) LIKE CONCAT('%', LOWER(?), '%')
                OR LOWER(cl.nombre)       LIKE CONCAT('%', LOWER(?), '%')
              )
            ORDER BY
              CASE
                WHEN LOWER(o.numero_operacion) LIKE CONCAT(LOWER(?), '%') THEN 1
                ELSE 2
              END,
              o.numero_operacion
            LIMIT 10;
        ";

    return $this->selectAll($sql, [$needle, $needle, trim($term)]) ?: [];
  }

  /**
   * Lista SOLO contenedores MARÍTIMOS de la operación MF (ya NO incluye físicos/ferros).
   */
  public function getContenedoresPorOperacion(int $operacionId): array
  {
    $sql = "
            SELECT
              o.id_operacion,
              o.numero_operacion,
              cl.nombre AS nombre_cliente,
              'Maritimo' AS tipo_contenedor,
              cm.id_contenedor_maritimo AS id_contenedor,
              cm.numero_contenedor      AS numero_contenedor,
              cm.tipo                   AS tipo_equipo
            FROM operaciones o
            JOIN clientes cl ON cl.id_cliente = o.cliente_id
            JOIN contenedores_maritimos_operacion cmo
              ON cmo.operacion_id = o.id_operacion
            JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE o.id_operacion = ?
              AND o.tipo_operacion_id = 11
            ORDER BY cm.numero_contenedor ASC;
        ";

    return $this->selectAll($sql, [$operacionId]) ?: [];
  }

  /**
   * Detalle de contenedor MARÍTIMO por operación + id_contenedor_maritimo.
   */
  public function getDetalleContenedorMaritimo(int $operacionId, int $contenedorMaritimoId): array
  {
    $sql = "
            SELECT
              o.id_operacion,
              o.numero_operacion,
              o.etd,
              o.eta,
              o.numero_bl,
              o.notas                              AS comentarios_operacion,
              o.isf,
              o.cita_puerto,
              b.nombre                              AS broker,
              tr.nombre                             AS transportista,
              so.id_subtipo,
              p.nombre                             AS puerto,
              cmo.id                               AS cont_maritimo_operacion_id,
              cm.id_contenedor_maritimo,
              cm.numero_contenedor,
              cm.tipo                              AS tipo_contenedor,
              cm.observaciones                     AS observaciones_contenedor
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o
              ON o.id_operacion = cmo.operacion_id
            JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            LEFT JOIN subtipos_operacion so
              ON so.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN puertos p
              ON p.id_puerto = so.puerto_arribo_default_id
            LEFT JOIN brokers b
              ON b.id_broker = o.broker_id
            LEFT JOIN transportistas tr
              ON tr.id_transportista = o.transportista_id
            WHERE cmo.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND cmo.contenedor_maritimo_id = ?
            LIMIT 1;
        ";

    return $this->selectAll($sql, [$operacionId, $contenedorMaritimoId]) ?: [];
  }

  /**
   * Documentos faltantes para un CONTENEDOR MARÍTIMO.
   * (Ya NO soporta 'contenedor_fisico' ni pivots de contenedores_operacion).
   */
  public function faltantesPorContenedorMaritimo(
    int $operacionId,
    int $contenedorMaritimoId,
    bool $soloActivos = true,
    ?string $busca = null
  ): array {
    $params = [$operacionId, $contenedorMaritimoId];

    $where = [];
    $where[] = "t.aplica_sobre IN ('contenedor_maritimo','cualquiera')";
    if ($soloActivos) {
      $where[] = "t.activo = 1";
    }
    if ($busca !== null && trim($busca) !== '') {
      $where[]  = "(LOWER(t.nombre) LIKE CONCAT('%', LOWER(?), '%')
                       OR LOWER(t.clave)  LIKE CONCAT('%', LOWER(?), '%'))";
      $params[] = trim($busca);
      $params[] = trim($busca);
    }
    $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "
            SELECT
              t.id_tipo_documento AS id,
              t.nombre,
              t.clave,
              t.aplica_sobre
            FROM tipos_documento t
            JOIN contenedores_maritimos_operacion cmo
              ON cmo.operacion_id = ? AND cmo.contenedor_maritimo_id = ?
            LEFT JOIN documentos_operacion d
              ON d.tipo_documento_id          = t.id_tipo_documento
             AND d.operacion_id              = cmo.operacion_id
             AND d.cont_maritimo_operacion_id = cmo.id
            {$whereSql}
            GROUP BY t.id_tipo_documento, t.nombre, t.clave, t.aplica_sobre
            HAVING COUNT(d.id_documento) = 0
            ORDER BY t.nombre ASC
            LIMIT 500;
        ";

    return $this->selectAll($sql, $params) ?: [];
  }

  public function contarFaltantesPorContenedorMaritimo(int $operacionId, int $contenedorMaritimoId): int
  {
    $rows = $this->faltantesPorContenedorMaritimo($operacionId, $contenedorMaritimoId, true, null);
    return is_array($rows) ? count($rows) : 0;
  }

  /**
   * TOTAL de costos de la operación (solo GASTO).
   * (Se conserva porque ahora TODO vive en la operación marítima maestra).
   */
  public function getCostosTotalesOperacion(int $operacionId): float
  {
    $sql = "
            SELECT SUM(co.monto) AS total
            FROM costos_operacion co
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            JOIN operaciones o
              ON o.id_operacion = co.operacion_id
            WHERE co.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND co.estatus = 1
              AND tm.tipo = 'GASTO';
        ";

    $row = $this->select($sql, [$operacionId]);
    return isset($row['total']) ? (float)$row['total'] : 0.0;
  }

  /**
   * DESGLOSE de costos de la operación (solo GASTO).
   */
  public function getCostosDesglosadosOperacion(int $operacionId): array
  {
    $sql = "
            SELECT
              co.id_costo_operacion,
              co.tipo_movimiento_id,
              tm.nombre   AS nombre_movimiento,
              co.monto,
              tm.moneda   AS moneda,
              co.comentario,
              co.fecha_creacion
            FROM costos_operacion co
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            JOIN operaciones o
              ON o.id_operacion = co.operacion_id
            WHERE co.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND co.estatus = 1
              AND tm.tipo = 'GASTO'
            ORDER BY co.fecha_creacion ASC;
        ";

    $rows = $this->selectAll($sql, [$operacionId]);
    return is_array($rows) ? $rows : [];
  }

  /**
   * Eventos logísticos SOLO para contenedor MARÍTIMO (si lo sigues usando en resumen).
   * (Ya NO existe wrapper para Físico, ni eventos_ferroviarios).
   */
  public function getEventosLogisticosMaritimo(int $operacionId, int $contenedorMaritimoId): array
  {
    $sql = "
            SELECT
              el.id_evento,
              el.operacion_id,
              el.cont_maritimo_operacion_id,
              el.tipo_evento_id,
              tel.nombre   AS nombre_evento,
              el.fecha,
              el.comentario,
              el.estatus
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o
              ON o.id_operacion = cmo.operacion_id
            JOIN eventos_logisticos el
              ON el.operacion_id               = cmo.operacion_id
             AND el.cont_maritimo_operacion_id = cmo.id
            JOIN tipos_evento_logistico tel
              ON tel.id_tipo_evento = el.tipo_evento_id
            WHERE cmo.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND cmo.contenedor_maritimo_id = ?
              AND el.estatus = 1
            ORDER BY (el.fecha IS NULL), el.fecha ASC, el.id_evento ASC;
        ";

    return $this->selectAll($sql, [$operacionId, $contenedorMaritimoId]) ?: [];
  }

  /**
   * Progreso de eventos para contenedor MARÍTIMO (id_tipo_operacion = 1).
   */
  public function getEventosProgresoMaritimo(int $operacionId, int $contenedorMaritimoId): array
  {
    $sqlDone = "
            SELECT COUNT(DISTINCT el.tipo_evento_id) AS completados
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o
              ON o.id_operacion = cmo.operacion_id
            JOIN eventos_logisticos el
              ON el.operacion_id               = cmo.operacion_id
             AND el.cont_maritimo_operacion_id = cmo.id
             AND el.estatus = 1
            WHERE cmo.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND cmo.contenedor_maritimo_id = ?;
        ";
    $rowDone = $this->select($sqlDone, [$operacionId, $contenedorMaritimoId]);
    $completados = (int)($rowDone['completados'] ?? 0);

    $sqlTotal = "
            SELECT COUNT(*) AS total
            FROM tipos_evento_logistico tel
            WHERE tel.estatus = 1
              AND tel.id_tipo_operacion = 1;
        ";
    $rowTotal = $this->select($sqlTotal, []);
    $total = (int)($rowTotal['total'] ?? 0);

    return [
      'completados' => $completados,
      'total'       => $total,
      'restantes'   => max($total - $completados, 0),
    ];
  }
}
