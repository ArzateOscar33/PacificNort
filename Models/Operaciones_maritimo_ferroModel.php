<?php
class Operaciones_maritimo_ferroModel extends Query
{
    /* =========================
       ===  CATÁLOGOS / HELP ===
       ========================= */

    /** Subtipo con tipo_operacion_id y banderas */
    public function getSubtipoFull(int $id): ?array
    {
        $sql = "SELECT id_subtipo, tipo_operacion_id, nombre,
                       requiere_naviera, requiere_forwarder,
                       puerto_arribo_default_id, prefijo_codigo
                FROM subtipos_operacion
                WHERE id_subtipo = ?
                LIMIT 1";
        return $this->select($sql, [$id]) ?: null;
    }

    /** Prefijo de código por subtipo (para folio) */
    public function getPrefijoSubtipo(int $subtipoId): ?string
    {
        $row = $this->select(
            "SELECT prefijo_codigo FROM subtipos_operacion WHERE id_subtipo=? LIMIT 1",
            [$subtipoId]
        );
        $p = $row ? trim((string)$row['prefijo_codigo']) : '';
        return $p !== '' ? $p : null;
    }

    private function lpadNumeroN(int $n): string
    {
        // 2 dígitos mínimo hasta 99 (LC-01..LC-99), luego longitud natural
        return str_pad((string)$n, ($n < 100 ? 2 : strlen((string)$n)), '0', STR_PAD_LEFT);
    }

    private function nextConsecutivoSeguro(int $subtipoId): int
    {
        $ok = $this->save(
            "INSERT INTO secuencias_operacion (subtipo_id, valor)
             VALUES (?, 1)
             ON DUPLICATE KEY UPDATE valor = LAST_INSERT_ID(valor + 1)",
            [$subtipoId]
        );
        if ($ok === false) return 0;

        $row = $this->select("SELECT LAST_INSERT_ID() AS n");
        return (int)($row['n'] ?? 0);
    }

    /** Genera código por secuencia (seguro) */
    public function generarCodigoPorSecuencia(int $subtipoId): ?string
    {
        $pref = $this->getPrefijoSubtipo($subtipoId);
        if (!$pref) return null;

        $consec = $this->nextConsecutivoSeguro($subtipoId);
        if ($consec <= 0) return null;

        return $pref . '-' . $this->lpadNumeroN($consec);
    }

