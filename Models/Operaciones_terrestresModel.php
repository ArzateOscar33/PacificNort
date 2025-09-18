<?php
class Operaciones_terrestresModel extends Query
{
    /** Catálogo de ferros/cajas activos */
    public function catalogoFerros(): array
    {
        $sql = "SELECT id_fisico, numero_ferro
                  FROM contenedores_fisicos
                 WHERE estatus = 1
              ORDER BY numero_ferro";
        return $this->selectAll($sql) ?: [];
    }

    /** Catálogo de operaciones activas (ajusta estatus si lo requieres) */
    public function catalogoOperacionesActivas(): array
    {
        $sql = "SELECT o.id_operacion, o.numero_operacion,
                       o.numero_bl, o.etd, o.eta,
                       c.id_cliente AS cliente_id,
                       COALESCE(c.nombre,'') AS cliente
                  FROM operaciones o
             LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
                 WHERE o.estatus_id IN (1,5,9,10)
              ORDER BY o.id_operacion DESC
                 LIMIT 1000";
        return $this->selectAll($sql) ?: [];
    }
// En Operaciones_terrestresModel.php
// En Operaciones_terrestresModel.php
public function sugerenciasOperacionesFerroOP(string $q = '', int $limit = 15): array
{
    // 1) Buscar operaciones activas (con cliente)
    $q = trim($q);
    $args = [];
    $where = " WHERE o.estatus_id IN (1,5,9,10) ";
    if ($q !== '') {
        $where .= " AND (
            LOWER(o.numero_operacion) LIKE ?
            OR LOWER(COALESCE(o.numero_bl,'')) LIKE ?
            OR LOWER(COALESCE(c.nombre,'')) LIKE ?
        )";
        $needle = '%' . mb_strtolower($q, 'UTF-8') . '%';
        $args = [$needle, $needle, $needle];
    }

    $sqlOps = "SELECT
                  o.id_operacion,
                  o.numero_operacion,
                  COALESCE(o.numero_bl,'')  AS numero_bl,
                  c.id_cliente              AS cliente_id,
                  COALESCE(c.nombre,'')     AS cliente
               FROM operaciones o
               LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
               {$where}
               ORDER BY o.id_operacion DESC
               LIMIT " . max(1, (int)$limit);

    $ops = $this->selectAll($sqlOps, $args) ?: [];
    if (!$ops) return [];

    // 2) Traer los contenedores marítimos (y bultos) de esas operaciones
    $opIds = array_map(fn($r) => (int)$r['id_operacion'], $ops);
    $ph    = implode(',', array_fill(0, count($opIds), '?'));

    $sqlMar = "SELECT
                  cmo.operacion_id,
                  cm.id_contenedor_maritimo,
                  cm.numero_contenedor,
                  COALESCE(cmo.bultos,0) AS bultos
               FROM contenedores_maritimos_operacion cmo
               INNER JOIN contenedores_maritimos cm
                       ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
               WHERE cmo.operacion_id IN ($ph)
               ORDER BY cm.numero_contenedor";

    $marRows = $this->selectAll($sqlMar, $opIds) ?: [];

    // Indexar marítimos por operación
    $marByOp = [];
    foreach ($marRows as $m) {
        $oid = (int)$m['operacion_id'];
        if (!isset($marByOp[$oid])) $marByOp[$oid] = [];
        $marByOp[$oid][] = [
            'id_contenedor_maritimo' => (int)$m['id_contenedor_maritimo'],
            'numero_contenedor'      => (string)$m['numero_contenedor'],
            'bultos'                 => (int)$m['bultos'],
        ];
    }

