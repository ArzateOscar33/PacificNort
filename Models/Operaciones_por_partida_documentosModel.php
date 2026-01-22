<?php
class Operaciones_por_partida_documentosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // FACTURA (solo lo mínimo requerido por Documentos)
    // =========================================================

    /**
     * Obtiene header de factura para módulo Documentos.
     * Nota: en tu controller docs llamas $this->model->obtenerFactura($factura_id)
     * Aquí lo dejamos con el mismo nombre para no romper.
     */
    public function obtenerFactura(int $facturaId): ?array
    {
        $sql = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.proveedor,
                    f.bodega_id,
                    b.nombre AS bodega_nombre
                FROM op_partida_facturas f
                LEFT JOIN bodegas b
                    ON b.id_bodega = f.bodega_id
                WHERE f.id_factura = ?
                LIMIT 1";

        $row = $this->select($sql, [$facturaId]);
        return $row ?: null;
    }

    /**
     * Valida existencia de factura activa.
     * (Tu controller docs la usa antes de insertar)
     */
    public function existeFacturaActiva(int $facturaId): bool
    {
        $sql = "SELECT id_factura
                FROM op_partida_facturas
                WHERE id_factura = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$facturaId]);
        return !empty($row);
    }

    // =========================================================
    // DOCUMENTOS: LISTADOS / CATALOGO
    // =========================================================

    /**
     * Listar documentos por factura, con búsqueda opcional y estatus.
     */
    public function listarPorFactura(int $facturaId, string $term = "", int $estatus = 1): array
    {
        $facturaId = (int)$facturaId;
        $estatus   = (int)$estatus;
        $term      = trim($term);

        $where  = " WHERE d.factura_id = ? AND d.estatus = ? ";
        $params = [$facturaId, $estatus];

        if ($term !== "") {
            $where .= " AND (
                d.nombre_archivo LIKE ?
                OR td.nombre LIKE ?
                OR IFNULL(d.mime_type,'') LIKE ?
            ) ";
            $like = '%' . $term . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT
                    d.id_documento,
                    d.factura_id,
                    f.numero_factura,
                    d.tipo_documento_id,
                    td.nombre AS tipo_documento,
                    d.nombre_archivo,
                    d.ruta_archivo,
                    d.mime_type,
                    d.tamano_bytes,
                    d.subido_por,
                    CONCAT(u.nombre,' ',u.apellido) AS subido_por_nombre,
                    d.fecha_subida,
                    d.estatus
                FROM op_partida_documentos d
                INNER JOIN op_partida_facturas f
                    ON f.id_factura = d.factura_id
                INNER JOIN tipos_documento td
                    ON td.id_tipo_documento = d.tipo_documento_id
                LEFT JOIN usuarios u
                    ON u.id_usuario = d.subido_por
                $where
                ORDER BY d.fecha_subida DESC, d.id_documento DESC";

        $rows = $this->selectAll($sql, $params);
        return ($rows === false) ? [] : $rows;
    }

    /**
     * Tipos de documento aplicables a OPP.
     * NOTA: En tu controller tienes endpoint listarTiposDocumentoOPP()
     * En tu model original NO vi este método implementado, así que lo agrego aquí.
     * Ajusta la lógica/columnas si tu tabla tipos_documento tiene otra estructura.
     */
    public function listarTiposDocumentoOPP(): array
    {
        $sql = "SELECT
                    td.id_tipo_documento,
                    td.nombre
                FROM tipos_documento td
                WHERE td.activo = 1
                  AND td.aplica_sobre IN ('operaciones_por_partida','cualquiera')
                ORDER BY td.nombre ASC";

        $rows = $this->selectAll($sql);
        return ($rows === false) ? [] : $rows;
    }

    /**
     * Validar si un tipo de documento aplica para OPP.
     */
    public function existeTipoDocumentoOPP(int $tipoId): bool
    {
        $sql = "SELECT id_tipo_documento
                FROM tipos_documento
                WHERE id_tipo_documento = ?
                  AND activo = 1
                  AND aplica_sobre IN ('operaciones_por_partida','cualquiera')
                LIMIT 1";

        $row = $this->select($sql, [$tipoId]);
        return !empty($row);
    }

    // =========================================================
    // DOCUMENTOS: INSERTS
    // =========================================================

    public function insertarDocumentoPartida(array $d): int
    {
        $sql = "INSERT INTO op_partida_documentos
                (factura_id, tipo_documento_id, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, notas, subido_por, fecha_subida, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";

        $params = [
            (int)($d['factura_id'] ?? 0),
            (int)($d['tipo_documento_id'] ?? 0),
            (string)($d['nombre_archivo'] ?? ''),
            (string)($d['ruta_archivo'] ?? ''),
            (string)($d['mime_type'] ?? ''),
            (int)($d['tamano_bytes'] ?? 0),
            ($d['notas'] ?? null),
            ($d['subido_por'] ?? null),
        ];

        $id = $this->insertar($sql, $params);
        return (int)$id;
    }

    /**
     * Batch insert (útil si en algún punto quieres insertar en bloque).
     */
    public function insertarDocumentosPartidaBatch(
        int $facturaId,
        int $tipoId,
        array $items,
        ?int $userId,
        ?string $notas = null
    ): array {
        $insertados = 0;
        $ids = [];

        foreach ($items as $it) {
            $newId = $this->insertarDocumentoPartida([
                'factura_id'        => $facturaId,
                'tipo_documento_id' => $tipoId,
                'nombre_archivo'    => $it['nombre_archivo'] ?? '',
                'ruta_archivo'      => $it['ruta_archivo'] ?? '',
                'mime_type'         => $it['mime_type'] ?? '',
                'tamano_bytes'      => $it['tamano_bytes'] ?? 0,
                'notas'             => $notas,
                'subido_por'        => $userId
            ]);

            if ($newId > 0) {
                $insertados++;
                $ids[] = $newId;
            }
        }

        return ['insertados' => $insertados, 'ids' => $ids];
    }

    // =========================================================
    // DOCUMENTOS: GET / DELETE
    // =========================================================

    public function getDocumentoPartidaById(int $idDocumento): ?array
    {
        $sql = "SELECT
                    d.id_documento,
                    d.factura_id,
                    d.ruta_archivo,
                    d.nombre_archivo
                FROM op_partida_documentos d
                WHERE d.id_documento = ?
                LIMIT 1";

        $row = $this->select($sql, [$idDocumento]);
        return $row ?: null;
    }

    public function eliminarDocumentoPartida(int $idDocumento): bool
    {
        $sql = "DELETE FROM op_partida_documentos
                WHERE id_documento = ?
                LIMIT 1";
        return (bool)$this->save($sql, [$idDocumento]);
    }

    public function sugerirFacturas(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;
    if ($limit < 1)  $limit = 10;
    if ($limit > 25) $limit = 25;
    if ($term === '') return [];

    $like = '%' . $term . '%';

    $sql = "SELECT
                f.id_factura,
                f.numero_factura,
                f.proveedor,
                b.nombre AS bodega_nombre
            FROM op_partida_facturas f
            INNER JOIN bodegas b ON b.id_bodega = f.bodega_id
            WHERE f.estatus = 1
              AND (
                f.numero_factura LIKE ?
                OR IFNULL(f.proveedor,'') LIKE ?
              )
            ORDER BY f.id_factura DESC
            LIMIT $limit";

    $rows = $this->selectAll($sql, [$like, $like]);
    return ($rows === false) ? [] : $rows;
}

}
