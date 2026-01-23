<?php
class Operaciones_por_partida_rutasModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function existeFacturaActiva(int $factura_id): bool
    {
        $sql = "SELECT id_factura
                FROM op_partida_facturas
                WHERE id_factura = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$factura_id]);
        return !empty($row);
    }

    // ===================== SUGERENCIAS FACTURAS =====================
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

    // ===================== LISTAR PRODUCTOS (RUTAS) =====================
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

        /*
          IMPORTANTE:
          Ajusta estos nombres de columnas según tu tabla real op_partida_envios:
          - e.cajas_enviadas
          - e.estatus
          - e.producto_id
          - e.factura_id

          Si tu columna se llama diferente (ej. cajas, qty, cajas_envio, etc.),
          aquí es donde se corrige.
        */
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

    // ===================== LISTAR ENVIOS (DETALLE) =====================
    public function listarEnviosProducto(int $facturaId, int $productoId): array
    {
        $facturaId  = (int)$facturaId;
        $productoId = (int)$productoId;

        /*
          Ajusta nombres reales de tu tabla op_partida_envios:
          - e.destino (o ciudad_destino_id)
          - e.fecha_envio
          - e.caja_ferro (o id_fisico)
          - e.cajas_enviadas
        */
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

    // ===================== CIUDADES =====================
    public function listarCiudadesActivas(): array
    {
        // AJUSTA tabla/campos según tu BD real
        $sql = "SELECT
                    c.id_ciudad,
                    c.nombre
                FROM ciudades c
                WHERE c.estatus = 1
                ORDER BY c.nombre ASC";

        $rows = $this->selectAll($sql);
        return ($rows === false || empty($rows)) ? [] : $rows;
    }

    // =====================
    // SUGERIR CAJA/FERRO
    // =====================

 

// =====================
// SUGERIR FERROS (FÍSICOS)
// Tabla real: contenedores_fisicos (id_fisico, numero_ferro, estatus)
// =====================

// Wrapper para que el Controller/JS llamen sugerirFisicos()
public function sugerirFisicos(string $term, int $limit = 10): array
{
    return $this->sugerirFerros($term, $limit);
}

public function sugerirFerros(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;

    if ($limit < 1)  $limit = 10;
    if ($limit > 25) $limit = 25;

    // para sugerencias: no saturar si escriben 1 caracter
    if ($term === '' || mb_strlen($term) < 2) return [];

    $like = '%' . $term . '%';

    // Opcional: prioriza los que empiezan con lo escrito
    $likeStart = $term . '%';

    $sql = "SELECT
                cf.id_fisico AS id,
                'FERRO'      AS tipo,
                cf.numero_ferro AS texto
            FROM contenedores_fisicos cf
            WHERE cf.estatus = 1
              AND cf.numero_ferro LIKE ?
            ORDER BY
              (cf.numero_ferro LIKE ?) DESC,
              cf.id_fisico DESC
            LIMIT $limit";

    $rows = $this->selectAll($sql, [$like, $likeStart]);
    return ($rows === false) ? [] : $rows;
}

// =====================
// SUGERENCIAS CIUDADES (DESTINOS)
// =====================
public function sugerirCiudades(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;

    if ($limit < 1)  $limit = 10;
    if ($limit > 25) $limit = 25;

    // Evita consultas con 1 caracter
    if ($term === '' || mb_strlen($term) < 2) return [];

    $like      = '%' . $term . '%';
    $likeStart = $term . '%';

    // AJUSTE DE CAMPO:
    // Si tu columna se llama "nombre" en vez de "nombre_ciudad", cámbialo aquí.
    $sql = "SELECT
                c.id_ciudad AS id,
                c.nombre_ciudad AS texto
            FROM ciudades c
            WHERE c.estatus = 1
              AND c.nombre_ciudad LIKE ?
            ORDER BY
              (c.nombre_ciudad LIKE ?) DESC,
              c.nombre_ciudad ASC
            LIMIT $limit";

    $rows = $this->selectAll($sql, [$like, $likeStart]);
    return ($rows === false) ? [] : $rows;
}

    
}
