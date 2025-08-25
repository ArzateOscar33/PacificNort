<?php
class Operaciones_maritimas_documentosModel extends Query
{
 public function buscarOperacionesConContenedores(string $term): array
    {
        $needle = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "
            SELECT 
                o.id_operacion   AS id,
                o.numero_operacion AS label,
                COUNT(co.id_contenedor) AS contenedores
            FROM operaciones o
            JOIN contenedores_operacion co ON co.operacion_id = o.id_operacion
            WHERE LOWER(o.numero_operacion) LIKE ?
            GROUP BY o.id_operacion, o.numero_operacion
            ORDER BY o.id_operacion DESC
            LIMIT 20
        ";
        return $this->selectAll($sql, [$needle]);
    }

    // Contenedores de una operación (para autocomplete)
    public function contenedoresDeOperacion(int $operacion_id): array
    {
        $sql = "
            SELECT 
                co.id_contenedor             AS id,
                cf.numero_ferro              AS label,
                cl.id_cliente,
                cl.nombre                    AS cliente
            FROM contenedores_operacion co
            LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
            LEFT JOIN clientes cl ON cl.id_cliente = co.cliente_id
            WHERE co.operacion_id = ?
            ORDER BY cf.numero_ferro ASC
        ";
        return $this->selectAll($sql, [$operacion_id]);
    }

    // Listar documentos (por operación y opcional por contenedor)
    public function listarDocumentos(int $operacion_id, ?int $contenedor_id): array
    {
        $params = [$operacion_id];
        $whereCont = "";
        if (!empty($contenedor_id)) {
            $whereCont = " AND d.contenedor_operacion_id = ? ";
            $params[] = $contenedor_id;
        }

        $sql = "
            SELECT 
                d.id_documento,
                o.numero_operacion,
                cf.numero_ferro         AS contenedor,
                cl.nombre               AS cliente,
                d.tipo,
                d.nombre_archivo,
                d.ruta_archivo,
                d.fecha_subida,
                d.subido_por
            FROM documentos_operacion d
            JOIN operaciones o              ON o.id_operacion = d.operacion_id
            LEFT JOIN contenedores_operacion co ON co.id_contenedor = d.contenedor_operacion_id
            LEFT JOIN contenedores_fisicos cf   ON cf.id_fisico = co.id_fisico
            LEFT JOIN clientes cl               ON cl.id_cliente = co.cliente_id
            WHERE d.operacion_id = ?
            {$whereCont}
            ORDER BY d.fecha_subida DESC, d.id_documento DESC
            LIMIT 500
        ";
        return $this->selectAll($sql, $params);
    }

    public function contenedoresDeOperacionMixto(int $operacion_id): array
{
    // Físicos (con cliente del contenedor)
    $sqlFis = "
        SELECT 
            co.id_contenedor      AS id,
            cf.numero_ferro       AS label,
            cl.nombre             AS cliente,
            'F'                   AS tipo
        FROM contenedores_operacion co
        LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
        LEFT JOIN clientes cl            ON cl.id_cliente = co.cliente_id
        WHERE co.operacion_id = ?
    ";

    // Marítimos (cliente tomado de la operación)
    $sqlMar = "
        SELECT
            cmo.id                AS id,
            cm.numero_contenedor  AS label,
            clop.nombre           AS cliente,
            'M'                   AS tipo
        FROM contenedores_maritimos_operacion cmo
        JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        JOIN operaciones o             ON o.id_operacion = cmo.operacion_id
        LEFT JOIN clientes clop        ON clop.id_cliente = o.cliente_id
        WHERE cmo.operacion_id = ?
    ";

    $sql = "$sqlFis UNION ALL $sqlMar ORDER BY tipo, label";
    return $this->selectAll($sql, [$operacion_id, $operacion_id]);
}

public function listarDocumentosMixto(int $operacion_id, ?int $contenedor_id, ?string $tipo): array
{
    $params = [$operacion_id];
    $filtro = "";

    if (!empty($contenedor_id) && $tipo === "F") {
        $filtro = " AND d.contenedor_operacion_id = ? ";
        $params[] = $contenedor_id;
    } elseif (!empty($contenedor_id) && $tipo === "M") {
        $filtro = " AND d.cont_maritimo_operacion_id = ? ";
        $params[] = $contenedor_id;
    }

    $sql = "
        SELECT 
            d.id_documento,
            o.numero_operacion,
            COALESCE(cf.numero_ferro, cm.numero_contenedor) AS contenedor,
            COALESCE(clco.nombre, clop.nombre)              AS cliente,
            d.tipo,
            d.nombre_archivo,
            d.ruta_archivo,
            d.fecha_subida,
            d.subido_por
        FROM documentos_operacion d
        JOIN operaciones o ON o.id_operacion = d.operacion_id
        LEFT JOIN contenedores_operacion co  ON co.id_contenedor = d.contenedor_operacion_id
        LEFT JOIN contenedores_fisicos cf    ON cf.id_fisico    = co.id_fisico
        LEFT JOIN clientes clco              ON clco.id_cliente = co.cliente_id
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id = d.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        LEFT JOIN clientes clop              ON clop.id_cliente = o.cliente_id
        WHERE d.operacion_id = ?
        $filtro
        ORDER BY d.fecha_subida DESC, d.id_documento DESC
        LIMIT 500
    ";
    return $this->selectAll($sql, $params);
}
}
