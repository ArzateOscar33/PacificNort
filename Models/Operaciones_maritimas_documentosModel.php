<?php
class Operaciones_maritimas_documentosModel extends Query
{
    /* =========================
     *  BÚSQUEDA DE OPERACIONES
     * ========================= */
    public function buscarOperaciones(string $term): array
    {
        $needle = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "
            SELECT 
                o.id_operacion   AS id,
                o.numero_operacion AS label,
                cl.nombre        AS cliente
            FROM operaciones o
            LEFT JOIN clientes cl ON cl.id_cliente = o.cliente_id
            WHERE LOWER(o.numero_operacion) LIKE ?
              AND o.estatus_id IN (1,5,9)
            ORDER BY o.id_operacion DESC
            LIMIT 20
        ";
        return $this->selectAll($sql, [$needle]) ?: [];
    }

    /* ===============================
     *  LISTADO SOLO POR OPERACIÓN
     * =============================== */
    public function listarDocumentosOperacion(int $operacion_id): array
    {
        $sql = "
            SELECT
                d.id_documento,
                o.numero_operacion,
                cl.nombre                               AS cliente,
                t.nombre                                 AS tipo_nombre,
                t.clave                                  AS tipo_clave,
                d.nombre_archivo,
                d.mime_type, 
                d.ruta_archivo,
                d.fecha_subida,
                COALESCE(CONCAT(u.nombre,' ',u.apellido), u.nombre, u.apellido, CAST(d.subido_por AS CHAR)) AS subido_por
            FROM documentos_operacion d
            JOIN operaciones o  ON o.id_operacion = d.operacion_id
            LEFT JOIN clientes cl ON cl.id_cliente = o.cliente_id
            JOIN tipos_documento t ON t.id_tipo_documento = d.tipo_documento_id
            LEFT JOIN usuarios u ON u.id_usuario = d.subido_por
            WHERE d.operacion_id = ?
            ORDER BY d.fecha_subida DESC, d.id_documento DESC
            LIMIT 500
        ";
        return $this->selectAll($sql, [$operacion_id]) ?: [];
    }
public function getContenedoresMaritimosOperacion(int $operacion_id): array
{
    $sql = "SELECT cm.numero_contenedor
            FROM contenedores_maritimos_operacion cmo
            JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE cmo.operacion_id = ?
            ORDER BY cm.numero_contenedor ASC";
    $rows = $this->selectAll($sql, [$operacion_id]) ?: [];
    return array_map(fn($r)=>$r['numero_contenedor'], $rows);
}

    /* ======================================
     *  TIPOS DE DOCUMENTO PARA OPERACIÓN
     * ====================================== */
    public function tiposDocumentoOperacion(bool $soloActivos = true, ?string $q = null): array
    {
        $where = ["aplica_sobre IN ('operacion','cualquiera')"];
        $params = [];

        if ($soloActivos) $where[] = "activo = 1";

        if ($q !== null && $q !== '') {
            $where[] = "(LOWER(nombre) LIKE ? OR LOWER(clave) LIKE ?)";
            $needle = '%'.mb_strtolower($q, 'UTF-8').'%';
            $params[] = $needle;
            $params[] = $needle;
        }

        $sql = "
            SELECT 
                id_tipo_documento AS id,
                clave,
                nombre,
                aplica_sobre,
                activo
            FROM tipos_documento
            WHERE ".implode(' AND ', $where)."
            ORDER BY nombre ASC
            LIMIT 500
        ";

        return $this->selectAll($sql, $params) ?: [];
    }

    /* ======================================
     *  FALTANTES POR OPERACIÓN
     * ====================================== */
    public function faltantesPorOperacion(int $operacion_id): array
    {
        $sql = "
            SELECT
                t.id_tipo_documento AS id,
                t.nombre,
                t.clave
            FROM tipos_documento t
            LEFT JOIN documentos_operacion d
              ON d.tipo_documento_id = t.id_tipo_documento
             AND d.operacion_id      = ?
            WHERE t.activo = 1
              AND t.aplica_sobre IN ('operacion','cualquiera')
            GROUP BY t.id_tipo_documento, t.nombre, t.clave
            HAVING COUNT(d.id_documento) = 0
            ORDER BY t.nombre ASC
            LIMIT 500
        ";
        return $this->selectAll($sql, [$operacion_id]) ?: [];
    }

    /* =========================
     *  INSERTAR / ELIMINAR
     * ========================= */
    public function insertarDocumentoOperacion(array $d): bool
    {
        $sql = "INSERT INTO documentos_operacion
                (operacion_id, tipo_documento_id, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256, fecha_subida, subido_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        return (bool)$this->insertar($sql, [
            (int)($d['operacion_id'] ?? 0),
            (int)($d['tipo_doc_id'] ?? 0),
            (string)($d['nombre_orig'] ?? ''),
            (string)($d['ruta'] ?? ''),
            (string)($d['mime'] ?? ''),
            (int)($d['size'] ?? 0),
            (string)($d['hash'] ?? ''),
            (int)($d['subido_por'] ?? 0),
        ]);
    }

    public function eliminarDocumento(int $id): bool
    {
        $sql = "DELETE FROM documentos_operacion WHERE id_documento = ? LIMIT 1";
        return (bool)$this->save($sql, [$id]);
    }

    /* =========================
     *  UTILITARIOS
     * ========================= */
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
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function getNumeroOperacion(int $operacion_id): ?string
    {
        $row = $this->select("SELECT numero_operacion FROM operaciones WHERE id_operacion = ? LIMIT 1", [$operacion_id]);
        return $row['numero_operacion'] ?? null;
    }

    public function getClienteInfoOperacion(int $operacion_id): array
    {
        $sql = "SELECT cl.nombre AS cliente_nombre, cl.correo AS cliente_email
                FROM operaciones o
                LEFT JOIN clientes cl ON cl.id_cliente = o.cliente_id
                WHERE o.id_operacion = ? LIMIT 1";
        return $this->select($sql, [$operacion_id]) ?? [];
    }
}
