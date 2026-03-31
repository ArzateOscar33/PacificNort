<?php
class Operaciones_por_partidaModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }


    public function listarFacturas(array $filters = []): array
    {
        $page     = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $perPage  = isset($filters['per_page']) ? max(1, (int)$filters['per_page']) : 10;
        $offset   = ($page - 1) * $perPage;

        $bodegaId  = isset($filters['bodega_id']) ? trim((string)$filters['bodega_id']) : '';
        $clienteId = isset($filters['cliente_id']) ? trim((string)$filters['cliente_id']) : '';
        $term      = isset($filters['term']) ? trim((string)$filters['term']) : '';
        $fi        = isset($filters['fi']) ? trim((string)$filters['fi']) : '';
        $ff        = isset($filters['ff']) ? trim((string)$filters['ff']) : '';

        // ===== WHERE dinámico =====
        $where  = " WHERE f.estatus = 1 ";
        $params = [];

        if ($bodegaId !== '' && $bodegaId !== '0') {
            $where .= " AND f.bodega_id = ? ";
            $params[] = (int)$bodegaId;
        }

        if ($clienteId !== '' && $clienteId !== '0') {
            $where .= " AND f.cliente_id = ? ";
            $params[] = (int)$clienteId;
        }

        if ($term !== '') {
            $where .= " AND (
            f.numero_factura LIKE ?
            OR f.proveedor LIKE ?
            OR c.nombre LIKE ?
        ) ";
            $like = '%' . $term . '%';
            $params[] = $like;
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
                 LEFT JOIN clientes c
                    ON c.id_cliente = f.cliente_id
                 $where";

        $rowTotal = $this->select($sqlTotal, $params);
        $total = $rowTotal ? (int)$rowTotal['total'] : 0;

        // ===== Rows =====
        $sqlRows = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.proveedor,
                    f.revision_estatus,
                    f.pallets_inv,
                    f.fecha_recibido,
                    f.notas,
                    f.bodega_id,
                    b.nombre AS bodega_nombre,
                    f.cliente_id,
                    c.nombre AS cliente_nombre,

                    -- cuántos productos tiene la factura
                    (
                        SELECT COUNT(*)
                        FROM op_partida_productos p
                        WHERE p.factura_id = f.id_factura
                          AND p.estatus = 1
                    ) AS productos_count,

                    -- cajas totales de la factura
                    (
                        SELECT COALESCE(SUM(p.cajas), 0)
                        FROM op_partida_productos p
                        WHERE p.factura_id = f.id_factura
                          AND p.estatus = 1
                    ) AS cajas_totales,

                    -- cajas ya enviadas de la factura
                    (
                        SELECT COALESCE(SUM(d.cajas_enviadas), 0)
                        FROM operaciones_partida_envio_detalle d
                        INNER JOIN operaciones_partida_envios e
                            ON e.id_envio = d.envio_id
                        WHERE d.factura_id = f.id_factura
                          AND d.estatus = 1
                          AND e.estatus = 1
                    ) AS cajas_enviadas,

                    -- cajas restantes
                    (
                        (
                            SELECT COALESCE(SUM(p.cajas), 0)
                            FROM op_partida_productos p
                            WHERE p.factura_id = f.id_factura
                              AND p.estatus = 1
                        )
                        -
                        (
                            SELECT COALESCE(SUM(d.cajas_enviadas), 0)
                            FROM operaciones_partida_envio_detalle d
                            INNER JOIN operaciones_partida_envios e
                                ON e.id_envio = d.envio_id
                            WHERE d.factura_id = f.id_factura
                              AND d.estatus = 1
                              AND e.estatus = 1
                        )
                    ) AS cajas_restantes

                FROM op_partida_facturas f
                LEFT JOIN bodegas b
                    ON b.id_bodega = f.bodega_id
                   AND b.estatus = 1
                LEFT JOIN clientes c
                    ON c.id_cliente = f.cliente_id
                   AND c.estatus = 1
                $where
                ORDER BY f.id_factura DESC
                LIMIT $perPage OFFSET $offset";

        $rows = $this->selectAll($sqlRows, $params);
        if ($rows === false) {
            $rows = [];
        }

        // Normalizar negativos por seguridad
        foreach ($rows as &$row) {
            $row['productos_count']  = (int)($row['productos_count'] ?? 0);
            $row['cajas_totales']    = (int)($row['cajas_totales'] ?? 0);
            $row['cajas_enviadas']   = (int)($row['cajas_enviadas'] ?? 0);
            $row['cajas_restantes']  = max(0, (int)($row['cajas_restantes'] ?? 0));
        }
        unset($row);

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
                            p.item,
                            p.upc,
                            p.marca,
                            p.expiracion,
                            p.inner_pack,
                            p.case_pack,
                            p.pallets_rcv,
                            p.cajas,
                            p.piezas,
                            p.observaciones,
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
        $clienteId     = isset($data['cliente_id']) && $data['cliente_id'] !== '' ? (int)$data['cliente_id'] : 0;
        $numeroFactura = isset($data['numero_factura']) ? trim((string)$data['numero_factura']) : '';
        $proveedor     = isset($data['proveedor']) ? trim((string)$data['proveedor']) : '';
        $revisionEstatus = isset($data['revision_estatus']) ? (int)$data['revision_estatus'] : 0;
        $palletsRcv    = isset($data['pallets_inv']) ? (int)$data['pallets_inv'] : 0;
        $fechaRecibido = isset($data['fecha_recibido']) ? trim((string)$data['fecha_recibido']) : null;
        $notas         = isset($data['notas']) ? trim((string)$data['notas']) : null;
        $creadoPor     = isset($data['creado_por']) && $data['creado_por'] !== '' ? (int)$data['creado_por'] : null;

        // ===== Validaciones mínimas =====
        if ($bodegaId <= 0) {
            return ['ok' => false, 'msg' => 'Selecciona una bodega válida.', 'id_factura' => null];
        }

        if ($clienteId <= 0) {
            return ['ok' => false, 'msg' => 'Selecciona un cliente válido.', 'id_factura' => null];
        }

        if ($numeroFactura === '') {
            return ['ok' => false, 'msg' => 'El número de factura es obligatorio.', 'id_factura' => null];
        }

        if ($proveedor === '') {
            return ['ok' => false, 'msg' => 'El proveedor es obligatorio.', 'id_factura' => null];
        }

        if ($palletsRcv < 0) {
            return ['ok' => false, 'msg' => 'Pallets INV (Factura) debe ser 0 o mayor.', 'id_factura' => null];
        }

        // ===== Validar bodega activa =====
        $bodega = $this->select(
            "SELECT id_bodega
         FROM bodegas
         WHERE id_bodega = ?
           AND estatus = 1
         LIMIT 1",
            [$bodegaId]
        );

        if (!$bodega) {
            return ['ok' => false, 'msg' => 'La bodega seleccionada no existe o está inactiva.', 'id_factura' => null];
        }

        // ===== Validar cliente activo =====
        $cliente = $this->select(
            "SELECT id_cliente
         FROM clientes
         WHERE id_cliente = ?
           AND estatus = 1
         LIMIT 1",
            [$clienteId]
        );

        if (!$cliente) {
            return ['ok' => false, 'msg' => 'El cliente seleccionado no existe o está inactivo.', 'id_factura' => null];
        }

        // ===== Evitar duplicado =====
        $dup = $this->select(
            "SELECT id_factura, estatus
         FROM op_partida_facturas
         WHERE bodega_id = ?
           AND cliente_id = ?
           AND numero_factura = ?
           AND proveedor = ?
         LIMIT 1",
            [$bodegaId, $clienteId, $numeroFactura, $proveedor]
        );

        if ($dup) {
            return [
                'ok' => false,
                'msg' => 'Ya existe una factura con esa bodega, cliente, número y proveedor.',
                'id_factura' => (int)$dup['id_factura']
            ];
        }

        // ===== Insert =====
        $sql = "INSERT INTO op_partida_facturas
            (bodega_id, cliente_id, numero_factura, proveedor, revision_estatus, pallets_inv, fecha_recibido, notas, estatus, creado_por)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

        $params = [
            $bodegaId,
            $clienteId,
            $numeroFactura,
            $proveedor,
            $revisionEstatus,
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

    // obtener factura para editar
    public function getFacturaByIdEditar(int $idFactura)
    {
        $sql = "SELECT
                f.id_factura,
                f.bodega_id,
                b.nombre AS bodega_nombre,
                f.cliente_id,
                c.nombre AS cliente_nombre,
                f.numero_factura,
                f.proveedor,
                f.revision_estatus,
                f.pallets_inv,
                DATE_FORMAT(f.fecha_recibido, '%Y-%m-%d') AS fecha_recibido,
                f.notas,
                f.estatus
            FROM op_partida_facturas f
            LEFT JOIN bodegas b
                ON b.id_bodega = f.bodega_id
               AND b.estatus = 1
            LEFT JOIN clientes c
                ON c.id_cliente = f.cliente_id
               AND c.estatus = 1
            WHERE f.id_factura = ?
              AND f.estatus = 1
            LIMIT 1";

        return $this->select($sql, [$idFactura]);
    }

    public function actualizarFactura(int $idFactura, array $data): array
    {
        $bodegaId       = isset($data['bodega_id']) ? (int)$data['bodega_id'] : 0;
        $clienteId      = isset($data['cliente_id']) && $data['cliente_id'] !== '' ? (int)$data['cliente_id'] : 0;
        $numeroFactura  = isset($data['numero_factura']) ? trim((string)$data['numero_factura']) : '';
        $proveedor      = isset($data['proveedor']) ? trim((string)$data['proveedor']) : '';
        $revisionEstatus = isset($data['revision_estatus']) ? (int)$data['revision_estatus'] : 0;
        $palletsInv     = isset($data['pallets_inv']) ? (int)$data['pallets_inv'] : 0;
        $fechaRecibido  = isset($data['fecha_recibido']) ? trim((string)$data['fecha_recibido']) : null; // YYYY-MM-DD o null
        $notas          = isset($data['notas']) ? trim((string)$data['notas']) : null;
        $actualizadoPor = isset($data['actualizado_por']) && $data['actualizado_por'] !== '' ? (int)$data['actualizado_por'] : null;

        // ===== Validaciones =====
        if ($idFactura <= 0) {
            return ['ok' => false, 'msg' => 'Factura inválida.'];
        }

        if ($bodegaId <= 0) {
            return ['ok' => false, 'msg' => 'Selecciona una bodega válida.'];
        }

        if ($clienteId <= 0) {
            return ['ok' => false, 'msg' => 'Selecciona un cliente válido.'];
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
            "SELECT id_factura
         FROM op_partida_facturas
         WHERE id_factura = ?
           AND estatus = 1
         LIMIT 1",
            [$idFactura]
        );

        if (!$exists) {
            return ['ok' => false, 'msg' => 'La factura no existe o está inactiva.'];
        }

        // Validar bodega activa
        $bodega = $this->select(
            "SELECT id_bodega
         FROM bodegas
         WHERE id_bodega = ?
           AND estatus = 1
         LIMIT 1",
            [$bodegaId]
        );

        if (!$bodega) {
            return ['ok' => false, 'msg' => 'La bodega seleccionada no existe o está inactiva.'];
        }

        // Validar cliente activo
        $cliente = $this->select(
            "SELECT id_cliente
         FROM clientes
         WHERE id_cliente = ?
           AND estatus = 1
         LIMIT 1",
            [$clienteId]
        );

        if (!$cliente) {
            return ['ok' => false, 'msg' => 'El cliente seleccionado no existe o está inactivo.'];
        }

        // Evitar duplicado
        $dup = $this->select(
            "SELECT id_factura
         FROM op_partida_facturas
         WHERE bodega_id = ?
           AND cliente_id = ?
           AND numero_factura = ?
           AND proveedor = ?
           AND id_factura <> ?
         LIMIT 1",
            [$bodegaId, $clienteId, $numeroFactura, $proveedor, $idFactura]
        );

        if ($dup) {
            return ['ok' => false, 'msg' => 'Ya existe otra factura con esa bodega, cliente, número y proveedor.'];
        }

        $sql = "UPDATE op_partida_facturas
            SET bodega_id = ?,
                cliente_id = ?,
                numero_factura = ?,
                proveedor = ?,
                revision_estatus = ?,
                pallets_inv = ?,
                fecha_recibido = ?,
                notas = ?,
                actualizado_en = NOW()
            WHERE id_factura = ?
              AND estatus = 1";

        $params = [
            $bodegaId,
            $clienteId,
            $numeroFactura,
            $proveedor,
            $revisionEstatus,
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
        $observaciones = trim((string)($d["observaciones"] ?? ""));
        if ($observaciones === '') {
            $observaciones = null;
        }

        $sql = "INSERT INTO op_partida_productos
            (
                factura_id,
                descripcion,
                item,
                upc,
                marca,
                expiracion,
                inner_pack,
                case_pack,
                pallets_rcv,
                cajas,
                piezas,
                observaciones,
                estatus
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

        $params = [
            (int)$d["factura_id"],
            ($d["descripcion"] ?? null),
            trim((string)($d["item"] ?? "")),
            trim((string)($d["upc"] ?? "")),
            ($d["marca"] ?? null),
            ($d["expiracion"] ?? null),
            (int)($d["inner_pack"] ?? 0),
            (int)($d["case_pack"] ?? 0),
            (int)($d["pallets_rcv"] ?? 0),
            (int)($d["cajas"] ?? 0),
            (int)($d["piezas"] ?? 0),
            $observaciones
        ];

        $res = $this->insertar($sql, $params);
        return (int)$res;
    }

    public function getProductoById(int $idProducto, int $facturaId)
    {
        $sql = "SELECT
            p.id_producto,
            p.factura_id,
            p.descripcion,
            p.item,
            p.upc,
            p.marca,
            p.expiracion,
            p.inner_pack,
            p.case_pack,
            p.pallets_rcv,
            p.cajas,
            p.piezas,
            p.observaciones,
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

        $item = trim((string)($d['item'] ?? ''));
        if ($item === '') {
            return ['ok' => false, 'msg' => 'El item es obligatorio.'];
        }

        $inner = (int)($d['inner_pack'] ?? 0);
        $case  = (int)($d['case_pack'] ?? 0);
        $pal   = (int)($d['pallets_rcv'] ?? 0);
        $caj   = (int)($d['cajas'] ?? 0);
        $pzs   = (int)($d['piezas'] ?? 0);

        if ($inner < 0 || $case < 0 || $pal < 0 || $caj < 0 || $pzs < 0) {
            return ['ok' => false, 'msg' => 'Los campos numéricos no pueden ser negativos.'];
        }

        $observaciones = trim((string)($d['observaciones'] ?? ''));
        if ($observaciones === '') {
            $observaciones = null;
        }

        $sql = "UPDATE op_partida_productos
            SET descripcion    = ?,
                item           = ?,
                upc            = ?,
                marca          = ?,
                expiracion     = ?,
                inner_pack     = ?,
                case_pack      = ?,
                pallets_rcv    = ?,
                cajas          = ?,
                piezas         = ?,
                observaciones  = ?,
                actualizado_en = NOW()
            WHERE id_producto = ?
            AND factura_id    = ?
            AND estatus       = 1";

        $params = [
            ($d['descripcion'] ?? null),
            $item,
            $upc,
            ($d['marca'] ?? null),
            ($d['expiracion'] ?? null),
            $inner,
            $case,
            $pal,
            $caj,
            $pzs,
            $observaciones,
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
            $d['factura_id'] = $facturaId;

            // Normalización mínima del nuevo campo
            $d['observaciones'] = trim((string)($d['observaciones'] ?? ''));

            $idProducto = (int)($d['id_producto'] ?? 0);

            if ($idProducto > 0) {
                $r = $this->actualizarProductoFactura($idProducto, $facturaId, $d);
                if (!$r['ok']) {
                    return ['ok' => false, 'msg' => "Error en producto #" . ($i + 1) . ": " . $r['msg']];
                }
                $actualizados++;
            } else {
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
    public function listarClientes(): array
    {
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                ORDER BY nombre ASC";
        $rows = $this->selectAll($sql);
        return ($rows === false || empty($rows)) ? [] : $rows;
    }

// ============================================================
// FOTOS DE PRODUCTOS
// ============================================================

    /**
     * Obtiene las fotos de un producto (máx 3), ordenadas por posición.
     */
    public function getFotosByProducto(int $productoId): array
    {
        $sql = "SELECT
                id_foto,
                producto_id,
                factura_id,
                orden,
                nombre_archivo,
                ruta_archivo,
                mime_type,
                tamano_bytes,
                creado_en
            FROM op_partida_producto_fotos
            WHERE producto_id = ?
              AND estatus = 1
            ORDER BY orden ASC";

        $rows = $this->selectAll($sql, [$productoId]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Obtiene todas las fotos de todos los productos de una factura.
     * Útil para cargarlas todas en una sola query al abrir el modal.
     */
    public function getFotosByFactura(int $facturaId): array
    {
        $sql = "SELECT
                f.id_foto,
                f.producto_id,
                f.factura_id,
                f.orden,
                f.nombre_archivo,
                f.ruta_archivo,
                f.mime_type,
                f.tamano_bytes,
                f.creado_en
            FROM op_partida_producto_fotos f
            WHERE f.factura_id = ?
              AND f.estatus = 1
            ORDER BY f.producto_id ASC, f.orden ASC";

        $rows = $this->selectAll($sql, [$facturaId]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Inserta o reemplaza una foto en una posición (orden 1, 2 o 3).
     * Si ya existe foto en esa posición para ese producto, la sobreescribe en BD.
     * El archivo físico anterior debe borrarse desde el controlador antes de llamar esto.
     * Retorna el id_foto insertado o 0 si falla.
     */
    public function upsertFotoProducto(array $d): int
    {
        $productoId   = (int)($d['producto_id']    ?? 0);
        $facturaId    = (int)($d['factura_id']      ?? 0);
        $orden        = (int)($d['orden']           ?? 1);
        $nombreArch   = trim((string)($d['nombre_archivo'] ?? ''));
        $rutaArch     = trim((string)($d['ruta_archivo']   ?? ''));
        $mimeType     = trim((string)($d['mime_type']      ?? ''));
        $tamano       = (int)($d['tamano_bytes']    ?? 0);
        $subidoPor    = isset($d['subido_por']) && $d['subido_por'] !== '' ? (int)$d['subido_por'] : null;

        if ($productoId <= 0 || $facturaId <= 0 || $orden < 1 || $orden > 3 || $rutaArch === '') {
            return 0;
        }

        // INSERT ... ON DUPLICATE KEY UPDATE aprovecha el UNIQUE KEY (producto_id, orden)
        $sql = "INSERT INTO op_partida_producto_fotos
                (producto_id, factura_id, orden, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, subido_por, estatus)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                nombre_archivo = VALUES(nombre_archivo),
                ruta_archivo   = VALUES(ruta_archivo),
                mime_type      = VALUES(mime_type),
                tamano_bytes   = VALUES(tamano_bytes),
                subido_por     = VALUES(subido_por),
                estatus        = 1
              ";

        $params = [
            $productoId,
            $facturaId,
            $orden,
            $nombreArch,
            $rutaArch,
            ($mimeType !== '' ? $mimeType : null),
            ($tamano > 0 ? $tamano : null),
            $subidoPor
        ];

        $id = $this->insertar($sql, $params);
        return (int)$id;
    }

    /**
     * Obtiene una foto por su ID (para validar antes de eliminar).
     */
    public function getFotoById(int $idFoto): ?array
    {
        $sql = "SELECT
                id_foto,
                producto_id,
                factura_id,
                orden,
                nombre_archivo,
                ruta_archivo,
                estatus
            FROM op_partida_producto_fotos
            WHERE id_foto = ?
            LIMIT 1";

        $row = $this->select($sql, [$idFoto]);
        return $row ?: null;
    }

    /**
     * Elimina físicamente el registro de la foto en BD (hard delete).
     * El archivo físico se borra desde el controlador.
     */
    public function eliminarFotoProducto(int $idFoto): bool
    {
        if ($idFoto <= 0) return false;

        $ok = $this->save(
            "DELETE FROM op_partida_producto_fotos
         WHERE id_foto = ?",
            [$idFoto]
        );

        return (bool)$ok;
    }

    /**
     * Modifica listarProductos para incluir las fotos de cada producto.
     * Llama al método existente y luego adjunta las fotos por producto_id.
     * 
     * NOTA: Este método REEMPLAZA la llamada a listarProductos en el controlador,
     * o puedes llamar a listarProductos y luego a este para enriquecer el resultado.
     */
    public function listarProductosConFotos(int $facturaId, array $filters = []): array
    {
        // Reutiliza el método existente
        $result = $this->listarProductos($facturaId, $filters);

        if (empty($result['rows'])) {
            return $result;
        }

        // Obtener todas las fotos de la factura en una sola query
        $todasLasFotos = $this->getFotosByFactura($facturaId);

        // Indexar fotos por producto_id para asignación rápida
        $fotosPorProducto = [];
        foreach ($todasLasFotos as $foto) {
            $pid = (int)$foto['producto_id'];
            $fotosPorProducto[$pid][] = $foto;
        }

        // Adjuntar fotos a cada producto
        foreach ($result['rows'] as &$row) {
            $pid = (int)$row['id_producto'];
            $row['fotos'] = $fotosPorProducto[$pid] ?? [];
        }
        unset($row);

        return $result;
    }
}
