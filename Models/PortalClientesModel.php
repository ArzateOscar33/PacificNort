<?php

class PortalClientesModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
    // usuario para cuenta pendientes de vinculacion
    public function getUsuarioById(int $idUsuario): array
    {
        $sql = "SELECT id_usuario, nombre, correo, cliente_id
            FROM usuarios
            WHERE id_usuario = ?
            LIMIT 1";

        return $this->select($sql, [$idUsuario]) ?: [];
    }


    // Datos de sesión
    public function getNombreCliente(): string
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
        if ($clienteId <= 0) return '';

        $sql = "SELECT nombre FROM clientes WHERE id_cliente = ? LIMIT 1";
        $row = $this->select($sql, [$clienteId]);

        return $row['nombre'] ?? '';
    }

    public function getNombreUsuario(): string
    {
        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        if ($usuarioId <= 0) return '';

        $sql = "SELECT nombre FROM usuarios WHERE id_usuario = ? LIMIT 1";
        $row = $this->select($sql, [$usuarioId]);

        return $row['nombre'] ?? '';
    }
    //datos para filtros
    public function getEstatusOp(): array
    {
        $sql = "SELECT id_estatus, nombre FROM estatus ORDER BY id_estatus";
        return $this->selectAll($sql);
    }

    public function listarOperacionesCliente(array $filtros): array
    {
        $clienteId = (int)($filtros['cliente_id'] ?? 0);
        if ($clienteId <= 0) return ['rows' => [], 'total' => 0];

        $search    = trim((string)($filtros['search'] ?? ''));
        $tipoClave = trim((string)($filtros['tipo'] ?? ''));       // "MAR" | "LBMF" | ""
        $estatus   = (int)($filtros['estatus'] ?? 0);              // 0 = todos
        $etaIni    = trim((string)($filtros['eta_ini'] ?? ''));    // YYYY-MM-DD
        $etaFin    = trim((string)($filtros['eta_fin'] ?? ''));    // YYYY-MM-DD

        $page     = max(1, (int)($filtros['page'] ?? 1));
        $pageSize = (int)($filtros['page_size'] ?? 15);
        if (!in_array($pageSize, [15, 30, 50], true)) $pageSize = 15;

        $offset = ($page - 1) * $pageSize;

        $where  = " WHERE o.cliente_id = ? ";
        $params = [$clienteId];

        // Tipo
        if ($tipoClave !== '') {
            $where .= " AND st.clave = ? ";
            $params[] = $tipoClave;
        }

        // Estatus
        if ($estatus > 0) {
            $where .= " AND o.estatus_id = ? ";
            $params[] = $estatus;
        }

        // Rango ETA (si o.eta es DATETIME, esto evita broncas por hora)
        if ($etaIni !== '') {
            $where .= " AND DATE(o.eta) >= ? ";
            $params[] = $etaIni;
        }
        if ($etaFin !== '') {
            $where .= " AND DATE(o.eta) <= ? ";
            $params[] = $etaFin;
        }

        // Search
        if ($search !== '') {
            $where .= " AND (
            o.numero_operacion LIKE ?
            OR o.numero_bl LIKE ?
            OR cm.numero_contenedor LIKE ?
        ) ";
            $q = '%' . $search . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        // Total
        $sqlTotal = "
        SELECT COUNT(DISTINCT o.id_operacion) AS total
        FROM operaciones o
        LEFT JOIN subtipos_operacion st
               ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN estatus e
               ON e.id_estatus = o.estatus_id
        LEFT JOIN contenedores_maritimos_operacion cmo
               ON cmo.operacion_id = o.id_operacion
        LEFT JOIN contenedores_maritimos cm
               ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        $where
        ";

        $rowTotal = $this->select($sqlTotal, $params);
        $total = $rowTotal ? (int)$rowTotal['total'] : 0;

        // Rows
        $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            o.numero_bl,
            o.etd,
            o.eta,
            o.estatus_id,
            e.nombre AS estatus,
            st.clave  AS tipo_clave,
            st.nombre AS tipo_nombre,
            GROUP_CONCAT(DISTINCT cm.numero_contenedor ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
        FROM operaciones o
        LEFT JOIN subtipos_operacion st
            ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN estatus e
            ON e.id_estatus = o.estatus_id
        LEFT JOIN contenedores_maritimos_operacion cmo
            ON cmo.operacion_id = o.id_operacion
        LEFT JOIN contenedores_maritimos cm
            ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        $where
        GROUP BY o.id_operacion
        ORDER BY o.id_operacion DESC
        LIMIT $pageSize OFFSET $offset
    ";

        $rows = $this->selectAll($sql, $params) ?: [];

        return ['rows' => $rows, 'total' => $total];
    }


    public function obtenerDetalleMaritima(int $clienteId, int $operacionId): ?array
    {
        if ($clienteId <= 0 || $operacionId <= 0) return null;

        $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            o.numero_bl,
            o.etd,
            o.eta,
            o.estatus_id,
            e.nombre AS estatus,

            st.clave  AS tipo_clave,
            st.nombre AS tipo_nombre,

            COALESCE(c.nombre,'') AS cliente,

            COALESCE(nv.nombre,'') AS naviera,
            COALESCE(pu.nombre,'') AS puerto,

            COALESCE(o.notas,'') AS comentario, 

            GROUP_CONCAT(DISTINCT cm.numero_contenedor ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
        FROM operaciones o
        LEFT JOIN clientes c
               ON c.id_cliente = o.cliente_id
        LEFT JOIN subtipos_operacion st
               ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN estatus e
               ON e.id_estatus = o.estatus_id
        LEFT JOIN navieras nv
               ON nv.id_naviera = o.naviera_id
        LEFT JOIN puertos pu
               ON pu.id_puerto = st.puerto_arribo_default_id
        LEFT JOIN contenedores_maritimos_operacion cmo
               ON cmo.operacion_id = o.id_operacion
        LEFT JOIN contenedores_maritimos cm
               ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE o.id_operacion = :op_id
          AND o.cliente_id = :cliente_id
        GROUP BY o.id_operacion
        LIMIT 1
    ";

        $row = $this->select($sql, [
            'op_id' => $operacionId,
            'cliente_id' => $clienteId
        ]);

        return $row ?: null;
    }


    /**
     * Eventos de operación (solo lectura) si pertenece al cliente.
     * Ajusta nombres de columnas si tu tabla varía.
     */
    public function listarEventosOperacion(int $clienteId, int $operacionId): array
    {
        if ($clienteId <= 0 || $operacionId <= 0) return [];

        // Seguridad: validamos pertenencia (evita filtrar eventos de otra cuenta)
        $check = $this->select(
            "SELECT 1 AS ok FROM operaciones WHERE id_operacion = :op_id AND cliente_id = :cliente_id LIMIT 1",
            ['op_id' => $operacionId, 'cliente_id' => $clienteId]
        );
        if (!$check) return [];

        $sql = "
            SELECT
                e.id_evento,
                e.fecha,
                te.nombre AS evento,
                COALESCE(e.comentario,'') AS comentario
            FROM eventos_logisticos e
            LEFT JOIN tipos_evento_logistico te
                   ON te.id_tipo_evento = e.tipo_evento_id
            WHERE e.operacion_id = :op_id
              AND e.estatus = 1
            ORDER BY e.fecha DESC, e.id_evento DESC
        ";

        return $this->selectAll($sql, ['op_id' => $operacionId]) ?: [];
    }

    /**
     * Wrapper: detalle + eventos (ideal para endpoint JSON del modal)
     */
    public function obtenerDetalleMaritimaConEventos(int $clienteId, int $operacionId): array
    {
        $detalle = $this->obtenerDetalleMaritima($clienteId, $operacionId);
        if (!$detalle) {
            return ['ok' => false, 'msg' => 'Operación no encontrada o sin acceso.'];
        }

        $eventos = $this->listarEventosOperacion($clienteId, $operacionId);

        return [
            'ok' => true,
            'detalle' => $detalle,
            'eventos' => $eventos,
        ];
    }

    //operaciones FO
    // Models/PortalClientesModel.php (ejemplo)
    public function listarOperacionesFerroCliente(int $clienteId, int $limit = 1000): array
    {
        $sql = "
        SELECT DISTINCT
            of.id_operacion_ferro,
            of.numero_operacion,
            of.fecha,
            of.bultos_total,
            of.comentarios,

            of.estatus_id,
            e.nombre AS estatus,

            of.subtipo_operacion_id,
            st.clave  AS subtipo_clave,
            st.nombre AS subtipo_nombre,

            of.destino_id,
            ciu.nombre_ciudad AS destino,

            of.transportista_id,
            tr.nombre AS transportista,

            of.contenedor_fisico_id,
            cf.numero_ferro AS contenedor_fisico,

            of.creado_por,
            u.nombre AS creado_por_nombre,

            /* ✅ Resumen de contenedores marítimos asignados a esta FO */
            (
                SELECT GROUP_CONCAT(
                           DISTINCT cm.numero_contenedor
                           ORDER BY cm.numero_contenedor SEPARATOR ', '
                       )
                FROM contenedor_maritimo_ferro cmf2
                INNER JOIN contenedores_maritimos_operacion cmo2
                        ON cmo2.id = cmf2.cont_maritimo_operacion_id
                LEFT JOIN contenedores_maritimos cm
                       ON cm.id_contenedor_maritimo = cmo2.contenedor_maritimo_id
                WHERE cmf2.operacion_ferro_id = of.id_operacion_ferro
                  AND cmf2.estatus = 1
            ) AS contenedores_maritimos

        FROM operaciones_ferroviarias of
        /* Catálogos para mostrar info en el Portal */
        LEFT JOIN estatus e               ON e.id_estatus = of.estatus_id
        LEFT JOIN subtipos_operacion st   ON st.id_subtipo = of.subtipo_operacion_id
        LEFT JOIN ciudades ciu            ON ciu.id_ciudad = of.destino_id
        LEFT JOIN transportistas tr       ON tr.id_transportista = of.transportista_id
        LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id
        LEFT JOIN usuarios u              ON u.id_usuario = of.creado_por

        WHERE
            /* 1) FO directa del cliente */
            of.cliente_id = ?

            OR

            /* 2) FO vinculada a operación marítima cuyo cliente es el mismo */
            EXISTS (
                SELECT 1
                FROM contenedor_maritimo_ferro cmf
                INNER JOIN contenedores_maritimos_operacion cmo
                        ON cmo.id = cmf.cont_maritimo_operacion_id
                INNER JOIN operaciones o
                        ON o.id_operacion = cmo.operacion_id
                WHERE cmf.operacion_ferro_id = of.id_operacion_ferro
                  AND o.cliente_id = ?
            )

        ORDER BY of.id_operacion_ferro DESC
        LIMIT " . (int)$limit . "
    ";

        return $this->selectAll($sql, [$clienteId, $clienteId]) ?: [];
    }


    public function contarOperacionesFerroCliente(int $clienteId): int
    {
        $sql = "
        SELECT COUNT(DISTINCT of.id_operacion_ferro) AS n
        FROM operaciones_ferroviarias of
        WHERE
            of.cliente_id = ?
            OR EXISTS (
                SELECT 1
                FROM contenedor_maritimo_ferro cmf
                INNER JOIN contenedores_maritimos_operacion cmo
                        ON cmo.id = cmf.cont_maritimo_operacion_id
                INNER JOIN operaciones o
                        ON o.id_operacion = cmo.operacion_id
                WHERE cmf.operacion_ferro_id = of.id_operacion_ferro
                  AND o.cliente_id = ?
            )
    ";
        $row = $this->select($sql, [$clienteId, $clienteId]);
        return $row ? (int)$row['n'] : 0;
    }

    // ✅ Seguridad: valida que la FO pertenezca al cliente (directa o vía marítima)
    private function foPerteneceACliente(int $clienteId, int $opFerroId): bool
    {
        if ($clienteId <= 0 || $opFerroId <= 0) return false;

        $sql = "
        SELECT 1 AS ok
        FROM operaciones_ferroviarias of
        WHERE of.id_operacion_ferro = ?
          AND (
                of.cliente_id = ?
                OR EXISTS (
                    SELECT 1
                    FROM contenedor_maritimo_ferro cmf
                    INNER JOIN contenedores_maritimos_operacion cmo
                            ON cmo.id = cmf.cont_maritimo_operacion_id
                    INNER JOIN operaciones o
                            ON o.id_operacion = cmo.operacion_id
                    WHERE cmf.operacion_ferro_id = of.id_operacion_ferro
                      AND o.cliente_id = ?
                )
          )
        LIMIT 1
    ";

        $row = $this->select($sql, [$opFerroId, $clienteId, $clienteId]);
        return !empty($row);
    }

    public function listarAsignacionesMaritimasFO(int $clienteId, int $opFerroId): array
    {
        if (!$this->foPerteneceACliente($clienteId, $opFerroId)) return [];

        $sql = "
        SELECT
            cmf.id,
            cmf.operacion_ferro_id,
            cmf.cont_maritimo_operacion_id,
            cmf.bultos_asignados,
            cmf.fecha_asignacion,

            o.id_operacion AS operacion_maritima_id,
            o.numero_operacion AS operacion_maritima,

            cm.id_contenedor_maritimo,
            cm.numero_contenedor AS contenedor_maritimo

        FROM contenedor_maritimo_ferro cmf
        INNER JOIN contenedores_maritimos_operacion cmo
                ON cmo.id = cmf.cont_maritimo_operacion_id
        INNER JOIN operaciones o
                ON o.id_operacion = cmo.operacion_id
        LEFT JOIN contenedores_maritimos cm
               ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE cmf.operacion_ferro_id = ?
          AND cmf.estatus = 1
        ORDER BY cmf.fecha_asignacion DESC, cmf.id DESC
    ";

        return $this->selectAll($sql, [$opFerroId]) ?: [];
    }
    public function listarEventosFO(int $clienteId, int $opFerroId): array
    {
        if (!$this->foPerteneceACliente($clienteId, $opFerroId)) return [];

        $sql = "
        SELECT
            e.id_evento,
            e.fecha,
            te.nombre AS evento,
            COALESCE(e.comentario,'') AS comentario
        FROM eventos_ferroviarios e
        LEFT JOIN tipos_evento_logistico te
               ON te.id_tipo_evento = e.tipo_evento_id
        WHERE e.operacion_ferro_id = ?
          AND e.estatus = 1
        ORDER BY e.fecha DESC, e.id_evento DESC
    ";

        return $this->selectAll($sql, [$opFerroId]) ?: [];
    }


    //DOCUMENTOS OP MARITIMAS
    public function listarDocumentosOperacionPortal(
        int $clienteId,
        int $operacionId,
        string $tipoOperacion,
        ?int $contenedorId = null
    ): array {
        if ($clienteId <= 0 || $operacionId <= 0) return [];

        $tipo = strtoupper(trim($tipoOperacion));

        if ($tipo === 'FO') {
            // FO: d.operacion_id apunta a operaciones_ferroviarias.id_operacion_ferro
            $params = [$operacionId, $clienteId];

            $filtro = '';
            if (!empty($contenedorId)) {
                $filtro = ' AND d.contenedor_operacion_id = ? ';
                $params[] = $contenedorId;
            }

            $sql = "
            SELECT
                d.id_documento,
                ofe.numero_operacion,
                cf.numero_ferro AS contenedor,
                t.nombre AS tipo_nombre,
                t.clave  AS tipo_clave,
                d.nombre_archivo,
                d.mime_type,
                d.ruta_archivo,
                d.fecha_subida,
                COALESCE(CONCAT(u.nombre,' ',u.apellido), u.nombre, u.apellido, CAST(d.subido_por AS CHAR)) AS subido_por
            FROM documentos_operacion d
            JOIN tipos_documento t ON t.id_tipo_documento = d.tipo_documento_id
            JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = d.operacion_id
            LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = d.contenedor_operacion_id
            LEFT JOIN usuarios u ON u.id_usuario = d.subido_por
            WHERE d.operacion_id = ?
              AND ofe.cliente_id = ?
              $filtro
            ORDER BY d.fecha_subida DESC, d.id_documento DESC
            LIMIT 500
        ";

            return $this->selectAll($sql, $params) ?: [];
        }

        // MAR / LBMF: d.operacion_id apunta a operaciones.id_operacion
        $params = [$operacionId, $clienteId, $clienteId];

        $filtro = '';
        if (!empty($contenedorId)) {
            // aquí decides: si quieres filtrar por contenedor marítimo dentro de la operación
            $filtro = ' AND d.cont_maritimo_operacion_id = ? ';
            $params[] = $contenedorId;
        }

        $sql = "
        SELECT
            d.id_documento,
            o.numero_operacion,
            cm.numero_contenedor AS contenedor_maritimo,
            t.nombre AS tipo_nombre,
            t.clave  AS tipo_clave,
            d.nombre_archivo,
            d.mime_type,
            d.ruta_archivo,
            d.fecha_subida,
            COALESCE(CONCAT(u.nombre,' ',u.apellido), u.nombre, u.apellido, CAST(d.subido_por AS CHAR)) AS subido_por
        FROM documentos_operacion d
        JOIN tipos_documento t ON t.id_tipo_documento = d.tipo_documento_id
        JOIN operaciones o     ON o.id_operacion      = d.operacion_id
        LEFT JOIN contenedores_operacion co ON co.id_contenedor = d.contenedor_operacion_id
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = d.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        LEFT JOIN usuarios u ON u.id_usuario = d.subido_por
        WHERE d.operacion_id = ?
          AND (o.cliente_id = ? OR co.cliente_id = ?)
          $filtro
        ORDER BY d.fecha_subida DESC, d.id_documento DESC
        LIMIT 500
    ";

        return $this->selectAll($sql, $params) ?: [];
    }
}
