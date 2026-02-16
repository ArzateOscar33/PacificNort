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
            "
            SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_operacion,'-',-1) AS UNSIGNED)), 0) AS maxn
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

    //Tomara el puerto de la consulta de subtipomaritimoFerro
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

    /** Catálogo contenedor marítimo */
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
       ===  MARÍTIMO (base)  ===
       ========================= */

    /* public function getSubtipo(int $id): ?array
    {
        $sql = "SELECT id_subtipo, nombre, requiere_naviera, requiere_forwarder, puerto_arribo_default_id
                FROM subtipos_operacion
                WHERE id_subtipo = ?
                LIMIT 1";
        return $this->select($sql, [$id]) ?: null;
    }*/

    public function findContenedorByNumero(string $numero): ?array
    {
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$numero]) ?: null;
    }

    public function createContenedor(string $numero): int
    {
        $sql = "INSERT INTO contenedores_maritimos (numero_contenedor, estatus) VALUES (?, 1)";
        return (int)$this->insertar($sql, [$numero]);
    }

    public function linkContenedorOperacion(int $opId, int $contenedorId, $bultos = null): int
    {
        $sql = "INSERT INTO contenedores_maritimos_operacion (operacion_id, contenedor_maritimo_id, bultos)
                VALUES (?, ?, ?)";
        $valBultos = ($bultos === '' || $bultos === null) ? null : (is_numeric($bultos) ? (int)$bultos : null);
        return (int)$this->insertar($sql, [$opId, $contenedorId, $valBultos]);
    }

    public function actualizarBultos(int $operacionId, int $contenedorMaritimoId, $bultos): bool
    {
        $sql = "UPDATE contenedores_maritimos_operacion
                SET bultos = ?
                WHERE operacion_id = ? AND contenedor_maritimo_id = ?
                LIMIT 1";
        $valBultos = ($bultos === '' || $bultos === null) ? null : (is_numeric($bultos) ? (int)$bultos : null);
        return $this->save($sql, [$valBultos, $operacionId, $contenedorMaritimoId]) !== false;
    }

    public function getContenedoresByOperacion(int $opId): array
    {
        $sql = "SELECT
                    cm.id_contenedor_maritimo,
                    cm.numero_contenedor,
                    cmo.bultos
                FROM contenedores_maritimos_operacion cmo
                INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                WHERE cmo.operacion_id = ?
                ORDER BY cm.numero_contenedor";
        return $this->selectAll($sql, [$opId]) ?: [];
    }





    /* =========================
       ===       LISTAR      ===
       ========================= */

    /**
     * Lista operaciones Marítimo/Ferro (subtipo.tipo_operacion_id IN (1,11))
     * Filtros: filtroSubtipo|subtipo_id, term (multi-coma), fecha_inicio/fecha_fin (ETA)
     */
    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $page    = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset  = ($page - 1) * $perPage;

        // Base: mostrar subtipos marítimo o ferro
        $where = "WHERE st.tipo_operacion_id IN (11)";
        $args  = [];

        // Filtro opcional por subtipo
        $subtipoId = isset($filters['filtroSubtipo']) ? (int)$filters['filtroSubtipo']
            : (isset($filters['subtipo_id']) ? (int)$filters['subtipo_id'] : 0);
        if ($subtipoId > 0) {
            $where .= " AND o.subtipo_operacion_id = ? ";
            $args[] = $subtipoId;
        }

        // (Opcional) filtro tipo: 1 o 11
        if (!empty($filters['tipo']) && in_array((int)$filters['tipo'], [11], true)) {
            $where .= " AND st.tipo_operacion_id = ? ";
            $args[] = (int)$filters['tipo'];
        }

        // Búsqueda multi-término (separado por coma)
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

        // Filtro por fechas (ETA)
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

        // Total
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

        // Data
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
                e.nombre   AS estatus,
                o.isf,
                o.cita_puerto,
                GROUP_CONCAT(DISTINCT cm.numero_contenedor
                             ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
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
            $where
            GROUP BY o.id_operacion, o.numero_operacion, st.nombre, st.tipo_operacion_id,
            o.numero_bl, p.nombre, n.nombre, f.nombre, c.nombre, o.etd, o.eta, e.nombre,
            o.isf, o.cita_puerto
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

    /**
     * Inserta operación (marítima o ferroviaria) y relaciones marítimas.
     * NOTA: tipo_operacion_id se deriva del SUBTIPO.
     * @param array $op Campos de 'operaciones' (sin tipo_operacion_id)
     * @param array $contenedores [['id'=>?, 'numero'=>?, 'bultos'=>?], ...]
     */
    public function insertarOperacion(array $op, array $contenedores, int $usuarioId): array
    {
        try {
            // A) Folio
            if (empty($op['numero_operacion'])) {
                $codigo = $this->generarCodigoPorSecuencia((int)$op['subtipo_operacion_id']);
                if (!$codigo) return ['status' => 'error', 'msg' => 'No se pudo generar el folio'];
                $op['numero_operacion'] = $codigo;
            }

            $this->save("START TRANSACTION", []);

            // B) Subtipo + tipo derivado
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

            // C) Insert en operaciones
            $sqlOp = "INSERT INTO operaciones
            (numero_operacion, tipo_operacion_id, subtipo_operacion_id, etd, eta, numero_bl,
            cliente_id, estatus_id, naviera_id, forwarder_id, shipper_id, notas, isf, cita_puerto)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
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
            ];
            $opId = (int)$this->insertar($sqlOp, $paramsOp);
            if ($opId <= 0) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo guardar la operación'];
            }

            // D) Contenedores marítimos (catálogo + vínculo + bultos)
            if (!empty($contenedores)) {
                $vistos = [];
                foreach ($contenedores as $c) {
                    $cid  = isset($c['id']) ? (int)$c['id'] : 0;
                    $cnum = trim($c['numero'] ?? '');
                    $cbul = $c['bultos'] ?? null;

                    if ($cid <= 0 && $cnum === '') continue;

                    $key = $cid > 0 ? 'id:' . $cid : 'num:' . mb_strtolower($cnum, 'UTF-8');
                    if (isset($vistos[$key])) continue;
                    $vistos[$key] = true;

                    if ($cid <= 0) {
                        $found = $this->findContenedorByNumero($cnum);
                        if ($found) $cid = (int)$found['id_contenedor_maritimo'];
                        else        $cid = (int)$this->createContenedor($cnum);
                        if ($cid <= 0) {
                            $this->save("ROLLBACK", []);
                            return ['status' => 'error', 'msg' => "No se pudo crear el contenedor: {$cnum}"];
                        }
                    }

                    $linkId = $this->linkContenedorOperacion($opId, $cid, $cbul);
                    if ($linkId <= 0) {
                        $this->save("ROLLBACK", []);
                        return ['status' => 'error', 'msg' => 'No se pudo relacionar contenedor con la operación'];
                    }
                }
            }

            // E) Log
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
            error_log("PAYLOAD op: " . json_encode($op, JSON_UNESCAPED_UNICODE));
            error_log("CONTENEDORES: " . json_encode($contenedores, JSON_UNESCAPED_UNICODE));
            try {
                $this->save("ROLLBACK", []);
            } catch (\Throwable $e2) {
            }
            error_log("insertarOperacion error: " . $ex->getMessage());
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
                s.nombre AS shipper_nombre,
                o.shipper_id,
                st.puerto_arribo_default_id AS puerto_arribo_id_prefill,
                p.nombre AS puerto_arribo_nombre,
                o.numero_bl,
                o.naviera_id,
                o.forwarder_id,
                o.cliente_id,
                c.nombre AS cliente_nombre,
                o.etd, o.eta,
                o.estatus_id,
                e.nombre AS estatus_nombre,
                o.isf,
                o.cita_puerto,
                o.notas
            FROM operaciones o
            LEFT JOIN shippers s          ON s.id_shipper = o.shipper_id
            LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN puertos p             ON p.id_puerto = st.puerto_arribo_default_id
            LEFT JOIN clientes c            ON c.id_cliente = o.cliente_id
            LEFT JOIN estatus e             ON e.id_estatus = o.estatus_id
            WHERE o.id_operacion = ?
            LIMIT 1
        ";
        return $this->select($sql, [$id]) ?: null;
    }

    public function actualizarOperacion(array $d): bool
    {
        $st = $this->getSubtipoFull((int)($d['subtipo_operacion_id'] ?? 0));
        if (!$st) return false;

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
                    notas                 = ?
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

            // ORDEN CORRECTO para: isf=?, cita_puerto=?, notas=?, id_operacion=?
            (int)($d['isf'] ?? 0),
            ($d['cita_puerto'] ?? null),
            ($d['notas'] ?? null),
            (int)$d['id_operacion'],
        ];


        $res = $this->save($sql, $args);
        return $res !== false;
    }

    public function getContenedoresDeOperacion(int $id): array
    {
        $sql = "SELECT 
                cm.id_contenedor_maritimo, 
                cm.numero_contenedor,
                cmo.bultos
            FROM contenedores_maritimos cm
            INNER JOIN contenedores_maritimos_operacion cmo
                ON cmo.contenedor_maritimo_id = cm.id_contenedor_maritimo
            WHERE cmo.operacion_id = ?
            ORDER BY cm.numero_contenedor";
        return $this->selectAll($sql, [$id]) ?: [];
    }


    /* =========================
       ===  BAJA LÓGICA      ===
       ========================= */

    public function desactivarOperacion(int $id, int $usuarioId): array
    {
        try {
            $this->save("START TRANSACTION", []);
            // Define tu estatus de baja (ej. 6 = cancelada)
            $res = $this->save("UPDATE operaciones SET estatus_id = 6 WHERE id_operacion = ?", [$id]);
            if (!$res) {
                $this->save("ROLLBACK", []);
                return ['status' => 'error', 'msg' => 'No se pudo desactivar la operación'];
            }
            $this->crearLog($id, $usuarioId, 'eliminar', 'Operación desactivada');
            $this->save("COMMIT", []);
            return ['status' => 'success', 'msg' => 'Operación desactivada'];
        } catch (\Throwable $e) {
            $this->save("ROLLBACK", []);
            return ['status' => 'error', 'msg' => 'Error inesperado al desactivar'];
        }
    }
}
