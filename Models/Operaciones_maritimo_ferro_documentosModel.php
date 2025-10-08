<?php
class Operaciones_maritimo_ferro_documentosModel extends Query
{
    public function buscarOperacionesConContenedores(string $term): array
    {
        $needle = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "
            SELECT 
                o.id_operacion             AS id,
                o.numero_operacion         AS label,
                COALESCE(cnt_mar.cnt, 0)   AS contenedores,
                'MF'                       AS fuente
            FROM operaciones o
            LEFT JOIN (
                SELECT operacion_id, COUNT(*) AS cnt
                FROM contenedores_maritimos_operacion
                GROUP BY operacion_id
            ) AS cnt_mar ON cnt_mar.operacion_id = o.id_operacion
            WHERE o.estatus_id IN (1,5,9)
              AND o.tipo_operacion_id = 11
              AND LOWER(o.numero_operacion) LIKE ?
            UNION ALL
            SELECT 
                ofe.id_operacion_ferro AS id,
                ofe.numero_operacion   AS label,
                (CASE WHEN ofe.contenedor_fisico_id IS NULL THEN 0 ELSE 1 END
                  + (SELECT COUNT(*) 
                       FROM contenedor_maritimo_ferro cmf
                      WHERE cmf.operacion_ferro_id = ofe.id_operacion_ferro)
                )                       AS contenedores,
                'F'                     AS fuente
            FROM operaciones_ferroviarias ofe
            WHERE ofe.estatus_id IN (1,5,9)
              AND LOWER(ofe.numero_operacion) LIKE ?
            ORDER BY label DESC
            LIMIT 20
        ";
        return $this->selectAll($sql, [$needle, $needle]);
    }

    /* === NUEVOS para el autocomplete de contenedor según fuente === */
    public function contenedoresDeOperacionMF(int $operacion_id): array {
        $sql = "SELECT
                    cmo.id               AS id,
                    cm.numero_contenedor AS label,
                    'M'                  AS tipo
                FROM contenedores_maritimos_operacion cmo
                JOIN contenedores_maritimos cm 
                  ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                WHERE cmo.operacion_id = ?
                ORDER BY cm.numero_contenedor ASC";
        return $this->selectAll($sql, [$operacion_id]);
    }

    public function contenedorDeOperacionFO(int $op_ferro_id): array {
        $sql = "SELECT 
                    cf.id_fisico    AS id,
                    cf.numero_ferro AS label,
                    'F'             AS tipo
                FROM operaciones_ferroviarias ofe
                JOIN contenedores_fisicos cf 
                  ON cf.id_fisico = ofe.contenedor_fisico_id
                WHERE ofe.id_operacion_ferro = ?
                LIMIT 1";
        $row = $this->select($sql, [$op_ferro_id]);
        return $row ? [$row] : [];
    }

    /* === Altas / consultas comunes (LBMF) === */
    public function validarTipoDocumento(int $id_tipo, string $tipoCont): bool {
        $row = $this->select("SELECT aplica_sobre, activo FROM tipos_documento WHERE id_tipo_documento = ? LIMIT 1", [$id_tipo]);
        if (!$row || (int)$row['activo'] !== 1) return false;
        if ($row['aplica_sobre'] === 'contenedor_fisico'   && $tipoCont !== 'F') return false;
        if ($row['aplica_sobre'] === 'contenedor_maritimo' && $tipoCont !== 'M') return false;
        return true;
    }

public function insertarDocumento(array $d): bool {
    $sql = "INSERT INTO documentos_operacion
           (operacion_id, contenedor_operacion_id, cont_maritimo_operacion_id,
            tipo_documento_id, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256, subido_por)
            VALUES (?,?,?,?,?,?,?,?,?,?)";
    return (bool)$this->insertar($sql, [
        $d['operacion_id'],
        $d['co_id'],     // LBMF-F: id_contenedor | FO-F: id_fisico | LBMF-M: null
        $d['cmo_id'],    // LBMF-M: id de contenedores_maritimos_operacion | F: null
        $d['tipo_doc_id'],
        $d['nombre_orig'],
        $d['ruta'],
        $d['mime'],
        $d['size'],
        $d['hash'],
        $d['subido_por']
    ]);
}
public function insertarDocumentoFO(array $d): bool {
    // $d['operacion_id'] = id_operacion_ferro (FO)
    // $d['co_id'] = id_fisico (FO)
    $d['cmo_id'] = null;
    return $this->insertarDocumento($d);
}

public function insertarDocumentoMF(array $d): bool {
    // $d['operacion_id'] = id_operacion (LBMF)
    // Si es F (LBMF), $d['co_id'] = id_contenedor (contenedores_operacion)
    // Si es M (LBMF), $d['cmo_id'] = id de contenedores_maritimos_operacion
    return $this->insertarDocumento($d);
}


