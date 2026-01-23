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

    // Wrapper para que no truene si el Controller llama sugerirFisicos()
    public function sugerirFisicos(string $term, int $limit = 10): array
    {
        return $this->sugerirCajaFerro($term, $limit);
    }

    public function sugerirCajaFerro(string $term, int $limit = 10): array
    {
        $term  = trim((string)$term);
        $limit = (int)$limit;

        if ($limit < 1) $limit = 10;
        if ($limit > 25) $limit = 25;
        if ($term === '' || mb_strlen($term) < 2) return [];

        $like = '%' . $term . '%';

        /*
          AJUSTA A TU BD REAL.
          Te lo dejo como lo tenías: “cajas_fisicas” y “ferros”.
          Si en realidad usas “contenedores_fisicos” o algo similar,
          aquí se cambia.
        */

        // (1) CAJAS
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

        $rows = array_merge($cajas, $ferros);

        if (count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }

        return $rows;
    }

    
}
