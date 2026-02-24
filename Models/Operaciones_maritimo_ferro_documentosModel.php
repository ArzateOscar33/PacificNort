<?php
class Operaciones_maritimo_ferro_documentosModel extends Query
{
    /**
     * Autocomplete de operaciones: SOLO MF (operaciones.tipo_operacion_id = 11)
     * Cuenta contenedores F (contenedores_operacion) + M (contenedores_maritimos_operacion).
     */
    public function buscarOperacionesConContenedores(string $term): array
    {
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

        $sql = "
                SELECT 
                    o.id_operacion AS id,
                    CONCAT(o.numero_operacion, ' - ', COALESCE(cl.nombre, 'SIN CLIENTE')) AS label,
                    (COALESCE(cnt_fis.cnt, 0) + COALESCE(cnt_mar.cnt, 0)) AS contenedores,
                    COALESCE(cl.nombre, '') AS cliente,
                    'MF' AS fuente
                FROM operaciones o
                LEFT JOIN clientes cl ON cl.id_cliente = o.cliente_id
                LEFT JOIN (
                    SELECT operacion_id, COUNT(*) AS cnt
                    FROM contenedores_operacion
                    WHERE estatus = 1
                    GROUP BY operacion_id
                ) AS cnt_fis ON cnt_fis.operacion_id = o.id_operacion
                LEFT JOIN (
                    SELECT operacion_id, COUNT(*) AS cnt
                    FROM contenedores_maritimos_operacion
                    GROUP BY operacion_id
                ) AS cnt_mar ON cnt_mar.operacion_id = o.id_operacion
                WHERE o.estatus_id IN (1,5,6,7,9,10,11,12,13)
                AND o.tipo_operacion_id = 11
                AND (
                        LOWER(o.numero_operacion) LIKE ?
                    OR LOWER(COALESCE(cl.nombre,'')) LIKE ?
                )
                ORDER BY o.numero_operacion DESC
                LIMIT 20
            ";

        return $this->selectAll($sql, [$needle, $needle]);
    }

    /**
     * Autocomplete de contenedores por operación MF:
     * - F: contenedores_operacion -> contenedores_fisicos.numero_ferro
     * - M: contenedores_maritimos_operacion -> contenedores_maritimos.numero_contenedor
     */
    public function contenedoresDeOperacionMF(int $operacion_id): array
    {
        $sql = "
            SELECT
                co.id_contenedor AS id,
                cf.numero_ferro  AS label,
                'F'              AS tipo
            FROM contenedores_operacion co
            JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
            WHERE co.operacion_id = ?
              AND co.estatus = 1

            UNION ALL

            SELECT
                cmo.id               AS id,
                cm.numero_contenedor AS label,
                'M'                  AS tipo
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm 
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.operacion_id = ?

            ORDER BY label ASC
        ";

        return $this->selectAll($sql, [$operacion_id, $operacion_id]);
    }

    /* === Validaciones / altas === */

    public function validarTipoDocumento(int $id_tipo, string $tipoCont): bool
    {
        $row = $this->select(
            "SELECT aplica_sobre, activo FROM tipos_documento WHERE id_tipo_documento = ? LIMIT 1",
            [$id_tipo]
        );
        if (!$row || (int)$row['activo'] !== 1) return false;

        if ($row['aplica_sobre'] === 'contenedor_fisico'   && $tipoCont !== 'F') return false;
        if ($row['aplica_sobre'] === 'contenedor_maritimo' && $tipoCont !== 'M') return false;

        return true;
    }

    /**
     * Insert genérico para MF:
     * - operacion_id siempre es operaciones.id_operacion (MF)
     * - co_id (F) = contenedores_operacion.id_contenedor
     * - cmo_id (M) = contenedores_maritimos_operacion.id
     */
    public function insertarDocumentoMF(array $d): bool
    {
        $sql = "INSERT INTO documentos_operacion
           (operacion_id, contenedor_operacion_id, cont_maritimo_operacion_id,
            tipo_documento_id, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256, subido_por)
            VALUES (?,?,?,?,?,?,?,?,?,?)";

        return (bool)$this->insertar($sql, [
            $d['operacion_id'],
            $d['co_id'],   // F: id_contenedor (contenedores_operacion) | M: null
            $d['cmo_id'],  // M: id (contenedores_maritimos_operacion) | F: null
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
            $in = implode(',', array_fill(0, count($aplicaSobre), '?'));
            $where[] = "aplica_sobre IN ($in)";
            foreach ($aplicaSobre as $v) $params[] = $v;
        }

        if ($soloActivos) $where[] = "activo = 1";

        if ($q !== null && $q !== '') {
            $where[] = "(LOWER(nombre) LIKE ? OR LOWER(clave) LIKE ?)";
            $needle = '%' . mb_strtolower($q, 'UTF-8') . '%';
            $params[] = $needle;
            $params[] = $needle;
        }

        $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "
            SELECT id_tipo_documento AS id, clave, nombre, aplica_sobre, activo
            FROM tipos_documento
            $whereSql
            ORDER BY nombre ASC
            LIMIT 500
        ";

        return $this->selectAll($sql, $params);
    }

    public function getNumeroOperacion(int $operacion_id): ?string
    {
        $row = $this->select(
            "SELECT numero_operacion FROM operaciones WHERE id_operacion = ? LIMIT 1",
            [$operacion_id]
        );
        return $row ? $row['numero_operacion'] : null;
    }

    /**
     * Etiqueta de contenedor dentro de MF:
     * - F: contenedores_operacion(id_contenedor) -> contenedores_fisicos.numero_ferro
     * - M: contenedores_maritimos_operacion(id) -> contenedores_maritimos.numero_contenedor
     */
    public function getEtiquetaContenedor(string $tipo, int $contenedor_id): ?string
    {
        if ($tipo === 'F') {
            $row = $this->select("
                SELECT cf.numero_ferro AS etiqueta
                FROM contenedores_operacion co
                JOIN contenedores_fisicos cf ON cf.id_fisico = co.id_fisico
                WHERE co.id_contenedor = ?
                LIMIT 1
            ", [$contenedor_id]);

            return $row ? $row['etiqueta'] : null;
        }

        // 'M'
        $row = $this->select("
            SELECT cm.numero_contenedor AS etiqueta
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.id = ?
            LIMIT 1
        ", [$contenedor_id]);

        return $row ? $row['etiqueta'] : null;
    }

    /**
     * Lista documentos de una operación MF, opcionalmente filtrado por contenedor (F o M).
     */
    public function listarDocumentosMF(int $operacion_id, ?int $contenedor_id, ?string $tipo): array
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

    /**
     * Tipos faltantes por contenedor dentro de MF.
     */
    public function faltantesMF(int $operacion_id, int $contenedor_id, string $tipo): array
    {
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

    /**
     * Cliente:
     * - F: cliente del contenedor_operacion
     * - M: cliente de la operación
     */
    public function getClienteInfo(int $operacion_id, int $contenedor_id, string $tipo): array
    {
        if ($tipo === 'F') {
            $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                    FROM contenedores_operacion co
                    LEFT JOIN clientes cl ON cl.id_cliente = co.cliente_id
                    WHERE co.id_contenedor = ? LIMIT 1";
            return $this->select($sql, [$contenedor_id]) ?? [];
        }

        $sql = "SELECT clop.nombre AS cliente_nombre, clop.correo AS cliente_email
                FROM operaciones o
                LEFT JOIN clientes clop ON clop.id_cliente = o.cliente_id
                WHERE o.id_operacion = ? LIMIT 1";
        return $this->select($sql, [$operacion_id]) ?? [];
    }
}
