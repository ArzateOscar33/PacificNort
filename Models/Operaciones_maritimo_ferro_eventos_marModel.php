<?php
class Operaciones_maritimo_ferro_eventos_marModel extends Query
{

public function listarEventosMFPaginado(
    int $page,
    int $perPage,
    ?int $opId = null,
    ?int $contMarOpId = null,
    string $q = ''
): array
{
    // saneo de paginación
    $perPage = min(100, max(1, $perPage));
    $offset  = max(0, ($page - 1) * $perPage);

    // WHERE base: solo activos + operaciones MF (id_tipo_operacion = 11)
    $where  = [
        "e.estatus = 1",
        "o.tipo_operacion_id = 11" // MF
    ];
    $params = [];

    // filtro por operación (opcional)
    if (!empty($opId)) {
        $where[] = "e.operacion_id = ?";
        $params[] = (int)$opId;
    }

    // filtro por contenedor marítimo en operación (opcional)
    if (!empty($contMarOpId)) {
        $where[] = "e.cont_maritimo_operacion_id = ?";
        $params[] = (int)$contMarOpId; // cmo.id
    }

    // búsqueda libre (evento, comentario, operación, contenedor)
    if ($q !== '') {
        $like = '%'.mb_strtolower($q, 'UTF-8').'%';
        $where[] = "("
                 . "LOWER(te.nombre) LIKE ? "
                 . "OR LOWER(e.comentario) LIKE ? "
                 . "OR LOWER(o.numero_operacion) LIKE ? "
                 . "OR LOWER(cm.numero_contenedor) LIKE ?"
                 . ")";
        array_push($params, $like, $like, $like, $like);
    }

    $whereSql = 'WHERE '.implode(' AND ', $where);

    // ---- total
    $countSql = "
        SELECT COUNT(*) AS total
        FROM eventos_logisticos e
        JOIN operaciones o ON o.id_operacion = e.operacion_id
        LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        $whereSql
    ";
    $rowCount = $this->select($countSql, $params);
    $total    = $rowCount ? (int)$rowCount['total'] : 0;

    // ---- datos (orden: operación↑, contenedor↑, fecha↓, id↓)
    $dataSql = "
        SELECT
            e.id_evento,
            e.operacion_id,
            e.cont_maritimo_operacion_id,
            e.tipo_evento_id,
            te.nombre               AS evento,
            e.fecha,
            e.comentario,
            o.numero_operacion      AS operacion,
            cm.numero_contenedor    AS contenedor
        FROM eventos_logisticos e
        JOIN operaciones o ON o.id_operacion = e.operacion_id
        LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        $whereSql
        ORDER BY
            o.numero_operacion ASC,
            contenedor ASC,
            e.fecha DESC,
            e.id_evento DESC
        LIMIT $perPage OFFSET $offset
    ";
    $rows = $this->selectAll($dataSql, $params);

    return [
        'rows'     => is_array($rows) ? $rows : [],
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage
    ];
}



       /** 
 * Eventos MARÍTIMOS (catálogo) para construir columnas dinámicas.
 * Devuelve: [{id, nombre, key}] donde key es un slug seguro para usar como llave en el frontend/pivoteo.
 */
public function listarEventosMaritimosParaColumnas(): array
{
    $sql = "SELECT 
                te.id_tipo_evento AS id, 
                te.nombre
            FROM tipos_evento_logistico te
            WHERE te.estatus = 1
              AND te.id_tipo_operacion = 1         -- 1 = Marítimo (catálogo)
            ORDER BY te.id_tipo_evento ASC";

    $rows = $this->selectAll($sql);
    if (!is_array($rows)) return [];

    // agregamos una llave 'key' estable para usar como nombre de columna/propiedad
    foreach ($rows as &$r) {
        $r['key'] = $this->slugEvento($r['nombre']);
    }
    return $rows;
}

/** Convierte 'Cita en puerto' => 'cita_en_puerto' (sin acentos, solo [a-z0-9_]) */
private function slugEvento(string $nombre): string
{
    $s = mb_strtolower(trim($nombre), 'UTF-8');
    // quitar acentos
    $s = strtr($s, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
        'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ÿ'=>'y','ñ'=>'n'
    ]);
    // cualquier cosa que no sea letra/numero => espacio
    $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
    // colapsar guiones bajos
    $s = preg_replace('/_+/', '_', $s);
    return trim($s, '_');
}

/* ===== AUTOCOMPLETES (OPERACIÓN MF=11 + CONTENEDOR MARÍTIMO) ===== */

