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
     * Traer una factura por ID (para modal editar/ver).
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

    //obtener factura para editar
    public function getFacturaByIdEditar(int $idFactura)
    {
        $sql = "SELECT
                f.id_factura,
                f.bodega_id,
                b.nombre AS bodega_nombre,
                f.numero_factura,
                f.proveedor,
                f.revision_pasa,
                f.pallets_inv,
                DATE_FORMAT(f.fecha_recibido, '%Y-%m-%d') AS fecha_recibido,
                f.notas,
                f.estatus
                FROM op_partida_facturas f
                LEFT JOIN bodegas b
                ON b.id_bodega = f.bodega_id
                WHERE f.id_factura = ?
                AND f.estatus = 1
                LIMIT 1";

        return $this->select($sql, [$idFactura]);
    }

    public function actualizarFactura(int $idFactura, array $data): array
    {
        $bodegaId      = isset($data['bodega_id']) ? (int)$data['bodega_id'] : 0;
        $numeroFactura = isset($data['numero_factura']) ? trim((string)$data['numero_factura']) : '';
        $proveedor     = isset($data['proveedor']) ? trim((string)$data['proveedor']) : '';
        $revisionPasa  = !empty($data['revision_pasa']) ? 1 : 0;
        $palletsInv    = isset($data['pallets_inv']) ? (int)$data['pallets_inv'] : 0;
        $fechaRecibido = isset($data['fecha_recibido']) ? trim((string)$data['fecha_recibido']) : null; // YYYY-MM-DD o null
        $notas         = isset($data['notas']) ? trim((string)$data['notas']) : null;
        $actualizadoPor = isset($data['actualizado_por']) && $data['actualizado_por'] !== '' ? (int)$data['actualizado_por'] : null;

        // ===== Validaciones =====
        if ($idFactura <= 0) {
            return ['ok' => false, 'msg' => 'Factura inválida.'];
        }
        if ($bodegaId <= 0) {
            return ['ok' => false, 'msg' => 'Selecciona una bodega válida.'];
        }
        if ($numeroFactura === '') {
            return ['ok' => false, 'msg' => 'El número de factura es obligatorio.'];
        }
        if ($proveedor === '') {
            return ['ok' => false, 'msg' => 'El proveedor es obligatorio.'];
        }
        if ($palletsInv < 0) {
            return ['ok' => false, 'msg' => 'Pallets INV (Factura) debe ser 0 o mayor.'];
        }

        // Validar que exista factura activa
        $exists = $this->select(
            "SELECT id_factura FROM op_partida_facturas WHERE id_factura = ? AND estatus = 1 LIMIT 1",
            [$idFactura]
        );
        if (!$exists) {
            return ['ok' => false, 'msg' => 'La factura no existe o está inactiva.'];
        }

        // Validar bodega activa
        $bodega = $this->select(
            "SELECT id_bodega FROM bodegas WHERE id_bodega = ? AND estatus = 1 LIMIT 1",
            [$bodegaId]
        );
        if (!$bodega) {
            return ['ok' => false, 'msg' => 'La bodega seleccionada no existe o está inactiva.'];
        }

        // Evitar duplicado (si tienes UNIQUE por bodega/numero/proveedor)
        $dup = $this->select(
            "SELECT id_factura
            FROM op_partida_facturas
            WHERE bodega_id = ?
            AND numero_factura = ?
            AND proveedor = ?
            AND id_factura <> ?
            LIMIT 1",
            [$bodegaId, $numeroFactura, $proveedor, $idFactura]
        );
        if ($dup) {
            return ['ok' => false, 'msg' => 'Ya existe otra factura con esa bodega, número y proveedor.'];
        }

        $sql = "UPDATE op_partida_facturas
                SET bodega_id = ?,
                    numero_factura = ?,
                    proveedor = ?,
                    revision_pasa = ?,
                    pallets_inv = ?,
                    fecha_recibido = ?,
                    notas = ?,
                    actualizado_en = NOW()
                WHERE id_factura = ?
                AND estatus = 1";

        $params = [
            $bodegaId,
            $numeroFactura,
            $proveedor,
            $revisionPasa,
            $palletsInv,
            ($fechaRecibido === '' ? null : $fechaRecibido),
            ($notas === '' ? null : $notas),

            $idFactura
        ];

        $ok = $this->save($sql, $params);

        if (!$ok) {
            return ['ok' => false, 'msg' => 'No se pudo actualizar la factura.'];
        }

        return ['ok' => true, 'msg' => 'Factura actualizada correctamente.'];
    }

    /**
     * Baja lógica de factura (no elimina productos).
     * Cambia estatus a 0.
     */
    public function bajaFactura(int $idFactura, ?int $usuarioId = null): array
    {
        if ($idFactura <= 0) {
            return ['ok' => false, 'msg' => 'Factura inválida.'];
        }

        // Validar que exista activa
        $existe = $this->select(
            "SELECT id_factura
            FROM op_partida_facturas
            WHERE id_factura = ?
            AND estatus = 1
            LIMIT 1",
            [$idFactura]
        );

        if (!$existe) {
            return ['ok' => false, 'msg' => 'La factura no existe o ya está dada de baja.'];
        }


        $sql = "UPDATE op_partida_facturas
                SET estatus = 0,
                    actualizado_en = NOW()
                WHERE id_factura = ?
                AND estatus = 1";

        $ok = $this->save($sql, [$idFactura]);

        if (!$ok) {
            return ['ok' => false, 'msg' => 'No se pudo dar de baja la factura.'];
        }

        return ['ok' => true, 'msg' => 'Factura dada de baja correctamente.'];
    }


    //registrar productos
    public function existeFacturaActiva(int $factura_id): bool
    {
        $sql = "SELECT id_factura
                FROM op_partida_facturas
                WHERE id_factura = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$factura_id]);
        return !empty($row);
    }

    public function insertarProductoFactura(array $d): int
    {
        $sql = "INSERT INTO op_partida_productos
                (factura_id, descripcion, upc, marca, expiracion, inner_pack, case_pack, pallets_rcv, cajas, piezas, estatus)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $params = [
            $d["factura_id"],
            $d["descripcion"],
            $d["upc"],
            $d["marca"],
            $d["expiracion"],
            $d["inner_pack"],
            $d["case_pack"],
            $d["pallets_rcv"],
            $d["cajas"],
            $d["piezas"]
        ];

        $res = $this->insertar($sql, $params);
        // Ajusta según tu Query: si insertar() retorna ID, regresa eso; si retorna bool, usa lastInsertId()
        return (int)$res;
    }

    public function getProductoById(int $idProducto, int $facturaId)
    {
        $sql = "SELECT
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
                p.estatus,
                p.creado_en,
                p.actualizado_en
                FROM op_partida_productos p
                WHERE p.id_producto = ?
                AND p.factura_id = ?
                AND p.estatus = 1
                LIMIT 1";
        return $this->select($sql, [$idProducto, $facturaId]);
    }

    public function actualizarProductoFactura(int $idProducto, int $facturaId, array $d): array
    {
        if ($idProducto <= 0 || $facturaId <= 0) {
            return ['ok' => false, 'msg' => 'Producto o factura inválidos.'];
        }

        // Validar factura activa
        if (!$this->existeFacturaActiva($facturaId)) {
            return ['ok' => false, 'msg' => 'La factura no existe o está inactiva.'];
        }

        // Validar que el producto exista y pertenezca a la factura
        $prod = $this->getProductoById($idProducto, $facturaId);
        if (!$prod) {
            return ['ok' => false, 'msg' => 'El producto no existe, está inactivo o no pertenece a la factura.'];
        }

        $upc = trim((string)($d['upc'] ?? ''));
        if ($upc === '') {
            return ['ok' => false, 'msg' => 'El UPC es obligatorio.'];
        }

        $inner = (int)($d['inner_pack'] ?? 0);
        $case  = (int)($d['case_pack'] ?? 0);
        $pal   = (int)($d['pallets_rcv'] ?? 0);
        $caj   = (int)($d['cajas'] ?? 0);
        $pzs   = (int)($d['piezas'] ?? 0);

        if ($inner < 0 || $case < 0 || $pal < 0 || $caj < 0 || $pzs < 0) {
            return ['ok' => false, 'msg' => 'Los campos numéricos no pueden ser negativos.'];
        }

        $sql = "UPDATE op_partida_productos
                SET descripcion   = ?,
                    upc           = ?,
                    marca         = ?,
                    expiracion    = ?,
                    inner_pack    = ?,
                    case_pack     = ?,
                    pallets_rcv   = ?,
                    cajas         = ?,
                    piezas        = ?,
                    actualizado_en = NOW()
                WHERE id_producto = ?
                AND factura_id  = ?
                AND estatus     = 1";

        $params = [
            ($d['descripcion'] ?? null),
            $upc,
            ($d['marca'] ?? null),
            ($d['expiracion'] ?? null),
            $inner,
            $case,
            $pal,
            $caj,
            $pzs,
            $idProducto,
            $facturaId
        ];

        $ok = $this->save($sql, $params);
        if (!$ok) {
            return ['ok' => false, 'msg' => 'No se pudo actualizar el producto.'];
        }

        return ['ok' => true, 'msg' => 'Producto actualizado correctamente.'];
    }


    public function guardarProductosFactura(int $facturaId, array $items): array
    {
        if ($facturaId <= 0) {
            return ['ok' => false, 'msg' => 'Factura inválida.'];
        }
        if (!$this->existeFacturaActiva($facturaId)) {
            return ['ok' => false, 'msg' => 'La factura no existe o está inactiva.'];
        }
        if (!is_array($items) || empty($items)) {
            return ['ok' => false, 'msg' => 'No hay productos para guardar.'];
        }

        $insertados = 0;
        $actualizados = 0;
        $idsInsertados = [];

        foreach ($items as $i => $d) {
            // Normaliza factura_id desde backend (no confíes 100% en el front)
            $d['factura_id'] = $facturaId;

            $idProducto = (int)($d['id_producto'] ?? 0);

            if ($idProducto > 0) {
                $r = $this->actualizarProductoFactura($idProducto, $facturaId, $d);
                if (!$r['ok']) {
                    return ['ok' => false, 'msg' => "Error en producto #" . ($i + 1) . ": " . $r['msg']];
                }
                $actualizados++;
            } else {
                // Validaciones mínimas para insert
                $upc = trim((string)($d['upc'] ?? ''));
                if ($upc === '') {
                    return ['ok' => false, 'msg' => "Error en producto #" . ($i + 1) . ": El UPC es obligatorio."];
                }

                $newId = $this->insertarProductoFactura($d);
                if ($newId <= 0) {
                    return ['ok' => false, 'msg' => "Error en producto #" . ($i + 1) . ": No se pudo insertar."];
                }
                $insertados++;
                $idsInsertados[] = $newId;
            }
        }

        return [
            'ok' => true,
            'msg' => 'Productos guardados correctamente.',
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'ids_insertados' => $idsInsertados
        ];
    }

    public function bajaProductoFactura(int $idProducto, int $facturaId): array
    {
        if ($idProducto <= 0 || $facturaId <= 0) {
            return ['ok' => false, 'msg' => 'Producto o factura inválidos.'];
        }

        // Validar factura activa
        if (!$this->existeFacturaActiva($facturaId)) {
            return ['ok' => false, 'msg' => 'La factura no existe o está inactiva.'];
        }

        // Validar que el producto exista, pertenezca a la factura y esté activo
        $prod = $this->select(
            "SELECT id_producto
            FROM op_partida_productos
            WHERE id_producto = ?
            AND factura_id = ?
            AND estatus = 1
            LIMIT 1",
            [$idProducto, $facturaId]
        );

        if (!$prod) {
            return ['ok' => false, 'msg' => 'El producto no existe, ya está dado de baja o no pertenece a la factura.'];
        }

        $ok = $this->save(
            "UPDATE op_partida_productos
            SET estatus = 0,
                actualizado_en = NOW()
            WHERE id_producto = ?
            AND factura_id = ?
            AND estatus = 1",
            [$idProducto, $facturaId]
        );

        if (!$ok) {
            return ['ok' => false, 'msg' => 'No se pudo dar de baja el producto.'];
        }

        return ['ok' => true, 'msg' => 'Producto dado de baja correctamente.'];
    }
    public function getTotalesProductosFactura(int $facturaId): array
    {
        $sql = "SELECT
                COALESCE(SUM(cajas), 0)       AS total_cajas,
                COALESCE(SUM(piezas), 0)      AS total_piezas,
                COALESCE(SUM(pallets_rcv), 0) AS total_pallets_rcv
                FROM op_partida_productos
                WHERE factura_id = ?
                AND estatus = 1";
        $row = $this->select($sql, [$facturaId]);

        return [
            'total_cajas'       => (int)($row['total_cajas'] ?? 0),
            'total_piezas'      => (int)($row['total_piezas'] ?? 0),
            'total_pallets_rcv' => (int)($row['total_pallets_rcv'] ?? 0),
        ];
    }


    public function listarTransportistas(): array
    {
        $sql = "SELECT 
                    t.id_transportista ,
                    t.nombre,
                    t.tipo
                FROM transportistas t
                WHERE t.estatus = 1
                ORDER BY t.nombre ASC";

        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }

    function listarCiudades(): array
    {
        $sql = "SELECT 
                    c.id_ciudad,
                    c.nombre_ciudad
                FROM ciudades c
                WHERE c.estatus = 1
                ORDER BY c.nombre_ciudad ASC";
        $rows = $this->selectAll($sql);
        return is_array($rows) ? $rows : [];
    }


    //rutas
    /*
public function sugerirFacturas(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;
    if ($limit < 1)  $limit = 10;
    if ($limit > 25) $limit = 25;

    // Si no hay term, no regreses “todo” (evita carga y UX rara)
    if ($term === '') return [];

    $like = '%' . $term . '%';

    // Nota: LIMIT no se puede bindear en MySQL con PDO en muchos setups,
    // por eso se fuerza a int arriba y se inyecta como número seguro.
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
public function listarProductosRutas(int $facturaId, string $term = ''): array
{
    $facturaId = (int)$facturaId;
    $term      = trim($term);

    $where  = " WHERE p.estatus = 1 AND p.factura_id = ? ";
    $params = [$facturaId];

    if ($term !== '') {
        $where .= " AND (
            p.descripcion LIKE ?
            OR IFNULL(p.upc,'') LIKE ?
            OR IFNULL(p.marca,'') LIKE ?
        ) ";
        $like = '%' . $term . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // IMPORTANTE:
    // Ajusta nombres de columnas de envíos según tu BD final:
    // - e.cajas_enviadas
    // - e.estatus
    // - e.producto_id
    // - e.factura_id
    $sql = "SELECT
                p.id_producto,
                p.factura_id,
                p.descripcion,
                p.upc,
                p.marca,
                p.cajas AS cajas_total,
                COALESCE(SUM(CASE WHEN e.estatus = 1 THEN e.cajas_enviadas ELSE 0 END), 0) AS cajas_enviadas,
                (p.cajas - COALESCE(SUM(CASE WHEN e.estatus = 1 THEN e.cajas_enviadas ELSE 0 END), 0)) AS cajas_restantes
            FROM op_partida_productos p
            LEFT JOIN op_partida_envios e
              ON e.factura_id  = p.factura_id
             AND e.producto_id = p.id_producto
            $where
            GROUP BY
                p.id_producto, p.factura_id, p.descripcion, p.upc, p.marca, p.cajas
            ORDER BY p.id_producto DESC";

    $rows = $this->selectAll($sql, $params);
    return ($rows === false) ? [] : $rows;
}

public function listarEnviosProducto(int $facturaId, int $productoId): array
{
    $facturaId  = (int)$facturaId;
    $productoId = (int)$productoId;

    // Ajusta nombres reales:
    // e.destino (o ciudad_destino_id)
    // e.fecha_envio
    // e.caja_ferro (o id_fisico si ya decidiste guardar eso)
    // e.notas
    $sql = "SELECT
                e.id_envio,
                e.factura_id,
                e.producto_id,
                e.destino,
                e.fecha_envio,
                e.caja_ferro,
                e.cajas_enviadas,
                e.notas,
                e.estatus,
                e.creado_en
            FROM op_partida_envios e
            WHERE e.factura_id = ?
              AND e.producto_id = ?
            ORDER BY e.fecha_envio DESC, e.id_envio DESC";

    $rows = $this->selectAll($sql, [$facturaId, $productoId]);
    return ($rows === false) ? [] : $rows;
}
public function resumenEnviosProducto(int $facturaId, int $productoId): array
{
    $facturaId  = (int)$facturaId;
    $productoId = (int)$productoId;

    $sql = "SELECT
                e.destino,
                SUM(CASE WHEN e.estatus = 1 THEN e.cajas_enviadas ELSE 0 END) AS cajas_enviadas
            FROM op_partida_envios e
            WHERE e.factura_id = ?
              AND e.producto_id = ?
            GROUP BY e.destino
            ORDER BY e.destino ASC";

    $rows = $this->selectAll($sql, [$facturaId, $productoId]);
    return ($rows === false) ? [] : $rows;
}
public function getCajasRestantesProducto(int $facturaId, int $productoId): int
{
    $facturaId  = (int)$facturaId;
    $productoId = (int)$productoId;

    $sql = "SELECT
                (p.cajas - COALESCE(SUM(CASE WHEN e.estatus = 1 THEN e.cajas_enviadas ELSE 0 END), 0)) AS restantes
            FROM op_partida_productos p
            LEFT JOIN op_partida_envios e
              ON e.factura_id = p.factura_id
             AND e.producto_id = p.id_producto
            WHERE p.factura_id = ?
              AND p.id_producto = ?
              AND p.estatus = 1
            GROUP BY p.cajas
            LIMIT 1";

    $row = $this->select($sql, [$facturaId, $productoId]);
    return (int)($row['restantes'] ?? 0);
}

// ===== RUTAS: CIUDADES =====
public function listarCiudadesActivas(): array
{
    // AJUSTA: nombre de tabla/campos según tu BD real
    $sql = "SELECT
                c.id_ciudad,
                c.nombre
            FROM ciudades c
            WHERE c.estatus = 1
            ORDER BY c.nombre ASC";

    $rows = $this->selectAll($sql);
    return ($rows === false || empty($rows)) ? [] : $rows;
}


// ===== RUTAS: SUGERIR CAJA / FERRO =====
public function sugerirCajaFerro(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;

    if ($limit < 1) $limit = 10;
    if ($limit > 25) $limit = 25;
    if ($term === '' || mb_strlen($term) < 2) return [];

    $like = '%' . $term . '%';

    // ==========================
    // AJUSTA ESTOS SELECTS A TU BD REAL
    // ==========================

    // (1) CAJAS
    // Ejemplo supuestos: tabla cajas_fisicas (id_caja, folio, estatus)
    $sqlCajas = "SELECT
                    c.id_caja AS id,
                    'CAJA'    AS tipo,
                    c.folio   AS texto
                 FROM cajas_fisicas c
                 WHERE c.estatus = 1
                   AND c.folio LIKE ?
                 ORDER BY c.id_caja DESC
                 LIMIT $limit";

    $cajas = $this->selectAll($sqlCajas, [$like]);
    if ($cajas === false) $cajas = [];

    // (2) FERROS
    // Ejemplo supuestos: tabla ferros (id_ferro, folio, estatus)
    $sqlFerros = "SELECT
                    f.id_ferro AS id,
                    'FERRO'    AS tipo,
                    f.folio    AS texto
                  FROM ferros f
                  WHERE f.estatus = 1
                    AND f.folio LIKE ?
                  ORDER BY f.id_ferro DESC
                  LIMIT $limit";

    $ferros = $this->selectAll($sqlFerros, [$like]);
    if ($ferros === false) $ferros = [];

    // Mezcla (prioriza coincidencias, luego por id)
    $rows = array_merge($cajas, $ferros);

    // Opcional: recortar a limit global
    if (count($rows) > $limit) {
        $rows = array_slice($rows, 0, $limit);
    }

    return $rows;
}
*/
}
