<?php
class Operaciones_por_partidaModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }
 

    //rutas
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



}
