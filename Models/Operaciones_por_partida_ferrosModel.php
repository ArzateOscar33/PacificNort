<?php
class Operaciones_por_partida_ferrosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /* =========================
       SUGERENCIAS FERROS
       ========================= */
    public function sugerirFerros(string $term, int $limit = 10): array
    {
        $term  = trim((string)$term);
        $limit = (int)$limit;
        if ($limit < 1)  $limit = 10;
        if ($limit > 25) $limit = 25;

        if ($term === '') return [];

        $like = '%' . $term . '%';

        $sql = "SELECT id_fisico, numero_ferro
                FROM contenedores_fisicos
                WHERE estatus = 1
                  AND numero_ferro LIKE ?
                ORDER BY numero_ferro ASC
                LIMIT $limit";

        return $this->selectAll($sql, [$like]);
    }

    /* =========================
       LISTADO FERROS + ENVIOS
       (SOLO LECTURA)
       ========================= */
    public function listarFerrosEnvios(array $filters = []): array
    {
        $ferro_id = (int)($filters['ferro_id'] ?? 0);
        $fi       = trim((string)($filters['fi'] ?? '')); // YYYY-MM-DD
        $ff       = trim((string)($filters['ff'] ?? '')); // YYYY-MM-DD
        $termProd = trim((string)($filters['term'] ?? ''));

        $where = [];
        $params = [];

        // Estatus: si quieres mostrar todo (incluye 1,2) excluye 0 porque es baja 
        $where[] = "e.estatus IN (1,2)";

        if ($ferro_id > 0) {
            $where[] = "e.id_fisico = ?";
            $params[] = $ferro_id;
        }

        // Fechas (fi/ff en date => se convierten a datetime)
        if ($fi !== '' && $ff !== '') {
            $where[] = "e.fecha_envio BETWEEN ? AND ?";
            $params[] = $fi . " 00:00:00";
            $params[] = $ff . " 23:59:59";
        } elseif ($fi !== '') {
            $where[] = "e.fecha_envio >= ?";
            $params[] = $fi . " 00:00:00";
        } elseif ($ff !== '') {
            $where[] = "e.fecha_envio <= ?";
            $params[] = $ff . " 23:59:59";
        }

        if ($termProd !== '') {
            $like = '%' . $termProd . '%';
            $where[] = "(p.descripcion LIKE ? OR p.upc LIKE ? OR p.marca LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT
                  e.id_envio,
                  e.fecha_envio,
                  e.cajas_enviadas,
                  e.estatus AS envio_estatus,
                  CASE e.estatus
                    WHEN 1 THEN 'En camino'
                    WHEN 2 THEN 'Entregado'
                    WHEN 0 THEN 'Baja'
                    ELSE 'Desconocido'
                END AS envio_estatus_txt,
                  f.id_fisico,
                  f.numero_ferro,
                  fa.id_factura,
                  fa.numero_factura,
                  p.id_producto,
                  p.descripcion,
                  p.upc,
                  p.marca
                FROM op_partida_envios e
                INNER JOIN contenedores_fisicos f
                  ON f.id_fisico = e.id_fisico
                INNER JOIN op_partida_facturas fa
                  ON fa.id_factura = e.factura_id
                INNER JOIN op_partida_productos p
                  ON p.factura_id = e.factura_id
                 AND p.id_producto = e.producto_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY e.fecha_envio DESC, e.id_envio DESC";

        return $this->selectAll($sql, $params);
    }
}
