<?php

class Operaciones_maritimo_ferro_eventos_ferModel extends Query
{
  /* ==========================================================
     EVENTOS TERRESTRES - MARÍTIMO FERRO
     Nuevo flujo:
     - Edición directa tipo Excel desde la celda.
     - Un solo método para guardar fecha por celda.
     - Si la celda no existe, inserta.
     - Si la celda ya existe, actualiza.
     - Si la fecha viene vacía, elimina/baja lógica.
     ========================================================== */


  /* ============================
     COLUMNAS / CATÁLOGO TERRESTRE
     ============================ */
  public function listarTiposEventoTerrestre(): array
  {
    $sql = "
      SELECT id_tipo_evento, nombre
      FROM tipos_evento_logistico
      WHERE estatus = 1
        AND id_tipo_operacion = 2
      ORDER BY id_tipo_evento ASC
    ";

    $rows = $this->selectAll($sql);
    return is_array($rows) ? $rows : [];
  }


  /* =======================================================
     SUGERENCIAS DE OPERACIÓN MARÍTIMA / FERRO / CONTENEDOR
     Devuelve la operación marítima maestra.
     ======================================================= */
  public function sugerirOperacionesMFoContenedor(string $term, int $limit = 8): array
  {
    $term = trim($term);
    if ($term === '') return [];

    $limit  = max(1, min(20, (int)$limit));
    $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';
    $isNum  = ctype_digit($term);

    $where = "(
      LOWER(COALESCE(o.numero_operacion, '')) LIKE ?
      OR LOWER(COALESCE(of.numero_operacion, '')) LIKE ?
      OR LOWER(COALESCE(cf.numero_ferro, '')) LIKE ?
      OR LOWER(COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '')) LIKE ?
    )";

    $params = [$needle, $needle, $needle, $needle];

    if ($isNum) {
      $where = "(
        {$where}
        OR o.id_operacion = ?
        OR of.id_operacion_ferro = ?
      )";

      $params[] = (int)$term;
      $params[] = (int)$term;
    }

    $sql = "
      SELECT
        o.id_operacion AS id,
        o.numero_operacion AS label,

        GROUP_CONCAT(
          DISTINCT NULLIF(TRIM(cf.numero_ferro), '')
          ORDER BY cf.numero_ferro ASC
          SEPARATOR ', '
        ) AS ferro,

        GROUP_CONCAT(
          DISTINCT NULLIF(TRIM(COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '')), '')
          ORDER BY COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '') ASC
          SEPARATOR ', '
        ) AS contenedor

      FROM operacion_ferro_operacion ofo

      INNER JOIN operaciones o
        ON o.id_operacion = ofo.operacion_id

      INNER JOIN operaciones_ferroviarias of
        ON of.id_operacion_ferro = ofo.operacion_ferro_id

      LEFT JOIN contenedores_fisicos cf
        ON cf.id_fisico = of.contenedor_fisico_id
       AND cf.estatus = 1

      LEFT JOIN contenedor_maritimo_ferro cmf
        ON cmf.operacion_ferro_id = of.id_operacion_ferro
       AND cmf.contenedor_fisico_id = cf.id_fisico
       AND cmf.estatus = 1

      LEFT JOIN contenedores_maritimos cm
        ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id

      LEFT JOIN contenedores_maritimos_operacion cmo
        ON cmo.id = cmf.cont_maritimo_operacion_id

      LEFT JOIN contenedores_maritimos cm2
        ON cm2.id_contenedor_maritimo = cmo.contenedor_maritimo_id

      WHERE o.estatus_id NOT IN (13, 7)
        AND of.estatus_id NOT IN (13, 7)
        AND {$where}

      GROUP BY o.id_operacion, o.numero_operacion
      ORDER BY o.numero_operacion ASC
      LIMIT {$limit}
    ";

    $rows = $this->selectAll($sql, $params);
    return is_array($rows) ? $rows : [];
  }


  /* ================================================================
     FERROS / CAJAS DE UNA OPERACIÓN FERRO
     En este módulo la operación ferro apunta a un contenedor físico.
     ================================================================= */
  public function buscarFerrosDeOperacion(int $opFerroId, string $term = '', int $limit = 10): array
  {
    if ($opFerroId <= 0) return [];

    $limit  = max(1, min(50, (int)$limit));
    $params = [$opFerroId];
    $filtro = '';

    if (trim($term) !== '') {
      $filtro = " AND LOWER(cf.numero_ferro) LIKE ? ";
      $params[] = '%' . mb_strtolower(trim($term), 'UTF-8') . '%';
    }

    $sql = "
      SELECT 
        cf.id_fisico AS id,
        cf.numero_ferro AS label,
        'FERRO' AS tipo
      FROM operaciones_ferroviarias ofe
      INNER JOIN contenedores_fisicos cf 
        ON cf.id_fisico = ofe.contenedor_fisico_id 
       AND cf.estatus = 1
      WHERE ofe.id_operacion_ferro = ?
        {$filtro}
      ORDER BY cf.numero_ferro ASC
      LIMIT {$limit}
    ";

    $rows = $this->selectAll($sql, $params);
    return is_array($rows) ? $rows : [];
  }


  public function getFerroDeOperacion(int $opFerroId): ?array
  {
    if ($opFerroId <= 0) return null;

    $sql = "
      SELECT 
        cf.id_fisico AS id,
        cf.numero_ferro AS label
      FROM operaciones_ferroviarias ofe
      INNER JOIN contenedores_fisicos cf 
        ON cf.id_fisico = ofe.contenedor_fisico_id 
       AND cf.estatus = 1
      WHERE ofe.id_operacion_ferro = ?
      LIMIT 1
    ";

    $row = $this->select($sql, [$opFerroId]);
    return $row ?: null;
  }


  /* ==========================================================
     LISTADO PAGINADO
     Devuelve pares operación ferro + ferro/caja, y sus eventos.
     ========================================================== */
  public function listarEventosFOPaginado(
    int $page,
    int $perPage,
    ?int $opId = null,
    ?int $ferroId = null,
    string $q = '',
    ?string $fechaDesde = null,
    ?string $fechaHasta = null,
    ?int $transportistaId = null,
    ?int $clienteId = null,
    ?int $destinoId = null,
    string $contenedor = '',
    string $ferro = '',
    string $operacion = ''
  ): array {
    $page    = max(1, $page);
    $perPage = min(10000, max(1, $perPage));
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    $q          = trim($q);
    $contenedor = trim($contenedor);
    $ferro      = trim($ferro);
    $operacion  = trim($operacion);

    $where[] = "cf.estatus = 1";
    $where[] = "o.estatus_id NOT IN (13)";
    $where[] = "ofe.estatus_id NOT IN (13)";

    if (!empty($opId) && $opId > 0) {
      $where[] = "o.id_operacion = ?";
      $params[] = $opId;
    }

    if (!empty($ferroId) && $ferroId > 0) {
      $where[] = "cf.id_fisico = ?";
      $params[] = $ferroId;
    }

    if (!empty($transportistaId) && $transportistaId > 0) {
      $where[] = "ofe.transportista_id = ?";
      $params[] = $transportistaId;
    }

    if (!empty($clienteId) && $clienteId > 0) {
      $where[] = "o.cliente_id = ?";
      $params[] = $clienteId;
    }

    if (!empty($destinoId) && $destinoId > 0) {
      $where[] = "ofe.destino_id = ?";
      $params[] = $destinoId;
    }

    if ($contenedor !== '') {
      $where[] = "LOWER(COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '')) LIKE ?";
      $params[] = '%' . mb_strtolower($contenedor, 'UTF-8') . '%';
    }

    if ($ferro !== '') {
      $where[] = "LOWER(COALESCE(cf.numero_ferro, '')) LIKE ?";
      $params[] = '%' . mb_strtolower($ferro, 'UTF-8') . '%';
    }

    if ($operacion !== '') {
      $likeOperacion = '%' . mb_strtolower($operacion, 'UTF-8') . '%';

      $where[] = "(
        LOWER(COALESCE(o.numero_operacion, '')) LIKE ?
        OR LOWER(COALESCE(ofe.numero_operacion, '')) LIKE ?
      )";

      $params[] = $likeOperacion;
      $params[] = $likeOperacion;
    }

    if ($q !== '') {
      $like = '%' . mb_strtolower($q, 'UTF-8') . '%';

      $where[] = "(
        LOWER(COALESCE(o.numero_operacion, '')) LIKE ?
        OR LOWER(COALESCE(ofe.numero_operacion, '')) LIKE ?
        OR LOWER(COALESCE(cf.numero_ferro, '')) LIKE ?
        OR LOWER(COALESCE(cl.nombre, '')) LIKE ?
        OR LOWER(COALESCE(tr.nombre, '')) LIKE ?
        OR LOWER(COALESCE(ci.nombre_ciudad, '')) LIKE ?
        OR LOWER(COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '')) LIKE ?
        OR LOWER(COALESCE(ua.ubicacion_actual, '')) LIKE ?
        OR LOWER(COALESCE(obs.observacion, '')) LIKE ?
      )";

      array_push(
        $params,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
      );
    }

    if (!empty($fechaDesde)) {
      $where[] = "EXISTS (
        SELECT 1
        FROM eventos_ferroviarios efd
        WHERE efd.estatus = 1
          AND efd.operacion_ferro_id = ofe.id_operacion_ferro
          AND efd.contenedor_fisico_id = cf.id_fisico
          AND DATE(efd.fecha) >= ?
      )";

      $params[] = $fechaDesde;
    }

    if (!empty($fechaHasta)) {
      $where[] = "EXISTS (
        SELECT 1
        FROM eventos_ferroviarios efh
        WHERE efh.estatus = 1
          AND efh.operacion_ferro_id = ofe.id_operacion_ferro
          AND efh.contenedor_fisico_id = cf.id_fisico
          AND DATE(efh.fecha) <= ?
      )";

      $params[] = $fechaHasta;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $sqlUbicacion = "
      SELECT
        e.operacion_ferro_id,
        e.contenedor_fisico_id,
        te.nombre AS ubicacion_actual,
        e.fecha AS ubicacion_fecha
      FROM eventos_ferroviarios e
      INNER JOIN (
        SELECT 
          operacion_ferro_id,
          contenedor_fisico_id,
          MAX(fecha) AS max_fecha
        FROM eventos_ferroviarios
        WHERE estatus = 1
        GROUP BY operacion_ferro_id, contenedor_fisico_id
      ) mx
        ON mx.operacion_ferro_id = e.operacion_ferro_id
       AND mx.contenedor_fisico_id = e.contenedor_fisico_id
       AND mx.max_fecha = e.fecha
      LEFT JOIN tipos_evento_logistico te
        ON te.id_tipo_evento = e.tipo_evento_id
      WHERE e.estatus = 1
    ";

    $sqlCount = "
      SELECT COUNT(*) AS total_rows
      FROM (
        SELECT 
          o.id_operacion,
          ofe.id_operacion_ferro,
          cf.id_fisico

        FROM operacion_ferro_operacion ofo

        INNER JOIN operaciones o
          ON o.id_operacion = ofo.operacion_id

        INNER JOIN operaciones_ferroviarias ofe
          ON ofe.id_operacion_ferro = ofo.operacion_ferro_id

        INNER JOIN contenedores_fisicos cf
          ON cf.id_fisico = ofe.contenedor_fisico_id
         AND cf.estatus = 1

        LEFT JOIN clientes cl
          ON cl.id_cliente = o.cliente_id

        LEFT JOIN transportistas tr
          ON tr.id_transportista = ofe.transportista_id

        LEFT JOIN ciudades ci
          ON ci.id_ciudad = ofe.destino_id

        LEFT JOIN contenedor_maritimo_ferro cmf
          ON cmf.operacion_ferro_id = ofe.id_operacion_ferro
         AND cmf.contenedor_fisico_id = cf.id_fisico
         AND cmf.estatus = 1

        LEFT JOIN contenedores_maritimos cm
          ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id

        LEFT JOIN contenedores_maritimos_operacion cmo
          ON cmo.id = cmf.cont_maritimo_operacion_id

        LEFT JOIN contenedores_maritimos cm2
          ON cm2.id_contenedor_maritimo = cmo.contenedor_maritimo_id

        LEFT JOIN ({$sqlUbicacion}) ua
          ON ua.operacion_ferro_id = ofe.id_operacion_ferro
         AND ua.contenedor_fisico_id = cf.id_fisico

        LEFT JOIN eventos_terrestres_observaciones obs
          ON obs.operacion_id = o.id_operacion
         AND obs.operacion_ferro_id = ofe.id_operacion_ferro
         AND obs.contenedor_fisico_id = cf.id_fisico
         AND obs.estatus = 1

        {$whereSql}

        GROUP BY 
          o.id_operacion,
          ofe.id_operacion_ferro,
          cf.id_fisico
      ) t
    ";

    $rowCount  = $this->select($sqlCount, $params);
    $totalRows = $rowCount ? (int)$rowCount['total_rows'] : 0;

    $sqlRows = "
      SELECT
        o.id_operacion AS operacion_id,
        o.numero_operacion AS operacion_maritima,
        COALESCE(cm.numero_contenedor, cm2.numero_contenedor, '') AS contenedor_maritimo,
        cl.nombre AS cliente,
        COALESCE(ua.ubicacion_actual, '-') AS ubicacion_actual,

        ofe.id_operacion_ferro AS op_ferro_id,
        ofe.numero_operacion AS operacion_ferro,

        cf.id_fisico AS ferro_id,
        cf.numero_ferro AS ferro,

        ci.nombre_ciudad AS destino,
        tr.nombre AS transportista,

        COALESCE(obs.observacion, '') AS observacion_renglon

      FROM operacion_ferro_operacion ofo

      INNER JOIN operaciones o
        ON o.id_operacion = ofo.operacion_id

      INNER JOIN operaciones_ferroviarias ofe
        ON ofe.id_operacion_ferro = ofo.operacion_ferro_id

      INNER JOIN contenedores_fisicos cf
        ON cf.id_fisico = ofe.contenedor_fisico_id
       AND cf.estatus = 1

      LEFT JOIN clientes cl
        ON cl.id_cliente = o.cliente_id

      LEFT JOIN ciudades ci
        ON ci.id_ciudad = ofe.destino_id

      LEFT JOIN transportistas tr
        ON tr.id_transportista = ofe.transportista_id

      LEFT JOIN contenedor_maritimo_ferro cmf
        ON cmf.operacion_ferro_id = ofe.id_operacion_ferro
       AND cmf.contenedor_fisico_id = cf.id_fisico
       AND cmf.estatus = 1

      LEFT JOIN contenedores_maritimos cm
        ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id

      LEFT JOIN contenedores_maritimos_operacion cmo
        ON cmo.id = cmf.cont_maritimo_operacion_id

      LEFT JOIN contenedores_maritimos cm2
        ON cm2.id_contenedor_maritimo = cmo.contenedor_maritimo_id

      LEFT JOIN ({$sqlUbicacion}) ua
        ON ua.operacion_ferro_id = ofe.id_operacion_ferro
       AND ua.contenedor_fisico_id = cf.id_fisico

      LEFT JOIN eventos_terrestres_observaciones obs
        ON obs.operacion_id = o.id_operacion
       AND obs.operacion_ferro_id = ofe.id_operacion_ferro
       AND obs.contenedor_fisico_id = cf.id_fisico
       AND obs.estatus = 1

      {$whereSql}

      GROUP BY 
        o.id_operacion,
        ofe.id_operacion_ferro,
        cf.id_fisico,
        o.numero_operacion,
        ofe.numero_operacion,
        cf.numero_ferro,
        cm.numero_contenedor,
        cm2.numero_contenedor,
        cl.nombre,
        ci.nombre_ciudad,
        tr.nombre,
        ua.ubicacion_actual,
        obs.observacion

      ORDER BY o.id_operacion DESC, cf.numero_ferro DESC
      LIMIT {$perPage} OFFSET {$offset}
    ";

    $rowsPairs = $this->selectAll($sqlRows, $params) ?: [];

    if (empty($rowsPairs)) {
      return [
        'rows'     => [],
        'total'    => $totalRows,
        'page'     => $page,
        'per_page' => $perPage
      ];
    }

    $opFerroIds = array_values(array_unique(array_map('intval', array_column($rowsPairs, 'op_ferro_id'))));
    $fxIds      = array_values(array_unique(array_map('intval', array_column($rowsPairs, 'ferro_id'))));

    if (empty($opFerroIds) || empty($fxIds)) {
      return [
        'rows'     => [],
        'total'    => $totalRows,
        'page'     => $page,
        'per_page' => $perPage
      ];
    }

    $inOps = implode(',', array_fill(0, count($opFerroIds), '?'));
    $inFxs = implode(',', array_fill(0, count($fxIds), '?'));

    $paramsEvt = array_merge($opFerroIds, $fxIds);

    $sqlEvts = "
      SELECT
        e.id_evento,
        e.operacion_ferro_id,
        e.contenedor_fisico_id,
        e.tipo_evento_id,
        te.nombre AS evento,
        e.fecha,
        e.comentario
      FROM eventos_ferroviarios e
      LEFT JOIN tipos_evento_logistico te
        ON te.id_tipo_evento = e.tipo_evento_id
      WHERE e.estatus = 1
        AND e.operacion_ferro_id IN ({$inOps})
        AND e.contenedor_fisico_id IN ({$inFxs})
    ";

    $rowsEvts = $this->selectAll($sqlEvts, $paramsEvt) ?: [];

    $byPair = [];

    foreach ($rowsPairs as $r) {
      $key = (int)$r['op_ferro_id'] . '_' . (int)$r['ferro_id'];
      $byPair[$key] = $r;
    }

    $out = [];

    foreach ($rowsEvts as $e) {
      $key = (int)$e['operacion_ferro_id'] . '_' . (int)$e['contenedor_fisico_id'];

      if (!isset($byPair[$key])) continue;

      $p = $byPair[$key];

      $out[] = [
        'id_evento'            => (int)$e['id_evento'],
        'operacion_ferro_id'   => (int)$e['operacion_ferro_id'],
        'contenedor_fisico_id' => (int)$e['contenedor_fisico_id'],
        'tipo_evento_id'       => (int)$e['tipo_evento_id'],
        'evento'               => (string)($e['evento'] ?? ''),
        'fecha'                => (string)($e['fecha'] ?? ''),
        'comentario'           => (string)($e['comentario'] ?? ''),

        'operacion_id'         => (int)($p['operacion_id'] ?? 0),
        'operacion_maritima'   => (string)($p['operacion_maritima'] ?? ''),
        'contenedor_maritimo'  => (string)($p['contenedor_maritimo'] ?? ''),
        'cliente'              => (string)($p['cliente'] ?? ''),
        'ubicacion_actual'     => (string)($p['ubicacion_actual'] ?? '-'),
        'destino'              => (string)($p['destino'] ?? ''),
        'transportista'        => (string)($p['transportista'] ?? ''),
        'operacion'            => (string)($p['operacion_ferro'] ?? ''),
        'ferro'                => (string)($p['ferro'] ?? ''),
        'observacion_renglon'  => (string)($p['observacion_renglon'] ?? ''),
      ];
    }

    $pairsConEvento = array_unique(array_map(
      fn($r) => (int)$r['operacion_ferro_id'] . '_' . (int)$r['contenedor_fisico_id'],
      $out
    ));

    foreach ($rowsPairs as $r) {
      $key = (int)$r['op_ferro_id'] . '_' . (int)$r['ferro_id'];

      if (!in_array($key, $pairsConEvento, true)) {
        $out[] = [
          'id_evento'            => null,
          'operacion_ferro_id'   => (int)$r['op_ferro_id'],
          'contenedor_fisico_id' => (int)$r['ferro_id'],
          'tipo_evento_id'       => null,
          'evento'               => null,
          'fecha'                => null,
          'comentario'           => null,

          'operacion_id'         => (int)($r['operacion_id'] ?? 0),
          'operacion_maritima'   => (string)($r['operacion_maritima'] ?? ''),
          'contenedor_maritimo'  => (string)($r['contenedor_maritimo'] ?? ''),
          'cliente'              => (string)($r['cliente'] ?? ''),
          'ubicacion_actual'     => (string)($r['ubicacion_actual'] ?? '-'),
          'destino'              => (string)($r['destino'] ?? ''),
          'transportista'        => (string)($r['transportista'] ?? ''),
          'operacion'            => (string)($r['operacion_ferro'] ?? ''),
          'ferro'                => (string)($r['ferro'] ?? ''),
          'observacion_renglon'  => (string)($r['observacion_renglon'] ?? ''),
        ];
      }
    }

    return [
      'rows'     => $out,
      'total'    => $totalRows,
      'page'     => $page,
      'per_page' => $perPage
    ];
  }


  /* ==========================================================
     NUEVO FLUJO TIPO EXCEL
     Guarda una celda de evento terrestre.

     Recibe:
     - operacion_ferro_id
     - contenedor_fisico_id
     - tipo_evento_id
     - fecha
     - comentario opcional

     Retorna:
     [
       ok => bool,
       accion => insertado|actualizado|eliminado|sin_cambios|error,
       id_evento => int|null,
       fecha => string,
       msg => string
     ]
     ========================================================== */
  public function guardarCeldaEvento(array $data, int $idUsuario = 0): array
  {
    $opFerroId  = (int)($data['operacion_ferro_id'] ?? 0);
    $ferroId    = (int)($data['contenedor_fisico_id'] ?? 0);
    $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
    $fecha      = trim((string)($data['fecha'] ?? ''));
    $comentario = trim((string)($data['comentario'] ?? ''));

    $usuario = $idUsuario > 0 ? $idUsuario : null;

    if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
      return [
        'ok'        => false,
        'accion'    => 'error',
        'id_evento' => null,
        'fecha'     => '',
        'msg'       => 'Faltan datos de la celda.'
      ];
    }

    if (!$this->validarCeldaEventoTerrestre($opFerroId, $ferroId, $tipoEvtId)) {
      return [
        'ok'        => false,
        'accion'    => 'error',
        'id_evento' => null,
        'fecha'     => '',
        'msg'       => 'La operación, ferro/caja o tipo de evento no es válido.'
      ];
    }

    /*
      Si la fecha viene vacía, se interpreta como limpiar celda.
      Esto permite que después el JS pueda borrar una fecha con Delete/Backspace.
    */
    if ($fecha === '') {
      return $this->eliminarEventoPorClave($opFerroId, $ferroId, $tipoEvtId);
    }

    if (!$this->fechaSQLValida($fecha)) {
      return [
        'ok'        => false,
        'accion'    => 'error',
        'id_evento' => null,
        'fecha'     => $fecha,
        'msg'       => 'La fecha no tiene un formato válido.'
      ];
    }

    $actual = $this->obtenerEventoActivoPorClave($opFerroId, $ferroId, $tipoEvtId);

    if ($actual) {
      $idEvento = (int)$actual['id_evento'];

      $sqlUpd = "
        UPDATE eventos_ferroviarios
        SET fecha = ?,
            comentario = ?
        WHERE id_evento = ?
          AND estatus = 1
        LIMIT 1
      ";

      $ok = (bool)$this->save($sqlUpd, [
        $fecha,
        $comentario,
        $idEvento
      ]);

      return [
        'ok'        => $ok,
        'accion'    => $ok ? 'actualizado' : 'error',
        'id_evento' => $idEvento,
        'fecha'     => $fecha,
        'msg'       => $ok ? 'Evento actualizado correctamente.' : 'No fue posible actualizar el evento.'
      ];
    }

    /*
      Como ya agregaste índice en base de datos, dejamos protección con
      ON DUPLICATE KEY UPDATE. Esto evita duplicados si dos usuarios intentan
      guardar la misma celda casi al mismo tiempo.
    */
    $sqlIns = "
  INSERT INTO eventos_ferroviarios
    (
      operacion_ferro_id,
      contenedor_fisico_id,
      tipo_evento_id,
      fecha,
      comentario,
      creado_por,
      estatus
    )
  VALUES (?, ?, ?, ?, ?, ?, 1)
  ON DUPLICATE KEY UPDATE
    fecha = VALUES(fecha),
    comentario = VALUES(comentario),
    estatus = 1,
    id_evento = LAST_INSERT_ID(id_evento)