    // 3) Armar respuesta para el autocomplete
    $out = [];
    foreach ($ops as $r) {
        $oid = (int)$r['id_operacion'];
        $mar = $marByOp[$oid] ?? [];

        // texto lindo para la lista (muestra hasta 2 contenedores)
        $marNums = array_map(fn($x) => $x['numero_contenedor'], $mar);
        $preview = '';
        if ($marNums) {
            $preview = ' — ' . (count($marNums) > 2
                        ? (implode(', ', array_slice($marNums, 0, 2)) . '…')
                        : implode(', ', $marNums));
        }

        $label = trim(
            $r['numero_operacion'] 
        );

        $out[] = [
            'id'           => $oid,
            'label'        => $label,
            'cliente_id'   => (int)($r['cliente_id'] ?? 0),
            'cliente'      => (string)($r['cliente'] ?? ''),
            // Marítimos completos para el modal:
            'maritimos'    => $mar, // [{id_contenedor_maritimo, numero_contenedor, bultos}, ...]
            // Totales útiles (por si quieres mostrarlos):
            'total_bultos_maritimos' => array_sum(array_map(fn($x)=> (int)$x['bultos'], $mar)),
        ];
    }
    return $out;
}
// Modelo: Operaciones_terrestresModel.php
public function getSumaBultosPorOperacion(int $operacion_id): array
{
    $sql = "SELECT COALESCE(SUM(co.bultos),0) AS total_asignados
              FROM contenedores_operacion co
             WHERE co.operacion_id = ?";
    return $this->select($sql, [$operacion_id]) ?: ['total_asignados'=>0];
}


 public function listarFerrosPaginado(array $filters = [], int $page = 1, int $per_page = 10): array
{
    $argsBuscar = [];

    // ---- Búsqueda libre ----
    $term   = isset($filters['term']) ? trim(mb_strtolower($filters['term'], 'UTF-8')) : '';
    $buscar = ($term !== '');
    $needle = $buscar ? "%{$term}%" : null;

    $whereBusq = $buscar
        ? " AND (
                LOWER(o.numero_operacion) LIKE ?
             OR LOWER(cm.numero_contenedor) LIKE ?
             OR LOWER(cf.numero_ferro) LIKE ?
             OR LOWER(COALESCE(cli2.nombre, cli.nombre, '')) LIKE ?
           )"
        : "";

    if ($buscar) {
        array_push($argsBuscar, $needle, $needle, $needle, $needle);
    }

    // ---- Filtro de fechas (YYYY-MM-DD) sobre co.fecha_creacion ----
    $dateFrom = isset($filters['date_from']) && $filters['date_from'] !== '' ? $filters['date_from'] : null;
    $dateTo   = isset($filters['date_to'])   && $filters['date_to']   !== '' ? $filters['date_to']   : null;

    $argsDate = [];
    $whereDate = "";
    if ($dateFrom !== null) {
        $whereDate .= " AND co.fecha_creacion >= ? ";
        $argsDate[] = $dateFrom . " 00:00:00";
    }
    if ($dateTo !== null) {
        $whereDate .= " AND co.fecha_creacion <= ? ";
        $argsDate[] = $dateTo . " 23:59:59";
    }

    // ---- Subquery base:
    // una FILA por co.id_contenedor (ferro en operación)
    // con GROUP_CONCAT de todos los contenedores marítimos de ESA operación
    $sub = "
        SELECT
            co.id_contenedor                                      AS id_row,
            o.id_operacion                                        AS operacion_id,
            o.numero_operacion                                    AS numero_operacion,
            COALESCE(GROUP_CONCAT(DISTINCT cm.numero_contenedor
                                   ORDER BY cm.numero_contenedor
                                   SEPARATOR ', '), '')           AS contenedores_maritimos,
            COALESCE(SUM(cmo.bultos), 0)                          AS bultos_maritimo,
            COALESCE(cli2.nombre, cli.nombre, '')                 AS cliente,
            cf.numero_ferro                                       AS ferro,
            COALESCE(co.bultos, 0)                                AS bultos_asignados,
            co.fecha_creacion                                     AS fecha_creacion
        FROM contenedores_operacion co
        INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = co.id_fisico
        INNER JOIN operaciones o
                ON o.id_operacion = co.operacion_id
        LEFT  JOIN clientes cli
                ON cli.id_cliente = o.cliente_id
        LEFT  JOIN clientes cli2
                ON cli2.id_cliente = co.cliente_id
        LEFT  JOIN contenedores_maritimos_operacion cmo
                ON cmo.operacion_id = co.operacion_id
        LEFT  JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE co.estatus = 1
          AND o.estatus_id IN (1,5,9,10)
          {$whereBusq}
          {$whereDate}
        GROUP BY
            co.id_contenedor,
            o.id_operacion,
            o.numero_operacion,
            cliente,
            cf.numero_ferro,
            co.fecha_creacion
    ";

    // ---- TOTAL ----
    $sqlCount = "SELECT COUNT(*) AS total FROM ({$sub}) AS t";
    $row = $this->select($sqlCount, array_merge($argsBuscar, $argsDate));
    $total = (int)($row['total'] ?? 0);

    // ---- Paginación ----
    $per_page = max(1, min($per_page, 200));
    $total_pages = max(1, (int)ceil($total / $per_page));
    $page   = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;

    // ---- DATA ----
    $sqlData = "{$sub}
                ORDER BY fecha_creacion DESC, numero_operacion DESC, ferro ASC
                LIMIT {$per_page} OFFSET {$offset}";
    $data = $this->selectAll($sqlData, array_merge($argsBuscar, $argsDate)) ?: [];

    return [
        'data' => $data,
        'meta' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => $total_pages,
        ]
    ];
}

}
