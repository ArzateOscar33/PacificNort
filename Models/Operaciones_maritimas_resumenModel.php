<?php
class Operaciones_maritimas_resumenModel extends Query
{
  public function buscarOperacionesConContenedores(string $term): array
  {
    // Normaliza el término
    $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

    $sql = "
      /* SUGERENCIAS sin duplicados, prioriza prefijo en numero_operacion */
      SELECT 
        o.id_operacion     AS id,
        o.numero_operacion AS numero,
        cl.nombre          AS cliente,
        CONCAT(o.numero_operacion, ' — ', cl.nombre) AS label
      FROM operaciones o
      JOIN clientes cl ON cl.id_cliente = o.cliente_id
      WHERE 
        LOWER(o.numero_operacion) LIKE CONCAT('%', LOWER(?), '%')
        OR LOWER(cl.nombre)       LIKE CONCAT('%', LOWER(?), '%')
      ORDER BY 
        CASE 
          WHEN LOWER(o.numero_operacion) LIKE CONCAT(LOWER(?), '%') THEN 1  -- prefijo primero
          ELSE 2
        END,
        o.numero_operacion
      LIMIT 10;

      ";

    // Nota: tres parámetros en total: (prefijo, contains, contains)
    return $this->selectAll($sql, [$term, $needle, $needle]);
  }

