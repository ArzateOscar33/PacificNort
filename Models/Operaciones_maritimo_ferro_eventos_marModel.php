<?php

class Operaciones_maritimo_ferro_eventos_marModel extends Query
{
    /* ==========================================================
       EVENTOS MARÍTIMOS - MARÍTIMO FERRO
       Nuevo flujo:
       - Edición directa tipo Excel desde la celda.
       - Un solo método para guardar fecha por celda.
       - Si la celda no existe, inserta.
       - Si la celda ya existe, actualiza.
       - Si la fecha viene vacía, elimina/baja lógica.
       - Tabla principal: eventos_logisticos.
       ========================================================== */


    /* ============================
       LISTADO PAGINADO
       Devuelve pares operación + contenedor marítimo, y sus eventos.
       ============================ */
    public function listarEventosMFPaginado(
        int $page,
        int $perPage,
        ?int $opId = null,
        ?int $contMarOpId = null,
        ?int $clienteId = null,
        string $contenedor = '',
        string $q = ''
    ): array {
        $page    = max(1, $page);
        $perPage = min(1000000, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        $contenedor = trim($contenedor);
        $q          = trim($q);

        /*
          MF = tipo_operacion_id 11.
          Excluimos canceladas/finalizadas igual que en terrestre.
          En tu catálogo actual se usa 13 como CANCELADO y 7 como ENTREGADO.
          Si quieres ver entregados, quita el 7.
        */
        $where[] = "o.tipo_operacion_id = 11";
        $where[] = "o.estatus_id NOT IN (13)";

        if (!empty($opId) && $opId > 0) {
            $where[] = "o.id_operacion = ?";
            $params[] = $opId;
        }

        if (!empty($contMarOpId) && $contMarOpId > 0) {
            $where[] = "cmo.id = ?";
            $params[] = $contMarOpId;
        }

        if (!empty($clienteId) && $clienteId > 0) {
            $where[] = "o.cliente_id = ?";
            $params[] = $clienteId;
        }

        if ($contenedor !== '') {
            $where[] = "LOWER(COALESCE(cm.numero_contenedor, '')) LIKE ?";
            $params[] = '%' . mb_strtolower($contenedor, 'UTF-8') . '%';
        }

        if ($q !== '') {
            $like = '%' . mb_strtolower($q, 'UTF-8') . '%';

            $where[] = "(
                LOWER(COALESCE(o.numero_operacion, '')) LIKE ?
                OR LOWER(COALESCE(cm.numero_contenedor, '')) LIKE ?
                OR LOWER(COALESCE(cli.nombre, '')) LIKE ?
            )";

            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sqlCount = "
            SELECT COUNT(*) AS total_rows
            FROM (
                SELECT 
                    o.id_operacion,
                    cmo.id AS cmo_id
                FROM contenedores_maritimos_operacion cmo

                INNER JOIN operaciones o
                    ON o.id_operacion = cmo.operacion_id

                INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                   AND cm.estatus = 1

                LEFT JOIN clientes cli
                    ON cli.id_cliente = o.cliente_id

                {$whereSql}

                GROUP BY 
                    o.id_operacion,
                    cmo.id
            ) t
        ";

        $rowCount  = $this->select($sqlCount, $params);
        $totalRows = $rowCount ? (int)$rowCount['total_rows'] : 0;

        $sqlPairs = "
            SELECT
                o.id_operacion,
                o.numero_operacion AS operacion,
                o.eta AS arribo_puerto,

                cmo.id AS cmo_id,
                cm.numero_contenedor AS contenedor,

                COALESCE(cli.nombre, '') AS cliente

            FROM contenedores_maritimos_operacion cmo

            INNER JOIN operaciones o
                ON o.id_operacion = cmo.operacion_id

            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
               AND cm.estatus = 1

            LEFT JOIN clientes cli
                ON cli.id_cliente = o.cliente_id

            {$whereSql}

            GROUP BY 
                o.id_operacion,
                cmo.id,
                o.numero_operacion,
                o.eta,
                cm.numero_contenedor,
                cli.nombre

            ORDER BY o.id_operacion DESC, cmo.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $rowsPairs = $this->selectAll($sqlPairs, $params) ?: [];

        if (empty($rowsPairs)) {
            return [
                'rows'     => [],
                'total'    => $totalRows,
                'page'     => $page,
                'per_page' => $perPage
            ];
        }

        $cmoIds = array_values(array_unique(array_map('intval', array_column($rowsPairs, 'cmo_id'))));

        if (empty($cmoIds)) {
            return [
                'rows'     => [],
                'total'    => $totalRows,
                'page'     => $page,
                'per_page' => $perPage
            ];
        }

        $inCmo = implode(',', array_fill(0, count($cmoIds), '?'));

        $sqlEvts = "
            SELECT
                e.id_evento,
                e.operacion_id,
                e.cont_maritimo_operacion_id,
                e.tipo_evento_id,
                te.nombre AS evento,
                e.fecha,
                e.comentario

            FROM eventos_logisticos e

            LEFT JOIN tipos_evento_logistico te
                ON te.id_tipo_evento = e.tipo_evento_id

            WHERE e.estatus = 1
              AND e.cont_maritimo_operacion_id IN ({$inCmo})
        ";

        $rowsEvts = $this->selectAll($sqlEvts, $cmoIds) ?: [];

        $byCmo = [];

        foreach ($rowsPairs as $r) {
            $key = (int)$r['cmo_id'];
            $byCmo[$key] = $r;
        }

        $out = [];

        foreach ($rowsEvts as $e) {
            $key = (int)$e['cont_maritimo_operacion_id'];

            if (!isset($byCmo[$key])) {
                continue;
            }

            $p = $byCmo[$key];

            $out[] = [
                'id_evento'                  => (int)$e['id_evento'],
                'operacion_id'               => (int)$p['id_operacion'],
                'cont_maritimo_operacion_id' => (int)$p['cmo_id'],
                'tipo_evento_id'             => (int)$e['tipo_evento_id'],
                'evento'                     => (string)($e['evento'] ?? ''),
                'fecha'                      => (string)($e['fecha'] ?? ''),
                'comentario'                 => (string)($e['comentario'] ?? ''),

                'operacion'                  => (string)($p['operacion'] ?? ''),
                'contenedor'                 => (string)($p['contenedor'] ?? ''),
                'cliente'                    => (string)($p['cliente'] ?? ''),
                'arribo_puerto'              => (string)($p['arribo_puerto'] ?? ''),
            ];
        }

        $cmosConEvento = array_unique(array_map(
            fn($r) => (int)$r['cont_maritimo_operacion_id'],
            $out
        ));

        foreach ($rowsPairs as $r) {
            $cmoId = (int)$r['cmo_id'];

            if (!in_array($cmoId, $cmosConEvento, true)) {
                $out[] = [
                    'id_evento'                  => null,
                    'operacion_id'               => (int)$r['id_operacion'],
                    'cont_maritimo_operacion_id' => $cmoId,
                    'tipo_evento_id'             => null,
                    'evento'                     => null,
                    'fecha'                      => null,
                    'comentario'                 => null,

                    'operacion'                  => (string)($r['operacion'] ?? ''),
                    'contenedor'                 => (string)($r['contenedor'] ?? ''),
                    'cliente'                    => (string)($r['cliente'] ?? ''),
                    'arribo_puerto'              => (string)($r['arribo_puerto'] ?? ''),
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


    /* ============================
       COLUMNAS / CATÁLOGO MARÍTIMO
       ============================ */
    public function listarEventosMaritimosParaColumnas(): array
    {
        $sql = "
            SELECT 
                id_tipo_evento AS id,
                nombre
            FROM tipos_evento_logistico
            WHERE estatus = 1
              AND id_tipo_operacion = 1
            ORDER BY id_tipo_evento ASC
        ";

        $rows = $this->selectAll($sql);

        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$r) {
            $r['key'] = $this->slugEvento((string)($r['nombre'] ?? ''));
        }

        return $rows;
    }


    public function listarTiposEventoMaritimo(): array
    {
        $sql = "
            SELECT 
                id_tipo_evento,
                nombre
            FROM tipos_evento_logistico
            WHERE estatus = 1
              AND id_tipo_operacion = 1
            ORDER BY id_tipo_evento ASC
        ";

        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }


    private function slugEvento(string $nombre): string
    {
        $s = mb_strtolower(trim($nombre), 'UTF-8');

        $s = strtr($s, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'à' => 'a',
            'è' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
            'ä' => 'a',
            'ë' => 'e',
            'ï' => 'i',
            'ö' => 'o',
            'ÿ' => 'y',
            'ñ' => 'n'
        ]);

        $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
        $s = preg_replace('/_+/', '_', $s);

        return trim($s, '_');
    }


    /* ==========================================================
       NUEVO FLUJO TIPO EXCEL
       Guarda una celda de evento marítimo.

       Recibe:
       - operacion_id
       - cont_maritimo_operacion_id
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
        $operacionId = (int)($data['operacion_id'] ?? 0);
        $cmoId       = (int)($data['cont_maritimo_operacion_id'] ?? 0);
        $tipoEvtId   = (int)($data['tipo_evento_id'] ?? 0);
        $fecha       = trim((string)($data['fecha'] ?? ''));
        $comentario  = trim((string)($data['comentario'] ?? ''));

        $usuario = $idUsuario > 0 ? $idUsuario : null;

        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0) {
            return [
                'ok'        => false,
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => '',
                'msg'       => 'Faltan datos de la celda.'
            ];
        }

        if (!$this->validarCeldaEventoMaritimo($operacionId, $cmoId, $tipoEvtId)) {
            return [
                'ok'        => false,
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => '',
                'msg'       => 'La operación, contenedor marítimo o tipo de evento no es válido.'
            ];
        }

        /*
          Si la fecha viene vacía, se interpreta como limpiar celda.
          Esto permitirá usar Delete/Backspace desde el JS.
        */
        if ($fecha === '') {
            return $this->eliminarEventoPorClave($operacionId, $cmoId, $tipoEvtId);
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

        $actual = $this->obtenerEventoActivoPorClave($operacionId, $cmoId, $tipoEvtId);

        if ($actual) {
            $idEvento = (int)$actual['id_evento'];

            $sqlUpd = "
                UPDATE eventos_logisticos
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
          Requiere índice único:
          uk_evmar_celda_activa
          (cont_maritimo_operacion_id, tipo_evento_id, celda_activa)
        */
        $sqlIns = "
            INSERT INTO eventos_logisticos
                (
                    operacion_id,
                    cont_maritimo_operacion_id,
                    tipo_evento_id,
                    fecha,
                    comentario,
                    creado_por,
                    estatus
                )
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                operacion_id = VALUES(operacion_id),
                fecha = VALUES(fecha),
                comentario = VALUES(comentario),
                estatus = 1,
                id_evento = LAST_INSERT_ID(id_evento)
        ";

        $idInsert = (int)$this->insertar($sqlIns, [
            $operacionId,
            $cmoId,
            $tipoEvtId,
            $fecha,
            $comentario,
            $usuario
        ]);

        $row = $this->obtenerEventoActivoPorClave($operacionId, $cmoId, $tipoEvtId);

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


    private function validarCeldaEventoMaritimo(
        int $operacionId,
        int $cmoId,
        int $tipoEvtId
    ): bool {
        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0) {
            return false;
        }

        $sql = "
            SELECT
                o.id_operacion,
                cmo.id AS cont_maritimo_operacion_id,
                te.id_tipo_evento

            FROM operaciones o

            INNER JOIN contenedores_maritimos_operacion cmo
                ON cmo.operacion_id = o.id_operacion

            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
               AND cm.estatus = 1

            INNER JOIN tipos_evento_logistico te
                ON te.id_tipo_evento = ?
               AND te.id_tipo_operacion = 1
               AND te.estatus = 1

            WHERE o.id_operacion = ?
              AND cmo.id = ?
              AND o.tipo_operacion_id = 11
              AND o.estatus_id NOT IN (13)
            LIMIT 1
        ";

        $row = $this->select($sql, [
            $tipoEvtId,
            $operacionId,
            $cmoId
        ]);

        return !empty($row);
    }


    public function obtenerEventoActivoPorClave(
        int $operacionId,
        int $cmoId,
        int $tipoEvtId
    ): ?array {
        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0) {
            return null;
        }

        $sql = "
            SELECT
                id_evento,
                operacion_id,
                cont_maritimo_operacion_id,
                tipo_evento_id,
                fecha,
                comentario,
                estatus
            FROM eventos_logisticos
            WHERE operacion_id = ?
              AND cont_maritimo_operacion_id = ?
              AND tipo_evento_id = ?
              AND estatus = 1
            LIMIT 1
        ";

        $row = $this->select($sql, [
            $operacionId,
            $cmoId,
            $tipoEvtId
        ]);

        return $row ?: null;
    }


    public function eliminarEventoPorClave(
        int $operacionId,
        int $cmoId,
        int $tipoEvtId
    ): array {
        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0) {
            return [
                'ok'        => false,
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => '',
                'msg'       => 'Faltan datos para eliminar la celda.'
            ];
        }

        $actual = $this->obtenerEventoActivoPorClave($operacionId, $cmoId, $tipoEvtId);

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
            UPDATE eventos_logisticos
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
        if ($idEvento <= 0) {
            return false;
        }

        $sql = "
            UPDATE eventos_logisticos
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
       COMPATIBILIDAD CON FLUJO ANTERIOR
       registrar / actualizar / eliminar
       ========================================================== */
    public function registrar(array $data, int $idUsuario): int
    {
        $res = $this->guardarCeldaEvento($data, $idUsuario);

        if (!is_array($res) || empty($res['ok'])) {
            return 0;
        }

        return (int)($res['id_evento'] ?? 0);
    }


    public function actualizar(array $data): bool
    {
        $idEvento   = (int)($data['id_evento'] ?? 0);
        $operacionId = (int)($data['operacion_id'] ?? 0);
        $cmoId       = (int)($data['cont_maritimo_operacion_id'] ?? 0);
        $tipoEvtId   = (int)($data['tipo_evento_id'] ?? 0);
        $fecha       = trim((string)($data['fecha'] ?? ''));
        $comentario  = trim((string)($data['comentario'] ?? ''));

        if ($idEvento <= 0 || $operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return false;
        }

        if (!$this->validarCeldaEventoMaritimo($operacionId, $cmoId, $tipoEvtId)) {
            return false;
        }

        if (!$this->fechaSQLValida($fecha)) {
            return false;
        }

        $sql = "
            UPDATE eventos_logisticos
            SET operacion_id = ?,
                cont_maritimo_operacion_id = ?,
                tipo_evento_id = ?,
                fecha = ?,
                comentario = ?
            WHERE id_evento = ?
              AND estatus = 1
            LIMIT 1
        ";

        return (bool)$this->save($sql, [
            $operacionId,
            $cmoId,
            $tipoEvtId,
            $fecha,
            $comentario,
            $idEvento
        ]);
    }


    public function obtenerEventoPorClave(int $operacionId, int $cmoId, int $tipoEventoId): ?array
    {
        return $this->obtenerEventoActivoPorClave($operacionId, $cmoId, $tipoEventoId);
    }


    public function eliminar(int $idEvento): bool
    {
        return $this->eliminarEventoPorId($idEvento);
    }


    public function existeEventoMaritimoDuplicado(
        int $contMaritimoOperacionId,
        int $tipoEventoId,
        ?int $excluirId = null
    ): bool {
        if ($contMaritimoOperacionId <= 0 || $tipoEventoId <= 0) {
            return false;
        }

        $sql = "
            SELECT id_evento
            FROM eventos_logisticos
            WHERE estatus = 1
              AND cont_maritimo_operacion_id = ?
              AND tipo_evento_id = ?
        ";

        $params = [
            $contMaritimoOperacionId,
            $tipoEventoId
        ];

        if (!empty($excluirId) && $excluirId > 0) {
            $sql .= " AND id_evento <> ? ";
            $params[] = $excluirId;
        }

        $sql .= " LIMIT 1 ";

        return (bool)$this->select($sql, $params);
    }


    /* ==========================================================
       AUTOCOMPLETES / APOYO A VISTA Y CONTROLADOR ACTUAL
       ========================================================== */
    public function buscarOperacionesMaritimoFerro(string $term, int $limit = 10): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $limit  = max(1, min(20, (int)$limit));
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';
        $isNum  = ctype_digit($term);

        $where = "(
            LOWER(COALESCE(o.numero_operacion, '')) LIKE ?
            OR LOWER(COALESCE(cm.numero_contenedor, '')) LIKE ?
        )";

        $params = [$needle, $needle];

        if ($isNum) {
            $where = "(
                {$where}
                OR o.id_operacion = ?
                OR cmo.id = ?
            )";

            $params[] = (int)$term;
            $params[] = (int)$term;
        }

        $sql = "
            SELECT
                o.id_operacion AS id,
                o.numero_operacion AS label,

                COUNT(DISTINCT cmo.id) AS maritimos,

                GROUP_CONCAT(
                    DISTINCT NULLIF(TRIM(cm.numero_contenedor), '')
                    ORDER BY cm.numero_contenedor ASC
                    SEPARATOR ', '
                ) AS contenedores

            FROM operaciones o

            LEFT JOIN contenedores_maritimos_operacion cmo
                ON cmo.operacion_id = o.id_operacion

            LEFT JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
               AND cm.estatus = 1

            WHERE o.tipo_operacion_id = 11
              AND o.estatus_id NOT IN (13)
              AND {$where}

            GROUP BY o.id_operacion, o.numero_operacion
            ORDER BY o.numero_operacion ASC
            LIMIT {$limit}
        ";

        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }


