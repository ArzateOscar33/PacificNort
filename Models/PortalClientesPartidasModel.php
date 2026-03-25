<?php

class PortalClientesPartidasModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // DATOS BASE DE SESIÓN
    // =========================================================
    public function getUsuarioById(int $idUsuario): array
    {
        $sql = "SELECT id_usuario, nombre, correo, cliente_id
                FROM usuarios
                WHERE id_usuario = ?
                LIMIT 1";

        return $this->select($sql, [$idUsuario]) ?: [];
    }

    public function getNombreCliente(): string
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
        if ($clienteId <= 0) return '';

        $sql = "SELECT nombre
                FROM clientes
                WHERE id_cliente = ?
                LIMIT 1";

        $row = $this->select($sql, [$clienteId]);
        return $row['nombre'] ?? '';
    }

    public function getNombreUsuario(): string
    {
        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        if ($usuarioId <= 0) return '';

        $sql = "SELECT nombre
                FROM usuarios
                WHERE id_usuario = ?
                LIMIT 1";

        $row = $this->select($sql, [$usuarioId]);
        return $row['nombre'] ?? '';
    }

    // =========================================================
    // CATÁLOGOS PARA FILTROS
    // =========================================================
    public function getCiudadesActivas(): array
    {
        $sql = "SELECT id_ciudad, nombre_ciudad
                FROM ciudades
                WHERE estatus = 1
                ORDER BY nombre_ciudad ASC";

        return $this->selectAll($sql) ?: [];
    }

    public function getTransportistasActivos(): array
    {
        $sql = "SELECT id_transportista, nombre
                FROM transportistas
                WHERE estatus = 1
                ORDER BY nombre ASC";

        return $this->selectAll($sql) ?: [];
    }
    //    // =========================================================
    //HELPER
    //    // =========================================================
    private function normalizarRutaPublica(?string $ruta): string
    {
        $ruta = trim((string)$ruta);
        if ($ruta === '') return '';

        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }

        return BASE_URL . ltrim($ruta, '/');
    }
    // =========================================================
    // KPI DEL MÓDULO OPERACIONES POR PARTIDA (PORTAL)
    // =========================================================
    public function kpisPortalClientePartida(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [
                'facturas'   => 0,
                'ferros'     => 0,
                'productos'  => 0,
                'cajas'      => 0,
            ];
        }

        $sql = "SELECT
                    /* Facturas activas del cliente */
                    (
                        SELECT COUNT(*)
                        FROM op_partida_facturas f
                        WHERE f.cliente_id = ?
                          AND f.estatus = 1
                    ) AS facturas,

                    /* Ferros/cajas distintos vinculados a sus facturas */
                    (
                        SELECT COUNT(DISTINCT e.contenedor_fisico_id)
                        FROM operaciones_partida_envio_detalle d
                        INNER JOIN operaciones_partida_envios e
                            ON e.id_envio = d.envio_id
                           AND e.estatus = 1
                        INNER JOIN op_partida_facturas f
                            ON f.id_factura = d.factura_id
                           AND f.estatus = 1
                        WHERE f.cliente_id = ?
                          AND d.estatus = 1
                    ) AS ferros,

                    /* Productos registrados en facturas del cliente */
                    (
                        SELECT COUNT(*)
                        FROM op_partida_productos p
                        INNER JOIN op_partida_facturas f
                            ON f.id_factura = p.factura_id
                           AND f.estatus = 1
                        WHERE f.cliente_id = ?
                    ) AS productos,

                    /* Cajas enviadas del detalle */
                    (
                        SELECT COALESCE(SUM(d.cajas_enviadas), 0)
                        FROM operaciones_partida_envio_detalle d
                        INNER JOIN op_partida_facturas f
                            ON f.id_factura = d.factura_id
                           AND f.estatus = 1
                        WHERE f.cliente_id = ?
                          AND d.estatus = 1
                    ) AS cajas";

        $row = $this->select($sql, [$clienteId, $clienteId, $clienteId, $clienteId]);

        return [
            'facturas'  => (int)($row['facturas'] ?? 0),
            'ferros'    => (int)($row['ferros'] ?? 0),
            'productos' => (int)($row['productos'] ?? 0),
            'cajas'     => (int)($row['cajas'] ?? 0),
        ];
    }

    // =========================================================
    // LISTADO PRINCIPAL
    // Una fila = Factura + Envío
    // Si la factura no tiene envío, igualmente aparece.
    // =========================================================
    public function listarOperacionesPartidaPortal(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $page    = max(1, (int)$page);
        $perPage = (int)$perPage;
        if ($perPage <= 0) $perPage = 15;

        $isAll  = ($perPage >= 10000000);
        $offset = ($page - 1) * $perPage;

        $clienteId = (int)($filters['cliente_id'] ?? ($_SESSION['cliente_id'] ?? 0));
        if ($clienteId <= 0) {
            return [
                'rows'     => [],
                'total'    => 0,
                'page'     => $page,
                'per_page' => $perPage,
            ];
        }

        $buscar           = trim((string)($filters['buscar'] ?? ''));
        $estatusEnvio     = trim((string)($filters['estatus_envio'] ?? ''));
        $destinoId        = (int)($filters['destino_id'] ?? 0);
        $fechaInicio      = trim((string)($filters['fecha_inicio'] ?? ''));
        $fechaFin         = trim((string)($filters['fecha_fin'] ?? ''));
        $transportistaId  = (int)($filters['transportista_id'] ?? 0);

        $where  = ["f.estatus = 1", "f.cliente_id = ?"];
        $params = [$clienteId];

        if ($buscar !== '') {
            $where[] = "(
                f.numero_factura LIKE ?
                OR f.proveedor LIKE ?
                OR cf.numero_ferro LIKE ?
                OR t.nombre LIKE ?
                OR c.nombre_ciudad LIKE ?
            )";
            $like = '%' . $buscar . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        if ($estatusEnvio !== '') {
            $where[]  = "e.estatus_envio = ?";
            $params[] = $estatusEnvio;
        }

        if ($destinoId > 0) {
            $where[]  = "e.destino_ciudad_id = ?";
            $params[] = $destinoId;
        }

        if ($transportistaId > 0) {
            $where[]  = "e.transportista_id = ?";
            $params[] = $transportistaId;
        }

        if ($fechaInicio !== '') {
            $where[]  = "e.fecha_envio >= ?";
            $params[] = $fechaInicio;
        }

        if ($fechaFin !== '') {
            $where[]  = "e.fecha_envio <= ?";
            $params[] = $fechaFin;
        }

        $whereSql = implode(' AND ', $where);

        // -----------------------------------------------------
        // COUNT real del grid:
        // cuenta combinaciones factura + envío
        // si no existe envío, cuenta factura + 0
        // -----------------------------------------------------
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM (
                        SELECT
                            f.id_factura,
                            COALESCE(e.id_envio, 0) AS envio_key
                        FROM op_partida_facturas f
                        LEFT JOIN operaciones_partida_envio_detalle d
                               ON d.factura_id = f.id_factura
                              AND d.estatus = 1
                        LEFT JOIN operaciones_partida_envios e
                               ON e.id_envio = d.envio_id
                              AND e.estatus = 1
                        LEFT JOIN contenedores_fisicos cf
                               ON cf.id_fisico = e.contenedor_fisico_id
                        LEFT JOIN transportistas t
                               ON t.id_transportista = e.transportista_id
                        LEFT JOIN ciudades c
                               ON c.id_ciudad = e.destino_ciudad_id
                        WHERE {$whereSql}
                        GROUP BY f.id_factura, COALESCE(e.id_envio, 0)
                     ) q";

        $rowTotal = $this->select($sqlCount, $params);
        $total    = (int)($rowTotal['total'] ?? 0);

        // -----------------------------------------------------
        // LISTADO
        // -----------------------------------------------------
        $sql = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.pallets_inv,
                    f.proveedor,
                    f.fecha_recibido,
                    f.notas AS notas_factura,
                    f.revision_estatus,

                    e.id_envio,
                    e.fecha_envio,
                    e.estatus_envio,
                    e.notas AS notas_envio,
                    e.candado,

                    cf.id_fisico AS contenedor_fisico_id,
                    cf.numero_ferro,

                    t.id_transportista,
                    t.nombre AS transportista_nombre,

                    c.id_ciudad,
                    c.nombre_ciudad AS destino_nombre,

                    /* total productos de la factura */
                    (
                        SELECT COUNT(*)
                        FROM op_partida_productos p0
                        WHERE p0.factura_id = f.id_factura
                    ) AS total_productos_factura,

                    /* total fotos de mercancía de la factura */
                    (
                        SELECT COUNT(*)
                        FROM op_partida_producto_fotos pf0
                        WHERE pf0.factura_id = f.id_factura
                    ) AS total_fotos_mercancia,

                    /* total imágenes del envío */
                    (
                        SELECT COUNT(*)
                        FROM operaciones_partida_envio_imagenes ei0
                        WHERE ei0.envio_id = e.id_envio
                    ) AS total_imagenes_envio,

                    /* productos enviados en ese envío para esa factura */
                    COUNT(DISTINCT d.producto_id) AS productos_enviados,

                    /* cajas enviadas en ese envío para esa factura */
                    COALESCE(SUM(d.cajas_enviadas), 0) AS cajas_enviadas,

                    /* resumen de productos enviados */
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            p.descripcion,
                            ' (', d.cajas_enviadas, ' cajas)'
                        )
                        ORDER BY p.descripcion ASC
                        SEPARATOR ' | '
                    ) AS productos_enviados_resumen,

                    /* notas del detalle */
                    GROUP_CONCAT(
                        DISTINCT NULLIF(TRIM(d.notas_detalle), '')
                        SEPARATOR ' | '
                    ) AS notas_detalle_resumen

                FROM op_partida_facturas f
                LEFT JOIN operaciones_partida_envio_detalle d
                       ON d.factura_id = f.id_factura
                      AND d.estatus = 1
                LEFT JOIN operaciones_partida_envios e
                       ON e.id_envio = d.envio_id
                      AND e.estatus = 1
                LEFT JOIN op_partida_productos p
                       ON p.id_producto = d.producto_id
                LEFT JOIN contenedores_fisicos cf
                       ON cf.id_fisico = e.contenedor_fisico_id
                LEFT JOIN transportistas t
                       ON t.id_transportista = e.transportista_id
                LEFT JOIN ciudades c
                       ON c.id_ciudad = e.destino_ciudad_id
                WHERE {$whereSql}
                GROUP BY
                    f.id_factura,
                    f.numero_factura,
                    f.pallets_inv,
                    f.proveedor,
                    f.fecha_recibido,
                    f.notas,
                    f.revision_estatus,
                    e.id_envio,
                    e.fecha_envio,
                    e.estatus_envio,
                    e.notas,
                    e.candado,
                    cf.id_fisico,
                    cf.numero_ferro,
                    t.id_transportista,
                    t.nombre,
                    c.id_ciudad,
                    c.nombre_ciudad
                ORDER BY
                    COALESCE(e.fecha_envio, f.fecha_recibido) DESC,
                    f.id_factura DESC";

        if (!$isAll) {
            $sql .= " LIMIT {$offset}, {$perPage}";
        }

        $rows = $this->selectAll($sql, $params) ?: [];

        return [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    // =========================================================
    // DETALLE DE FACTURA
    // Encabezado + productos + fotos mercancía
    // =========================================================
    public function getFacturaDetallePortal(int $facturaId, int $clienteId): array
    {
        if ($facturaId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    f.id_factura,
                    f.cliente_id,
                    f.numero_factura,
                    f.proveedor,
                    f.revision_estatus,
                    f.pallets_inv,
                    f.fecha_recibido,
                    f.notas,
                    c.nombre AS cliente_nombre,
                    b.nombre AS bodega_nombre
                FROM op_partida_facturas f
                LEFT JOIN clientes c
                       ON c.id_cliente = f.cliente_id
                LEFT JOIN bodegas b
                       ON b.id_bodega = f.bodega_id
                WHERE f.id_factura = ?
                  AND f.cliente_id = ?
                  AND f.estatus = 1
                LIMIT 1";

        return $this->select($sql, [$facturaId, $clienteId]) ?: [];
    }

    public function getFacturaProductosPortal(int $facturaId, int $clienteId): array
    {
        if ($facturaId <= 0 || $clienteId <= 0) {
            return [];
        }

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

                    (
                        SELECT COUNT(*)
                        FROM op_partida_producto_fotos fto
                        WHERE fto.producto_id = p.id_producto
                          AND fto.factura_id = p.factura_id
                    ) AS total_fotos
                FROM op_partida_productos p
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = p.factura_id
                       AND f.estatus = 1
                WHERE p.factura_id = ?
                  AND f.cliente_id = ?
                ORDER BY p.id_producto ASC";

        return $this->selectAll($sql, [$facturaId, $clienteId]) ?: [];
    }

    public function getFacturaFotosMercanciaPortal(int $facturaId, int $clienteId): array
    {
        if ($facturaId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    pf.id_foto,
                    pf.producto_id,
                    pf.factura_id,
                    pf.orden,
                    pf.nombre_archivo,
                    pf.ruta_archivo,
                    p.descripcion AS producto_descripcion,
                    p.item,
                    p.marca
                FROM op_partida_producto_fotos pf
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = pf.factura_id
                       AND f.estatus = 1
                LEFT JOIN op_partida_productos p
                       ON p.id_producto = pf.producto_id
                WHERE pf.factura_id = ?
                  AND f.cliente_id = ?
                ORDER BY pf.producto_id ASC, pf.orden ASC, pf.id_foto ASC";
        $rows = $this->selectAll($sql, [$facturaId, $clienteId]) ?: [];

        foreach ($rows as &$row) {
            $row['ruta_archivo'] = $this->normalizarRutaPublica($row['ruta_archivo'] ?? '');
        }
        unset($row);

        return $rows;
    }

    // =========================================================
    // DETALLE DE ENVÍO
    // Encabezado + facturas en ese ferro/caja + productos + imágenes
    // =========================================================
    public function getEnvioDetallePortal(int $envioId, int $clienteId): array
    {
        if ($envioId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    e.id_envio,
                    e.fecha_envio,
                    e.estatus_envio,
                    e.notas,
                    e.candado,
                    e.contenedor_fisico_id,
                    e.destino_ciudad_id,
                    e.transportista_id,
                    cf.numero_ferro,
                    c.nombre_ciudad AS destino_nombre,
                    t.nombre AS transportista_nombre
                FROM operaciones_partida_envios e
                INNER JOIN operaciones_partida_envio_detalle d
                        ON d.envio_id = e.id_envio
                       AND d.estatus = 1
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = d.factura_id
                       AND f.estatus = 1
                       AND f.cliente_id = ?
                LEFT JOIN contenedores_fisicos cf
                       ON cf.id_fisico = e.contenedor_fisico_id
                LEFT JOIN ciudades c
                       ON c.id_ciudad = e.destino_ciudad_id
                LEFT JOIN transportistas t
                       ON t.id_transportista = e.transportista_id
                WHERE e.id_envio = ?
                  AND e.estatus = 1
                LIMIT 1";

        return $this->select($sql, [$clienteId, $envioId]) ?: [];
    }

    public function getEnvioFacturasPortal(int $envioId, int $clienteId): array
    {
        if ($envioId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.proveedor,
                    f.fecha_recibido,
                    f.pallets_inv,
                    COUNT(DISTINCT d.producto_id) AS productos,
                    COALESCE(SUM(d.cajas_enviadas), 0) AS cajas
                FROM operaciones_partida_envio_detalle d
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = d.factura_id
                       AND f.estatus = 1
                WHERE d.envio_id = ?
                  AND d.estatus = 1
                  AND f.cliente_id = ?
                GROUP BY
                    f.id_factura,
                    f.numero_factura,
                    f.proveedor,
                    f.fecha_recibido,
                    f.pallets_inv
                ORDER BY f.numero_factura ASC";

        return $this->selectAll($sql, [$envioId, $clienteId]) ?: [];
    }

    public function getEnvioProductosPortal(int $envioId, int $clienteId): array
    {
        if ($envioId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    d.id_envio_detalle,
                    d.envio_id,
                    d.factura_id,
                    d.producto_id,
                    d.cajas_enviadas,
                    d.notas_detalle,
                    f.numero_factura,
                    p.descripcion,
                    p.item,
                    p.upc,
                    p.marca,
                    p.expiracion,
                    p.inner_pack,
                    p.case_pack
                FROM operaciones_partida_envio_detalle d
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = d.factura_id
                       AND f.estatus = 1
                INNER JOIN op_partida_productos p
                        ON p.id_producto = d.producto_id
                WHERE d.envio_id = ?
                  AND d.estatus = 1
                  AND f.cliente_id = ?
                ORDER BY f.numero_factura ASC, p.descripcion ASC";

        return $this->selectAll($sql, [$envioId, $clienteId]) ?: [];
    }

    public function getEnvioImagenesPortal(int $envioId, int $clienteId): array
    {
        if ($envioId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    ei.id_imagen,
                    ei.envio_id,
                    ei.nombre_archivo,
                    ei.ruta_archivo,
                    ei.mime_type,
                    ei.tamano_bytes,
                    ei.orden_visual
                FROM operaciones_partida_envio_imagenes ei
                INNER JOIN operaciones_partida_envios e
                        ON e.id_envio = ei.envio_id
                       AND e.estatus = 1
                INNER JOIN operaciones_partida_envio_detalle d
                        ON d.envio_id = e.id_envio
                       AND d.estatus = 1
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = d.factura_id
                       AND f.estatus = 1
                WHERE ei.envio_id = ?
                  AND f.cliente_id = ?
                GROUP BY
                    ei.id_imagen,
                    ei.envio_id,
                    ei.nombre_archivo,
                    ei.ruta_archivo,
                    ei.mime_type,
                    ei.tamano_bytes,
                    ei.orden_visual
                ORDER BY ei.orden_visual ASC, ei.id_imagen ASC";

        $rows = $this->selectAll($sql, [$envioId, $clienteId]) ?: [];

        foreach ($rows as &$row) {
            $row['ruta_archivo'] = $this->normalizarRutaPublica($row['ruta_archivo'] ?? '');
        }
        unset($row);

        return $rows;
    }

    // =========================================================
    // MODAL DE IMÁGENES DE MERCANCÍA
    // Puedes usarlo para botón "Ver imágenes de mercancía"
    // =========================================================
    public function getProductoImagenesPortal(int $productoId, int $facturaId, int $clienteId): array
    {
        if ($productoId <= 0 || $facturaId <= 0 || $clienteId <= 0) {
            return [];
        }

        $sql = "SELECT
                    pf.id_foto,
                    pf.producto_id,
                    pf.factura_id,
                    pf.orden,
                    pf.nombre_archivo,
                    pf.ruta_archivo,
                    p.descripcion AS producto_descripcion,
                    f.numero_factura
                FROM op_partida_producto_fotos pf
                INNER JOIN op_partida_facturas f
                        ON f.id_factura = pf.factura_id
                       AND f.estatus = 1
                LEFT JOIN op_partida_productos p
                       ON p.id_producto = pf.producto_id
                WHERE pf.producto_id = ?
                  AND pf.factura_id = ?
                  AND f.cliente_id = ?
                ORDER BY pf.orden ASC, pf.id_foto ASC";
        $rows = $this->selectAll($sql, [$productoId, $facturaId, $clienteId]) ?: [];

        foreach ($rows as &$row) {
            $row['ruta_archivo'] = $this->normalizarRutaPublica($row['ruta_archivo'] ?? '');
        }
        unset($row);

        return $rows;
    }
}
