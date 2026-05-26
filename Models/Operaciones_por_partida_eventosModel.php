<?php

class Operaciones_por_partida_eventosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ==========================================================
       EVENTOS OPERACIONES POR PARTIDA - FERRO / CAJA
       Flujo tipo Excel:
       - Edición directa desde celda.
       - Si no existe la celda: inserta.
       - Si ya existe la celda: actualiza.
       - Si fecha viene vacía: elimina/baja lógica.
       - Llave lógica:
         envio_partida_id + contenedor_fisico_id + tipo_evento_id
       ========================================================== */


    /* ============================
       COLUMNAS / CATÁLOGO TERRESTRE
       ============================ */
    public function listarTiposEventoTerrestre(): array
    {
        $sql = "
            SELECT 
                id_tipo_evento, 
                nombre
            FROM tipos_evento_logistico
            WHERE estatus = 1
              AND id_tipo_operacion = 2
            ORDER BY id_tipo_evento ASC
        ";

        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }


    /* =======================================================
       SUGERENCIAS DE ENVÍO / FERRO / FACTURA / CLIENTE
       Devuelve el envío de operaciones por partida.
       ======================================================= */
    public function sugerirOperacionesPartidaOFerro(string $term, int $limit = 8): array
    {
        $term = trim($term);
        if ($term === '') return [];

        $limit  = max(1, min(20, (int)$limit));
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';
        $isNum  = ctype_digit($term);

        $where = "(
            LOWER(COALESCE(CAST(ope.id_envio AS CHAR), '')) LIKE ?
            OR LOWER(COALESCE(cf.numero_ferro, '')) LIKE ?
            OR LOWER(COALESCE(tr.nombre, '')) LIKE ?
            OR LOWER(COALESCE(ci.nombre_ciudad, '')) LIKE ?
            OR LOWER(COALESCE(cli.nombre, '')) LIKE ?
            OR LOWER(COALESCE(fac.numero_factura, '')) LIKE ?
        )";

        $params = [
            $needle,
            $needle,
            $needle,
            $needle,
            $needle,
            $needle
        ];

        if ($isNum) {
            $where = "(
                {$where}
                OR ope.id_envio = ?
            )";

            $params[] = (int)$term;
        }

        $sql = "
            SELECT
                ope.id_envio AS id,
                CONCAT('ENV-', ope.id_envio) AS label,

                GROUP_CONCAT(
                    DISTINCT NULLIF(TRIM(cf.numero_ferro), '')
                    ORDER BY cf.numero_ferro ASC
                    SEPARATOR ', '
                ) AS ferro,

                GROUP_CONCAT(
                    DISTINCT NULLIF(TRIM(fac.numero_factura), '')
                    ORDER BY fac.numero_factura ASC
                    SEPARATOR ', '
                ) AS factura,

                GROUP_CONCAT(
                    DISTINCT NULLIF(TRIM(cli.nombre), '')
                    ORDER BY cli.nombre ASC
                    SEPARATOR ', '
                ) AS cliente

            FROM operaciones_partida_envios ope

            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1

            LEFT JOIN transportistas tr
                ON tr.id_transportista = ope.transportista_id

            LEFT JOIN ciudades ci
                ON ci.id_ciudad = ope.destino_ciudad_id

            LEFT JOIN operaciones_partida_envio_detalle ed
                ON ed.envio_id = ope.id_envio
               AND ed.estatus = 1

            LEFT JOIN op_partida_facturas fac
                ON fac.id_factura = ed.factura_id
               AND fac.estatus = 1

            LEFT JOIN clientes cli
                ON cli.id_cliente = fac.cliente_id
               AND cli.estatus = 1

            WHERE ope.estatus = 1
              AND {$where}

            GROUP BY ope.id_envio
            ORDER BY ope.id_envio DESC
            LIMIT {$limit}
        ";

        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }


    /* =======================================================
       FERRO / CAJA DE UN ENVÍO POR PARTIDA
       ======================================================= */
    public function buscarFerrosDeOperacion(int $envioId, string $term = '', int $limit = 10): array
    {
        if ($envioId <= 0) return [];

        $limit  = max(1, min(50, (int)$limit));
        $params = [$envioId];
        $filtro = '';

        if (trim($term) !== '') {
            $filtro = " AND LOWER(cf.numero_ferro) LIKE ? ";
            $params[] = '%' . mb_strtolower(trim($term), 'UTF-8') . '%';
        }

        $sql = "
            SELECT
                cf.id_fisico    AS id,
                cf.numero_ferro AS label,
                'FERRO'         AS tipo
            FROM operaciones_partida_envios ope

            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1

            WHERE ope.id_envio = ?
              AND ope.estatus = 1
              {$filtro}

            ORDER BY cf.numero_ferro ASC
            LIMIT {$limit}
        ";

        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }


    public function getFerroDeOperacion(int $envioId): ?array
    {
        if ($envioId <= 0) return null;

        $sql = "
            SELECT
                cf.id_fisico    AS id,
                cf.numero_ferro AS label
            FROM operaciones_partida_envios ope

            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1

            WHERE ope.id_envio = ?
              AND ope.estatus = 1

            LIMIT 1
        ";

        $row = $this->select($sql, [$envioId]);
        return $row ?: null;
    }


    /* ==========================================================
       LISTADO PAGINADO
       Devuelve pares envío + ferro/caja, y sus eventos.
       
       Se conserva el nombre listarPaginado porque tu módulo actual
       ya trabaja con ese método.
       ========================================================== */
    public function listarPaginado(
        int $page,
        int $perPage,
        ?int $opId = null,
        string $factura = '',
        string $ferro = '',
        ?int $transportistaId = null,
        ?int $destinoId = null
    ): array {
        $page    = max(1, $page);
        $perPage = min(10000, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        $factura = trim($factura);
        $ferro   = trim($ferro);

        $where[] = "e.estatus = 1";
        $where[] = "cf.estatus = 1";

        if (!empty($opId) && $opId > 0) {
            $where[] = "e.id_envio = ?";
            $params[] = $opId;
        }

        if ($ferro !== '') {
            $where[] = "LOWER(COALESCE(cf.numero_ferro, '')) LIKE ?";
            $params[] = '%' . mb_strtolower($ferro, 'UTF-8') . '%';
        }

        if (!empty($transportistaId) && $transportistaId > 0) {
            $where[] = "e.transportista_id = ?";
            $params[] = $transportistaId;
        }

        if (!empty($destinoId) && $destinoId > 0) {
            $where[] = "e.destino_ciudad_id = ?";
            $params[] = $destinoId;
        }

        if ($factura !== '') {
            $where[] = "LOWER(COALESCE(fx.facturas, '')) LIKE ?";
            $params[] = '%' . mb_strtolower($factura, 'UTF-8') . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sqlFx = "
            SELECT
                ed.envio_id,

                GROUP_CONCAT(
                    DISTINCT f.numero_factura
                    ORDER BY f.numero_factura ASC
                    SEPARATOR ', '
                ) AS facturas,

                GROUP_CONCAT(
                    DISTINCT c.nombre
                    ORDER BY c.nombre ASC
                    SEPARATOR ', '
                ) AS clientes

            FROM operaciones_partida_envio_detalle ed

            INNER JOIN op_partida_facturas f
                ON f.id_factura = ed.factura_id
               AND f.estatus = 1

            LEFT JOIN clientes c
                ON c.id_cliente = f.cliente_id
               AND c.estatus = 1

            WHERE ed.estatus = 1

            GROUP BY ed.envio_id
        ";

        $sqlBase = "
            FROM operaciones_partida_envios e

            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = e.contenedor_fisico_id
               AND cf.estatus = 1

            LEFT JOIN ciudades d
                ON d.id_ciudad = e.destino_ciudad_id

            LEFT JOIN transportistas t
                ON t.id_transportista = e.transportista_id

            LEFT JOIN ({$sqlFx}) fx
                ON fx.envio_id = e.id_envio

            {$whereSql}
        ";

        /*
          Conteo correcto:
          Cuenta pares envío + ferro/caja, NO eventos.
          Esto evita que la paginación crezca artificialmente cuando
          un mismo envío tiene varios eventos.
        */
        $sqlCount = "
            SELECT COUNT(*) AS total
            FROM (
                SELECT
                    e.id_envio,
                    e.contenedor_fisico_id
                {$sqlBase}
                GROUP BY
                    e.id_envio,
                    e.contenedor_fisico_id
            ) q
        ";

        $rowCount = $this->select($sqlCount, $params);
        $total    = $rowCount ? (int)$rowCount['total'] : 0;

        $sqlPairs = "
            SELECT
                e.id_envio AS envio_partida_id,
                e.id_envio AS operacion_ferro_id,
                e.contenedor_fisico_id,

                CONCAT('ENV-', e.id_envio) AS operacion,
                CONCAT('ENV-', e.id_envio) AS operacion_maritima,

                cf.numero_ferro AS ferro,

                COALESCE(fx.facturas, '') AS factura,
                COALESCE(fx.clientes, '') AS cliente,

                COALESCE(d.nombre_ciudad, '') AS destino,
                COALESCE(t.nombre, '') AS transportista

            {$sqlBase}

            GROUP BY
                e.id_envio,
                e.contenedor_fisico_id,
                cf.numero_ferro,
                fx.facturas,
                fx.clientes,
                d.nombre_ciudad,
                t.nombre

            ORDER BY e.id_envio DESC, cf.numero_ferro ASC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $rowsPairs = $this->selectAll($sqlPairs, $params) ?: [];

        if (empty($rowsPairs)) {
            return [
                'rows'     => [],
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage
            ];
        }

        $envioIds = array_values(array_unique(array_map(
            'intval',
            array_column($rowsPairs, 'envio_partida_id')
        )));

        $ferroIds = array_values(array_unique(array_map(
            'intval',
            array_column($rowsPairs, 'contenedor_fisico_id')
        )));

        if (empty($envioIds) || empty($ferroIds)) {
            return [
                'rows'     => [],
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage
            ];
        }

        $inEnvios = implode(',', array_fill(0, count($envioIds), '?'));
        $inFerros = implode(',', array_fill(0, count($ferroIds), '?'));

        $paramsEvt = array_merge($envioIds, $ferroIds);

        $sqlEvts = "
            SELECT
                ev.id_evento,
                ev.envio_partida_id,
                ev.envio_partida_id AS operacion_ferro_id,
                ev.contenedor_fisico_id,
                ev.tipo_evento_id,
                te.nombre AS evento,
                ev.fecha,
                ev.comentario

            FROM eventos_operacion_partida_ferro ev

            LEFT JOIN tipos_evento_logistico te
                ON te.id_tipo_evento = ev.tipo_evento_id
               AND te.estatus = 1

            WHERE ev.estatus = 1
              AND ev.envio_partida_id IN ({$inEnvios})
              AND ev.contenedor_fisico_id IN ({$inFerros})
        ";

        $rowsEvts = $this->selectAll($sqlEvts, $paramsEvt) ?: [];

        $byPair = [];

        foreach ($rowsPairs as $p) {
            $key = (int)$p['envio_partida_id'] . '_' . (int)$p['contenedor_fisico_id'];
            $byPair[$key] = $p;
        }

        $out = [];

        foreach ($rowsEvts as $e) {
            $key = (int)$e['envio_partida_id'] . '_' . (int)$e['contenedor_fisico_id'];

            if (!isset($byPair[$key])) {
                continue;
            }

            $p = $byPair[$key];

            $out[] = [
                'id_evento'            => (int)$e['id_evento'],

                'envio_partida_id'     => (int)$e['envio_partida_id'],
                'operacion_ferro_id'   => (int)$e['envio_partida_id'],

                'contenedor_fisico_id' => (int)$e['contenedor_fisico_id'],
                'tipo_evento_id'       => (int)$e['tipo_evento_id'],
                'evento'               => (string)($e['evento'] ?? ''),
                'fecha'                => (string)($e['fecha'] ?? ''),
                'comentario'           => (string)($e['comentario'] ?? ''),

                'operacion'            => (string)($p['operacion'] ?? ''),
                'operacion_maritima'   => (string)($p['operacion_maritima'] ?? ''),
                'ferro'                => (string)($p['ferro'] ?? ''),
                'factura'              => (string)($p['factura'] ?? ''),
                'cliente'              => (string)($p['cliente'] ?? ''),
                'destino'              => (string)($p['destino'] ?? ''),
                'transportista'        => (string)($p['transportista'] ?? '')
            ];
        }

        /*
          Agregar renglones sin evento para que también aparezcan
          celdas vacías editables.
        */
        $pairsConEvento = array_unique(array_map(
            fn($r) => (int)$r['envio_partida_id'] . '_' . (int)$r['contenedor_fisico_id'],
            $out
        ));

        foreach ($rowsPairs as $p) {
            $key = (int)$p['envio_partida_id'] . '_' . (int)$p['contenedor_fisico_id'];

            if (!in_array($key, $pairsConEvento, true)) {
                $out[] = [
                    'id_evento'            => null,

                    'envio_partida_id'     => (int)$p['envio_partida_id'],
                    'operacion_ferro_id'   => (int)$p['envio_partida_id'],

                    'contenedor_fisico_id' => (int)$p['contenedor_fisico_id'],
                    'tipo_evento_id'       => null,
                    'evento'               => null,
                    'fecha'                => null,
                    'comentario'           => null,

                    'operacion'            => (string)($p['operacion'] ?? ''),
                    'operacion_maritima'   => (string)($p['operacion_maritima'] ?? ''),
                    'ferro'                => (string)($p['ferro'] ?? ''),
                    'factura'              => (string)($p['factura'] ?? ''),
                    'cliente'              => (string)($p['cliente'] ?? ''),
                    'destino'              => (string)($p['destino'] ?? ''),
                    'transportista'        => (string)($p['transportista'] ?? '')
                ];
            }
        }

        return [
            'rows'     => $out,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage
        ];
    }


    /*
      Alias por compatibilidad si después quieres que el controlador
      se parezca al de eventos terrestres.
    */
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
        return $this->listarPaginado(
            $page,
            $perPage,
            $opId,
            $q,
            $ferro,
            $transportistaId,
            $destinoId
        );
    }


    /* ==========================================================
       NUEVO FLUJO TIPO EXCEL
       Guarda una celda de evento por partida.

       Recibe:
       - envio_partida_id u operacion_ferro_id
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
        /*
          Aceptamos ambos nombres:
          - envio_partida_id: nombre real correcto.
          - operacion_ferro_id: alias para no romper el JS/controlador actual.
        */
        $envioId = (int)(
            $data['envio_partida_id']
            ?? $data['operacion_ferro_id']
            ?? 0
        );

        $ferroId    = (int)($data['contenedor_fisico_id'] ?? 0);
        $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
        $fecha      = trim((string)($data['fecha'] ?? ''));
        $comentario = trim((string)($data['comentario'] ?? ''));

        $usuario = $idUsuario > 0 ? $idUsuario : null;

        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
            return [
                'ok'        => false,
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => '',
                'msg'       => 'Faltan datos de la celda.'
            ];
        }

        if (!$this->validarCeldaEventoPartida($envioId, $ferroId, $tipoEvtId)) {
            return [
                'ok'        => false,
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => '',
                'msg'       => 'El envío, ferro/caja o tipo de evento no es válido.'
            ];
        }

        /*
          Si la fecha viene vacía, se interpreta como limpiar celda.
          Esto permite que el JS borre con Delete/Backspace.
        */
        if ($fecha === '') {
            return $this->eliminarEventoPorClave($envioId, $ferroId, $tipoEvtId);
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

        $actual = $this->obtenerEventoActivoPorClave($envioId, $ferroId, $tipoEvtId);

        if ($actual) {
            $idEvento = (int)$actual['id_evento'];

            $sqlUpd = "
                UPDATE eventos_operacion_partida_ferro
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
                'msg'       => $ok
                    ? 'Evento actualizado correctamente.'
                    : 'No fue posible actualizar el evento.'
            ];
        }

        /*
          Requiere el índice único:
          uk_eopf_celda_activa
          (
            envio_partida_id,
            contenedor_fisico_id,
            tipo_evento_id,
            celda_activa
          )

          Esto evita duplicados si dos usuarios guardan la misma celda.
        */
        $sqlIns = "
            INSERT INTO eventos_operacion_partida_ferro
                (
                    envio_partida_id,
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
            $envioId,
            $ferroId,
            $tipoEvtId,
            $fecha,
            $comentario,
            $usuario
        ]);

        $row = $this->obtenerEventoActivoPorClave($envioId, $ferroId, $tipoEvtId);

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


    private function validarCeldaEventoPartida(
        int $envioId,
        int $ferroId,
        int $tipoEvtId
    ): bool {
        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
            return false;
        }

        $sql = "
            SELECT
                ope.id_envio,
                cf.id_fisico,
                te.id_tipo_evento

            FROM operaciones_partida_envios ope

            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1

            INNER JOIN tipos_evento_logistico te
                ON te.id_tipo_evento = ?
               AND te.id_tipo_operacion = 2
               AND te.estatus = 1

            WHERE ope.id_envio = ?
              AND ope.contenedor_fisico_id = ?
              AND ope.estatus = 1

            LIMIT 1
        ";

        $row = $this->select($sql, [
            $tipoEvtId,
            $envioId,
            $ferroId
        ]);

        return !empty($row);
    }


    public function obtenerEventoActivoPorClave(
        int $envioId,
        int $ferroId,
        int $tipoEvtId
    ): ?array {
        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
            return null;
        }

        $sql = "
            SELECT
                id_evento,
                envio_partida_id,
                envio_partida_id AS operacion_ferro_id,
                contenedor_fisico_id,
                tipo_evento_id,
                fecha,
                comentario,
                estatus
            FROM eventos_operacion_partida_ferro
            WHERE envio_partida_id = ?
              AND contenedor_fisico_id = ?
              AND tipo_evento_id = ?
              AND estatus = 1
            LIMIT 1
        ";

        $row = $this->select($sql, [
            $envioId,
            $ferroId,
            $tipoEvtId
        ]);

        return $row ?: null;
    }


    public function eliminarEventoPorClave(
        int $envioId,
        int $ferroId,
        int $tipoEvtId
    ): array {
        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
            return [
                'ok'        => false,
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => '',
                'msg'       => 'Faltan datos para eliminar la celda.'
            ];
        }

        $actual = $this->obtenerEventoActivoPorClave($envioId, $ferroId, $tipoEvtId);

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
            UPDATE eventos_operacion_partida_ferro
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
            'msg'       => $ok
                ? 'Evento eliminado correctamente.'
                : 'No fue posible eliminar el evento.'
        ];
    }


    public function eliminarEventoPorId(int $idEvento): bool
    {
        if ($idEvento <= 0) return false;

        $sql = "
            UPDATE eventos_operacion_partida_ferro
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
       MÉTODOS VIEJOS CONSERVADOS POR COMPATIBILIDAD
       El nuevo flujo debe usar guardarCeldaEvento().
       ========================================================== */

    public function registrar(array $data, int $idUsuario = 0): int
    {
        $res = $this->guardarCeldaEvento($data, $idUsuario);

        if (!is_array($res) || empty($res['ok'])) {
            return 0;
        }

        return (int)($res['id_evento'] ?? 0);
    }


    public function actualizar(array $data): bool
    {
        $idEvento = (int)($data['id_evento'] ?? 0);

        if ($idEvento <= 0) {
            return false;
        }

        $envioId = (int)(
            $data['envio_partida_id']
            ?? $data['operacion_ferro_id']
            ?? 0
        );

        $ferroId    = (int)($data['contenedor_fisico_id'] ?? 0);
        $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
        $fecha      = trim((string)($data['fecha'] ?? ''));
        $comentario = trim((string)($data['comentario'] ?? ''));

        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return false;
        }

        if (!$this->fechaSQLValida($fecha)) {
            return false;
        }

        if (!$this->validarCeldaEventoPartida($envioId, $ferroId, $tipoEvtId)) {
            return false;
        }

        $sql = "
            UPDATE eventos_operacion_partida_ferro
            SET envio_partida_id = ?,
                contenedor_fisico_id = ?,
                tipo_evento_id = ?,
                fecha = ?,
                comentario = ?
            WHERE id_evento = ?
              AND estatus = 1
            LIMIT 1
        ";

        return (bool)$this->save($sql, [
            $envioId,
            $ferroId,
            $tipoEvtId,
            $fecha,
            $comentario,
            $idEvento
        ]);
    }


    public function eliminar(int $idEvento): bool
    {
        return $this->eliminarEventoPorId($idEvento);
    }


    public function obtenerEventoPorClave(int $envioId, int $ferroId, int $tipoEvtId): ?array
    {
        return $this->obtenerEventoActivoPorClave($envioId, $ferroId, $tipoEvtId);
    }


    public function existeEventoFerroDuplicado(
        int $envioId,
        int $ferroId,
        int $tipoEvtId,
        int $exceptId = 0
    ): bool {
        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) {
            return false;
        }

        $params = [$envioId, $ferroId, $tipoEvtId];

        $extra = '';

        if ($exceptId > 0) {
            $extra = " AND id_evento <> ? ";
            $params[] = $exceptId;
        }

        $sql = "
            SELECT id_evento
            FROM eventos_operacion_partida_ferro
            WHERE envio_partida_id = ?
              AND contenedor_fisico_id = ?
              AND tipo_evento_id = ?
              AND estatus = 1
              {$extra}
            LIMIT 1
        ";

        $row = $this->select($sql, $params);

        return !empty($row);
    }
}