    public function sugerirOperacionesMF(string $term, int $limit = 8): array
    {
        return $this->buscarOperacionesMaritimoFerro($term, $limit);
    }


    public function buscarContenedoresMarDeOperacion(
        int $operacionId,
        string $term = '',
        int $limit = 15
    ): array {
        if ($operacionId <= 0) {
            return [];
        }

        $limit  = max(1, min(50, (int)$limit));
        $params = [$operacionId];
        $filtro = '';

        $term = trim($term);

        if ($term !== '') {
            $filtro = " AND LOWER(COALESCE(cm.numero_contenedor, '')) LIKE ? ";
            $params[] = '%' . mb_strtolower($term, 'UTF-8') . '%';
        }

        $sql = "
            SELECT
                cmo.id AS id,
                cm.numero_contenedor AS label,
                'MARITIMO' AS tipo

            FROM contenedores_maritimos_operacion cmo

            INNER JOIN operaciones o
                ON o.id_operacion = cmo.operacion_id

            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
               AND cm.estatus = 1

            WHERE cmo.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND o.estatus_id NOT IN (13)
              {$filtro}

            ORDER BY cm.numero_contenedor ASC
            LIMIT {$limit}
        ";

        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }


    public function getContenedorMaritimoDeOperacion(int $operacionId): ?array
    {
        if ($operacionId <= 0) {
            return null;
        }

        $sql = "
            SELECT
                cmo.id AS id,
                cm.numero_contenedor AS label

            FROM contenedores_maritimos_operacion cmo

            INNER JOIN operaciones o
                ON o.id_operacion = cmo.operacion_id

            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
               AND cm.estatus = 1

            WHERE cmo.operacion_id = ?
              AND o.tipo_operacion_id = 11
              AND o.estatus_id NOT IN (13)

            ORDER BY cmo.id ASC
            LIMIT 1
        ";

        $row = $this->select($sql, [$operacionId]);
        return $row ?: null;
    }
}
