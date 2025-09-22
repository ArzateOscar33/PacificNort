<?php
class Operaciones_maritimo_ferro_documentosModel extends Query
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
            WHERE LOWER(o.numero_operacion) LIKE ? AND o.estatus_id IN (1,5,9)
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
                d.mime_type,
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

 
public function validarTipoDocumento(int $id_tipo, string $tipoCont): bool {
    // Opcional: validar aplica_sobre vs F/M
    $row = $this->select("SELECT aplica_sobre, activo FROM tipos_documento WHERE id_tipo_documento = ? LIMIT 1", [$id_tipo]);
    if (!$row || (int)$row['activo'] !== 1) return false;
    if ($row['aplica_sobre'] === 'contenedor_fisico'     && $tipoCont !== 'F') return false;
    if ($row['aplica_sobre'] === 'contenedor_maritimo'   && $tipoCont !== 'M') return false;
    return true;
}

public function insertarDocumento(array $d): bool {
    $sql = "INSERT INTO documentos_operacion
           (operacion_id, contenedor_operacion_id, cont_maritimo_operacion_id,
            tipo_documento_id, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256,
            fecha_subida, subido_por)
            VALUES (?,?,?,?,?,?,?,?,?, NOW(), ?)";
    return (bool)$this->insertar($sql, [
        $d['operacion_id'],
        $d['co_id'],
        $d['cmo_id'],
        $d['tipo_doc_id'],
        $d['nombre_orig'],
        $d['ruta'],
        $d['mime'],
        $d['size'],
        $d['hash'],
        $d['subido_por']
    ]);
}
public function tiposDocumentoFiltrados(?array $aplicaSobre, bool $soloActivos = true, ?string $q = null): array
{
    $where = [];
    $params = [];

    if ($aplicaSobre && count($aplicaSobre) > 0) {
        // Construir IN dinámico
        $in = implode(',', array_fill(0, count($aplicaSobre), '?'));
        $where[] = "aplica_sobre IN ($in)";
        foreach ($aplicaSobre as $v) { $params[] = $v; }
    }

    if ($soloActivos) {
        $where[] = "activo = 1";
    }

    if ($q !== null && $q !== '') {
        $where[] = "(LOWER(nombre) LIKE ? OR LOWER(clave) LIKE ?)";
        $needle = '%'.mb_strtolower($q, 'UTF-8').'%';
        $params[] = $needle;
        $params[] = $needle;
    }

    $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
    $sql = "
        SELECT 
            id_tipo_documento      AS id,
            clave,
            nombre,
            aplica_sobre,
            activo
        FROM tipos_documento
        $whereSql
        ORDER BY nombre ASC
        LIMIT 500
    ";

    return $this->selectAll($sql, $params);
}
 public function getNumeroOperacion(int $operacion_id): ?string {
    $row = $this->select("SELECT numero_operacion FROM operaciones WHERE id_operacion = ? LIMIT 1", [$operacion_id]);
    return $row ? $row['numero_operacion'] : null;
}

