<?php
class Operaciones_maritimasModel extends Query
{
    /* =========================
       ===  CATÁLOGOS/HELPERS ===
       ========================= */

    public function getSubtipo(int $id): ?array
    {
        $sql = "SELECT id_subtipo, nombre, requiere_naviera, requiere_forwarder, puerto_arribo_default_id
                FROM subtipos_operacion
                WHERE id_subtipo = ?
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function findContenedorByNumero(string $numero): ?array
    {
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                LIMIT 1";
        $row = $this->select($sql, [$numero]);
        return $row ?: null;
    }

    public function createContenedor(string $numero): int
    {
        $sql = "INSERT INTO contenedores_maritimos (numero_contenedor, estatus) VALUES (?, 1)";
        return (int)$this->insertar($sql, [$numero]);
    }

    public function linkContenedorOperacion(int $opId, int $contenedorId): int
    {
        $sql = "INSERT INTO contenedores_maritimos_operacion (operacion_id, contenedor_maritimo_id)
                VALUES (?, ?)";
        return (int)$this->insertar($sql, [$opId, $contenedorId]);
    }

    public function crearLog(int $opId, int $usuarioId, string $accion, string $descripcion = ''): int
    {
        $sql = "INSERT INTO operaciones_log (operacion_id, usuario_id, accion, descripcion, fecha)
                VALUES (?, ?, ?, ?, NOW())";
        return (int)$this->insertar($sql, [$opId, $usuarioId, $accion, $descripcion]);
    }

    /* =========================
       ===        LISTAR      ===
       ========================= */
public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 10): array
{
    // 1) Paginación
    $page    = max(1, (int)$page);
    $perPage = max(1, (int)$perPage);
    $offset  = ($page - 1) * $perPage;

    // 2) WHERE base
    $where = "WHERE UPPER(tt.nombre_operacion) LIKE 'MARIT%'";
    $args  = [];

    // --- (opcional) subtipo/term: tal cual ya lo tenías ---
    $subtipoId = isset($filters['filtroSubtipo']) ? (int)$filters['filtroSubtipo']
              : (isset($filters['subtipo_id']) ? (int)$filters['subtipo_id'] : 0);
    if ($subtipoId > 0) {
        $where .= " AND o.subtipo_operacion_id = ? ";
        $args[] = $subtipoId;
    }

    // --- búsqueda multi-término (coma) ---
    $raw = trim($filters['term'] ?? '');
    if ($raw !== '') {
        // separa por coma, limpia y limita a 5 tokens
        $terms = array_values(array_filter(array_map(
            fn($t) => mb_strtolower(trim($t), 'UTF-8'),
            explode(',', $raw)
        ), fn($t) => $t !== ''));
        $terms = array_slice($terms, 0, 5);

        foreach ($terms as $t) {
            $needle = '%'.$t.'%';
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


    // === SOLO ESTOS DOS FILTROS: fecha_inicio / fecha_fin sobre ETA ===
    $fi = trim($filters['fecha_inicio'] ?? '');
    $ff = trim($filters['fecha_fin'] ?? '');

    // Valida formato YYYY-MM-DD
    $isDate = static function(string $d): bool {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    };
    if ($fi !== '' && !$isDate($fi)) { $fi = ''; }
    if ($ff !== '' && !$isDate($ff)) { $ff = ''; }

    // Corrige orden si vienen invertidas
    if ($fi !== '' && $ff !== '' && $fi > $ff) {
        [$fi, $ff] = [$ff, $fi];
    }

    // Aplica a ETA (o.eta). Usa DATE() para ignorar horas si las hay.
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

    // 3) TOTAL
    $sqlCount = "
        SELECT COUNT(DISTINCT o.id_operacion) AS total
        FROM operaciones o
        JOIN tipos_operacion tt       ON tt.id_tipo_operacion = o.tipo_operacion_id
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN puertos p           ON p.id_puerto = st.puerto_arribo_default_id
        LEFT JOIN clientes c          ON c.id_cliente = o.cliente_id
        LEFT JOIN estatus e           ON e.id_estatus = o.estatus_id
        LEFT JOIN shippers s          ON s.id_shipper = o.shipper_id
        $where
    ";
    $rowCount = $this->select($sqlCount, $args) ?: ['total' => 0];
    $total    = (int)$rowCount['total'];

    // 4) DATA
    $limit = (int)$perPage;
    $off   = (int)$offset;

    $sqlData = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            st.nombre  AS subtipo,
            o.numero_bl,
            p.nombre   AS puerto_arribo,
            n.nombre   AS naviera,
            f.nombre   AS forwarder,
            c.nombre   AS cliente,
            o.etd, o.eta,
            e.nombre   AS estatus,
            GROUP_CONCAT(DISTINCT cm.numero_contenedor
                         ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
        FROM operaciones o
        JOIN tipos_operacion tt       ON tt.id_tipo_operacion = o.tipo_operacion_id
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
        GROUP BY o.id_operacion, o.numero_operacion, st.nombre, o.numero_bl,
                 p.nombre, n.nombre, f.nombre, c.nombre, o.etd, o.eta, e.nombre
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
     * Inserta operación marítima y relaciones usando SOLO métodos de Query (transacción con SAVE).
     * @param array      $op            Campos para tabla operaciones
     * @param array      $contenedores  [['id'=>?, 'numero'=>?], ...]
     * @param int        $usuarioId
     * @return array     ['status'=>'success','id_operacion'=>X] | ['status'=>'error','msg'=>...]
     */
public function insertarOperacion(array $op, array $contenedores, int $usuarioId): array
{
    try {
        // =====================================================
        // A) Generar número de operación si viene vacío
        //    Usamos secuencia atómica (tabla secuencias_operacion)
        //    para evitar carreras al mismo tiempo.
        // =====================================================
        if (empty($op['numero_operacion'])) {
            $codigo = $this->generarCodigoPorSecuencia((int)$op['subtipo_operacion_id']); // <- NUEVO
            if (!$codigo) {
                return ['status' => 'error', 'msg' => 'No se pudo generar el folio'];
            }
            $op['numero_operacion'] = $codigo;
        }

        // =====================================================
        // B) Transacción principal de inserción
        // =====================================================
        $this->save("START TRANSACTION", []);

        // B.1) Validaciones de subtipo y requisitos
        $st = $this->getSubtipo((int)$op['subtipo_operacion_id']);
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

        // B.2) Insert en operaciones (con código ya único)
        $sqlOp = "INSERT INTO operaciones
            (numero_operacion, tipo_operacion_id, subtipo_operacion_id, etd, eta, numero_bl,
             cliente_id, estatus_id, naviera_id, forwarder_id, shipper_id, notas)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $paramsOp = [
            trim($op['numero_operacion']),
            (int)$op['tipo_operacion_id'],
            (int)$op['subtipo_operacion_id'],
            $op['etd'] ?? null,
            $op['eta'] ?? null,
            $op['numero_bl'] ?? null,
            (int)$op['cliente_id'],
            (int)($op['estatus_id'] ?? 9),
            !empty($op['naviera_id'])   ? (int)$op['naviera_id']   : null,
            !empty($op['forwarder_id']) ? (int)$op['forwarder_id'] : null,
            !empty($op['shipper_id'])   ? (int)$op['shipper_id']   : null,
            $op['notas'] ?? null,
        ];
        $opId = (int)$this->insertar($sqlOp, $paramsOp);
        if ($opId <= 0) {
            // Si falló (p.ej. por UNIQUE de numero_operacion), revertimos todo
            $this->save("ROLLBACK", []);
            return ['status' => 'error', 'msg' => 'No se pudo guardar la operación'];
        }

        // B.3) Contenedores (alta en catálogo si hace falta + relación)
        if (!empty($contenedores)) {
            $vistos = [];
            foreach ($contenedores as $c) {
                $cid  = isset($c['id']) ? (int)$c['id'] : 0;
                $cnum = trim($c['numero'] ?? '');

                // Ignora vacíos
                if ($cid <= 0 && $cnum === '') continue;

                // Evita duplicar la misma fila en la misma operación
                $key = $cid > 0 ? 'id:' . $cid : 'num:' . mb_strtolower($cnum, 'UTF-8');
                if (isset($vistos[$key])) continue;
                $vistos[$key] = true;

                // Si no viene id, intenta encontrar o crear
                if ($cid <= 0) {
                    $found = $this->findContenedorByNumero($cnum);
                    if ($found) $cid = (int)$found['id_contenedor_maritimo'];
                    else        $cid = (int)$this->createContenedor($cnum);

                    if ($cid <= 0) {
                        $this->save("ROLLBACK", []);
                        return ['status' => 'error', 'msg' => "No se pudo crear el contenedor: {$cnum}"];
                    }
                }

                // Relación con operación
                $linkId = $this->linkContenedorOperacion($opId, $cid);
                if ($linkId <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['status' => 'error', 'msg' => 'No se pudo relacionar contenedor con la operación'];
                }
            }
        }

        // B.4) Bitácora
        $logId = $this->crearLog($opId, $usuarioId, 'creacion', 'Operación creada');
        if ($logId <= 0) {
            $this->save("ROLLBACK", []);
            return ['status' => 'error', 'msg' => 'No se pudo registrar la bitácora de creación'];
        }

        // B.5) Commit
        $this->save("COMMIT", []);

        return [
            'status'           => 'success',
            'msg'              => 'Operación creada',
            'id_operacion'     => $opId,
            'numero_operacion' => $op['numero_operacion'],
        ];

    } catch (\Throwable $ex) {
        // Cerrar transacción si quedó abierta
        try { $this->save("ROLLBACK", []); } catch (\Throwable $e2) {}
        error_log("insertarOperacion error: " . $ex->getMessage());
        return ['status' => 'error', 'msg' => 'Error inesperado al guardar'];
    }
}


    /* =========================
       === LECTURAS PARA EDIT ===
       ========================= */

    public function getOperacionById(int $id): ?array
    {
        $sql = "SELECT *
                FROM operaciones
                WHERE id_operacion = ?
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function getContenedoresByOperacion(int $opId): array
    {
        $sql = "SELECT cm.id_contenedor_maritimo, cm.numero_contenedor
                FROM contenedores_maritimos_operacion cmo
                INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                WHERE cmo.operacion_id = ?";
        return $this->selectAll($sql, [$opId]) ?: [];
    }

    /* =========================
       ===  BAJA LÓGICA      ===
       ========================= */

    public function desactivarOperacion(int $id, int $usuarioId): array
    {
        try {
            $this->save("START TRANSACTION", []);

            // Ajusta el estatus_id según tu flujo (6 = cancelada, por ejemplo)
            $res = $this->save("UPDATE operaciones SET estatus_id = 6 WHERE id_operacion = ?", [$id]);
            if (!$res) {
                $this->save("ROLLBACK", []);
                return ['status'=>'error','msg'=>'No se pudo desactivar la operación'];
            }

            $this->crearLog($id, $usuarioId, 'eliminar', 'Operación desactivada');

            $this->save("COMMIT", []);
            return ['status'=>'success','msg'=>'Operación desactivada'];
        } catch (\Throwable $e) {
            $this->save("ROLLBACK", []);
            return ['status'=>'error','msg'=>'Error inesperado al desactivar'];
        }
    }

    /* =========================
       ===  CATÁLOGOS VISTA  ===
       ========================= */

    public function subtiposMaritimos(): array
    {
        $sql = "SELECT st.id_subtipo, st.nombre, st.requiere_naviera, st.requiere_forwarder, st.puerto_arribo_default_id
                FROM subtipos_operacion st
                INNER JOIN tipos_operacion tt ON tt.id_tipo_operacion = st.tipo_operacion_id
                WHERE UPPER(tt.nombre_operacion) LIKE 'MARIT%' AND st.estatus = 1
                ORDER BY st.nombre";
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
        $like = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                  AND LOWER(nombre) LIKE ?
                ORDER BY nombre
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    public function buscarContenedores(string $term): array
    {
        $like = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE estatus = 1
                  AND LOWER(numero_contenedor) LIKE ?
                ORDER BY numero_contenedor
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }

 

public function obtenerOperacion(int $id): ?array
{
    $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            o.subtipo_operacion_id,
            st.nombre AS subtipo_nombre,
            st.requiere_naviera,
            st.requiere_forwarder,
            s.nombre AS shipper_nombre,
            o.shipper_id,
            -- Puerto por defecto del SUBTIPO (no existe campo en 'operaciones')
            st.puerto_arribo_default_id        AS puerto_arribo_id_prefill,
            p.nombre                           AS puerto_arribo_nombre,

            o.numero_bl,
            o.naviera_id,
            o.forwarder_id,
            o.cliente_id,
            c.nombre AS cliente_nombre,
            o.etd, o.eta,
            o.estatus_id,
            e.nombre AS estatus_nombre,
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
    $sql = "UPDATE operaciones
            SET
              
              subtipo_operacion_id = ?,
              etd                  = ?,
              eta                  = ?,
              numero_bl            = ?,
              cliente_id           = ?,
              estatus_id           = ?,
              naviera_id           = ?,
              forwarder_id         = ?,
              shipper_id           = ?,  -- aquí no hay problema
              notas                = ?
            WHERE id_operacion = ?
            LIMIT 1";
    $args = [
       
      (int)($d['subtipo_operacion_id'] ?? 0),
      !empty($d['etd']) ? $d['etd'] : null,
      !empty($d['eta']) ? $d['eta'] : null,
      trim($d['numero_bl'] ?? ''),
      !empty($d['cliente_id'])  ? (int)$d['cliente_id']  : null,
      !empty($d['estatus_id'])  ? (int)$d['estatus_id']  : null,
      ($d['naviera_id']   ?? '') !== '' ? (int)$d['naviera_id']   : null,
      ($d['forwarder_id'] ?? '') !== '' ? (int)$d['forwarder_id'] : null,
      ($d['shipper_id']   ?? '') !== '' ? (int)$d['shipper_id']   : null,
      ($d['notas'] ?? null),
      (int)$d['id_operacion'],
    ];

    $res = $this->save($sql, $args);
    // ✅ Éxito si NO es false (0 filas afectadas cuenta como éxito)
    return $res !== false;
}


public function obtenerContenedoresOperacion(int $operacionId): array
{
    $sql = "
        SELECT 
            cm.id_contenedor_maritimo,
            cm.numero_contenedor
        FROM contenedores_maritimos_operacion cmo
        JOIN contenedores_maritimos cm
          ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE cmo.operacion_id = ?
        ORDER BY cm.numero_contenedor
    ";
    return $this->selectAll($sql, [$operacionId]) ?: [];
}

public function buscarShippers(string $term): array {
  $like = '%'.mb_strtolower($term, 'UTF-8').'%';
  $sql = "SELECT id_shipper, nombre
          FROM shippers
          WHERE estatus = 1 AND LOWER(nombre) LIKE ?
          ORDER BY nombre
          LIMIT 10";
  return $this->selectAll($sql, [$like]) ?: [];
}


// 1) Si tienes prefijo en la BD:
public function getPrefijoSubtipo(int $subtipoId): ?string {
  $row = $this->select("SELECT prefijo_codigo FROM subtipos_operacion WHERE id_subtipo=? LIMIT 1", [$subtipoId]);
  $p = $row ? trim((string)$row['prefijo_codigo']) : '';
  return $p !== '' ? $p : null;
}

 

private function lpadNumero(int $n): string {
  return str_pad((string)$n, ($n < 100 ? 2 : strlen((string)$n)), '0', STR_PAD_LEFT);
}

/**
 * Obtiene preview rápido (SIN candado). Útil para autollenar el input al elegir subtipo.
 */
public function previewCodigoSubtipo(int $subtipoId): ?array {
  $pref = $this->getPrefijoSubtipo($subtipoId);
  if (!$pref) return null;

  $row = $this->select("
    SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_operacion,'-',-1) AS UNSIGNED)), 0) AS maxn
    FROM operaciones
    WHERE tipo_operacion_id = 1 AND subtipo_operacion_id = ?
  ", [$subtipoId]);

  $next = (int)($row['maxn'] ?? 0) + 1;
  return [
    'prefijo' => $pref,
    'numero'  => $next,
    'codigo'  => $pref . '-' . $this->lpadNumero($next),
  ];
}

 
private function nextConsecutivoSeguro(int $subtipoId, int $anio): int
{
    // Requiere UNIQUE (subtipo_id, anio)
    $ok = $this->save(
        "INSERT INTO secuencias_operacion (subtipo_id, anio, valor)
         VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE valor = LAST_INSERT_ID(valor + 1)",
        [$subtipoId, $anio]
    );
    if ($ok === false) return 0;

    // Debe ser la misma conexión que hizo el save()
    $row = $this->select("SELECT LAST_INSERT_ID() AS n");
    return (int)($row['n'] ?? 0);
}





private function lpadNumeroN(int $n): string {
  // Si quieres siempre 2 dígitos mínimo (LC-01..LC-99, luego 100 sin pad extra):
  return str_pad((string)$n, ($n < 100 ? 2 : strlen((string)$n)), '0', STR_PAD_LEFT);
}

/** Nuevo generador: por secuencia (sin locks manuales) */
public function generarCodigoPorSecuencia(int $subtipoId): ?string {
  $pref = $this->getPrefijoSubtipo($subtipoId); // ya lo tienes
  if (!$pref) return null;

  $anio = (int)date('Y');
  $consec = $this->nextConsecutivoSeguro($subtipoId, $anio);
  if ($consec <= 0) return null;

  return $pref . '-' . $this->lpadNumeroN($consec);
}


}