/** Operaciones MARÍTIMO-FERROVIARIAS (id_tipo_operacion = 11) */
public function buscarOperacionesMaritimoFerro(string $term, int $limit = 10): array
{
    $limit  = max(1, (int)$limit);
    $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

    $sql = "
        SELECT 
            o.id_operacion AS id,
            o.numero_operacion AS label,
            COUNT(DISTINCT cmo.id) AS maritimos
        FROM operaciones o
        LEFT JOIN contenedores_maritimos_operacion cmo
               ON cmo.operacion_id = o.id_operacion
        WHERE o.tipo_operacion_id = 11                 -- <<<<<< CLAVE: MF
          AND LOWER(o.numero_operacion) LIKE ?
        GROUP BY o.id_operacion, o.numero_operacion
        ORDER BY o.numero_operacion ASC
        LIMIT $limit
    ";
    $rows = $this->selectAll($sql, [$needle]);
    return is_array($rows) ? $rows : [];
}

/** Contenedores MARÍTIMOS ligados a una operación MF (por operacion_id) */
public function buscarContenedoresMarDeOperacion(int $operacionId, string $term = '', int $limit = 15): array
{
    $limit  = max(1, (int)$limit);
    $params = [$operacionId];
    $filtro = '';

    if ($term !== '') {
        $filtro = " AND LOWER(cm.numero_contenedor) LIKE ? ";
        $params[] = '%'.mb_strtolower($term, 'UTF-8').'%';
    }

    $sql = "
        SELECT 
            cmo.id               AS id,          -- contenedores_maritimos_operacion.id
            cm.numero_contenedor AS label,
            'MARITIMO'           AS tipo
        FROM contenedores_maritimos_operacion cmo
        JOIN contenedores_maritimos cm 
          ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE cmo.operacion_id = ?
          AND cm.estatus = 1                    -- activos
          $filtro
        ORDER BY cm.numero_contenedor ASC
        LIMIT $limit
    ";
    $rows = $this->selectAll($sql, $params);
    return is_array($rows) ? $rows : [];
}

