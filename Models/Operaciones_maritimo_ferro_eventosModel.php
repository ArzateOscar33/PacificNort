<?php
class Operaciones_maritimo_ferro_eventosModel extends Query
{
     private const TIPOS_ENTREGA = [6, 10]; // 6=Entrega Cargado (Terrestre), 10=Entrega (Marítimo)
    public function listar()
    {
        $sql = "
        SELECT
            e.id_evento,
            te.nombre               AS evento,
            e.fecha,
            o.numero_operacion      AS operacion,
            cf.numero_ferro         AS contenedor,  -- contenedor físico (ferro) si aplica
            e.comentario
        FROM eventos_logisticos e
        LEFT JOIN tipos_evento_logistico te
               ON te.id_tipo_evento = e.tipo_evento_id
        LEFT JOIN operaciones o
               ON o.id_operacion = e.operacion_id
        LEFT JOIN contenedores_operacion co
               ON co.id_contenedor = e.contenedor_operacion_id
        LEFT JOIN contenedores_fisicos cf
               ON cf.id_fisico = co.id_fisico
        WHERE e.estatus = 1
          AND o.tipo_operacion_id = 1           -- 1 = Marítimo
        ORDER BY e.fecha DESC, e.id_evento DESC
        ";
        return $this->selectAll($sql);
    }

public function listarPaginado(int $page, int $perPage, ?int $opId = null, ?int $contId = null, string $q = ''): array
{
    $perPage = min(100, max(1, $perPage));
    $offset  = max(0, ($page - 1) * $perPage);

    // WHERE dinámico
    $where  = ["e.estatus = 1", "o.tipo_operacion_id = 1"];
    $params = [];

    if (!empty($opId)) {
        $where[] = "e.operacion_id = ?";
        $params[] = $opId;
    }

    // Filtro por contenedor físico (si luego filtras marítimo, añade otra rama)
    if (!empty($contId)) {
        $where[] = "e.contenedor_operacion_id = ?";
        $params[] = $contId;
    }

    if ($q !== '') {
        $like = '%'.mb_strtolower($q, 'UTF-8').'%';
        $where[] = "(LOWER(te.nombre) LIKE ? 
                     OR LOWER(e.comentario) LIKE ?
                     OR LOWER(o.numero_operacion) LIKE ?
                     OR LOWER(cf.numero_ferro) LIKE ?
                     OR LOWER(cm.numero_contenedor) LIKE ?)";
        array_push($params, $like, $like, $like, $like, $like);
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // Total
    $countSql = "
        SELECT COUNT(*) AS total
        FROM eventos_logisticos e
        LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
        LEFT JOIN operaciones o             ON o.id_operacion = e.operacion_id
        LEFT JOIN contenedores_operacion co ON co.id_contenedor = e.contenedor_operacion_id
        LEFT JOIN contenedores_fisicos cf   ON cf.id_fisico = co.id_fisico
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        $whereSql
    ";
    $rowCount = $this->select($countSql, $params);
    $total    = $rowCount ? (int)$rowCount['total'] : 0;

    // Data (orden: Operación ↑, Contenedor ↑, Fecha ↓, Id ↓)
    $dataSql = "
        SELECT
            e.id_evento,
            te.nombre AS evento,
            e.fecha,
            o.numero_operacion AS operacion,
            COALESCE(cf.numero_ferro, cm.numero_contenedor) AS contenedor,
            e.comentario
        FROM eventos_logisticos e
        LEFT JOIN tipos_evento_logistico te ON te.id_tipo_evento = e.tipo_evento_id
        LEFT JOIN operaciones o             ON o.id_operacion = e.operacion_id
        LEFT JOIN contenedores_operacion co ON co.id_contenedor = e.contenedor_operacion_id
        LEFT JOIN contenedores_fisicos cf   ON cf.id_fisico = co.id_fisico
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        $whereSql
        ORDER BY 
            o.numero_operacion DESC,
            contenedor ASC,
            e.fecha DESC,
            e.id_evento DESC

        LIMIT $perPage OFFSET $offset
    ";
    $rows = $this->selectAll($dataSql, $params);

    return ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
}