    public function tiposDocumentoFiltrados(?array $aplicaSobre, bool $soloActivos = true, ?string $q = null): array
    {
        $where = [];
        $params = [];
        if ($aplicaSobre && count($aplicaSobre) > 0) {
            $in = implode(',', array_fill(0, count($aplicaSobre), '?'));
            $where[] = "aplica_sobre IN ($in)";
            foreach ($aplicaSobre as $v) { $params[] = $v; }
        }
        if ($soloActivos) $where[] = "activo = 1";
        if ($q !== null && $q !== '') {
            $where[] = "(LOWER(nombre) LIKE ? OR LOWER(clave) LIKE ?)";
            $needle = '%'.mb_strtolower($q, 'UTF-8').'%';
            $params[] = $needle; $params[] = $needle;
        }

        $whereSql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';
        $sql = "
            SELECT id_tipo_documento AS id, clave, nombre, aplica_sobre, activo
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
        // 1) Caso clásico (LBMF con contenedores_operacion)
        $row = $this->select("
            SELECT cf.numero_ferro AS etiqueta
            FROM contenedores_operacion co
            JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
            WHERE co.id_contenedor = ? LIMIT 1
        ", [$contenedor_id]);
        if ($row) return $row['etiqueta'];

        // 2) Fallback FO: aquí el id que recibimos es id_fisico
        $row = $this->select("
            SELECT numero_ferro AS etiqueta
            FROM contenedores_fisicos
            WHERE id_fisico = ? LIMIT 1
        ", [$contenedor_id]);
        return $row ? $row['etiqueta'] : null;
    } else { // 'M'
        $row = $this->select("
            SELECT cm.numero_contenedor AS etiqueta
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.id = ? LIMIT 1
        ", [$contenedor_id]);
        return $row ? $row['etiqueta'] : null;
    }
}


    public function listarDocumentosMixto(int $operacion_id, ?int $contenedor_id, ?string $tipo): array
    {
        $params = [$operacion_id];
        $filtro = '';
        if (!empty($contenedor_id) && $tipo === 'F') {
            $filtro = ' AND d.contenedor_operacion_id = ? ';      $params[] = $contenedor_id;
        } elseif (!empty($contenedor_id) && $tipo === 'M') {
            $filtro = ' AND d.cont_maritimo_operacion_id = ? ';   $params[] = $contenedor_id;
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
                  id_documento, operacion_id, tipo_documento_id,
                  contenedor_operacion_id, cont_maritimo_operacion_id,
                  nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256
                FROM documentos_operacion
                WHERE id_documento = ?
                LIMIT 1";
        return $this->select($sql, [$id]) ?: null;
    }

    public function eliminarDocumento(int $id): bool
    {
        $sql = "DELETE FROM documentos_operacion WHERE id_documento = ? LIMIT 1";
        return (bool)$this->save($sql, [$id]); 
    }

    public function faltantesMixto(int $operacion_id, int $contenedor_id, string $tipo): array
    {
        if ($tipo === 'F') {
            $joinCond = "d.operacion_id = ? AND d.contenedor_operacion_id = ?";
            $params   = [$operacion_id, $contenedor_id];
            $aplicaIn = "('contenedor_fisico','cualquiera')";
        } else {
            $joinCond = "d.operacion_id = ? AND d.cont_maritimo_operacion_id = ?";
            $params   = [$operacion_id, $contenedor_id];
            $aplicaIn = "('contenedor_maritimo','cualquiera')";
        }

        $sql = "
            SELECT t.id_tipo_documento AS id, t.nombre, t.clave, t.aplica_sobre
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

    public function getClienteInfo(int $operacion_id, int $contenedor_id, string $tipo): array
    {
        if ($tipo === 'F') {
            $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                    FROM contenedores_operacion co
                    LEFT JOIN clientes cl ON cl.id_cliente = co.cliente_id
                    WHERE co.id_contenedor = ? LIMIT 1";
            return $this->select($sql, [$contenedor_id]) ?? [];
        } else {
            $sql = "SELECT clop.nombre AS cliente_nombre, clop.correo AS cliente_email
                    FROM operaciones o
                    LEFT JOIN clientes clop ON clop.id_cliente = o.cliente_id
                    WHERE o.id_operacion = ? LIMIT 1";
            return $this->select($sql, [$operacion_id]) ?? [];
        }
    }
    public function getNumeroOperacionMixto(int $operacion_id, ?string $fuente = null): ?string {
    // Si sabemos que es FO, probamos primero en operaciones_ferroviarias
    if ($fuente === 'F') {
        $row = $this->select(
            "SELECT numero_operacion FROM operaciones_ferroviarias WHERE id_operacion_ferro = ? LIMIT 1",
            [$operacion_id]
        );
        if ($row) return $row['numero_operacion'];
    }

    // Intentar en operaciones (LBMF)
    $row = $this->select(
        "SELECT numero_operacion FROM operaciones WHERE id_operacion = ? LIMIT 1",
        [$operacion_id]
    );
    if ($row) return $row['numero_operacion'];

    // Fallback: si no nos pasaron fuente o no lo encontró en operaciones, intenta FO
    $row = $this->select(
        "SELECT numero_operacion FROM operaciones_ferroviarias WHERE id_operacion_ferro = ? LIMIT 1",
        [$operacion_id]
    );
    return $row ? $row['numero_operacion'] : null;
}
public function listarDocumentosFerro(int $op_ferro_id, ?int $contenedor_id, ?string $tipo): array
{
    $params = [$op_ferro_id];
    $filtro = '';

    // En FO guardamos el id_fisico en documentos_operacion.contenedor_operacion_id
    if (!empty($contenedor_id)) {
        $filtro = ' AND d.contenedor_operacion_id = ? ';
        $params[] = $contenedor_id;
    }

    $sql = "
        SELECT
            d.id_documento,
            ofe.numero_operacion,
            cf.numero_ferro                              AS contenedor,
            cl.nombre                                    AS cliente,
            t.nombre                                     AS tipo_nombre,
            t.clave                                      AS tipo_clave,
            d.nombre_archivo,
            d.mime_type,
            d.ruta_archivo,
            d.fecha_subida,
            COALESCE(CONCAT(u.nombre,' ',u.apellido), u.nombre, u.apellido, CAST(d.subido_por AS CHAR)) AS subido_por
        FROM documentos_operacion d
        JOIN tipos_documento t      ON t.id_tipo_documento = d.tipo_documento_id
        JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = d.operacion_id
        LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = d.contenedor_operacion_id
        LEFT JOIN clientes cl             ON cl.id_cliente = ofe.cliente_id
        LEFT JOIN usuarios u              ON u.id_usuario = d.subido_por
        WHERE d.operacion_id = ?
        $filtro
        ORDER BY d.fecha_subida DESC, d.id_documento DESC
        LIMIT 500
    ";
    return $this->selectAll($sql, $params);
}
public function faltantesFerro(int $op_ferro_id, int $contenedor_fisico_id, string $tipo): array
{
    // Para seguridad, si mandan 'M' no aplica en FO
    if ($tipo !== 'F') return [];

    $sql = "
        SELECT t.id_tipo_documento AS id, t.nombre, t.clave, t.aplica_sobre
        FROM tipos_documento t
        LEFT JOIN documentos_operacion d
          ON d.tipo_documento_id = t.id_tipo_documento
         AND d.operacion_id = ?
         AND d.contenedor_operacion_id = ?
        WHERE t.activo = 1
          AND t.aplica_sobre IN ('contenedor_fisico','cualquiera')
        GROUP BY t.id_tipo_documento, t.nombre, t.clave, t.aplica_sobre
        HAVING COUNT(d.id_documento) = 0
        ORDER BY t.nombre ASC
        LIMIT 500
    ";
    return $this->selectAll($sql, [$op_ferro_id, $contenedor_fisico_id]);
}
public function getClienteInfoMixto(int $operacion_id, int $contenedor_id, string $tipo, ?string $fuente = null): array
{
    if ($fuente === 'F') {
        // En FO, cliente viene directamente de la operación ferroviaria
        $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                FROM operaciones_ferroviarias ofe
                LEFT JOIN clientes cl ON cl.id_cliente = ofe.cliente_id
                WHERE ofe.id_operacion_ferro = ? LIMIT 1";
        return $this->select($sql, [$operacion_id]) ?? [];
    }

    // Lógica Mixto original
    if ($tipo === 'F') {
        $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                FROM contenedores_operacion co
                LEFT JOIN clientes cl ON cl.id_cliente = co.cliente_id
                WHERE co.id_contenedor = ? LIMIT 1";
        return $this->select($sql, [$contenedor_id]) ?? [];
    } else {
        $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                FROM operaciones o
                LEFT JOIN clientes cl ON cl.id_cliente = o.cliente_id
                WHERE o.id_operacion = ? LIMIT 1";
        return $this->select($sql, [$operacion_id]) ?? [];
    }
}

}
