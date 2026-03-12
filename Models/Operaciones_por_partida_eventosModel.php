<?php
class Operaciones_por_partida_eventosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ============================
       COLUMNAS (catálogo terrestre)
       ============================ */
    public function listarTiposEventoTerrestre(): array
    {
        $sql = "SELECT id_tipo_evento, nombre
                FROM tipos_evento_logistico
                WHERE estatus = 1
                  AND id_tipo_operacion = 2
                ORDER BY id_tipo_evento ASC";

        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }

    /* =======================================================
       Sugerencias de ENVÍO / FERRO / FACTURA / CLIENTE
       Devuelve el envío de operaciones por partida
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

        $params = [$needle, $needle, $needle, $needle, $needle, $needle];

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
                ) AS factura

            FROM operaciones_partida_envios ope
            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1

            LEFT JOIN transportistas tr
                ON tr.id_transportista = ope.transportista_id
            LEFT JOIN ciudades ci
                ON ci.id_ciudad = ope.destino_ciudad_id

            LEFT JOIN operaciones_partida_envio_detalle det
                ON det.envio_id = ope.id_envio
               AND det.estatus = 1
            LEFT JOIN op_partida_facturas fac
                ON fac.id_factura = det.factura_id
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

    /* ================================================================
       FERRO de un envío por partida
       En este módulo hay 1 ferro por envío (ope.contenedor_fisico_id)
       ================================================================ */
    public function buscarFerrosDeOperacion(int $envioId, string $term = '', int $limit = 10): array
    {
        if ($envioId <= 0) return [];

        $limit  = max(1, (int)$limit);
        $params = [$envioId];
        $filtro = '';

        if ($term !== '') {
            $filtro = " AND LOWER(cf.numero_ferro) LIKE ? ";
            $params[] = '%' . mb_strtolower($term, 'UTF-8') . '%';
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

    /* ======================================================
       Obtener el ferro principal de un envío por partida
       ====================================================== */
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
       Conservé el nombre listarEventosFOPaginado para que luego
       el controlador pueda parecerse mucho al módulo original.
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
        $perPage = min(100, max(1, $perPage));
        $offset  = max(0, ($page - 1) * $perPage);

        $where = ["e.estatus = 1"];
        $params = [];

        if (!empty($opId)) {
            $where[] = "e.id_envio = ?";
            $params[] = $opId;
        }

        if (!empty($ferro)) {
            $where[] = "cf.numero_ferro LIKE ?";
            $params[] = "%" . $ferro . "%";
        }

        if (!empty($transportistaId)) {
            $where[] = "e.transportista_id = ?";
            $params[] = $transportistaId;
        }

        if (!empty($destinoId)) {
            $where[] = "e.destino_ciudad_id = ?";
            $params[] = $destinoId;
        }

        if (!empty($factura)) {
            $where[] = "f.numero_factura LIKE ?";
            $params[] = "%" . $factura . "%";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        /*
         * Subconsulta de facturas/cliente por envío:
         * - Un envío puede tener varias facturas
         * - Las agrupamos en un solo campo
         * - Cliente se toma desde op_partida_facturas -> clientes
         */
        $sqlBase = "
            FROM operaciones_partida_envios e
            INNER JOIN contenedores_fisicos cf 
                ON cf.id_fisico = e.contenedor_fisico_id
            LEFT JOIN ciudades d
                ON d.id_ciudad = e.destino_ciudad_id
            LEFT JOIN transportistas t
                ON t.id_transportista = e.transportista_id
            LEFT JOIN (
                SELECT
                    ed.envio_id,
                    GROUP_CONCAT(DISTINCT f.numero_factura ORDER BY f.numero_factura SEPARATOR ', ') AS facturas,
                    GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS clientes
                FROM operaciones_partida_envio_detalle ed
                INNER JOIN op_partida_facturas f
                    ON f.id_factura = ed.factura_id
                   AND f.estatus = 1
                LEFT JOIN clientes c
                    ON c.id_cliente = f.cliente_id
                   AND c.estatus = 1
                WHERE ed.estatus = 1
                GROUP BY ed.envio_id
            ) fx
                ON fx.envio_id = e.id_envio
            LEFT JOIN eventos_operacion_partida_ferro ev
                ON ev.envio_partida_id = e.id_envio
               AND ev.contenedor_fisico_id = e.contenedor_fisico_id
               AND ev.estatus = 1
            LEFT JOIN tipos_evento_logistico te
                ON te.id_tipo_evento = ev.tipo_evento_id
               AND te.estatus = 1
            $whereSql
        ";

        $sqlCount = "
            SELECT COUNT(*) AS total
            FROM (
                SELECT
                    e.id_envio,
                    e.contenedor_fisico_id,
                    COALESCE(ev.tipo_evento_id, 0) AS tipo_evento_id
                $sqlBase
                GROUP BY e.id_envio, e.contenedor_fisico_id, COALESCE(ev.tipo_evento_id, 0)
            ) q
        ";

        $totalRow = $this->select($sqlCount, $params);
        $total = !empty($totalRow) ? (int)$totalRow['total'] : 0;

        $sqlData = "
            SELECT
                e.id_envio AS operacion_ferro_id,
                e.contenedor_fisico_id,
                CONCAT('ENV-', e.id_envio) AS operacion,
                cf.numero_ferro AS ferro,
                COALESCE(fx.facturas, '') AS factura,
                COALESCE(fx.clientes, '') AS cliente,
                COALESCE(d.nombre_ciudad, '') AS destino,
                COALESCE(t.nombre, '') AS transportista,

                ev.id_evento,
                ev.tipo_evento_id,
                COALESCE(te.nombre, '') AS evento,
                ev.fecha,
                ev.comentario
            $sqlBase
            GROUP BY
                e.id_envio,
                e.contenedor_fisico_id,
                cf.numero_ferro,
                fx.facturas,
                fx.clientes,
                d.nombre_ciudad,
                t.nombre,
                ev.id_evento,
                ev.tipo_evento_id,
                te.nombre,
                ev.fecha,
                ev.comentario
            ORDER BY e.id_envio DESC, cf.numero_ferro ASC, ev.tipo_evento_id ASC
            LIMIT $offset, $perPage
        ";

        $rows = $this->selectAll($sqlData, $params);

        return [
            'total' => $total,
            'data'  => $rows
        ];
    }

    public function eventosFerroColumnas(): array
    {
        $sql = "SELECT id_tipo_evento AS id, nombre
                FROM tipos_evento_logistico
                WHERE estatus = 1
                ORDER BY id_tipo_evento ASC";
        return $this->selectAll($sql);
    }

    /* ==========================================================
       VALIDACIONES + CRUD
       ========================================================== */

    private function existeEventoFerroDuplicado(
        int $envioId,
        int $ferroId,
        int $tipoEvtId,
        ?int $excluirId = null
    ): bool {
        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) return false;

        $sql = "SELECT id_evento
                FROM eventos_operacion_partida_ferro
                WHERE estatus = 1
                  AND envio_partida_id = ?
                  AND contenedor_fisico_id = ?
                  AND tipo_evento_id = ?"
            . ($excluirId ? " AND id_evento <> ?" : "")
            . " LIMIT 1";

        $params = $excluirId
            ? [$envioId, $ferroId, $tipoEvtId, $excluirId]
            : [$envioId, $ferroId, $tipoEvtId];

        return (bool)$this->select($sql, $params);
    }

    public function registrar(array $data, int $idUsuario): int
    {
        $envioId    = (int)($data['operacion_ferro_id'] ?? $data['envio_partida_id'] ?? 0);
        $ferroId    = (int)($data['contenedor_fisico_id'] ?? 0);
        $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
        $fecha      = (string)($data['fecha'] ?? '');
        $comentario = $data['comentario'] ?? null;

        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return 0;
        }

        // 1) El envío debe existir
        $rowOp = $this->select("
            SELECT id_envio, contenedor_fisico_id
            FROM operaciones_partida_envios
            WHERE id_envio = ?
              AND estatus = 1
            LIMIT 1
        ", [$envioId]);

        if (!$rowOp) return 0;

        // 2) El ferro debe estar activo y corresponder al envío
        $rowF = $this->select("
            SELECT cf.id_fisico
            FROM operaciones_partida_envios ope
            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1
            WHERE ope.id_envio = ?
              AND ope.contenedor_fisico_id = ?
              AND ope.estatus = 1
            LIMIT 1
        ", [$envioId, $ferroId]);

        if (!$rowF) return 0;

        // 3) Tipo de evento terrestre activo
        $rowEvt = $this->select("
            SELECT id_tipo_evento
            FROM tipos_evento_logistico
            WHERE id_tipo_evento = ?
              AND id_tipo_operacion = 2
              AND estatus = 1
            LIMIT 1
        ", [$tipoEvtId]);

        if (!$rowEvt) return 0;

        // 4) No duplicar
        if ($this->existeEventoFerroDuplicado($envioId, $ferroId, $tipoEvtId)) {
            return 0;
        }

        // 5) Insertar
        $sqlIns = "INSERT INTO eventos_operacion_partida_ferro
                    (envio_partida_id, contenedor_fisico_id, tipo_evento_id, fecha, comentario, creado_por)
                   VALUES (?, ?, ?, ?, ?, ?)";

        $params = [$envioId, $ferroId, $tipoEvtId, $fecha, $comentario, ($idUsuario ?: null)];

        return (int)$this->insertar($sqlIns, $params);
    }

    public function actualizar(array $data): bool
    {
        $idEvento   = (int)($data['id_evento'] ?? 0);
        $envioId    = (int)($data['operacion_ferro_id'] ?? $data['envio_partida_id'] ?? 0);
        $ferroId    = (int)($data['contenedor_fisico_id'] ?? 0);
        $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
        $fecha      = (string)($data['fecha'] ?? '');
        $comentario = $data['comentario'] ?? null;

        if ($idEvento <= 0 || $envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return false;
        }

        $rowOp = $this->select("
            SELECT id_envio
            FROM operaciones_partida_envios
            WHERE id_envio = ?
              AND estatus = 1
            LIMIT 1
        ", [$envioId]);

        if (!$rowOp) return false;

        $rowF = $this->select("
            SELECT cf.id_fisico
            FROM operaciones_partida_envios ope
            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ope.contenedor_fisico_id
               AND cf.estatus = 1
            WHERE ope.id_envio = ?
              AND ope.contenedor_fisico_id = ?
              AND ope.estatus = 1
            LIMIT 1
        ", [$envioId, $ferroId]);

        if (!$rowF) return false;

        $rowEvt = $this->select("
            SELECT id_tipo_evento
            FROM tipos_evento_logistico
            WHERE id_tipo_evento = ?
              AND id_tipo_operacion = 2
              AND estatus = 1
            LIMIT 1
        ", [$tipoEvtId]);

        if (!$rowEvt) return false;

        if ($this->existeEventoFerroDuplicado($envioId, $ferroId, $tipoEvtId, $idEvento)) {
            return false;
        }

        $sql = "UPDATE eventos_operacion_partida_ferro
                SET envio_partida_id = ?,
                    contenedor_fisico_id = ?,
                    tipo_evento_id = ?,
                    fecha = ?,
                    comentario = ?
                WHERE id_evento = ?
                  AND estatus = 1";

        $params = [$envioId, $ferroId, $tipoEvtId, $fecha, $comentario, $idEvento];

        return (bool)$this->save($sql, $params);
    }

    public function eliminar(int $idEvento): bool
    {
        if ($idEvento <= 0) return false;

        $sql = "UPDATE eventos_operacion_partida_ferro
                SET estatus = 0
                WHERE id_evento = ?
                LIMIT 1";

        return (bool)$this->save($sql, [$idEvento]);
    }

    public function obtenerEventoPorClave(int $envioId, int $ferroId, int $tipoEvtId): ?array
    {
        if ($envioId <= 0 || $ferroId <= 0 || $tipoEvtId <= 0) return null;

        $sql = "SELECT
                    e.id_evento,
                    e.envio_partida_id AS operacion_ferro_id,
                    e.contenedor_fisico_id,
                    e.tipo_evento_id,
                    e.fecha,
                    e.comentario
                FROM eventos_operacion_partida_ferro e
                WHERE e.estatus = 1
                  AND e.envio_partida_id = ?
                  AND e.contenedor_fisico_id = ?
                  AND e.tipo_evento_id = ?
                LIMIT 1";

        $row = $this->select($sql, [$envioId, $ferroId, $tipoEvtId]);
        return $row ?: null;
    }
}
