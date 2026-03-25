<?php
class Operaciones_por_partida_enviosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function listarPaginado(
        int $page,
        int $perPage,
        ?int $fisicoId = null,
        ?int $transportistaId = null,
        string $estatusEnvio = '',
        string $q = ''
    ): array {
        $perPage = min(100, max(1, $perPage));
        $offset  = max(0, ($page - 1) * $perPage);

        // WHERE dinámico sobre encabezado
        $where  = ["e.estatus = 1"];
        $params = [];

        if (!empty($fisicoId)) {
            $where[]  = "e.contenedor_fisico_id = ?";
            $params[] = $fisicoId;
        }

        if (!empty($transportistaId)) {
            $where[]  = "e.transportista_id = ?";
            $params[] = $transportistaId;
        }

        if ($estatusEnvio !== '') {
            $where[]  = "LOWER(e.estatus_envio) = ?";
            $params[] = mb_strtolower(trim($estatusEnvio), 'UTF-8');
        }

        if ($q !== '') {
            $like = '%' . mb_strtolower(trim($q), 'UTF-8') . '%';

            $where[] = "(
                LOWER(cf.numero_ferro) LIKE ?
                OR LOWER(COALESCE(t.nombre, '')) LIKE ?
                OR LOWER(COALESCE(c.nombre_ciudad, '')) LIKE ?
                OR LOWER(COALESCE(e.estatus_envio, '')) LIKE ?
                OR LOWER(COALESCE(e.notas, '')) LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM operaciones_partida_envio_detalle d2
                    INNER JOIN op_partida_facturas f2
                        ON f2.id_factura = d2.factura_id
                    LEFT JOIN clientes cli2
                        ON cli2.id_cliente = f2.cliente_id
                    INNER JOIN op_partida_productos p2
                        ON p2.id_producto = d2.producto_id
                    WHERE d2.envio_id = e.id_envio
                      AND d2.estatus = 1
                      AND (
                            LOWER(COALESCE(f2.numero_factura, '')) LIKE ?
                            OR LOWER(COALESCE(cli2.nombre, '')) LIKE ?
                            OR LOWER(COALESCE(p2.descripcion, '')) LIKE ?
                            OR LOWER(COALESCE(p2.marca, '')) LIKE ?
                      )
                )
            )";

            array_push(
                $params,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like
            );
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countSql = "
            SELECT COUNT(*) AS total
            FROM operaciones_partida_envios e
            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = e.contenedor_fisico_id
            LEFT JOIN transportistas t
                ON t.id_transportista = e.transportista_id
            LEFT JOIN ciudades c
                ON c.id_ciudad = e.destino_ciudad_id
            $whereSql
        ";

        $rowCount = $this->select($countSql, $params);
        $total    = $rowCount ? (int)$rowCount['total'] : 0;

        $dataSql = "
            SELECT
                e.id_envio,
                e.contenedor_fisico_id,
                cf.numero_ferro AS ferro,
                e.transportista_id,
                COALESCE(t.nombre, '') AS transportista,
                e.fecha_envio,
                e.destino_ciudad_id,
                COALESCE(c.nombre_ciudad, '') AS destino,
                e.estatus_envio,
                COALESCE(det.clientes, '') AS clientes,
                COALESCE(det.facturas, '') AS facturas,
                COALESCE(det.productos, '') AS productos,
                COALESCE(det.total_cajas, 0) AS total_cajas,
                COALESCE(e.notas, '') AS notas
            FROM operaciones_partida_envios e
            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = e.contenedor_fisico_id
            LEFT JOIN transportistas t
                ON t.id_transportista = e.transportista_id
            LEFT JOIN ciudades c
                ON c.id_ciudad = e.destino_ciudad_id
            LEFT JOIN (
                SELECT
                    d.envio_id,
                    GROUP_CONCAT(
                        DISTINCT COALESCE(cli.nombre, 'Sin cliente')
                        ORDER BY cli.nombre ASC
                        SEPARATOR ' / '
                    ) AS clientes,
                    GROUP_CONCAT(
                        DISTINCT f.numero_factura
                        ORDER BY f.numero_factura ASC
                        SEPARATOR ' / '
                    ) AS facturas,
                    GROUP_CONCAT(
                        CONCAT(
                            p.descripcion,
                            ' (',
                            d.cajas_enviadas,
                            ')'
                        )
                        ORDER BY p.descripcion ASC
                        SEPARATOR ' | '
                    ) AS productos,
                    SUM(d.cajas_enviadas) AS total_cajas
                FROM operaciones_partida_envio_detalle d
                INNER JOIN op_partida_facturas f
                    ON f.id_factura = d.factura_id
                LEFT JOIN clientes cli
                    ON cli.id_cliente = f.cliente_id
                INNER JOIN op_partida_productos p
                    ON p.id_producto = d.producto_id
                WHERE d.estatus = 1
                GROUP BY d.envio_id
            ) det
                ON det.envio_id = e.id_envio
            $whereSql
            ORDER BY
                e.fecha_envio DESC,
                e.id_envio DESC
            LIMIT $perPage OFFSET $offset
        ";

        $rows = $this->selectAll($dataSql, $params);

        return [
            'rows'  => is_array($rows) ? $rows : [],
            'total' => $total
        ];
    }

    /* =========================================================
       CATÁLOGOS / BÚSQUEDAS PARA LLENAR LA VISTA
       ========================================================= */

    public function sugerirFerroCaja(string $term, int $limit = 10): array
    {
        $term = trim($term);
        $limit = max(1, min(20, (int)$limit));

        if ($term === '') {
            return [];
        }

        $sql = "SELECT 
                    cf.id_fisico AS id,
                    cf.numero_ferro
                FROM contenedores_fisicos cf
                WHERE cf.estatus = 1
                  AND cf.numero_ferro LIKE ?
                ORDER BY cf.numero_ferro ASC
                LIMIT {$limit}";

        $rows = $this->selectAll($sql, ['%' . $term . '%']);
        return is_array($rows) ? $rows : [];
    }

    public function sugerirFacturas(string $term, int $limit = 10): array
    {
        $term = trim($term);
        $limit = max(1, min(20, (int)$limit));

        if ($term === '') {
            return [];
        }

        $sql = "SELECT
                f.id_factura AS id,
                f.numero_factura,
                f.proveedor,
                f.bodega_id,
                b.nombre AS bodega,
                f.pallets_inv,
                f.fecha_recibido,
                COALESCE(SUM(p.cajas), 0) AS cajas_totales,
                COALESCE(SUM(GREATEST(p.cajas - COALESCE(env.total_enviado, 0), 0)), 0) AS cajas_disponibles
            FROM op_partida_facturas f
            LEFT JOIN bodegas b
                ON b.id_bodega = f.bodega_id
            LEFT JOIN op_partida_productos p
                ON p.factura_id = f.id_factura
               AND p.estatus = 1
            LEFT JOIN (
                SELECT
                    d.producto_id,
                    SUM(d.cajas_enviadas) AS total_enviado
                FROM operaciones_partida_envio_detalle d
                WHERE d.estatus = 1
                GROUP BY d.producto_id
            ) env
                ON env.producto_id = p.id_producto
            WHERE f.estatus = 1
              AND (
                    f.numero_factura LIKE ?
                    OR f.proveedor LIKE ?
                  )
            GROUP BY
                f.id_factura,
                f.numero_factura,
                f.proveedor,
                f.bodega_id,
                b.nombre,
                f.pallets_inv,
                f.fecha_recibido
            ORDER BY f.id_factura DESC
            LIMIT {$limit}";

        $params = ['%' . $term . '%', '%' . $term . '%'];
        $rows = $this->selectAll($sql, $params);

        return is_array($rows) ? $rows : [];
    }

    public function obtenerFacturaPorId(int $facturaId): ?array
    {
        $sql = "SELECT
                f.id_factura,
                f.numero_factura,
                f.proveedor,
                f.bodega_id,
                b.nombre AS bodega,
                
                f.pallets_inv,
                f.fecha_recibido,
                f.notas,
                COALESCE(SUM(p.cajas), 0) AS cajas_totales,
                COALESCE(SUM(GREATEST(p.cajas - COALESCE(env.total_enviado, 0), 0)), 0) AS cajas_disponibles
            FROM op_partida_facturas f
            LEFT JOIN bodegas b
                ON b.id_bodega = f.bodega_id
            LEFT JOIN op_partida_productos p
                ON p.factura_id = f.id_factura
               AND p.estatus = 1
            LEFT JOIN (
                SELECT
                    d.producto_id,
                    SUM(d.cajas_enviadas) AS total_enviado
                FROM operaciones_partida_envio_detalle d
                WHERE d.estatus = 1
                GROUP BY d.producto_id
            ) env
                ON env.producto_id = p.id_producto
            WHERE f.id_factura = ?
              AND f.estatus = 1
            GROUP BY
                f.id_factura,
                f.numero_factura,
                f.proveedor,
                f.bodega_id,
                b.nombre,
                 
                f.pallets_inv,
                f.fecha_recibido,
                f.notas
            LIMIT 1";

        $row = $this->select($sql, [$facturaId]);
        return is_array($row) ? $row : null;
    }

    /* =========================================================
       MÉTODOS DE REGISTRO
       Tablas destino:
       - operaciones_partida_envios
       - operaciones_partida_envio_detalle
       ========================================================= */

    public function registrarEnvio(
        int $contenedorFisicoId,
        ?int $destinoCiudadId,
        string $fechaEnvio,
        string $estatusEnvio,
        ?int $transportistaId,
        string $candado = '',
        string $notas = ''
    ) {
        $sql = "INSERT INTO operaciones_partida_envios (
                    contenedor_fisico_id,
                    destino_ciudad_id,
                    fecha_envio,
                    estatus_envio,
                    transportista_id,
                    candado,
                    notas
                ) VALUES (?, ?, ?, ?, ?,?, ?)";

        return $this->insertar($sql, [
            $contenedorFisicoId,
            $destinoCiudadId,
            $fechaEnvio,
            $estatusEnvio,
            $transportistaId,
            $candado,
            $notas
        ]);
    }

    public function registrarEnvioDetalle(
        int $envioId,
        int $facturaId,
        int $productoId,
        int $cajasEnviadas,
        string $notasDetalle = ''
    ) {
        $sql = "INSERT INTO operaciones_partida_envio_detalle (
                    envio_id,
                    factura_id,
                    producto_id,
                    cajas_enviadas,
                    notas_detalle
                ) VALUES (?, ?, ?, ?, ?)";

        return $this->insertar($sql, [
            $envioId,
            $facturaId,
            $productoId,
            $cajasEnviadas,
            $notasDetalle
        ]);
    }

    public function obtenerFerroPorNumero(string $numeroFerro): ?array
    {
        $numeroFerro = trim($numeroFerro);

        if ($numeroFerro === '') {
            return null;
        }

        $sql = "SELECT
                cf.id_fisico,
                cf.numero_ferro,
                cf.estatus
            FROM contenedores_fisicos cf
            WHERE TRIM(LOWER(cf.numero_ferro)) = TRIM(LOWER(?))
            LIMIT 1";

        $row = $this->select($sql, [$numeroFerro]);
        return is_array($row) ? $row : null;
    }

    public function registrarFerroCaja(string $numeroFerro)
    {
        $numeroFerro = trim($numeroFerro);

        if ($numeroFerro === '') {
            return false;
        }

        $sql = "INSERT INTO contenedores_fisicos (
                numero_ferro,
                estatus
            ) VALUES (?, 1)";

        return $this->insertar($sql, [$numeroFerro]);
    }

    public function obtenerOCrearFerroCaja(string $numeroFerro): ?array
    {
        $numeroFerro = trim($numeroFerro);

        if ($numeroFerro === '') {
            return null;
        }

        $existente = $this->obtenerFerroPorNumero($numeroFerro);
        if ($existente) {
            return $existente;
        }

        $nuevoId = $this->registrarFerroCaja($numeroFerro);
        if (!$nuevoId) {
            return null;
        }

        return [
            'id_fisico'    => (int)$nuevoId,
            'numero_ferro' => $numeroFerro,
            'estatus'      => 1
        ];
    }

    public function obtenerCajasYaEnviadasProducto(int $productoId): int
    {
        $sql = "SELECT
                COALESCE(SUM(d.cajas_enviadas), 0) AS total_enviado
            FROM operaciones_partida_envio_detalle d
            WHERE d.producto_id = ?
              AND d.estatus = 1";

        $row = $this->select($sql, [$productoId]);
        return $row ? (int)($row['total_enviado'] ?? 0) : 0;
    }

    public function obtenerProductoConDisponibilidad(int $productoId): ?array
    {
        $sql = "SELECT
                p.id_producto,
                p.factura_id,
                p.descripcion,
                p.upc,
                p.marca,
                p.cajas AS cajas_totales,
                COALESCE(env.total_enviado, 0) AS cajas_enviadas,
                GREATEST(p.cajas - COALESCE(env.total_enviado, 0), 0) AS cajas_restantes,
                p.piezas,
                p.estatus
            FROM op_partida_productos p
            LEFT JOIN (
                SELECT
                    d.producto_id,
                    SUM(d.cajas_enviadas) AS total_enviado
                FROM operaciones_partida_envio_detalle d
                WHERE d.estatus = 1
                GROUP BY d.producto_id
            ) env
                ON env.producto_id = p.id_producto
            WHERE p.id_producto = ?
              AND p.estatus = 1
            LIMIT 1";

        $row = $this->select($sql, [$productoId]);
        return is_array($row) ? $row : null;
    }

    public function listarProductosPorFactura(int $facturaId): array
    {
        $sql = "SELECT
                p.id_producto AS id,
                p.factura_id,
                p.descripcion,
                p.upc,
                p.marca,
                p.expiracion,
                p.inner_pack,
                p.case_pack,
                p.pallets_rcv,
                p.cajas AS cajas_totales,
                COALESCE(env.total_enviado, 0) AS cajas_enviadas,
                GREATEST(p.cajas - COALESCE(env.total_enviado, 0), 0) AS cajas_restantes,
                p.piezas
            FROM op_partida_productos p
            LEFT JOIN (
                SELECT
                    d.producto_id,
                    SUM(d.cajas_enviadas) AS total_enviado
                FROM operaciones_partida_envio_detalle d
                WHERE d.estatus = 1
                GROUP BY d.producto_id
            ) env
                ON env.producto_id = p.id_producto
            WHERE p.factura_id = ?
              AND p.estatus = 1
            ORDER BY p.descripcion ASC, p.id_producto ASC";

        $rows = $this->selectAll($sql, [$facturaId]);
        return is_array($rows) ? $rows : [];
    }

    public function obtenerProductoPorId(int $productoId): ?array
    {
        return $this->obtenerProductoConDisponibilidad($productoId);
    }

    public function validarCajasDisponiblesProducto(int $productoId, int $cajasSolicitadas): bool
    {
        $producto = $this->obtenerProductoConDisponibilidad($productoId);

        if (!$producto) {
            return false;
        }

        $restantes = (int)($producto['cajas_restantes'] ?? 0);
        return $cajasSolicitadas > 0 && $cajasSolicitadas <= $restantes;
    }

    /* =========================================================
       EDICIÓN
       ========================================================= */

    public function obtenerEnvioPorId(int $envioId): ?array
    {
        $sql = "SELECT
                e.id_envio,
                e.contenedor_fisico_id,
                e.candado,
                cf.numero_ferro AS ferro,
                e.transportista_id,
                COALESCE(t.nombre, '') AS transportista,
                e.fecha_envio,
                e.destino_ciudad_id,
                COALESCE(c.nombre_ciudad, '') AS destino,
                e.estatus_envio,
                COALESCE(e.notas, '') AS notas,
                e.estatus
            FROM operaciones_partida_envios e
            INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = e.contenedor_fisico_id
            LEFT JOIN transportistas t
                ON t.id_transportista = e.transportista_id
            LEFT JOIN ciudades c
                ON c.id_ciudad = e.destino_ciudad_id
            WHERE e.id_envio = ?
              AND e.estatus = 1
            LIMIT 1";

        $envio = $this->select($sql, [$envioId]);

        if (!is_array($envio) || empty($envio)) {
            return null;
        }

        $envio['detalle']  = $this->obtenerDetalleEnvio($envioId);
        $envio['imagenes'] = $this->obtenerImagenesEnvio($envioId);

        return $envio;
    }

    public function obtenerDetalleEnvio(int $envioId): array
    {
        $sql = "SELECT
                d.id_envio_detalle,
                d.envio_id,
                d.factura_id,
                d.producto_id,
                COALESCE(f.numero_factura, '') AS numero_factura,
                COALESCE(p.descripcion, '') AS descripcion,
                COALESCE(p.upc, '') AS upc,
                COALESCE(p.marca, '') AS marca,
                d.cajas_enviadas,
                COALESCE(d.notas_detalle, '') AS notas_detalle
            FROM operaciones_partida_envio_detalle d
            INNER JOIN op_partida_facturas f
                ON f.id_factura = d.factura_id
            INNER JOIN op_partida_productos p
                ON p.id_producto = d.producto_id
            WHERE d.envio_id = ?
              AND d.estatus = 1
            ORDER BY p.descripcion ASC, d.id_envio_detalle ASC";

        $rows = $this->selectAll($sql, [$envioId]);
        return is_array($rows) ? $rows : [];
    }

    public function actualizarEnvioEditable(
        int $envioId,
        string $estatusEnvio,
        string $notas = '',
        string $fechaEnvio = '',
        string $candado = ''
    ): bool {
        $sql = "UPDATE operaciones_partida_envios
                SET estatus_envio = ?,
                    notas = ?,
                    fecha_envio = ?,
                    candado = ?
                WHERE id_envio = ?
                  AND estatus = 1";

        $result = $this->save($sql, [
            trim($estatusEnvio),
            trim($notas),
            trim($fechaEnvio),
            trim($candado),
            $envioId
        ]);

        return $result == 1;
    }

    /* =========================================================
       IMÁGENES DEL ENVÍO
       Tabla: operaciones_partida_envio_imagenes
       ========================================================= */

    public function registrarEnvioImagen(
        int $envioId,
        string $nombreArchivo,
        string $rutaArchivo,
        ?string $mimeType = null,
        ?int $tamanoBytes = null,
        int $ordenVisual = 1,
        ?int $subidoPor = null
    ) {
        $sql = "INSERT INTO operaciones_partida_envio_imagenes (
                    envio_id,
                    nombre_archivo,
                    ruta_archivo,
                    mime_type,
                    tamano_bytes,
                    orden_visual,
                    subido_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        return $this->insertar($sql, [
            $envioId,
            trim($nombreArchivo),
            trim($rutaArchivo),
            $mimeType !== null ? trim($mimeType) : null,
            $tamanoBytes,
            $ordenVisual,
            $subidoPor
        ]);
    }

    public function obtenerImagenesEnvio(int $envioId): array
    {
        $sql = "SELECT
                i.id_imagen,
                i.envio_id,
                i.nombre_archivo,
                i.ruta_archivo,
                COALESCE(i.mime_type, '') AS mime_type,
                COALESCE(i.tamano_bytes, 0) AS tamano_bytes,
                COALESCE(i.orden_visual, 1) AS orden_visual,
                i.subido_por,
                i.fecha_subida,
                i.estatus
            FROM operaciones_partida_envio_imagenes i
            WHERE i.envio_id = ?
              AND i.estatus = 1
            ORDER BY i.orden_visual ASC, i.id_imagen ASC";

        $rows = $this->selectAll($sql, [$envioId]);
        return is_array($rows) ? $rows : [];
    }

    public function obtenerImagenEnvioPorId(int $imagenId): ?array
    {
        $sql = "SELECT
                i.id_imagen,
                i.envio_id,
                i.nombre_archivo,
                i.ruta_archivo,
                COALESCE(i.mime_type, '') AS mime_type,
                COALESCE(i.tamano_bytes, 0) AS tamano_bytes,
                COALESCE(i.orden_visual, 1) AS orden_visual,
                i.subido_por,
                i.fecha_subida,
                i.estatus
            FROM operaciones_partida_envio_imagenes i
            WHERE i.id_imagen = ?
            LIMIT 1";

        $row = $this->select($sql, [$imagenId]);
        return is_array($row) ? $row : null;
    }

    public function contarImagenesActivasEnvio(int $envioId): int
    {
        $sql = "SELECT COUNT(*) AS total
            FROM operaciones_partida_envio_imagenes
            WHERE envio_id = ?
              AND estatus = 1";

        $row = $this->select($sql, [$envioId]);
        return $row ? (int)($row['total'] ?? 0) : 0;
    }

    public function obtenerSiguienteOrdenImagen(int $envioId): int
    {
        $sql = "SELECT COALESCE(MAX(orden_visual), 0) AS ultimo_orden
            FROM operaciones_partida_envio_imagenes
            WHERE envio_id = ?
              AND estatus = 1";

        $row = $this->select($sql, [$envioId]);
        $ultimo = $row ? (int)($row['ultimo_orden'] ?? 0) : 0;

        return $ultimo + 1;
    }

    public function desactivarImagenEnvio(int $imagenId, int $envioId): bool
    {
        $sql = "UPDATE operaciones_partida_envio_imagenes
                SET estatus = 0
                WHERE id_imagen = ?
                  AND envio_id = ?
                  AND estatus = 1";

        $result = $this->save($sql, [$imagenId, $envioId]);

        return $result == 1;
    }

    public function desactivarImagenesEnvio(array $idsImagenes, int $envioId): bool
    {
        $ids = array_values(array_filter(array_map('intval', $idsImagenes), function ($id) {
            return $id > 0;
        }));

        if (empty($ids)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE operaciones_partida_envio_imagenes
                SET estatus = 0
                WHERE envio_id = ?
                  AND id_imagen IN ($placeholders)
                  AND estatus = 1";

        $params = array_merge([$envioId], $ids);
        $result = $this->save($sql, $params);

        return $result >= 0;
    }
}