    /** Preview de folio para UI (sin bloquear) */
    public function previewCodigoSubtipo(int $subtipoId): ?array
    {
        $pref = $this->getPrefijoSubtipo($subtipoId);
        if (!$pref) return null;

        $row = $this->select(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_operacion,'-',-1) AS UNSIGNED)), 0) AS maxn
             FROM operaciones
             WHERE subtipo_operacion_id = ?",
            [$subtipoId]
        );
        $next = (int)($row['maxn'] ?? 0) + 1;

        return [
            'prefijo' => $pref,
            'numero'  => $next,
            'codigo'  => $pref . '-' . $this->lpadNumeroN($next),
        ];
    }

    /* =========================
       ===   CATÁLOGOS VISTA  ===
       ========================= */

    /** Subtipos para este módulo (mar + ferro) */
    public function subtiposMaritimoFerro(): array
    {
        $sql = "SELECT id_subtipo, nombre, requiere_naviera, requiere_forwarder,
                       puerto_arribo_default_id, tipo_operacion_id, prefijo_codigo
                FROM subtipos_operacion
                WHERE estatus = 1
                  AND tipo_operacion_id IN (11)
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoEstatus(): array
    {
        $sql = "SELECT id_estatus, nombre
                FROM estatus
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoPuertos(): array
    {
        $sql = "SELECT id_puerto, nombre
                FROM puertos
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoNavieras(): array
    {
        $sql = "SELECT id_naviera, nombre
                FROM navieras
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoShippers(): array
    {
        $sql = "SELECT id_shipper, nombre
                FROM shippers
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoForwarders(): array
    {
        $sql = "SELECT id_forwarder, nombre
                FROM forwarders
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    /* =========================
       ===   AUTOCOMPLETES   ===
       ========================= */

    public function buscarClientes(string $term): array
    {
        $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                  AND LOWER(nombre) LIKE ?
                ORDER BY nombre
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    public function buscarContenedoresMar(string $term): array
    {
        $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE estatus = 1
                  AND LOWER(numero_contenedor) LIKE ?
                ORDER BY numero_contenedor
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    public function buscarShippers(string $term): array
    {
        $like = '%' . mb_strtolower($term, 'UTF-8') . '%';
        $sql = "SELECT id_shipper, nombre
                FROM shippers
                WHERE estatus = 1 AND LOWER(nombre) LIKE ?
                ORDER BY nombre
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    /* =========================
       ===  HELPERS CONTENEDOR ===
       ========================= */

    public function findContenedorByNumero(string $numero): ?array
    {
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$numero]) ?: null;
    }

    public function createContenedor(string $numero, ?string $tipo = null): int
    {
        $tipo = trim((string)$tipo);
        if ($tipo === '') $tipo = null;

        $sql = "INSERT INTO contenedores_maritimos (numero_contenedor, tipo, estatus)
                VALUES (?, ?, 1)";
        return (int)$this->insertar($sql, [$numero, $tipo]);
    }

    public function updateContenedorTipo(int $contenedorId, ?string $tipo): bool
    {
        $tipo = trim((string)$tipo);
        if ($contenedorId <= 0 || $tipo === '') return true;

        $res = $this->save(
            "UPDATE contenedores_maritimos
             SET tipo = ?
             WHERE id_contenedor_maritimo = ?
             LIMIT 1",
            [$tipo, $contenedorId]
        );
        return $res !== false;
    }

    /** UPSERT lógico: si ya existe vínculo, actualiza bultos; si no, inserta */
    public function upsertLinkContenedorOperacion(int $opId, int $contenedorId, $bultos = null): bool
    {
        $valBultos = ($bultos === '' || $bultos === null) ? null : (is_numeric($bultos) ? (int)$bultos : null);

        $exists = $this->select(
            "SELECT 1 AS ok
             FROM contenedores_maritimos_operacion
             WHERE operacion_id = ? AND contenedor_maritimo_id = ?
             LIMIT 1",
            [$opId, $contenedorId]
        );

        if ($exists) {
            $res = $this->save(
                "UPDATE contenedores_maritimos_operacion
                 SET bultos = ?
                 WHERE operacion_id = ? AND contenedor_maritimo_id = ?
                 LIMIT 1",
                [$valBultos, $opId, $contenedorId]
            );
            return $res !== false;
        }

        $ins = $this->insertar(
            "INSERT INTO contenedores_maritimos_operacion (operacion_id, contenedor_maritimo_id, bultos)
             VALUES (?, ?, ?)",
            [$opId, $contenedorId, $valBultos]
        );
        return ((int)$ins) > 0;
    }

    /** Sincroniza: elimina los que ya no vienen, y upsert de los que vienen */
    public function syncContenedoresOperacion(int $opId, array $contenedores, array $opFallback = []): bool
    {
        // Normalizar lista deseada a IDs
        $desiredIds = [];
        $items = [];

        foreach ($contenedores as $c) {
            $cid   = isset($c['id']) ? (int)$c['id'] : 0;
            $cnum  = trim($c['numero'] ?? '');
            $cbul  = $c['bultos'] ?? null;

            // tipo puede venir por contenedor o uno general (fallback)
            $ctipo = trim((string)($c['tipo'] ?? ($opFallback['tipo_contenedor'] ?? '')));

            if ($cid <= 0 && $cnum === '') continue;

            if ($cid <= 0) {
                $found = $this->findContenedorByNumero($cnum);
                if ($found) {
                    $cid = (int)$found['id_contenedor_maritimo'];
                } else {
                    $cid = (int)$this->createContenedor($cnum, $ctipo);
                }
                if ($cid <= 0) return false;
            }

            // Guardar item normalizado
            $desiredIds[$cid] = true;
            $items[] = ['id' => $cid, 'bultos' => $cbul, 'tipo' => $ctipo];
        }

        // 1) borrar vínculos que ya no vienen
        $current = $this->selectAll(
            "SELECT contenedor_maritimo_id
             FROM contenedores_maritimos_operacion
             WHERE operacion_id = ?",
            [$opId]
        ) ?: [];

        foreach ($current as $row) {
            $cid = (int)($row['contenedor_maritimo_id'] ?? 0);
            if ($cid > 0 && !isset($desiredIds[$cid])) {
                $res = $this->save(
                    "DELETE FROM contenedores_maritimos_operacion
                     WHERE operacion_id = ? AND contenedor_maritimo_id = ?
                     LIMIT 1",
                    [$opId, $cid]
                );
                if ($res === false) return false;
            }
        }

        // 2) upsert de los que vienen + update tipo siempre que venga
        foreach ($items as $it) {
            $cid = (int)$it['id'];
            if (!$this->updateContenedorTipo($cid, $it['tipo'])) return false;
            if (!$this->upsertLinkContenedorOperacion($opId, $cid, $it['bultos'])) return false;
        }

        return true;
    }

    /* =========================
       ===  HELPERS BROKER   ===
       ========================= */

    public function upsertOperacionBroker(int $operacionId, int $brokerId): bool
    {
        // Limpia relación
        $this->save("DELETE FROM operacion_brokers WHERE operacion_id = ?", [$operacionId]);

        if ($brokerId > 0) {
            $ok = $this->insertar(
                "INSERT INTO operacion_brokers (operacion_id, broker_id) VALUES (?, ?)",
                [$operacionId, $brokerId]
            );
            if (((int)$ok) <= 0) return false;
        }

        // Mantener también broker_id en operaciones consistente (NULL si 0)
        $res = $this->save(
            "UPDATE operaciones SET broker_id = ? WHERE id_operacion = ? LIMIT 1",
            [$brokerId > 0 ? $brokerId : null, $operacionId]
        );
        return $res !== false;
    }

    /* =========================
       ===       LISTAR      ===
       ========================= */

    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $page    = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset  = ($page - 1) * $perPage;

        $where = "WHERE st.tipo_operacion_id IN (11)";
        $args  = [];

        $subtipoId = isset($filters['filtroSubtipo']) ? (int)$filters['filtroSubtipo']
            : (isset($filters['subtipo_id']) ? (int)$filters['subtipo_id'] : 0);
        if ($subtipoId > 0) {
            $where .= " AND o.subtipo_operacion_id = ? ";
            $args[] = $subtipoId;
        }

        if (!empty($filters['tipo']) && in_array((int)$filters['tipo'], [11], true)) {
            $where .= " AND st.tipo_operacion_id = ? ";
            $args[] = (int)$filters['tipo'];
        }

        $raw = trim($filters['term'] ?? '');
        if ($raw !== '') {
            $terms = array_values(array_filter(array_map(
                fn($t) => mb_strtolower(trim($t), 'UTF-8'),
                explode(',', $raw)
            ), fn($t) => $t !== ''));
            $terms = array_slice($terms, 0, 5);

            foreach ($terms as $t) {
                $needle = '%' . $t . '%';
                $where .= " AND (
                    LOWER(o.numero_operacion) LIKE ?
                    OR LOWER(o.numero_bl)     LIKE ?
                    OR LOWER(p.nombre)        LIKE ?
                    OR LOWER(e.nombre)        LIKE ?
                    OR LOWER(c.nombre)        LIKE ?
                    OR LOWER(s.nombre)        LIKE ?
                    OR EXISTS (
                        SELECT 1
                        FROM contenedores_maritimos_operacion cmo2
                        JOIN contenedores_maritimos cm2
                          ON cm2.id_contenedor_maritimo = cmo2.contenedor_maritimo_id
                        WHERE cmo2.operacion_id = o.id_operacion
                          AND LOWER(cm2.numero_contenedor) LIKE ?
                    )
                )";
                array_push($args, $needle, $needle, $needle, $needle, $needle, $needle, $needle);
            }
        }

        $fi = trim($filters['fecha_inicio'] ?? '');
        $ff = trim($filters['fecha_fin'] ?? '');
        $isDate = static function (string $d): bool {
            return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        };
        if ($fi !== '' && !$isDate($fi)) $fi = '';
        if ($ff !== '' && !$isDate($ff)) $ff = '';
        if ($fi !== '' && $ff !== '' && $fi > $ff) {
            [$fi, $ff] = [$ff, $fi];
        }

        if ($fi !== '' && $ff !== '') {
            $where .= " AND DATE(o.eta) BETWEEN ? AND ? ";
            array_push($args, $fi, $ff);
        } elseif ($fi !== '') {
            $where .= " AND DATE(o.eta) >= ? ";
            $args[] = $fi;
        } elseif ($ff !== '') {
            $where .= " AND DATE(o.eta) <= ? ";
            $args[] = $ff;
        }

        $sqlCount = "
            SELECT COUNT(DISTINCT o.id_operacion) AS total
            FROM operaciones o
            LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN puertos p           ON p.id_puerto = st.puerto_arribo_default_id
            LEFT JOIN clientes c          ON c.id_cliente = o.cliente_id
            LEFT JOIN estatus e           ON e.id_estatus = o.estatus_id
            LEFT JOIN shippers s          ON s.id_shipper = o.shipper_id
            $where
        ";
        $rowCount = $this->select($sqlCount, $args) ?: ['total' => 0];
        $total    = (int)$rowCount['total'];

        $limit = (int)$perPage;
        $off   = (int)$offset;

        $sqlData = "
    SELECT
        o.id_operacion,
        o.numero_operacion,
        st.nombre  AS subtipo,
        st.tipo_operacion_id AS tipo,
        o.numero_bl,
        p.nombre   AS puerto_arribo,
        n.nombre   AS naviera,
        f.nombre   AS forwarder,
        c.nombre   AS cliente,
        o.etd, o.eta,
        o.descripcion_mercancia as mercancia,
        e.nombre   AS estatus,
        o.isf,
        o.cita_puerto,
        o.shipper_id AS shipper_id,
        s.nombre     AS shipper,

        GROUP_CONCAT(DISTINCT cm.numero_contenedor
                     ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores,

        o.peso_total AS peso_total,
        tr.nombre    AS transportista,
        COALESCE(SUM(cmo.bultos),0) AS bultos_total,
        MAX(cm.tipo) AS tipo_contenedor,

        GROUP_CONCAT(DISTINCT b.nombre
                     ORDER BY b.nombre SEPARATOR ', ') AS brokers,

        /* ====== NUEVOS CAMPOS (última asignación) ====== */
        MAX(ult.ferro_caja)      AS ferro_caja,
        MAX(ult.destino_nombre)  AS destino_ferro_caja,
        MAX(ult.fecha_salida)    AS fecha_salida_ferro_caja,
        MAX(ult.fecha_carga)     AS fecha_carga_ferro_caja

    FROM operaciones o
    LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
    LEFT JOIN puertos p           ON p.id_puerto = st.puerto_arribo_default_id
    LEFT JOIN navieras n          ON n.id_naviera = o.naviera_id
    LEFT JOIN forwarders f        ON f.id_forwarder = o.forwarder_id
    LEFT JOIN clientes c          ON c.id_cliente = o.cliente_id
    LEFT JOIN estatus e           ON e.id_estatus = o.estatus_id
    LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.operacion_id = o.id_operacion
    LEFT JOIN contenedores_maritimos cm            ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
    LEFT JOIN shippers s          ON s.id_shipper = o.shipper_id
    LEFT JOIN transportistas tr   ON tr.id_transportista = o.transportista_id
    LEFT JOIN operacion_brokers ob ON ob.operacion_id = o.id_operacion
    LEFT JOIN brokers b            ON b.id_broker = ob.broker_id

    /* ====== JOIN: última asignación por operación ====== */
    LEFT JOIN (
        SELECT
            x.operacion_id,
            cf.numero_ferro AS ferro_caja,
            x.fecha_salida,
            x.fecha_carga,
            x.destino_id,
            ci.nombre_ciudad AS destino_nombre
        FROM (
            SELECT
                cmo2.operacion_id,
                ofe.id_operacion_ferro,
                ofe.contenedor_fisico_id,
                ofe.destino_id,
                ofe.fecha       AS fecha_salida,
                ofe.fecha_carga AS fecha_carga,
                cmf2.fecha_asignacion,
                cmf2.id,
                ROW_NUMBER() OVER (
                    PARTITION BY cmo2.operacion_id
                    ORDER BY ofe.fecha DESC, cmf2.fecha_asignacion DESC, cmf2.id DESC
                ) AS rn
            FROM contenedor_maritimo_ferro cmf2
            INNER JOIN contenedores_maritimos_operacion cmo2
                ON cmo2.id = cmf2.cont_maritimo_operacion_id
            INNER JOIN operaciones_ferroviarias ofe
                ON ofe.id_operacion_ferro = cmf2.operacion_ferro_id
            WHERE cmf2.estatus = 1
        ) x
        INNER JOIN contenedores_fisicos cf
            ON cf.id_fisico = x.contenedor_fisico_id
        LEFT JOIN ciudades ci
            ON ci.id_ciudad = x.destino_id
        WHERE x.rn = 1
    ) ult ON ult.operacion_id = o.id_operacion

    $where

    GROUP BY
        o.id_operacion,
        o.numero_operacion,
        st.nombre,
        st.tipo_operacion_id,
        o.numero_bl,
        p.nombre,
        n.nombre,
        f.nombre,
        c.nombre,
        o.etd,
        o.eta,
        e.nombre,
        o.isf,
        o.cita_puerto,
        o.shipper_id,
        s.nombre,
        o.peso_total,
        tr.nombre

    ORDER BY o.id_operacion DESC
    LIMIT $limit OFFSET $off
";


        $rows = $this->selectAll($sqlData, $args) ?: [];

        return [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /* =========================
       ===  INSERT PRINCIPAL  ===
       ========================= */

    public function insertarOperacion(array $op, array $contenedores, int $usuarioId): array
    {
        try {
            if (empty($op['numero_operacion'])) {
                $codigo = $this->generarCodigoPorSecuencia((int)$op['subtipo_operacion_id']);
                if (!$codigo) return ['status' => 'error', 'msg' => 'No se pudo generar el folio'];
                $op['numero_operacion'] = $codigo;
            }

            $this->save("START TRANSACTION", []);

            $st = $this->getSubtipoFull((int)$op['subtipo_operacion_id']);
            if (!$st) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'Subtipo inválido'];
            }

            if ((int)$st['requiere_naviera'] === 1 && empty($op['naviera_id'])) {
                $this->save("ROLLBACK", []);
                return ['status' => 'warning', 'msg' => 'Selecciona una naviera'];
            }
            if ((int)$st['requiere_forwarder'] === 1 && empty($op['forwarder_id'])) {
                $this->save("ROLLBACK", []);
                return ['status' => 'warning', 'msg' => 'Selecciona un forwarder'];
            }

            $sqlOp = "INSERT INTO operaciones
                (numero_operacion, tipo_operacion_id, subtipo_operacion_id, etd, eta, numero_bl,
                 cliente_id, estatus_id, naviera_id, forwarder_id, shipper_id, notas, isf, cita_puerto,
                 peso_total, transportista_id, broker_id,descripcion_mercancia)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $paramsOp = [
                trim($op['numero_operacion']),
                (int)$st['tipo_operacion_id'],
                (int)$op['subtipo_operacion_id'],
                $op['etd'] ?? null,
                $op['eta'] ?? null,
                $op['numero_bl'] ?? null,
                !empty($op['cliente_id']) ? (int)$op['cliente_id'] : null,
                (int)($op['estatus_id'] ?? 9),
                !empty($op['naviera_id'])   ? (int)$op['naviera_id']   : null,
                !empty($op['forwarder_id']) ? (int)$op['forwarder_id'] : null,
                !empty($op['shipper_id'])   ? (int)$op['shipper_id']   : null,
                $op['notas'] ?? null,
                (int)($op['isf'] ?? 0),
                (!empty($op['cita_puerto']) ? $op['cita_puerto'] : null),

                (isset($op['peso_total']) && $op['peso_total'] !== '' ? (float)$op['peso_total'] : null),
                (!empty($op['transportista_id']) ? (int)$op['transportista_id'] : null),
                (!empty($op['broker_id']) ? (int)$op['broker_id'] : null),
                $op['descripcion_mercancia'] ?? null,
            ];

            $opId = (int)$this->insertar($sqlOp, $paramsOp);
            if ($opId <= 0) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo guardar la operación'];
            }

            // Broker pivot + sync broker_id NULL si aplica
            $brokerId = !empty($op['broker_id']) ? (int)$op['broker_id'] : 0;
            if (!$this->upsertOperacionBroker($opId, $brokerId)) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo vincular el broker a la operación'];
            }

            // Contenedores: sync (en alta funciona igual: inserta/actualiza tipo/bultos)
            if (!empty($contenedores)) {
                if (!$this->syncContenedoresOperacion($opId, $contenedores, $op)) {
                    $this->save("ROLLBACK", []);
                    return ['status' => 'error', 'msg' => 'No se pudieron guardar contenedores'];
                }
            }

            $logId = $this->crearLog($opId, $usuarioId, 'creacion', 'Operación creada');
            if ($logId <= 0) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo registrar la bitácora de creación'];
            }

            $this->save("COMMIT", []);
            return [
                'status'           => 'success',
                'msg'              => 'Operación creada',
                'id_operacion'     => $opId,
                'numero_operacion' => $op['numero_operacion'],
            ];
        } catch (\Throwable $ex) {
            error_log("insertarOperacion error: " . $ex->getMessage());
            try {
                $this->save("ROLLBACK", []);
            } catch (\Throwable $e2) {
            }
            return ['status' => 'error', 'msg' => 'Error inesperado al guardar'];
        }
    }

    public function crearLog(int $opId, int $usuarioId, string $accion, string $descripcion = ''): int
    {
        $sql = "INSERT INTO operaciones_log (operacion_id, usuario_id, accion, descripcion, fecha)
                VALUES (?, ?, ?, ?, NOW())";
        return (int)$this->insertar($sql, [$opId, $usuarioId, $accion, $descripcion]);
    }

    /* =========================
       === LECTURAS / EDITAR ===
       ========================= */

    public function getOperacionById(int $id): ?array
    {
        $sql = "SELECT * FROM operaciones WHERE id_operacion = ? LIMIT 1";
        return $this->select($sql, [$id]) ?: null;
    }

    public function obtenerOperacion(int $id): ?array
    {
        $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            o.subtipo_operacion_id,
            st.tipo_operacion_id,
            st.nombre AS subtipo_nombre,
            st.requiere_naviera,
            st.requiere_forwarder,

            o.shipper_id,
            s.nombre AS shipper_nombre,

            st.puerto_arribo_default_id AS puerto_arribo_id_prefill,
            p.nombre AS puerto_arribo_nombre,

            o.numero_bl,
            o.naviera_id,
            o.forwarder_id,

            o.cliente_id,
            c.nombre AS cliente_nombre,

            o.etd,
            o.eta,

            o.estatus_id,
            e.nombre AS estatus_nombre,

            o.isf,
            o.cita_puerto,
            o.notas,

            o.peso_total,

            o.transportista_id,
            o.descripcion_mercancia as mercancia,
            tr.nombre AS transportista_nombre,

             

            /* Broker: si existe vínculo en operacion_brokers, ese manda */
            COALESCE(MIN(ob.broker_id), o.broker_id) AS broker_id_real,
            COALESCE(MIN(b.nombre), br.nombre)       AS broker_nombre

        FROM operaciones o
        LEFT JOIN shippers s             ON s.id_shipper = o.shipper_id
        LEFT JOIN subtipos_operacion st  ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN puertos p              ON p.id_puerto = st.puerto_arribo_default_id
        LEFT JOIN clientes c             ON c.id_cliente = o.cliente_id
        LEFT JOIN estatus e              ON e.id_estatus = o.estatus_id

        LEFT JOIN transportistas tr      ON tr.id_transportista = o.transportista_id

        /* Si tu broker se maneja por tabla puente */
        LEFT JOIN operacion_brokers ob   ON ob.operacion_id = o.id_operacion
        LEFT JOIN brokers b              ON b.id_broker = ob.broker_id

        /* Fallback: broker directo en operaciones (si lo tienes) */
        LEFT JOIN brokers br             ON br.id_broker = o.broker_id

        WHERE o.id_operacion = ?
        GROUP BY
            o.id_operacion,
            o.numero_operacion,
            o.subtipo_operacion_id,
            st.tipo_operacion_id,
            st.nombre,
            st.requiere_naviera,
            st.requiere_forwarder,
            o.shipper_id,
            s.nombre,
            st.puerto_arribo_default_id,
            p.nombre,
            o.numero_bl,
            o.naviera_id,
            o.forwarder_id,
            o.cliente_id,
            c.nombre,
            o.etd,
            o.eta,
            o.estatus_id,
            e.nombre,
            o.isf,
            o.cita_puerto,
            o.notas,
            o.peso_total,
            o.transportista_id,
            tr.nombre,
            o.broker_id,
            br.nombre
        LIMIT 1
    ";

        $row = $this->select($sql, [$id]);
        if (!$row) return null;

        // Para que tu JS use broker_id "normal"
        $row['broker_id'] = (int)($row['broker_id_real'] ?? 0);

        return $row;
    }


    public function getContenedoresDeOperacion(int $operacionId): array
    {
        $sql = "
            SELECT
                cmo.contenedor_maritimo_id        AS id,
                cm.numero_contenedor              AS numero,
                cmo.bultos                        AS bultos,
                cm.tipo                           AS tipo
            FROM contenedores_maritimos_operacion cmo
            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.operacion_id = ?
            ORDER BY cm.numero_contenedor ASC
        ";

        $rows = $this->selectAll($sql, [$operacionId]);
        return is_array($rows) ? $rows : [];
    }


    /** EDITAR (ahora completo: tx + broker + contenedores + log) */
    public function actualizarOperacion(array $d, array $contenedores = [], int $usuarioId = 0): array
    {
        try {
            $opId = (int)($d['id_operacion'] ?? 0);
            if ($opId <= 0) return ['status' => 'error', 'msg' => 'ID operación inválido'];

            $st = $this->getSubtipoFull((int)($d['subtipo_operacion_id'] ?? 0));
            if (!$st) return ['status' => 'error', 'msg' => 'Subtipo inválido'];

            if ((int)$st['requiere_naviera'] === 1 && empty($d['naviera_id'])) {
                return ['status' => 'warning', 'msg' => 'Selecciona una naviera'];
            }
            if ((int)$st['requiere_forwarder'] === 1 && empty($d['forwarder_id'])) {
                return ['status' => 'warning', 'msg' => 'Selecciona un forwarder'];
            }

            $this->save("START TRANSACTION", []);

            $sql = "UPDATE operaciones
                SET tipo_operacion_id     = ?,
                    subtipo_operacion_id  = ?,
                    etd                   = ?,
                    eta                   = ?,
                    numero_bl             = ?,
                    cliente_id            = ?,
                    estatus_id            = ?,
                    naviera_id            = ?,
                    forwarder_id          = ?,
                    shipper_id            = ?,
                    isf                   = ?,
                    cita_puerto           = ?,
                    notas                 = ?,
                    peso_total            = ?,
                    transportista_id      = ?,
                    broker_id             = ?,
                    descripcion_mercancia = ?
                WHERE id_operacion = ?
                LIMIT 1";


            $args = [
                (int)$st['tipo_operacion_id'],
                (int)($d['subtipo_operacion_id'] ?? 0),
                !empty($d['etd']) ? $d['etd'] : null,
                !empty($d['eta']) ? $d['eta'] : null,
                trim($d['numero_bl'] ?? ''),
                !empty($d['cliente_id']) ? (int)$d['cliente_id'] : null,
                !empty($d['estatus_id']) ? (int)$d['estatus_id'] : null,
                ($d['naviera_id']   ?? '') !== '' ? (int)$d['naviera_id']   : null,
                ($d['forwarder_id'] ?? '') !== '' ? (int)$d['forwarder_id'] : null,
                ($d['shipper_id']   ?? '') !== '' ? (int)$d['shipper_id']   : null,
                (int)($d['isf'] ?? 0),
                ($d['cita_puerto'] ?? null),
                ($d['notas'] ?? null),

                (isset($d['peso_total']) && $d['peso_total'] !== '' ? (float)$d['peso_total'] : null),
                (!empty($d['transportista_id']) ? (int)$d['transportista_id'] : null),
                (!empty($d['broker_id']) ? (int)$d['broker_id'] : null),
                (isset($d['descripcion_mercancia']) && trim((string)$d['descripcion_mercancia']) !== '')
                    ? trim((string)$d['descripcion_mercancia'])
                    : null,
                $opId,
            ];

            $res = $this->save($sql, $args);
            if ($res === false) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo actualizar la operación'];
            }

            // Broker pivot + sync broker_id NULL si aplica
            $brokerId = !empty($d['broker_id']) ? (int)$d['broker_id'] : 0;
            if (!$this->upsertOperacionBroker($opId, $brokerId)) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo actualizar el broker'];
            }

            // Contenedores (si tu UI los manda en editar)
            if (is_array($contenedores) && !empty($contenedores)) {
                if (!$this->syncContenedoresOperacion($opId, $contenedores, $d)) {
                    $this->save("ROLLBACK", []);
                    return ['status' => 'error', 'msg' => 'No se pudieron actualizar contenedores'];
                }
            }

            // Log edición
            if ($usuarioId > 0) {
                $logId = $this->crearLog($opId, $usuarioId, 'edicion', 'Operación actualizada');
                if ($logId <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['status' => 'error', 'msg' => 'No se pudo registrar la bitácora de edición'];
                }
            }

            $this->save("COMMIT", []);
            return ['status' => 'success', 'msg' => 'Operación actualizada'];
        } catch (\Throwable $e) {
            try {
                $this->save("ROLLBACK", []);
            } catch (\Throwable $e2) {
            }
            return ['status' => 'error', 'msg' => 'Error inesperado al actualizar'];
        }
    }

    /* =========================
       ===  BAJA LÓGICA      ===
       ========================= */

    public function desactivarOperacion(int $id, int $usuarioId): array
    {
        try {
            $this->save("START TRANSACTION", []);
            $res = $this->save("UPDATE operaciones SET estatus_id = 6 WHERE id_operacion = ?", [$id]);
            if (!$res) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo desactivar la operación'];
            }
            $this->crearLog($id, $usuarioId, 'eliminar', 'Operación desactivada');
            $this->save("COMMIT", []);
            return ['status' => 'success', 'msg' => 'Operación desactivada'];
        } catch (\Throwable $e) {
            try {
                $this->save("ROLLBACK", []);
            } catch (\Throwable $e2) {
            }
            return ['status' => 'error', 'msg' => 'Error inesperado al desactivar'];
        }
    }

    /* =========================
       ===   CATÁLOGOS EXTRA  ===
       ========================= */

    public function getBrokers(): array
    {
        $sql = "SELECT id_broker, nombre
                FROM brokers
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function getTransportistas(): array
    {
        $sql = "SELECT id_transportista, nombre
                FROM transportistas
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }
    public function listarDestinos(): array
    {
        $sql = "SELECT id_ciudad,nombre_ciudad 
                FROM ciudades   
                WHERE estatus = 1 
                ORDER BY nombre_ciudad";
        return $this->selectAll($sql) ?: [];
    }
}