public function getEtiquetaContenedor(string $tipo, int $contenedor_id): ?string {
    if ($tipo === 'F') {
        $row = $this->select("
            SELECT cf.numero_ferro AS etiqueta
            FROM contenedores_operacion co
            JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
            WHERE co.id_contenedor = ? LIMIT 1
        ", [$contenedor_id]);
    } else { // 'M'
        $row = $this->select("
            SELECT cm.numero_contenedor AS etiqueta
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.id = ? LIMIT 1
        ", [$contenedor_id]);
    }
    return $row ? $row['etiqueta'] : null;
}

public function listarDocumentosMixto(int $operacion_id, ?int $contenedor_id, ?string $tipo): array
{
    $params = [$operacion_id];
    $filtro = '';

    if (!empty($contenedor_id) && $tipo === 'F') {
        $filtro = ' AND d.contenedor_operacion_id = ? ';
        $params[] = $contenedor_id;
    } elseif (!empty($contenedor_id) && $tipo === 'M') {
        $filtro = ' AND d.cont_maritimo_operacion_id = ? ';
        $params[] = $contenedor_id;
    }

    $sql = "
        SELECT
            d.id_documento,
            o.numero_operacion,
            COALESCE(cf.numero_ferro, cm.numero_contenedor) AS contenedor,
            COALESCE(clco.nombre, clop.nombre)              AS cliente,
            t.nombre                                        AS tipo_nombre,
            t.clave                                         AS tipo_clave,
            d.nombre_archivo,
            d.mime_type, 
            d.ruta_archivo,
            d.fecha_subida,
            /* Quién lo subió (intenta nombre completo; si no, cae al id) */
            COALESCE(CONCAT(u.nombre,' ',u.apellido), u.nombre, u.apellido, CAST(d.subido_por AS CHAR)) AS subido_por
        FROM documentos_operacion d
        JOIN tipos_documento t ON t.id_tipo_documento = d.tipo_documento_id
        JOIN operaciones o     ON o.id_operacion      = d.operacion_id
        LEFT JOIN contenedores_operacion co  ON co.id_contenedor          = d.contenedor_operacion_id
        LEFT JOIN contenedores_fisicos cf    ON cf.id_fisico              = co.id_fisico
        LEFT JOIN clientes clco              ON clco.id_cliente           = co.cliente_id
        LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.id          = d.cont_maritimo_operacion_id
        LEFT JOIN contenedores_maritimos cm  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        LEFT JOIN clientes clop              ON clop.id_cliente           = o.cliente_id
        LEFT JOIN usuarios u                 ON u.id_usuario              = d.subido_por
        WHERE d.operacion_id = ?
        $filtro
        ORDER BY d.fecha_subida DESC, d.id_documento DESC
        LIMIT 500
    ";

    return $this->selectAll($sql, $params);
}


public function getDocumentoPorId(int $id): ?array
{
    $sql = "SELECT 
              id_documento,
              nombre_archivo,
              ruta_archivo,
              mime_type,
              tamano_bytes,
              hash_sha256
            FROM documentos_operacion
            WHERE id_documento = ?
            LIMIT 1";
    return $this->select($sql, [$id]);
}
public function eliminarDocumento(int $id): bool
{
    $sql = "DELETE FROM documentos_operacion WHERE id_documento = ? LIMIT 1";
 
    return (bool)$this->save($sql, [$id]); 
}

public function faltantesMixto(int $operacion_id, int $contenedor_id, string $tipo): array
{
    // Mapea el tipo a la condición y a los aplica_sobre válidos
    if ($tipo === 'F') {
        $joinCond = "d.operacion_id = ? AND d.contenedor_operacion_id = ?";
        $params   = [$operacion_id, $contenedor_id];
        $aplicaIn = "('contenedor_fisico','cualquiera')";
    } else { // 'M'
        $joinCond = "d.operacion_id = ? AND d.cont_maritimo_operacion_id = ?";
        $params   = [$operacion_id, $contenedor_id];
        $aplicaIn = "('contenedor_maritimo','cualquiera')";
    }

    $sql = "
        SELECT
            t.id_tipo_documento AS id,
            t.nombre,
            t.clave,
            t.aplica_sobre
        FROM tipos_documento t
        LEFT JOIN documentos_operacion d
          ON d.tipo_documento_id = t.id_tipo_documento
         AND {$joinCond}
        WHERE t.activo = 1
          AND t.aplica_sobre IN {$aplicaIn}
        GROUP BY t.id_tipo_documento, t.nombre, t.clave, t.aplica_sobre
        HAVING COUNT(d.id_documento) = 0
        ORDER BY t.nombre ASC
        LIMIT 500
    ";

    return $this->selectAll($sql, $params);
}
// models/Operaciones_maritimas_documentosModel.php
public function getClienteInfo(int $operacion_id, int $contenedor_id, string $tipo): array
{
    if ($tipo === 'F') {
        // Cliente viene del contenedor físico
        $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                FROM contenedores_operacion co
                LEFT JOIN clientes cl ON cl.id_cliente = co.cliente_id
                WHERE co.id_contenedor = ? LIMIT 1";
        return $this->select($sql, [$contenedor_id]) ?? [];
    } else {
        // Cliente viene de la operación (marítimo)
        $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                FROM operaciones o
                LEFT JOIN clientes cl ON cl.id_cliente = o.cliente_id
                WHERE o.id_operacion = ? LIMIT 1";
        return $this->select($sql, [$operacion_id]) ?? [];
    }
}



}