  public function getContenedoresPorOperacion($id)
  {
    $sql = "
    -- Marítimos de la operación
    SELECT 
    o.id_operacion,
    o.numero_operacion,
    cl.nombre AS nombre_cliente,
    'Maritimo'  AS tipo_contenedor,
    cm.id_contenedor_maritimo AS id_contenedor,
    cm.numero_contenedor      AS numero_contenedor
    FROM operaciones o
    JOIN clientes cl ON cl.id_cliente = o.cliente_id
    JOIN contenedores_maritimos_operacion cmo 
    ON cmo.operacion_id = o.id_operacion
    JOIN contenedores_maritimos cm 
    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
    WHERE o.id_operacion = ?

    UNION ALL

    -- Físicos de la operación
    SELECT 
    o.id_operacion,
    o.numero_operacion,
    cl.nombre AS nombre_cliente,
    'Ferro'   AS tipo_contenedor,
    cf.id_fisico AS id_contenedor,
    cf.numero_ferro AS numero_contenedor
    FROM operaciones o
    JOIN clientes cl ON cl.id_cliente = o.cliente_id
    JOIN contenedores_operacion co 
    ON co.operacion_id = o.id_operacion
    JOIN contenedores_fisicos cf 
    ON cf.id_fisico = co.id_fisico
    WHERE o.id_operacion = ?
    ORDER BY tipo_contenedor, numero_contenedor;

     ";
    return $this->selectAll($sql, [$id, $id]);
  }

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
      WHERE cmo.operacion_id = ?
        AND cmo.contenedor_maritimo_id = ?
      LIMIT 1; ";
    return $this->selectAll($sql, [$operacionId, $contenedorMaritimoId]);
  }

  public function getDetalleContenedorFisico(int $operacionId, int $idFisico): array
  {
    $sql = "
      SELECT
    o.id_operacion,
    o.numero_operacion,
    cf.id_fisico,
    cf.numero_ferro,
    co.id_contenedor                     AS contenedor_operacion_id,
    co.bultos,
    co.comentarios                       AS comentarios_contenedor,
    MAX(CASE WHEN el.tipo_evento_id = 8 AND el.estatus = 1 THEN el.fecha END) AS arribo_a_puerto
    FROM contenedores_operacion co
    JOIN operaciones o
      ON o.id_operacion = co.operacion_id
    JOIN contenedores_fisicos cf
      ON cf.id_fisico = co.id_fisico
    LEFT JOIN eventos_logisticos el
      ON el.contenedor_operacion_id = co.id_contenedor
    -- Arribo a puerto
    AND el.tipo_evento_id = 8
    AND el.estatus = 1
    WHERE co.operacion_id = ?
      AND co.id_fisico   = ?
    GROUP BY
      o.id_operacion, o.numero_operacion,
      cf.id_fisico, cf.numero_ferro,
      co.id_contenedor, co.bultos, co.comentarios;
      ";
    return $this->selectAll($sql, [$operacionId, $idFisico]);
  }
  public function faltantesPorContenedor(int $operacionId, int $idBase, string $tipoUI,  bool $soloActivos = true,  ?string $busca = null): array
  {
    $t = mb_strtoupper(trim($tipoUI), 'UTF-8');

    // Construye el "pivot" dentro del SQL, según el tipo UI que TÚ ya usas
    if ($t === 'FERRO' || $t === 'FISICO' || $t === 'FÍSICO'|| $t === 'F') {
      // FÍSICO: base = cf.id_fisico  → pivot = co.id_contenedor
      $joinPivot = "
            JOIN contenedores_operacion co
              ON co.operacion_id = ? AND co.id_fisico = ?
        ";
      $params   = [$operacionId, $idBase];
      $docJoin  = "
            LEFT JOIN documentos_operacion d
              ON d.tipo_documento_id = t.id_tipo_documento
             AND d.operacion_id = co.operacion_id
             AND d.contenedor_operacion_id = co.id_contenedor
        ";
      $aplicaIn = "('contenedor_fisico','cualquiera')";
    } else {
      // MARÍTIMO: base = cm.id_contenedor_maritimo → pivot = cmo.id
      $joinPivot = "
            JOIN contenedores_maritimos_operacion cmo
              ON cmo.operacion_id = ? AND cmo.contenedor_maritimo_id = ?
        ";
      $params   = [$operacionId, $idBase];
      $docJoin  = "
            LEFT JOIN documentos_operacion d
              ON d.tipo_documento_id = t.id_tipo_documento
             AND d.operacion_id = cmo.operacion_id
             AND d.cont_maritimo_operacion_id = cmo.id
        ";
      $aplicaIn = "('contenedor_maritimo','cualquiera')";
    }

    // WHERE dinámico
    $where = [];
    $where[] = "t.aplica_sobre IN {$aplicaIn}";
    if ($soloActivos) {
      $where[] = "t.activo = 1";
    }
    if ($busca !== null && $busca !== '') {
      $where[]   = "(LOWER(t.nombre) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(t.clave) LIKE CONCAT('%', LOWER(?), '%'))";
      $params[]  = $busca;
      $params[]  = $busca;
    }
    $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "
        SELECT
            t.id_tipo_documento AS id,
            t.nombre,
            t.clave,
            t.aplica_sobre
        FROM tipos_documento t
        {$joinPivot}
        {$docJoin}
        {$whereSql}
        GROUP BY t.id_tipo_documento, t.nombre, t.clave, t.aplica_sobre
        HAVING COUNT(d.id_documento) = 0
        ORDER BY t.nombre ASC
        LIMIT 500
    ";

    return $this->selectAll($sql, $params) ?: [];
  }

  /** Solo el conteo, misma firma que arriba (usa tus mismos IDs/terminología) */
  public function contarFaltantesPorContenedor(int $operacionId, int $idBase, string $tipoUI): int
  {
    $rows = $this->faltantesPorContenedor($operacionId, $idBase, $tipoUI, true, null);
    return is_array($rows) ? count($rows) : 0;
  }
 
public function getCostosTotalesContenedor(int $operacionId, int $idFisico): float
{
    $sql = "
      SELECT SUM(c.monto) AS total
      FROM contenedores_operacion co
      JOIN costos_contenedor_operacion c
        ON c.contenedor_operacion_id = co.id_contenedor
      WHERE co.operacion_id = ?
        AND co.id_fisico    = ?
    ";
    $row = $this->select($sql, [$operacionId, $idFisico]);
    return isset($row['total']) ? (float)$row['total'] : 0.0;
}

public function getCostosDesglosadosContenedor(int $operacionId, int $idFisico): array
{
    $sql = "
      SELECT 
        c.id_costo_contenedor,
        c.tipo_movimiento_id,
        c.monto,
        c.comentario,
        c.fecha_creacion
      FROM contenedores_operacion co
      JOIN costos_contenedor_operacion c
        ON c.contenedor_operacion_id = co.id_contenedor
      WHERE co.operacion_id = ?
        AND co.id_fisico    = ?
      ORDER BY c.fecha_creacion ASC
    ";
    $rows = $this->selectAll($sql, [$operacionId, $idFisico]);
    return is_array($rows) ? $rows : [];
}

}