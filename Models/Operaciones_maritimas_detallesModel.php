<?php
class Operaciones_maritimas_detallesModel extends Query
{
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
        ORDER BY o.numero_operacion ASC
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

    public function registrar(array $data): int
    {
        $sql = "INSERT INTO eventos_logisticos (operacion_id, contenedor_operacion_id, tipo_evento_id, fecha, comentario, estatus)
                VALUES (?, ?, ?, ?, ?, 1)";
        $params = [
            $data['operacion_id'],
            $data['contenedor_operacion_id'] ?: null,
            $data['tipo_operacion_id'],
            $data['fecha'],
            $data['comentario'] ?: null
        ];
        return $this->insertar($sql, $params);
    }
     
}