    /** Sugerencias de operaciones marítimas por texto (LI, JL-0, etc.) */
    public function buscarOperacionesMaritimas(string $term, int $limit = 10): array
    {
        $limit = max(1, (int)$limit); // sanitiza LIMIT
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

        $sql = "
        SELECT 
            o.id_operacion AS id,
            o.numero_operacion AS label,
            COUNT(DISTINCT co.id_contenedor) AS fisicos,
            COUNT(DISTINCT cmo.id)           AS maritimos
        FROM operaciones o
        LEFT JOIN contenedores_operacion co
               ON co.operacion_id = o.id_operacion
        LEFT JOIN contenedores_maritimos_operacion cmo
               ON cmo.operacion_id = o.id_operacion
        WHERE o.tipo_operacion_id = 1
          AND LOWER(o.numero_operacion) LIKE ?
        GROUP BY o.id_operacion, o.numero_operacion
        ORDER BY o.numero_operacion DESC
        LIMIT $limit
    ";

        $rows = $this->selectAll($sql, [$needle]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Sugerencias de contenedores (FÍSICOS y MARÍTIMOS) pertenecientes a una operación.
     * Filtra opcionalmente por texto (ej. FXEU, WHSU, EMCU...).
     */
    public function buscarContenedoresDeOperacion(int $operacionId, string $term = '', int $limit = 15): array
    {
        $limit  = max(1, (int)$limit);
        $params = [];
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

        $filtroFis = '';
        $filtroMar = '';

        if ($term !== '') {
            $filtroFis = " AND LOWER(cf.numero_ferro) LIKE ? ";
            $filtroMar = " AND LOWER(cm.numero_contenedor) LIKE ? ";
        }

        $sql = "
        SELECT id, label, tipo FROM (
            -- FÍSICOS
            SELECT 
                co.id_contenedor     AS id,
                cf.numero_ferro      AS label,
                'FISICO'             AS tipo
            FROM contenedores_operacion co
            JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
            WHERE co.operacion_id = ? AND cf.estatus = 1
            $filtroFis

            UNION ALL

            -- MARÍTIMOS
            SELECT 
                cmo.id               AS id,
                cm.numero_contenedor AS label,
                'MARITIMO'           AS tipo
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.operacion_id = ? AND cm.estatus = 1
            $filtroMar
        ) t
        ORDER BY label ASC
        LIMIT $limit
    ";

        // Orden de parámetros según el SQL
        $params[] = $operacionId;
        if ($term !== '') $params[] = $needle;
        $params[] = $operacionId;
        if ($term !== '') $params[] = $needle;

        $rows = $this->selectAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public function listarTiposEvento(): array
    {
        $sql = "SELECT `id_tipo_evento`,`nombre` FROM `tipos_evento_logistico` WHERE `id_tipo_operacion`=1  AND estatus=1 ORDER BY nombre ASC";
        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }

    public function registrar(array $data,int $idUsuario): int
    {
        // Normaliza: solo uno de los dos contenedores debe ir con valor.
        $idFisico   = !empty($data['contenedor_operacion_id']) ? (int)$data['contenedor_operacion_id'] : null;
        $idMaritimo = !empty($data['cont_maritimo_operacion_id']) ? (int)$data['cont_maritimo_operacion_id'] : null;

        if ($idFisico !== null && $idMaritimo !== null) {
            // Si te gusta dejarlo a nivel modelo:
            // forzar uno a NULL (o podrías lanzar una excepción y validarlo en el controlador)
            $idMaritimo = null;
        }

        $sql = "INSERT INTO eventos_logisticos 
                (operacion_id, contenedor_operacion_id, cont_maritimo_operacion_id, tipo_evento_id, fecha, comentario, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [
            (int)$data['operacion_id'],
            $idFisico,
            $idMaritimo,
            (int)$data['tipo_evento_id'],
            $data['fecha'],                           // 'YYYY-MM-DD'
            $data['comentario'] ?? null,
            $idUsuario ?? null
        ];
        return $this->insertar($sql, $params);
    }

    /**
     * Actualiza un evento logístico existente.
     * Espera keys como en registrar() + id_evento (int, req).
     */
public function actualizar(array $data): bool
{
    $sql = "UPDATE eventos_logisticos
            SET operacion_id = ?, 
                contenedor_operacion_id = ?, 
                cont_maritimo_operacion_id = ?, 
                tipo_evento_id = ?, 
                fecha = ?, 
                comentario = ?
            WHERE id_evento = ? AND estatus = 1";
    $params = [
        (int)$data['operacion_id'],
        !empty($data['contenedor_operacion_id']) ? (int)$data['contenedor_operacion_id'] : null,
        !empty($data['cont_maritimo_operacion_id']) ? (int)$data['cont_maritimo_operacion_id'] : null,
        (int)$data['tipo_evento_id'],
        $data['fecha'],
        $data['comentario'] ?? null,
        (int)$data['id_evento']
    ];
    return $this->save($sql, $params);
}


public function obtenerEvento(int $id)
{
    $sql = "SELECT 
                e.id_evento,
                e.operacion_id,
                e.contenedor_operacion_id,
                e.cont_maritimo_operacion_id,
                e.tipo_evento_id,
                e.fecha,
                e.comentario,
                o.numero_operacion AS operacion_label,
                COALESCE(cf.numero_ferro, cm.numero_contenedor) AS contenedor_label
            FROM eventos_logisticos e
            LEFT JOIN operaciones o ON o.id_operacion = e.operacion_id
            LEFT JOIN contenedores_operacion co ON co.id_contenedor = e.contenedor_operacion_id
            LEFT JOIN contenedores_fisicos  cf ON cf.id_fisico = co.id_fisico
            LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = e.cont_maritimo_operacion_id
            LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE e.id_evento = ? AND e.estatus = 1
            LIMIT 1";
    return $this->select($sql, [$id]);
}

     public function existeEventoFisicoDuplicado(int $contenedorOperacionId, int $tipoEventoId, ?int $excluirId = null): bool
{
    $sql = "SELECT id_evento
            FROM eventos_logisticos
            WHERE estatus = 1
              AND contenedor_operacion_id = ?
              AND tipo_evento_id = ?"
          . ($excluirId ? " AND id_evento <> ?" : "")
          . " LIMIT 1";

    $params = $excluirId
        ? [$contenedorOperacionId, $tipoEventoId, $excluirId]
        : [$contenedorOperacionId, $tipoEventoId];

    return (bool)$this->select($sql, $params);
}

public function existeEventoMaritimoDuplicado(int $contMaritimoOperacionId, int $tipoEventoId, ?int $excluirId = null): bool
{
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
public function desactivar(int $idEvento): bool
{
    $sql = "UPDATE eventos_logisticos
            SET estatus = 0
            WHERE id_evento = ? AND estatus = 1";
    return $this->save($sql, [$idEvento]);
}
public function listarTiposEventoPorTipoOperacion(?int $tipoOperacionId): array
{
    $params = [];
    $sql = "SELECT id_tipo_evento, nombre
            FROM tipos_evento_logistico
            WHERE estatus = 1";

    if (!is_null($tipoOperacionId)) {
        $sql .= " AND id_tipo_operacion = ?";
        $params[] = $tipoOperacionId;
    }

    $sql .= " ORDER BY nombre ASC";
    $rows = $this->selectAll($sql, $params);
    return is_array($rows) ? $rows : [];
}
 public function existeEventoEntregaOperacion(int $operacionId): bool
    {
        if ($operacionId <= 0) return false;

        $in = implode(',', array_map('intval', self::TIPOS_ENTREGA));
        $sql = "SELECT 1
                  FROM eventos_logisticos
                 WHERE operacion_id = ?
                   AND estatus = 1
                   AND tipo_evento_id IN ($in)
                 LIMIT 1";
        $row = $this->select($sql, [$operacionId]);
        return !empty($row);
    }

}