";

    $idInsert = (int)$this->insertar($sqlIns, [
      $opFerroId,
      $ferroId,
      $tipoEvtId,
      $fecha,
      $comentario,
      $usuario
    ]);

    $row = $this->obtenerEventoActivoPorClave($opFerroId, $ferroId, $tipoEvtId);

    if (!$row) {
      return [
        'ok'        => false,
        'accion'    => 'error',
        'id_evento' => null,
        'fecha'     => $fecha,
        'msg'       => 'No fue posible guardar el evento.'
      ];
    }

    $idEvento = (int)$row['id_evento'];

    return [
      'ok'        => true,
      'accion'    => $idInsert > 0 ? 'insertado' : 'actualizado',
      'id_evento' => $idEvento,
      'fecha'     => $fecha,
      'msg'       => 'Evento guardado correctamente.'
    ];
  }


  private function validarCeldaEventoTerrestre(
    int $opFerroId,
    int $ferroId,
    int $tipoEvtId
  ): bool {
    if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
      return false;
    }

    $sql = "
      SELECT
        ofe.id_operacion_ferro,
        cf.id_fisico,
        te.id_tipo_evento
      FROM operaciones_ferroviarias ofe

      INNER JOIN contenedores_fisicos cf
        ON cf.id_fisico = ofe.contenedor_fisico_id
       AND cf.estatus = 1

      INNER JOIN tipos_evento_logistico te
        ON te.id_tipo_evento = ?
       AND te.id_tipo_operacion = 2
       AND te.estatus = 1

      WHERE ofe.id_operacion_ferro = ?
        AND cf.id_fisico = ?
        AND ofe.estatus_id NOT IN (13)
      LIMIT 1
    ";

    $row = $this->select($sql, [
      $tipoEvtId,
      $opFerroId,
      $ferroId
    ]);

    return !empty($row);
  }


  public function obtenerEventoActivoPorClave(
    int $opFerroId,
    int $ferroId,
    int $tipoEvtId
  ): ?array {
    if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
      return null;
    }

    $sql = "
      SELECT 
        id_evento,
        operacion_ferro_id,
        contenedor_fisico_id,
        tipo_evento_id,
        fecha,
        comentario,
        estatus
      FROM eventos_ferroviarios
      WHERE operacion_ferro_id = ?
        AND contenedor_fisico_id = ?
        AND tipo_evento_id = ?
        AND estatus = 1
      LIMIT 1
    ";

    $row = $this->select($sql, [
      $opFerroId,
      $ferroId,
      $tipoEvtId
    ]);

    return $row ?: null;
  }


  public function eliminarEventoPorClave(
    int $opFerroId,
    int $ferroId,
    int $tipoEvtId
  ): array {
    if ($opFerroId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
      return [
        'ok'        => false,
        'accion'    => 'error',
        'id_evento' => null,
        'fecha'     => '',
        'msg'       => 'Faltan datos para eliminar la celda.'
      ];
    }

    $actual = $this->obtenerEventoActivoPorClave($opFerroId, $ferroId, $tipoEvtId);

    if (!$actual) {
      return [
        'ok'        => true,
        'accion'    => 'sin_cambios',
        'id_evento' => null,
        'fecha'     => '',
        'msg'       => 'La celda ya estaba vacía.'
      ];
    }

    $idEvento = (int)$actual['id_evento'];

    $sql = "
      UPDATE eventos_ferroviarios
      SET estatus = 0
      WHERE id_evento = ?
      LIMIT 1
    ";

    $ok = (bool)$this->save($sql, [$idEvento]);

    return [
      'ok'        => $ok,
      'accion'    => $ok ? 'eliminado' : 'error',
      'id_evento' => $idEvento,
      'fecha'     => '',
      'msg'       => $ok ? 'Evento eliminado correctamente.' : 'No fue posible eliminar el evento.'
    ];
  }


  public function eliminarEventoPorId(int $idEvento): bool
  {
    if ($idEvento <= 0) return false;

    $sql = "
      UPDATE eventos_ferroviarios
      SET estatus = 0
      WHERE id_evento = ?
      LIMIT 1
    ";

    return (bool)$this->save($sql, [$idEvento]);
  }


  private function fechaSQLValida(string $fecha): bool
  {
    $fecha = trim($fecha);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
      return false;
    }

    [$year, $month, $day] = array_map('intval', explode('-', $fecha));

    return checkdate($month, $day, $year);
  }


  /* ==========================================================
     OBSERVACIONES POR RENGLÓN - EVENTOS TERRESTRES
     Tabla: eventos_terrestres_observaciones

     Llave lógica:
     operacion_id + operacion_ferro_id + contenedor_fisico_id
     ========================================================== */
  private function validarRenglonEventoTerrestre(
    int $operacionId,
    int $opFerroId,
    int $ferroId
  ): bool {
    if ($operacionId <= 0 || $opFerroId <= 0 || $ferroId <= 0) {
      return false;
    }

    $sql = "
      SELECT 
        o.id_operacion,
        ofe.id_operacion_ferro,
        cf.id_fisico
      FROM operacion_ferro_operacion ofo

      INNER JOIN operaciones o
        ON o.id_operacion = ofo.operacion_id

      INNER JOIN operaciones_ferroviarias ofe
        ON ofe.id_operacion_ferro = ofo.operacion_ferro_id

      INNER JOIN contenedores_fisicos cf
        ON cf.id_fisico = ofe.contenedor_fisico_id
       AND cf.estatus = 1

      WHERE o.id_operacion = ?
        AND ofe.id_operacion_ferro = ?
        AND cf.id_fisico = ?
        AND o.estatus_id NOT IN (13)
        AND ofe.estatus_id NOT IN (13)
      LIMIT 1
    ";

    $row = $this->select($sql, [
      $operacionId,
      $opFerroId,
      $ferroId
    ]);

    return !empty($row);
  }


  public function obtenerObservacionRenglon(
    int $operacionId,
    int $opFerroId,
    int $ferroId
  ): ?array {
    if ($operacionId <= 0 || $opFerroId <= 0 || $ferroId <= 0) {
      return null;
    }

    $sql = "
      SELECT
        id_observacion,
        operacion_id,
        operacion_ferro_id,
        contenedor_fisico_id,
        observacion,
        creado_por,
        actualizado_por,
        creado_en,
        actualizado_en,
        estatus
      FROM eventos_terrestres_observaciones
      WHERE operacion_id = ?
        AND operacion_ferro_id = ?
        AND contenedor_fisico_id = ?
        AND estatus = 1
      LIMIT 1
    ";

    $row = $this->select($sql, [
      $operacionId,
      $opFerroId,
      $ferroId
    ]);

    return $row ?: null;
  }


  public function guardarObservacionRenglon(array $data, int $idUsuario = 0): bool
  {
    $operacionId = (int)($data['operacion_id'] ?? 0);
    $opFerroId   = (int)($data['operacion_ferro_id'] ?? 0);
    $ferroId     = (int)($data['contenedor_fisico_id'] ?? 0);
    $observacion = trim((string)($data['observacion'] ?? ''));

    if ($operacionId <= 0 || $opFerroId <= 0 || $ferroId <= 0) {
      return false;
    }

    if (!$this->validarRenglonEventoTerrestre($operacionId, $opFerroId, $ferroId)) {
      return false;
    }

    $usuario = $idUsuario > 0 ? $idUsuario : null;

    if ($observacion === '') {
      $sqlDel = "
        UPDATE eventos_terrestres_observaciones
        SET estatus = 0,
            actualizado_por = ?
        WHERE operacion_id = ?
          AND operacion_ferro_id = ?
          AND contenedor_fisico_id = ?
          AND estatus = 1
        LIMIT 1
      ";

      return (bool)$this->save($sqlDel, [
        $usuario,
        $operacionId,
        $opFerroId,
        $ferroId
      ]);
    }

    $actual = $this->obtenerObservacionRenglon(
      $operacionId,
      $opFerroId,
      $ferroId
    );

    if ($actual) {
      $sqlUpd = "
        UPDATE eventos_terrestres_observaciones
        SET observacion = ?,
            actualizado_por = ?
        WHERE id_observacion = ?
          AND estatus = 1
        LIMIT 1
      ";

      return (bool)$this->save($sqlUpd, [
        $observacion,
        $usuario,
        (int)$actual['id_observacion']
      ]);
    }

    $sqlInactive = "
      SELECT id_observacion
      FROM eventos_terrestres_observaciones
      WHERE operacion_id = ?
        AND operacion_ferro_id = ?
        AND contenedor_fisico_id = ?
        AND estatus = 0
      LIMIT 1
    ";

    $inactive = $this->select($sqlInactive, [
      $operacionId,
      $opFerroId,
      $ferroId
    ]);

    if ($inactive) {
      $sqlReactivar = "
        UPDATE eventos_terrestres_observaciones
        SET observacion = ?,
            estatus = 1,
            actualizado_por = ?
        WHERE id_observacion = ?
        LIMIT 1
      ";

      return (bool)$this->save($sqlReactivar, [
        $observacion,
        $usuario,
        (int)$inactive['id_observacion']
      ]);
    }

    $sqlIns = "
      INSERT INTO eventos_terrestres_observaciones
        (
          operacion_id,
          operacion_ferro_id,
          contenedor_fisico_id,
          observacion,
          creado_por,
          actualizado_por
        )
      VALUES (?, ?, ?, ?, ?, ?)
    ";

    $id = $this->insertar($sqlIns, [
      $operacionId,
      $opFerroId,
      $ferroId,
      $observacion,
      $usuario,
      $usuario
    ]);

    return (int)$id > 0;
  }
}
