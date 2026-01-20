<?php
class Operaciones_por_partidaModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lista facturas con filtros y paginación.
     *
     * @param array $filters [
     *   'bodega_id' => (int|string) 0|''|id,
     *   'term'      => (string) búsqueda por numero_factura o proveedor,
     *   'fi'        => (string) YYYY-MM-DD fecha inicio,
     *   'ff'        => (string) YYYY-MM-DD fecha fin,
     *   'page'      => (int) 1..n,
     *   'per_page'  => (int) 10/25/50/100
     * ]
     *
     * @return array [
     *   'rows' => [...],
     *   'total' => (int)
     * ]
     */
    public function listarFacturas(array $filters = []): array
    {
        $page     = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $perPage  = isset($filters['per_page']) ? max(1, (int)$filters['per_page']) : 10;
        $offset   = ($page - 1) * $perPage;

        $bodegaId = isset($filters['bodega_id']) ? trim((string)$filters['bodega_id']) : '';
        $term     = isset($filters['term']) ? trim((string)$filters['term']) : '';
        $fi       = isset($filters['fi']) ? trim((string)$filters['fi']) : '';
        $ff       = isset($filters['ff']) ? trim((string)$filters['ff']) : '';

        // ===== WHERE dinámico =====
        $where = " WHERE f.estatus = 1 ";
        $params = [];

        if ($bodegaId !== '' && $bodegaId !== '0') {
            $where .= " AND f.bodega_id = ? ";
            $params[] = (int)$bodegaId;
        }

        if ($term !== '') {
            $where .= " AND (f.numero_factura LIKE ? OR f.proveedor LIKE ?) ";
            $like = '%' . $term . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($fi !== '') {
            $where .= " AND f.fecha_recibido >= ? ";
            $params[] = $fi;
        }

        if ($ff !== '') {
            $where .= " AND f.fecha_recibido <= ? ";
            $params[] = $ff;
        }

        // ===== Total =====
        $sqlTotal = "SELECT COUNT(*) AS total
                     FROM op_partida_facturas f
                     $where";
        $rowTotal = $this->select($sqlTotal, $params);
        $total = $rowTotal ? (int)$rowTotal['total'] : 0;

        // ===== Rows =====
        // Ajusta "b.bodega" si tu columna de nombre real es distinta (p.ej. b.nombre)
       $sqlRows = "SELECT
    f.id_factura,
    f.numero_factura,
    f.proveedor,
    f.revision_pasa,
    f.pallets_inv,
    f.fecha_recibido,
    f.notas,
    f.bodega_id,
    b.nombre AS bodega_nombre,
    (
      SELECT COUNT(*)
      FROM op_partida_productos p
      WHERE p.factura_id = f.id_factura
        AND p.estatus = 1
    ) AS productos_count
  FROM op_partida_facturas f
  LEFT JOIN bodegas b
    ON b.id_bodega = f.bodega_id
   AND b.estatus = 1
  $where
  ORDER BY f.id_factura DESC
  LIMIT $perPage OFFSET $offset";



        $rows = $this->selectAll($sqlRows, $params);
        if ($rows === false) $rows = [];

        return [
            'rows'  => $rows,
            'total' => $total
        ];
    }

    /**
     * (Opcional) Traer una factura por ID (para modal editar/ver).
     */
    public function getFacturaById(int $idFactura)
    {
        $sql = "SELECT
          f.*,
          b.nombre AS bodega_nombre
        FROM op_partida_facturas f
        LEFT JOIN bodegas b
          ON b.id_bodega = f.bodega_id
        WHERE f.id_factura = ?
          AND f.estatus = 1
        LIMIT 1";
        return $this->select($sql, [$idFactura]);
    }


    public function listarProductos(int $facturaId, array $filters = []): array
{
    $page    = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
    $perPage = isset($filters['per_page']) ? max(1, (int)$filters['per_page']) : 50; // modal suele usar 50
    $offset  = ($page - 1) * $perPage;

    $term = isset($filters['term']) ? trim((string)$filters['term']) : '';

    // ===== WHERE dinámico =====
    $where  = " WHERE p.estatus = 1 AND p.factura_id = ? ";
    $params = [$facturaId];

    if ($term !== '') {
        $where .= " AND (
            p.descripcion LIKE ? OR
            p.upc LIKE ? OR
            p.marca LIKE ?
        ) ";
        $like = '%' . $term . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // ===== Total registros =====
    $sqlTotal = "SELECT COUNT(*) AS total
                 FROM op_partida_productos p
                 $where";
    $rowTotal = $this->select($sqlTotal, $params);
    $total    = $rowTotal ? (int)$rowTotal['total'] : 0;

    // ===== Totales para badges del modal =====
    $sqlSums = "SELECT
                    COALESCE(SUM(p.cajas), 0)       AS total_cajas,
                    COALESCE(SUM(p.piezas), 0)      AS total_piezas,
                    COALESCE(SUM(p.pallets_rcv), 0) AS total_pallets_rcv
                FROM op_partida_productos p
                $where";
    $rowSums = $this->select($sqlSums, $params);

$totals = [
  'total_cajas'       => (int)($rowSums['total_cajas'] ?? 0),
  'total_piezas'      => (int)($rowSums['total_piezas'] ?? 0),
  'total_pallets_rcv' => (int)($rowSums['total_pallets_rcv'] ?? 0),
];


    // ===== Rows =====
    $sqlRows = "SELECT
                    p.id_producto,
                    p.factura_id,
                    p.descripcion,
                    p.upc,
                    p.marca,
                    p.expiracion,
                    p.inner_pack,
                    p.case_pack,
                    p.pallets_rcv,
                    p.cajas,
                    p.piezas,
                    p.creado_en,
                    p.actualizado_en
                FROM op_partida_productos p
                $where
                ORDER BY p.id_producto DESC
                LIMIT $perPage OFFSET $offset";

    $rows = $this->selectAll($sqlRows, $params);
    if ($rows === false) $rows = [];

    return [
        'rows'   => $rows,
        'total'  => $total,
        'totals' => $totals
    ];
}


/**
 * Lista bodegas activas (para selects).
 * Devuelve id y nombre.
 */
public function listarBodegasActivas(): array
{
    $sql = "SELECT id_bodega, nombre
            FROM bodegas
            WHERE estatus = 1
            ORDER BY nombre ASC";
    $rows = $this->selectAll($sql);
    return ($rows === false || empty($rows)) ? [] : $rows;
}

 /**
 * Registra encabezado de factura en op_partida_facturas.
 *
 * Reglas DB:
 * - bodega_id -> FK bodegas.id_bodega
 * - UNIQUE (bodega_id, numero_factura, proveedor)
 *
 * @return array [
 *   'ok' => bool,
 *   'msg' => string,
 *   'id_factura' => int|null
 * ]
 */
public function registrarFactura(array $data): array
{
    $bodegaId      = isset($data['bodega_id']) ? (int)$data['bodega_id'] : 0;
    $numeroFactura = isset($data['numero_factura']) ? trim((string)$data['numero_factura']) : '';
    $proveedor     = isset($data['proveedor']) ? trim((string)$data['proveedor']) : '';
    $revisionPasa  = !empty($data['revision_pasa']) ? 1 : 0;
    $palletsRcv    = isset($data['pallets_inv']) ? (int)$data['pallets_inv'] : 0;
    $fechaRecibido = isset($data['fecha_recibido']) ? trim((string)$data['fecha_recibido']) : null; // YYYY-MM-DD o null
    $notas         = isset($data['notas']) ? trim((string)$data['notas']) : null;
    $creadoPor     = isset($data['creado_por']) && $data['creado_por'] !== '' ? (int)$data['creado_por'] : null;

    // ===== Validaciones mínimas =====
    if ($bodegaId <= 0) {
        return ['ok' => false, 'msg' => 'Selecciona una bodega válida.', 'id_factura' => null];
    }
    if ($numeroFactura === '') {
        return ['ok' => false, 'msg' => 'El número de factura es obligatorio.', 'id_factura' => null];
    }
    if ($proveedor === '') {
        return ['ok' => false, 'msg' => 'El proveedor es obligatorio.', 'id_factura' => null];
    }

    // (Opcional recomendado) Validar bodega activa
    $bodega = $this->select(
        "SELECT id_bodega FROM bodegas WHERE id_bodega = ? AND estatus = 1 LIMIT 1",
        [$bodegaId]
    );
    if (!$bodega) {
        return ['ok' => false, 'msg' => 'La bodega seleccionada no existe o está inactiva.', 'id_factura' => null];
    }

    // ===== Evitar duplicado por UNIQUE (bodega_id, numero_factura, proveedor) =====
    $dup = $this->select(
        "SELECT id_factura, estatus
         FROM op_partida_facturas
         WHERE bodega_id = ?
           AND numero_factura = ?
           AND proveedor = ?
         LIMIT 1",
        [$bodegaId, $numeroFactura, $proveedor]
    );

    if ($dup) {
        // Si quieres permitir “reactivar” cuando estatus=0, aquí es donde se decide.
        return [
            'ok' => false,
            'msg' => 'Ya existe una factura con esa bodega, número y proveedor.',
            'id_factura' => (int)$dup['id_factura']
        ];
    }

    // ===== Insert =====
    $sql = "INSERT INTO op_partida_facturas
              (bodega_id, numero_factura, proveedor, revision_pasa, pallets_inv, fecha_recibido, notas, estatus, creado_por)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, 1, ?)";

    $params = [
        $bodegaId,
        $numeroFactura,
        $proveedor,
        $revisionPasa,
        $palletsRcv,
        ($fechaRecibido === '' ? null : $fechaRecibido),
        ($notas === '' ? null : $notas),
        $creadoPor
    ];

    $id = $this->insertar($sql, $params);

    if (!$id) {
        return ['ok' => false, 'msg' => 'No se pudo registrar la factura.', 'id_factura' => null];
    }

    return ['ok' => true, 'msg' => 'Factura registrada correctamente.', 'id_factura' => (int)$id];
}


}