/** Catálogo de tipos de evento MARÍTIMOS (se usan en MF) */
public function listarTiposEventoMaritimo(): array
{
    $sql = "SELECT id_tipo_evento, nombre
            FROM tipos_evento_logistico
            WHERE estatus = 1
              AND id_tipo_operacion = 1         -- <<<<<< eventos marítimos
            ORDER BY nombre ASC";
    $rows = $this->selectAll($sql);
    return is_array($rows) ? $rows : [];
}
public function getContenedorMaritimoDeOperacion(int $operacionId): ?array
{
    if ($operacionId <= 0) return null;

    $sql = "
        SELECT 
            cmo.id               AS id,           -- contenedores_maritimos_operacion.id
            cm.numero_contenedor AS label
        FROM contenedores_maritimos_operacion cmo
        JOIN operaciones o 
          ON o.id_operacion = cmo.operacion_id
        JOIN contenedores_maritimos cm 
          ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE cmo.operacion_id = ?
          AND o.tipo_operacion_id = 11           --  MF = 11
          AND cm.estatus = 1
        ORDER BY cmo.id ASC
        LIMIT 1
    ";
    $row = $this->select($sql, [$operacionId]);
    return $row ?: null;
}
 /* ========== Duplicados (misma pareja: cmo.id + tipo_evento_id, activo) ========== */
    public function existeEventoMaritimoDuplicado(int $contMaritimoOperacionId, int $tipoEventoId, ?int $excluirId = null): bool
    {
        if ($contMaritimoOperacionId <= 0 || $tipoEventoId <= 0) return false;

        $sql = "SELECT id_evento
                  FROM eventos_logisticos
                 WHERE estatus = 1
                   AND cont_maritimo_operacion_id = ?
                   AND tipo_evento_id = ?"
             . ($excluirId ? " AND id_evento <> ?" : "")
             . " LIMIT 1";

        $params = $excluirId
            ? [$contMaritimoOperacionId, $tipoEventoId, $excluirId]
            : [$contMaritimoOperacionId, $tipoEventoId];

        return (bool)$this->select($sql, $params);
    }

    /* ========== Registrar evento (MF=11 con eventos marítimos=1) ========== */
    public function registrar(array $data, int $idUsuario): int
    {
        // 1) Normalización básica
        $operacionId = (int)($data['operacion_id'] ?? 0);
        $cmoId       = (int)($data['cont_maritimo_operacion_id'] ?? 0); // contenedores_maritimos_operacion.id
        $tipoEvtId   = (int)($data['tipo_evento_id'] ?? 0);
        $fecha       = (string)($data['fecha'] ?? '');
        $comentario  = $data['comentario'] ?? null;

        if ($operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return 0; // faltan datos
        }

        // 2) Validar que la operación sea MF (id_tipo_operacion = 11)
        $rowOp = $this->select("SELECT id_operacion 
                                  FROM operaciones 
                                 WHERE id_operacion = ? 
                                   AND tipo_operacion_id = 11 
                                 LIMIT 1", [$operacionId]);
        if (!$rowOp) return 0;

        // 3) Validar que el CMO pertenezca a la operación y contenedor esté ACTIVO
        $rowCMO = $this->select("
            SELECT cmo.id
              FROM contenedores_maritimos_operacion cmo
              JOIN contenedores_maritimos cm 
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
             WHERE cmo.id = ?
               AND cmo.operacion_id = ?
               AND cm.estatus = 1
             LIMIT 1
        ", [$cmoId, $operacionId]);
        if (!$rowCMO) return 0;

        // 4) Validar que el tipo de evento sea MARÍTIMO (id_tipo_operacion = 1) y esté activo
        $rowEvt = $this->select("
            SELECT id_tipo_evento
              FROM tipos_evento_logistico
             WHERE id_tipo_evento = ?
               AND id_tipo_operacion = 1   -- catálogo marítimo
               AND estatus = 1
             LIMIT 1
        ", [$tipoEvtId]);
        if (!$rowEvt) return 0;

        // 5) Evitar duplicados (misma pareja cmo.id + tipo_evento_id, activo)
        if ($this->existeEventoMaritimoDuplicado($cmoId, $tipoEvtId)) {
            return 0;
        }

        // 6) Insertar
        $sqlIns = "INSERT INTO eventos_logisticos
                   (operacion_id, cont_maritimo_operacion_id, tipo_evento_id, fecha, comentario, creado_por)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$operacionId, $cmoId, $tipoEvtId, $fecha, $comentario, ($idUsuario ?: null)];

        return (int)$this->insertar($sqlIns, $params);
    }

    /* ========== (Opcional) Actualizar con las mismas reglas de validación ========== */
    public function actualizar(array $data): bool
    {
        $idEvento   = (int)($data['id_evento'] ?? 0);
        $operacionId= (int)($data['operacion_id'] ?? 0);
        $cmoId      = (int)($data['cont_maritimo_operacion_id'] ?? 0);
        $tipoEvtId  = (int)($data['tipo_evento_id'] ?? 0);
        $fecha      = (string)($data['fecha'] ?? '');
        $comentario = $data['comentario'] ?? null;

        if ($idEvento <= 0 || $operacionId <= 0 || $cmoId <= 0 || $tipoEvtId <= 0 || $fecha === '') {
            return false;
        }

        // Validaciones iguales a registrar:
        $rowOp = $this->select("SELECT id_operacion FROM operaciones WHERE id_operacion = ? AND tipo_operacion_id = 11 LIMIT 1", [$operacionId]);
        if (!$rowOp) return false;

        $rowCMO = $this->select("
            SELECT cmo.id
              FROM contenedores_maritimos_operacion cmo
              JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
             WHERE cmo.id = ?
               AND cmo.operacion_id = ?
               AND cm.estatus = 1
             LIMIT 1
        ", [$cmoId, $operacionId]);
        if (!$rowCMO) return false;

        $rowEvt = $this->select("
            SELECT id_tipo_evento
              FROM tipos_evento_logistico
             WHERE id_tipo_evento = ?
               AND id_tipo_operacion = 1
               AND estatus = 1
             LIMIT 1
        ", [$tipoEvtId]);
        if (!$rowEvt) return false;

        if ($this->existeEventoMaritimoDuplicado($cmoId, $tipoEvtId, $idEvento)) {
            return false;
        }

        $sql = "UPDATE eventos_logisticos
                   SET operacion_id = ?,
                       cont_maritimo_operacion_id = ?,
                       tipo_evento_id = ?,
                       fecha = ?,
                       comentario = ?
                 WHERE id_evento = ?
                   AND estatus = 1";
        $params = [$operacionId, $cmoId, $tipoEvtId, $fecha, $comentario, $idEvento];

        return (bool)$this->save($sql, $params);
    }

public function obtenerEventoPorClave(int $operacionId, int $cmoId, int $tipoEventoId): ?array
{
    if ($operacionId<=0 || $cmoId<=0 || $tipoEventoId<=0) return null;

    $sql = "SELECT 
                e.id_evento, e.operacion_id, e.cont_maritimo_operacion_id,
                e.tipo_evento_id, e.fecha, e.comentario
            FROM eventos_logisticos e
            WHERE e.estatus = 1
              AND e.operacion_id = ?
              AND e.cont_maritimo_operacion_id = ?
              AND e.tipo_evento_id = ?
            LIMIT 1";
    $row = $this->select($sql, [$operacionId, $cmoId, $tipoEventoId]);
    return $row ?: null;
}

}
