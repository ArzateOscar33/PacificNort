<?php
class Operaciones_maritimo_ferro_documentosModel extends Query
{
    /**
     * Autocomplete de operaciones MF.
     * Ahora todo cuelga de:
     * operaciones -> contenedores_maritimos_operacion -> contenedores_maritimos
     */
    public function buscarOperacionesConContenedores(string $term): array
    {
        $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

        $sql = "
            SELECT
                o.id_operacion AS id,
                CONCAT(
                    o.numero_operacion,
                    ' - ',
                    COALESCE(cl.nombre, 'SIN CLIENTE')
                ) AS label,
                COALESCE(cl.nombre, '') AS cliente,
                COALESCE(conts.contenedores, '') AS contenedores,
                'MF' AS fuente
            FROM operaciones o
            LEFT JOIN clientes cl
                ON cl.id_cliente = o.cliente_id
            LEFT JOIN (
                SELECT
                    cmo.operacion_id,
                    GROUP_CONCAT(
                        cm.numero_contenedor
                        ORDER BY cm.numero_contenedor ASC
                        SEPARATOR ', '
                    ) AS contenedores
                FROM contenedores_maritimos_operacion cmo
                INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                GROUP BY cmo.operacion_id
            ) AS conts
                ON conts.operacion_id = o.id_operacion
            WHERE o.estatus_id IN (1,5,6,7,9,10,11,12,13)
              AND o.tipo_operacion_id = 11
              AND (
                    LOWER(o.numero_operacion) LIKE ?
                 OR LOWER(COALESCE(cl.nombre, '')) LIKE ?
                 OR LOWER(COALESCE(conts.contenedores, '')) LIKE ?
              )
            ORDER BY o.numero_operacion DESC
            LIMIT 20
        ";

        return $this->selectAll($sql, [$needle, $needle, $needle]);
    }

    /**
     * Contenedores marítimos de una operación MF.
     * Regresa el ID de contenedores_maritimos_operacion (cmo.id),
     * que es el que ya usa documentos_operacion.cont_maritimo_operacion_id
     */
    public function contenedoresDeOperacionMF(int $operacion_id): array
    {
        $sql = "
            SELECT
                cmo.id AS id,
                cm.numero_contenedor AS label,
                'M' AS tipo,
                cmo.bultos
            FROM contenedores_maritimos_operacion cmo
            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.operacion_id = ?
            ORDER BY cm.numero_contenedor ASC
        ";

        return $this->selectAll($sql, [$operacion_id]);
    }

    /* =========================================================
     * VALIDACIONES / ALTAS
     * ========================================================= */

    /**
     * Ahora solo se aceptan documentos sobre contenedor marítimo
     * o cualquiera. Ya no usamos contenedor_fisico.
     */
    public function validarTipoDocumento(int $id_tipo, string $tipoCont = 'M'): bool
    {
        $row = $this->select(
            "SELECT aplica_sobre, activo
             FROM tipos_documento
             WHERE id_tipo_documento = ?
             LIMIT 1",
            [$id_tipo]
        );

        if (!$row || (int)$row['activo'] !== 1) {
            return false;
        }

        // El módulo ya no usa F
        if (strtoupper($tipoCont) !== 'M') {
            return false;
        }

        return in_array($row['aplica_sobre'], ['contenedor_maritimo', 'cualquiera'], true);
    }

