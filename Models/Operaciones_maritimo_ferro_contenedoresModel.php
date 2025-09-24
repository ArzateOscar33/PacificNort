<?php
class Operaciones_maritimo_ferro_contenedoresModel extends Query
{

    public function catalogoFerros(): array
    {
        $sql = "SELECT id_fisico, numero_ferro
                  FROM contenedores_fisicos
                 WHERE estatus = 1
              ORDER BY numero_ferro";
        return $this->selectAll($sql) ?: [];
    }
    public function getSumaBultosPorOperacion(int $operacion_id): array
    {
        $sql = "SELECT COALESCE(SUM(co.bultos),0) AS total_asignados
              FROM contenedores_operacion co
             WHERE co.operacion_id = ?";
        return $this->select($sql, [$operacion_id]) ?: ['total_asignados' => 0];
    }
    public function sugerenciasOperacionesFerroOP(string $q = '', int $limit = 15): array
    {
        $q = trim($q);
        $args = [];


        $where = " WHERE o.tipo_operacion_id = 11
               AND o.estatus_id IN (1,5,9,10) ";

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
                'total_bultos_maritimos' => array_sum(array_map(fn($x) => (int)$x['bultos'], $mar)),
            ];
        }
        return $out;
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
                LOWER(ofx.numero_operacion)          LIKE ?
             OR LOWER(o.numero_operacion)            LIKE ?
             OR LOWER(cm.numero_contenedor)          LIKE ?
             OR LOWER(cf.numero_ferro)               LIKE ?
             OR LOWER(COALESCE(cli.nombre,''))       LIKE ?
             OR LOWER(COALESCE(tr.nombre,''))        LIKE ?
             OR LOWER(COALESCE(cd.nombre_ciudad,'')) LIKE ?
           )"
        : "";

    if ($buscar) {
        array_push($argsBuscar, $needle, $needle, $needle, $needle, $needle, $needle, $needle);
    }

    // ---- Fechas (YYYY-MM-DD) sobre ofx.fecha ----
    $dateFrom = isset($filters['date_from']) && $filters['date_from'] !== '' ? $filters['date_from'] : null;
    $dateTo   = isset($filters['date_to'])   && $filters['date_to']   !== '' ? $filters['date_to']   : null;

    $argsDate  = [];
    $whereDate = "";
    if ($dateFrom !== null) { $whereDate .= " AND ofx.fecha >= ? "; $argsDate[] = $dateFrom; }
    if ($dateTo   !== null) { $whereDate .= " AND ofx.fecha <= ? "; $argsDate[] = $dateTo;   }

    // ---- Subquery: 1 fila por FX (operacion_ferro_id) ----
    $sub = "
        SELECT
            ofx.id_operacion_ferro                                        AS id_row,
            ofx.numero_operacion                                          AS numero_operacion,
            o.id_operacion                                                AS operacion_id,

            COALESCE(GROUP_CONCAT(DISTINCT cm.numero_contenedor
                                  ORDER BY cm.numero_contenedor
                                  SEPARATOR ', '), '')                     AS contenedores_maritimos,

            COALESCE(SUM(DISTINCT cmo.bultos), 0)                          AS bultos_maritimo,

            COALESCE(cli.nombre, '')                                       AS cliente,
            COALESCE(tr.nombre, '')                                        AS transportista,
            cf.numero_ferro                                                AS ferro,

            COALESCE(GROUP_CONCAT(
                CONCAT( cmf.bultos_asignados)
                ORDER BY cm.numero_contenedor
                SEPARATOR ' | '
            ), '')                                                         AS division_bultos,

            COALESCE(cd.nombre_ciudad, '')                                 AS destino,

            COALESCE(SUM(cmf.bultos_asignados), 0)                         AS bultos_asignados_total,

            ofx.fecha                                                      AS fecha_header
        FROM operaciones_ferroviarias ofx
        INNER JOIN contenedor_maritimo_ferro cmf
                ON cmf.operacion_ferro_id = ofx.id_operacion_ferro
        INNER JOIN contenedores_fisicos cf
                ON cf.id_fisico = ofx.contenedor_fisico_id
        INNER JOIN operaciones o
                ON o.id_operacion = cmf.operacion_id
        LEFT  JOIN clientes cli
                ON cli.id_cliente = o.cliente_id
        LEFT  JOIN transportistas tr
                ON tr.id_transportista = ofx.transportista_id
        LEFT  JOIN ciudades cd
                ON cd.id_ciudad = ofx.destino_id
        INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id
        INNER JOIN contenedores_maritimos_operacion cmo
                ON cmo.id = cmf.cont_maritimo_operacion_id
        WHERE ofx.estatus_id IN (1,5,9,10)
          AND o.estatus_id   IN (1,5,9,10)
          {$whereBusq}
          {$whereDate}
        GROUP BY
            ofx.id_operacion_ferro,
            ofx.numero_operacion,
            o.id_operacion,
            cliente,
            transportista,
            cf.numero_ferro,
            destino,
            ofx.fecha
    ";

    // ---- TOTAL ----
    $sqlCount = "SELECT COUNT(*) AS total FROM ({$sub}) AS t";
    $row   = $this->select($sqlCount, array_merge($argsBuscar, $argsDate));
    $total = (int)($row['total'] ?? 0);

    // ---- Paginación ----
    $per_page    = max(1, min($per_page, 200));
    $total_pages = max(1, (int)ceil($total / $per_page));
    $page        = max(1, min($page, $total_pages));
    $offset      = ($page - 1) * $per_page;

    // ---- DATA ----
    $sqlData = "{$sub}
                ORDER BY fecha_header DESC, numero_operacion DESC, ferro ASC
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



    public function sugerenciasTransportistas(string $q = '', int $limit = 15, array $tipos = ['ferroviario']): array
    {
        $q = trim($q);
        $limit = max(1, (int)$limit);

        // Siempre filtra por estatus; el resto se arma abajo
        $where = " WHERE estatus = 1 ";
        $args  = [];

        // Normaliza tipos a minúsculas
        $tipos = array_values(array_filter(array_map(function ($t) {
            return mb_strtolower(trim($t), 'UTF-8');
        }, $tipos)));

        // Aplica filtro por tipo SOLO si se solicitaron valores
        if (!empty($tipos)) {
            $in    = implode(',', array_fill(0, count($tipos), '?'));
            $where .= " AND LOWER(tipo) IN ($in) ";
            foreach ($tipos as $t) {
                $args[] = $t;
            }
        }

        // Filtro por texto (nombre)
        if ($q !== '') {
            $where .= " AND LOWER(nombre) LIKE ? ";
            $args[] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        $sql = "SELECT id_transportista AS id, nombre, tipo
              FROM transportistas
              {$where}
          ORDER BY nombre ASC
             LIMIT {$limit}";
        $rows = $this->selectAll($sql, $args) ?: [];

        return array_map(function ($r) {
            return [
                'id'    => (int)$r['id'],
                'label' => (string)$r['nombre'],
                'tipo'  => (string)$r['tipo'],
            ];
        }, $rows);
    }

    // En Operaciones_maritimo_ferro_contenedoresModel
    public function sugerenciasFerros(string $q = '', int $limit = 15): array
    {
        $q = trim($q);
        $limit = max(1, (int)$limit);

        $where = " WHERE estatus = 1 ";
        $args  = [];

        if ($q !== '') {
            $where .= " AND LOWER(numero_ferro) LIKE ? ";
            $args[] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        $sql = "SELECT id_fisico AS id, numero_ferro AS label
            FROM contenedores_fisicos
            {$where}
            ORDER BY numero_ferro ASC
            LIMIT {$limit}";

        return $this->selectAll($sql, $args) ?: [];
    }
    public function sugerenciasDestinos(string $q = '', int $limit = 15): array
    {
        $q = trim($q);
        $limit = max(1, (int)$limit);

        $where = " WHERE c.estatus = 1 ";
        $args  = [];

        if ($q !== '') {
            $where .= " AND (LOWER(c.nombre_ciudad) LIKE ?)";
            $args[] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        // Si quieres mostrar estado, puedes LEFT JOIN a 'estados'
        $sql = "SELECT 
                c.id_ciudad   AS id,
                c.nombre_ciudad AS ciudad
            FROM ciudades c
            {$where}
            ORDER BY c.nombre_ciudad ASC
            LIMIT {$limit}";
        $rows = $this->selectAll($sql, $args) ?: [];

        return array_map(fn($r) => [
            'id'    => (int)$r['id'],
            'label' => (string)$r['ciudad'],
        ], $rows);
    }

    //REGISTRAR
    public function registrarAsignacionFerro(array $in): array
    {
        // Espera en $in:
        // operacion_id, contenedor_maritimo_id, contenedor_fisico_id,
        // destino_id, transportista_id, bultos_asignados,
        // comentario (opcional), fecha (opcional, Y-m-d)
        $req = ['operacion_id', 'contenedor_maritimo_id', 'contenedor_fisico_id', 'destino_id', 'transportista_id', 'bultos_asignados'];
        foreach ($req as $k) {
            if (!isset($in[$k]) || $in[$k] === '' || $in[$k] === null) {
                return ['ok' => false, 'msg' => "Falta el campo requerido: {$k}"];
            }
        }

        $operacion_id         = (int)$in['operacion_id'];
        $cont_maritimo_id     = (int)$in['contenedor_maritimo_id'];
        $contenedor_fisico_id = (int)$in['contenedor_fisico_id'];
        $destino_id           = (int)$in['destino_id'];
        $transportista_id     = (int)$in['transportista_id'];
        $bultos_asignados     = (int)$in['bultos_asignados'];
        $comentario           = trim((string)($in['comentario'] ?? ''));
        $fecha                = !empty($in['fecha']) ? $in['fecha'] : date('Y-m-d');

        if ($bultos_asignados <= 0) {
            return ['ok' => false, 'msg' => 'Los bultos asignados deben ser mayores a 0.'];
        }

        // 1) Resolver el registro MG en la operación y sus bultos totales
        $rowMG = $this->select(
            "SELECT id, COALESCE(bultos,0) AS bultos
           FROM contenedores_maritimos_operacion
          WHERE operacion_id=? AND contenedor_maritimo_id=?
          LIMIT 1",
            [$operacion_id, $cont_maritimo_id]
        );
        if (!$rowMG) {
            return ['ok' => false, 'msg' => 'El contenedor marítimo no pertenece a la operación.'];
        }
        $cont_maritimo_operacion_id = (int)$rowMG['id'];
        $bultos_totales_mg          = (int)$rowMG['bultos'];

        // 2) Validar saldo disponible (lo ya asignado en el puente)
        $sumRow = $this->select(
            "SELECT COALESCE(SUM(bultos_asignados),0) AS asignados
           FROM contenedor_maritimo_ferro
          WHERE cont_maritimo_operacion_id=?",
            [$cont_maritimo_operacion_id]
        );
        $ya_asignados = (int)($sumRow['asignados'] ?? 0);
        $saldo = $bultos_totales_mg - $ya_asignados;
        if ($bultos_asignados > $saldo) {
            return ['ok' => false, 'msg' => "No hay saldo suficiente. Saldo disponible: {$saldo}."];
        }

        // 3) Upsert del HEADER en operaciones_ferroviarias (por FX + destino + transportista + fecha)
        $opFerro = $this->select(
            "SELECT id_operacion_ferro, numero_operacion
           FROM operaciones_ferroviarias
          WHERE contenedor_fisico_id=? AND destino_id=? AND transportista_id=? AND fecha=?
          LIMIT 1",
            [$contenedor_fisico_id, $destino_id, $transportista_id, $fecha]
        );

        $id_operacion_ferro = 0;
        $numero_operacion_ferro = '';
        $header_insertado_nuevo = false;

        if (!$opFerro) {
            // 3.1) Construir numero_operacion ferro: "<numero_op>-<numero_ferro>" (ej. LBMF-01-FX0101010)
            $opRow = $this->select(
                "SELECT numero_operacion FROM operaciones WHERE id_operacion=? LIMIT 1",
                [$operacion_id]
            );
            if (!$opRow) return ['ok' => false, 'msg' => 'Operación no encontrada.'];
            $numero_op_base = trim((string)$opRow['numero_operacion']);

            $fxRow = $this->select(
                "SELECT numero_ferro FROM contenedores_fisicos WHERE id_fisico=? LIMIT 1",
                [$contenedor_fisico_id]
            );
            if (!$fxRow) return ['ok' => false, 'msg' => 'Ferro/Caja no encontrada.'];
            $numero_ferro = trim((string)$fxRow['numero_ferro']);

            $numero_operacion_ferro = $numero_op_base . '-' . $numero_ferro;

            $id_operacion_ferro = (int)$this->insertar(
                "INSERT INTO operaciones_ferroviarias
               (numero_operacion, contenedor_fisico_id, destino_id, transportista_id, fecha, estatus_id, comentarios)
             VALUES (?,?,?,?,?, 9, ?)",
                [$numero_operacion_ferro, $contenedor_fisico_id, $destino_id, $transportista_id, $fecha, $comentario]
            );

            if ($id_operacion_ferro <= 0) {
                return ['ok' => false, 'msg' => 'No se pudo crear la operación ferroviaria (header).'];
            }
            $header_insertado_nuevo = true;
        } else {
            $id_operacion_ferro     = (int)$opFerro['id_operacion_ferro'];
            $numero_operacion_ferro = (string)$opFerro['numero_operacion'];

            // (Opcional) si viene comentario nuevo, lo anexamos
            if ($comentario !== '') {
                $this->save(
                    "UPDATE operaciones_ferroviarias
                    SET comentarios = CONCAT(COALESCE(comentarios,''), 
                                             CASE WHEN COALESCE(comentarios,'')='' THEN '' ELSE '\n' END,
                                             ?)
                  WHERE id_operacion_ferro=?",
                    [$comentario, $id_operacion_ferro]
                );
            }
        }

        // 4) Insert de la línea MG→FX en el puente
        $id_linea = (int)$this->insertar(
            "INSERT INTO contenedor_maritimo_ferro
           (operacion_ferro_id, contenedor_maritimo_id, cont_maritimo_operacion_id,
            contenedor_fisico_id, operacion_id, comentario, bultos_asignados)
         VALUES (?,?,?,?,?,?,?)",
            [
                $id_operacion_ferro,
                $cont_maritimo_id,
                $cont_maritimo_operacion_id,
                $contenedor_fisico_id,
                $operacion_id,
                $comentario,
                $bultos_asignados
            ]
        );

        if ($id_linea <= 0) {
            // intento de limpieza si acabamos de crear header
            if ($header_insertado_nuevo) {
                $this->save("DELETE FROM operaciones_ferroviarias WHERE id_operacion_ferro=?", [$id_operacion_ferro]);
            }
            return ['ok' => false, 'msg' => 'No se pudo registrar la asignación MG→FX.'];
        }

        // 5) Saldos actualizados para responder al UI
        $nuevo_sum = $ya_asignados + $bultos_asignados;
        $nuevo_saldo = max(0, $bultos_totales_mg - $nuevo_sum);

        return [
            'ok'        => true,
            'msg'       => 'Asignación registrada.',
            'ids'       => [
                'operacion_ferro_id'           => $id_operacion_ferro,
                'contenedor_maritimo_ferro_id' => $id_linea
            ],
            'numero_operacion_ferro' => $numero_operacion_ferro,
            'saldo'     => $nuevo_saldo
        ];
    }
    public function getSaldoMGByOperacionYMaritimo(int $operacion_id, int $contenedor_maritimo_id): array
    {
        $row = $this->select(
            "SELECT id, COALESCE(bultos,0) AS b_tot
           FROM contenedores_maritimos_operacion
          WHERE operacion_id=? AND contenedor_maritimo_id=?
          LIMIT 1",
            [$operacion_id, $contenedor_maritimo_id]
        );
        if (!$row) return ['total' => 0, 'asignados' => 0, 'saldo' => 0];

        $id_cmo = (int)$row['id'];
        $sum = $this->select(
            "SELECT COALESCE(SUM(bultos_asignados),0) AS b_asig
           FROM contenedor_maritimo_ferro
          WHERE cont_maritimo_operacion_id=?",
            [$id_cmo]
        );
        $tot  = (int)($row['b_tot'] ?? 0);
        $asig = (int)($sum['b_asig'] ?? 0);
        return ['total' => $tot, 'asignados' => $asig, 'saldo' => max(0, $tot - $asig)];
    }
}
