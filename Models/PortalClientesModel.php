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

        $search  = trim((string)($filtros['search'] ?? ''));
        $estatus = (int)($filtros['estatus'] ?? 0);           // 0 = todos
        $etaIni  = trim((string)($filtros['eta_ini'] ?? '')); // YYYY-MM-DD
        $etaFin  = trim((string)($filtros['eta_fin'] ?? '')); // YYYY-MM-DD

        $page     = max(1, (int)($filtros['page'] ?? 1));
        $pageSize = (int)($filtros['page_size'] ?? 15);
        if (!in_array($pageSize, [15, 30, 50, 100, 200, 1000, 10000000], true)) $pageSize = 15;

        $offset = ($page - 1) * $pageSize;

        // =========================
        // WHERE base (portal clientes, 1 sola tabla, operación maestra MF)
        // =========================
        $where  = " WHERE o.cliente_id = ? AND st.tipo_operacion_id IN (11) ";
        $params = [$clienteId];

        // Estatus
        if ($estatus > 0) {
            $where .= " AND o.estatus_id = ? ";
            $params[] = $estatus;
        }

        // Rango ETA
        if ($etaIni !== '') {
            $where .= " AND DATE(o.eta) >= ? ";
            $params[] = $etaIni;
        }
        if ($etaFin !== '') {
            $where .= " AND DATE(o.eta) <= ? ";
            $params[] = $etaFin;
        }

        // Search (operación, BL, contenedor)
        if ($search !== '') {
            $where .= " AND (
                    LOWER(o.numero_operacion) LIKE ?
                    OR LOWER(o.numero_bl)     LIKE ?
                    OR EXISTS (
                        SELECT 1
                        FROM contenedores_maritimos_operacion cmoS
                        INNER JOIN contenedores_maritimos cmS
                            ON cmS.id_contenedor_maritimo = cmoS.contenedor_maritimo_id
                        WHERE cmoS.operacion_id = o.id_operacion
                        AND LOWER(cmS.numero_contenedor) LIKE ?
                    )
                ) ";
            $q = '%' . mb_strtolower($search, 'UTF-8') . '%';
            array_push($params, $q, $q, $q);
        }

        // =========================
        // TOTAL (sin fan-out)
        // =========================
        $sqlTotal = "
                SELECT COUNT(DISTINCT o.id_operacion) AS total
                FROM operaciones o
                LEFT JOIN subtipos_operacion st
                    ON st.id_subtipo = o.subtipo_operacion_id
                $where
            ";
        $rowTotal = $this->select($sqlTotal, $params);
        $total = $rowTotal ? (int)$rowTotal['total'] : 0;

        // =========================
        // DATA (1 fila por operación)
        // =========================
        $sql = "
                SELECT
                    o.id_operacion,
                    o.numero_operacion,
                    o.numero_bl,
                    o.etd,
                    o.eta,
                    o.descripcion_mercancia AS mercancia,
                    o.cita_puerto,
                    o.peso_total,

                    e.nombre AS estatus,
                    tr.nombre AS transportista,

                    /* ===== agregados contenedores ===== */
                    cont.contenedores,
                    cont.tipo_contenedor AS medida,

                    /* (opcional) para abrir modal docs con contenedor default */
                    cont.docs_cont_id,
                    'M' AS docs_cont_tipo,

                    /* ===== brokers ===== */
                    bro.brokers,

                    /* ===== FO/ferros/cajas vinculados ===== */
                    asig.ferros_cajas,
                    asig.destinos_ferros_cajas,
                    asig.fechas_salida_ferros_cajas,
                    asig.ubicaciones_ferros_cajas,
                    asig.transportistas_ferros_cajas,

                    /* opcional: string “bonito” listo para UI */
                    asig.detalle_ferros_cajas

                FROM operaciones o
                LEFT JOIN subtipos_operacion st
                    ON st.id_subtipo = o.subtipo_operacion_id
                LEFT JOIN estatus e
                    ON e.id_estatus = o.estatus_id
                LEFT JOIN transportistas tr
                    ON tr.id_transportista = o.transportista_id

                /* ===== contenedores (1 fila por operación) ===== */
                LEFT JOIN (
                    SELECT
                        cmo.operacion_id,
                        MIN(cmo.id) AS docs_cont_id,
                        GROUP_CONCAT(DISTINCT cm.numero_contenedor
                            ORDER BY cm.numero_contenedor SEPARATOR ', '
                        ) AS contenedores,
                        MAX(cm.tipo) AS tipo_contenedor
                    FROM contenedores_maritimos_operacion cmo
                    INNER JOIN contenedores_maritimos cm
                        ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                    GROUP BY cmo.operacion_id
                ) cont ON cont.operacion_id = o.id_operacion

                /* ===== brokers (1 fila por operación) ===== */
                LEFT JOIN (
                    SELECT
                        ob.operacion_id,
                        GROUP_CONCAT(DISTINCT b.nombre
                            ORDER BY b.nombre SEPARATOR ', '
                        ) AS brokers
                    FROM operacion_brokers ob
                    INNER JOIN brokers b
                        ON b.id_broker = ob.broker_id
                    GROUP BY ob.operacion_id
                ) bro ON bro.operacion_id = o.id_operacion

                /* ===== TODAS las asignaciones ferro/caja (1 fila por operación) ===== */
                LEFT JOIN (
                    SELECT
                        cmo2.operacion_id,

                        GROUP_CONCAT(cf.numero_ferro
                            ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                            SEPARATOR ', '
                        ) AS ferros_cajas,

                        GROUP_CONCAT(COALESCE(ci.nombre_ciudad,'')
                            ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                            SEPARATOR ', '
                        ) AS destinos_ferros_cajas,

                        GROUP_CONCAT(DATE_FORMAT(ofe.fecha,'%Y-%m-%d')
                            ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                            SEPARATOR ', '
                        ) AS fechas_salida_ferros_cajas,

                        GROUP_CONCAT(COALESCE(tfu.ubicacion_actual,'')
                            ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                            SEPARATOR ', '
                        ) AS ubicaciones_ferros_cajas,

                        GROUP_CONCAT(COALESCE(trf.nombre,'')
                            ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                            SEPARATOR ', '
                        ) AS transportistas_ferros_cajas,

                        GROUP_CONCAT(
                            CONCAT(
                                cf.numero_ferro,
                                ' → ',
                                COALESCE(ci.nombre_ciudad,'—'),
                                ' | Salida: ', COALESCE(DATE_FORMAT(ofe.fecha,'%Y-%m-%d'),'—'),
                                ' | Transportista: ', COALESCE(trf.nombre,'—'),
                                ' | Ubicación: ', COALESCE(tfu.ubicacion_actual,'—')
                            )
                            ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                            SEPARATOR ' || '
                        ) AS detalle_ferros_cajas

                    FROM contenedor_maritimo_ferro cmf2
                    INNER JOIN contenedores_maritimos_operacion cmo2
                        ON cmo2.id = cmf2.cont_maritimo_operacion_id
                    INNER JOIN operaciones_ferroviarias ofe
                        ON ofe.id_operacion_ferro = cmf2.operacion_ferro_id
                    INNER JOIN contenedores_fisicos cf
                        ON cf.id_fisico = ofe.contenedor_fisico_id
                    LEFT JOIN ciudades ci
                        ON ci.id_ciudad = ofe.destino_id

                    LEFT JOIN transportistas trf
                        ON trf.id_transportista = ofe.transportista_id

                    /* última ubicación por FO */
                    LEFT JOIN (
                        SELECT
                            tf.operacion_ferro_id,
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(ci2.nombre_ciudad
                                    ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                                    SEPARATOR '||'
                                ),
                                '||', 1
                            ) AS ubicacion_actual
                        FROM trazabilidad_ferro tf
                        INNER JOIN ciudades ci2 ON ci2.id_ciudad = tf.ubicacion_id
                        WHERE tf.operacion_ferro_id IS NOT NULL
                        GROUP BY tf.operacion_ferro_id
                    ) tfu ON tfu.operacion_ferro_id = ofe.id_operacion_ferro

                    WHERE cmf2.estatus = 1
                    GROUP BY cmo2.operacion_id
                ) asig ON asig.operacion_id = o.id_operacion

                $where
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

    public function listarTiposDocumentoPorSubtipo(int $subtipoId): array
    {
        if ($subtipoId <= 0) return [];

        // 1) Obtener tipo_operacion_id desde el subtipo
        $sqlTipo = "SELECT tipo_operacion_id
                FROM subtipos_operacion
                WHERE id_subtipo = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sqlTipo, [$subtipoId]) ?: [];
        $tipoOperacionId = (int)($row['tipo_operacion_id'] ?? 0);

        // 2) Definir aplica_sobre permitido según tipo_operacion
        $aplica = ['cualquiera', 'operacion']; // base mínima

        if ($tipoOperacionId === 1) {
            $aplica = ['cualquiera', 'operacion', 'contenedor_maritimo'];
        } elseif ($tipoOperacionId === 2) {
            $aplica = ['cualquiera', 'operacion', 'contenedor_fisico'];
        } elseif ($tipoOperacionId === 11) {
            $aplica = ['cualquiera', 'operacion', 'contenedor_maritimo', 'contenedor_fisico'];
        }

        // 3) Query catálogo
        $placeholders = implode(',', array_fill(0, count($aplica), '?'));

        $sql = "SELECT
                id_tipo_documento,
                clave,
                nombre,
                descripcion,
                aplica_sobre,
                obligatorio_por_defecto
            FROM tipos_documento
            WHERE activo = 1
              AND aplica_sobre IN ($placeholders)
            ORDER BY obligatorio_por_defecto DESC, nombre ASC";

        return $this->selectAll($sql, $aplica) ?: [];
    }

    private function getSubtipoOperacionIdPorOperacion(string $tipo, int $operacionId): int
    {
        if ($operacionId <= 0) return 0;

        $tipo = strtoupper(trim($tipo));

        // MAR / LBMF viven en `operaciones`
        if ($tipo === 'MAR' || $tipo === 'LBMF') {
            $sql = "SELECT subtipo_operacion_id
                FROM operaciones
                WHERE id_operacion = ?
                LIMIT 1";
            $row = $this->select($sql, [$operacionId]) ?: [];
            return (int)($row['subtipo_operacion_id'] ?? 0);
        }

        // FO (operaciones_ferroviarias)
        if ($tipo === 'FO') {
            $sql = "SELECT subtipo_operacion_id
                FROM operaciones_ferroviarias
                WHERE id_operacion_ferro = ?
                LIMIT 1";
            $row = $this->select($sql, [$operacionId]) ?: [];
            return (int)($row['subtipo_operacion_id'] ?? 0);
        }

        return 0;
    }

    public function listarTiposDocumentoParaOperacion(string $tipo, int $operacionId): array
    {
        $subtipoId = $this->getSubtipoOperacionIdPorOperacion($tipo, $operacionId);
        if ($subtipoId <= 0) return [];
        return $this->listarTiposDocumentoPorSubtipo($subtipoId);
    }

    // En tu PortalClientesModel  
    public function getTipoDocumentoById(int $id): array
    {
        if ($id <= 0) return [];

        $sql = "SELECT id_tipo_documento, clave, nombre, aplica_sobre, activo
            FROM tipos_documento
            WHERE id_tipo_documento = ? AND activo = 1
            LIMIT 1";

        return $this->select($sql, [$id]) ?: [];
    }

    public function insertarDocumentoOperacion(array $d): int
    {
        // Validaciones mínimas (NOT NULL en tu BD)
        $operacionId     = (int)($d['operacion_id'] ?? 0);
        $tipoDocId       = (int)($d['tipo_documento_id'] ?? 0);
        $nombreArchivo   = trim((string)($d['nombre_archivo'] ?? ''));
        $rutaArchivo     = trim((string)($d['ruta_archivo'] ?? ''));

        if ($operacionId <= 0 || $tipoDocId <= 0 || $nombreArchivo === '' || $rutaArchivo === '') {
            return 0;
        }

        $sql = "INSERT INTO documentos_operacion
            (operacion_id, tipo_documento_id, contenedor_operacion_id, cont_maritimo_operacion_id,
             nombre_archivo, mime_type, tamano_bytes, hash_sha256, ruta_archivo, subido_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $operacionId,
            $tipoDocId,
            isset($d['contenedor_operacion_id']) ? (int)$d['contenedor_operacion_id'] : null,
            isset($d['cont_maritimo_operacion_id']) ? (int)$d['cont_maritimo_operacion_id'] : null,
            $nombreArchivo,
            $d['mime_type'] ?? null,
            isset($d['tamano_bytes']) ? (int)$d['tamano_bytes'] : null,
            $d['hash_sha256'] ?? null,
            $rutaArchivo,
            isset($d['subido_por']) ? (int)$d['subido_por'] : null,
        ];

        // Usamos insertar() para devolver lastInsertId()
        return (int)$this->insertar($sql, $params);
    }

    public function operacionPerteneceACliente(string $tipo, int $operacionId, int $clienteId): bool
    {
        $tipo = strtoupper(trim($tipo));
        if ($operacionId <= 0 || $clienteId <= 0) return false;

        if ($tipo === 'FO') {
            return $this->foPerteneceAClientePublic($clienteId, $operacionId);
        }

        // MAR / LBMF
        $sql = "SELECT 1
            FROM operaciones
            WHERE id_operacion = ? AND cliente_id = ?
            LIMIT 1";
        return (bool)$this->select($sql, [$operacionId, $clienteId]);
    }

    public function getNumeroOperacion(string $tipo, int $operacionId): string
    {
        if ($operacionId <= 0) return '';

        $tipo = strtoupper(trim($tipo));

        if ($tipo === 'FO') {
            $sql = "SELECT numero_operacion
                FROM operaciones_ferroviarias
                WHERE id_operacion_ferro = ?
                LIMIT 1";
            $row = $this->select($sql, [$operacionId]) ?: [];
            return trim((string)($row['numero_operacion'] ?? ''));
        }

        // MAR / LBMF
        $sql = "SELECT numero_operacion
            FROM operaciones
            WHERE id_operacion = ?
            LIMIT 1";
        $row = $this->select($sql, [$operacionId]) ?: [];
        return trim((string)($row['numero_operacion'] ?? ''));
    }


    public function getEtiquetaContenedor(string $tipoOp, string $tipoCont, int $contenedorId): string
    {
        if ($contenedorId <= 0) return '';

        $tipoOp   = strtoupper(trim($tipoOp));   // MAR|LBMF|FO
        $tipoCont = strtoupper(trim($tipoCont)); // F|M

        // ===== MAR: contenedorId = contenedores_maritimos_operacion.id =====
        if ($tipoOp === 'MAR') {
            $sql = "SELECT cm.numero_contenedor
                FROM contenedores_maritimos_operacion cmo
                INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                WHERE cmo.id = ?
                LIMIT 1";
            $row = $this->select($sql, [$contenedorId]) ?: [];
            return trim((string)($row['numero_contenedor'] ?? ''));
        }

        // ===== FO: contenedorId = contenedores_fisicos.id_fisico =====
        if ($tipoOp === 'FO') {
            $sql = "SELECT numero_ferro
                FROM contenedores_fisicos
                WHERE id_fisico = ?
                LIMIT 1";
            $row = $this->select($sql, [$contenedorId]) ?: [];
            return trim((string)($row['numero_ferro'] ?? ''));
        }

        // ===== LBMF =====
        if ($tipoOp === 'LBMF') {

            // LBMF F: contenedorId = contenedores_operacion.id_contenedor
            if ($tipoCont === 'F') {
                $sql = "SELECT cf.numero_ferro
                    FROM contenedores_operacion co
                    INNER JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
                    WHERE co.id_contenedor = ?
                    LIMIT 1";
                $row = $this->select($sql, [$contenedorId]) ?: [];
                return trim((string)($row['numero_ferro'] ?? ''));
            }

            // LBMF M: contenedorId = contenedores_maritimos_operacion.id
            if ($tipoCont === 'M') {
                $sql = "SELECT cm.numero_contenedor
                    FROM contenedores_maritimos_operacion cmo
                    INNER JOIN contenedores_maritimos cm
                        ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                    WHERE cmo.id = ?
                    LIMIT 1";
                $row = $this->select($sql, [$contenedorId]) ?: [];
                return trim((string)($row['numero_contenedor'] ?? ''));
            }
        }

        return '';
    }


    public function foPerteneceAClientePublic(int $clienteId, int $opFerroId): bool
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


    public function getTipoOperacionIdOperacion(int $operacionId): int
    {
        if ($operacionId <= 0) return 0;

        $sql = "SELECT tipo_operacion_id
            FROM operaciones
            WHERE id_operacion = ?
            LIMIT 1";

        $row = $this->select($sql, [$operacionId]) ?: [];
        return (int)($row['tipo_operacion_id'] ?? 0);
    }

    /* =========================
 * KPIs Portal Cliente
 * ========================= */

    public function kpisPortalCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [
                'mar_agua'    => 0,
                'mar_puerto'  => 0,
                'fo_camino'   => 0,
                'entregadas'  => 0,
                'bodegas'     => 0,
                'yardas'      => 0,
            ];
        }

        $marAgua   = $this->contarMaritimasPorEstatus($clienteId, 9);   // EN AGUA
        $marPuerto = $this->contarMaritimasPorEstatus($clienteId, 11);  // PUERTO
        $foCamino  = $this->contarFOporEstatus($clienteId, 1);          // CAMINO A DESTINO

        // Entregadas = (MAR+LBMF entregadas) + (FO entregadas)
        $entMar = $this->contarMaritimasPorEstatus($clienteId, 7);      // ENTREGADO
        $entFO  = $this->contarFOporEstatus($clienteId, 7);             // ENTREGADO
        $entregadas = $entMar + $entFO;

        // ✅ NUEVOS:
        // Bodegas = BODEGA TJ(5) + BODEGA SD(6)  (sumando MAR/LBMF + FO)
        $bodegas = $this->contarMaritimasPorEstatusIds($clienteId, [5, 6]) + $this->contarFOporEstatusIds($clienteId, [5, 6]);

        // Yardas = YARDA SD(10) + YARDA TJ(12)  (sumando MAR/LBMF + FO)
        $yardas  = $this->contarMaritimasPorEstatusIds($clienteId, [10, 12]) + $this->contarFOporEstatusIds($clienteId, [10, 12]);

        return [
            'mar_agua'   => $marAgua,
            'mar_puerto' => $marPuerto,
            'fo_camino'  => $foCamino,
            'entregadas' => $entregadas,
            'bodegas'    => $bodegas,
            'yardas'     => $yardas,
        ];
    }


    /**

     * Cuenta operaciones MAR + LBMF por estatus (filtrado por cliente_id).
     * MAR = tipo_operacion_id 1
     * LBMF = tipo_operacion_id 11
     */
    public function contarMaritimasPorEstatus(int $clienteId, int $estatusId): int
    {
        if ($clienteId <= 0 || $estatusId <= 0) return 0;

        $sql = "
        SELECT COUNT(DISTINCT o.id_operacion) AS n
        FROM operaciones o
        LEFT JOIN subtipos_operacion st
               ON st.id_subtipo = o.subtipo_operacion_id
        WHERE o.cliente_id = ?
          AND (
                o.tipo_operacion_id IN (1, 11)
                OR st.tipo_operacion_id IN (1, 11)
              )
          AND o.estatus_id = ?
    ";

        $row = $this->select($sql, [$clienteId, $estatusId]);
        return $row ? (int)$row['n'] : 0;
    }
    /**
     * Cuenta operaciones MAR + LBMF por lista de estatus.
     * MAR = tipo_operacion_id 1
     * LBMF = tipo_operacion_id 11
     */
    public function contarMaritimasPorEstatusIds(int $clienteId, array $estatusIds): int
    {
        if ($clienteId <= 0 || empty($estatusIds)) return 0;

        $estatusIds = array_values(array_filter(array_map('intval', $estatusIds), fn($x) => $x > 0));
        if (empty($estatusIds)) return 0;

        $ph = implode(',', array_fill(0, count($estatusIds), '?'));

        $sql = "
        SELECT COUNT(DISTINCT o.id_operacion) AS n
        FROM operaciones o
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        WHERE o.cliente_id = ?
          AND (
                o.tipo_operacion_id IN (1, 11)
                OR st.tipo_operacion_id IN (1, 11)
              )
          AND o.estatus_id IN ($ph)
    ";

        $params = array_merge([$clienteId], $estatusIds);
        $row = $this->select($sql, $params);
        return $row ? (int)$row['n'] : 0;
    }

    /**
     * Cuenta operaciones FO por lista de estatus (directa o vinculada a marítima del cliente).
     */
    public function contarFOporEstatusIds(int $clienteId, array $estatusIds): int
    {
        if ($clienteId <= 0 || empty($estatusIds)) return 0;

        $estatusIds = array_values(array_filter(array_map('intval', $estatusIds), fn($x) => $x > 0));
        if (empty($estatusIds)) return 0;

        $ph = implode(',', array_fill(0, count($estatusIds), '?'));

        $sql = "
        SELECT COUNT(DISTINCT of.id_operacion_ferro) AS n
        FROM operaciones_ferroviarias of
        WHERE
            of.estatus_id IN ($ph)
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
    ";

        $params = array_merge($estatusIds, [$clienteId, $clienteId]);
        $row = $this->select($sql, $params);
        return $row ? (int)$row['n'] : 0;
    }

    /**
     * Cuenta operaciones FO por estatus, considerando:
     * 1) FO directa del cliente (of.cliente_id = cliente)
     * 2) FO vinculada a operación marítima del cliente (EXISTS ... o.cliente_id = cliente)
     */
    public function contarFOporEstatus(int $clienteId, int $estatusId): int
    {
        if ($clienteId <= 0 || $estatusId <= 0) return 0;

        $sql = "
        SELECT COUNT(DISTINCT of.id_operacion_ferro) AS n
        FROM operaciones_ferroviarias of
        WHERE
            of.estatus_id = ?
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
    ";

        $row = $this->select($sql, [$estatusId, $clienteId, $clienteId]);
        return $row ? (int)$row['n'] : 0;
    }
}