    /**
     * Insert genérico para MF.
     * Se deja contenedor_operacion_id en NULL
     * y se usa solamente cont_maritimo_operacion_id.
     */
    public function insertarDocumentoMF(array $d): bool
    {
        $sql = "
            INSERT INTO documentos_operacion (
                operacion_id,
                contenedor_operacion_id,
                cont_maritimo_operacion_id,
                tipo_documento_id,
                nombre_archivo,
                ruta_archivo,
                mime_type,
                tamano_bytes,
                hash_sha256,
                subido_por
            ) VALUES (?,?,?,?,?,?,?,?,?,?)
        ";

        return (bool)$this->insertar($sql, [
            $d['operacion_id'],
            null,                  // ya no usamos contenedor_operacion_id
            $d['cmo_id'] ?? null,  // contenedores_maritimos_operacion.id
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
            foreach ($aplicaSobre as $v) {
                $params[] = $v;
            }
        }

        if ($soloActivos) {
            $where[] = "activo = 1";
        }

        if ($q !== null && $q !== '') {
            $where[] = "(LOWER(nombre) LIKE ? OR LOWER(clave) LIKE ?)";
            $needle = '%' . mb_strtolower($q, 'UTF-8') . '%';
            $params[] = $needle;
            $params[] = $needle;
        }

        $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT
                id_tipo_documento AS id,
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

    public function getNumeroOperacion(int $operacion_id): ?string
    {
        $row = $this->select(
            "SELECT numero_operacion
             FROM operaciones
             WHERE id_operacion = ?
             LIMIT 1",
            [$operacion_id]
        );

        return $row ? $row['numero_operacion'] : null;
    }

    /**
     * Etiqueta del contenedor marítimo a partir de cmo.id
     */
    public function getEtiquetaContenedor(string $tipo, int $contenedor_id): ?string
    {
        if (strtoupper($tipo) !== 'M') {
            return null;
        }

        $row = $this->select("
            SELECT cm.numero_contenedor AS etiqueta
            FROM contenedores_maritimos_operacion cmo
            INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.id = ?
            LIMIT 1
        ", [$contenedor_id]);

        return $row ? $row['etiqueta'] : null;
    }

    /**
     * Lista documentos de una operación MF,
     * opcionalmente filtrado por contenedor marítimo (cmo.id).
     */
    public function listarDocumentosMF(int $operacion_id, ?int $contenedor_id, ?string $tipo): array
    {
        $params = [$operacion_id];
        $filtro = '';

        if (!empty($contenedor_id)) {
            $filtro = ' AND d.cont_maritimo_operacion_id = ? ';
            $params[] = $contenedor_id;
        }

        $sql = "
            SELECT
                d.id_documento,
                o.numero_operacion,
                cm.numero_contenedor AS contenedor,
                COALESCE(clop.nombre, '') AS cliente,
                t.nombre AS tipo_nombre,
                t.clave AS tipo_clave,
                d.nombre_archivo,
                d.mime_type,
                d.ruta_archivo,
                d.fecha_subida,
                COALESCE(
                    CONCAT(u.nombre, ' ', u.apellido),
                    u.nombre,
                    u.apellido,
                    CAST(d.subido_por AS CHAR)
                ) AS subido_por
            FROM documentos_operacion d
            INNER JOIN tipos_documento t
                ON t.id_tipo_documento = d.tipo_documento_id
            INNER JOIN operaciones o
                ON o.id_operacion = d.operacion_id
            LEFT JOIN contenedores_maritimos_operacion cmo
                ON cmo.id = d.cont_maritimo_operacion_id
            LEFT JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            LEFT JOIN clientes clop
                ON clop.id_cliente = o.cliente_id
            LEFT JOIN usuarios u
                ON u.id_usuario = d.subido_por
            WHERE d.operacion_id = ?
              $filtro
            ORDER BY d.fecha_subida DESC, d.id_documento DESC
            LIMIT 500
        ";

        return $this->selectAll($sql, $params);
    }

    public function getDocumentoPorId(int $id): ?array
    {
        $sql = "
            SELECT
                id_documento,
                operacion_id,
                tipo_documento_id,
                contenedor_operacion_id,
                cont_maritimo_operacion_id,
                nombre_archivo,
                ruta_archivo,
                mime_type,
                tamano_bytes,
                hash_sha256
            FROM documentos_operacion
            WHERE id_documento = ?
            LIMIT 1
        ";

        return $this->select($sql, [$id]) ?: null;
    }

    public function eliminarDocumento(int $id): bool
    {
        $sql = "DELETE FROM documentos_operacion WHERE id_documento = ? LIMIT 1";
        return (bool)$this->save($sql, [$id]);
    }

    /**
     * Tipos faltantes por contenedor marítimo.
     * Ya no existe rama F.
     */
    public function faltantesMF(int $operacion_id, int $contenedor_id, string $tipo = 'M'): array
    {
        if (strtoupper($tipo) !== 'M') {
            return [];
        }

        $params = [$operacion_id, $contenedor_id];

        $sql = "
            SELECT
                t.id_tipo_documento AS id,
                t.nombre,
                t.clave,
                t.aplica_sobre
            FROM tipos_documento t
            LEFT JOIN documentos_operacion d
                ON d.tipo_documento_id = t.id_tipo_documento
               AND d.operacion_id = ?
               AND d.cont_maritimo_operacion_id = ?
            WHERE t.activo = 1
              AND t.aplica_sobre IN ('contenedor_maritimo', 'cualquiera')
            GROUP BY
                t.id_tipo_documento,
                t.nombre,
                t.clave,
                t.aplica_sobre
            HAVING COUNT(d.id_documento) = 0
            ORDER BY t.nombre ASC
            LIMIT 500
        ";

        return $this->selectAll($sql, $params);
    }

    /**
     * Cliente de la operación MF.
     * Ya no se toma de contenedor físico.
     */
    public function getClienteInfo(int $operacion_id, int $contenedor_id = 0, string $tipo = 'M'): array
    {
        $sql = "
            SELECT
                cl.nombre AS cliente_nombre,
                cl.correo AS cliente_email
            FROM operaciones o
            LEFT JOIN clientes cl
                ON cl.id_cliente = o.cliente_id
            WHERE o.id_operacion = ?
            LIMIT 1
        ";

        return $this->select($sql, [$operacion_id]) ?? [];
    }
}
