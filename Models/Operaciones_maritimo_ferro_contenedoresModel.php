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
    public function getSumaBultosPorOperacionFerro(int $operacion_ferro_id): int
    {
        $row = $this->select(
            "SELECT COALESCE(SUM(bultos_asignados),0) AS total
           FROM contenedor_maritimo_ferro
          WHERE operacion_ferro_id = ?",
            [$operacion_ferro_id]
        );
        return (int)($row['total'] ?? 0);
    }
    public function sugerenciasOperacionesMaritimasParaFerro(string $q = '', int $limit = 15): array
    {
        $q = trim($q);
        $args = [];
        $where = " WHERE o.estatus_id IN (1,5,9,10)  AND o.tipo_operacion_id IN(11)";

        if ($q !== '') {
            $where .= " AND (LOWER(o.numero_operacion) LIKE ?) ";
            $args[] = '%' . mb_strtolower($q, 'UTF-8') . '%';
        }

        $sql = "
        SELECT
            o.id_operacion,
            o.numero_operacion,
            cli.id_cliente,
            COALESCE(cli.nombre,'') AS cliente,
            cmo.id                  AS cmo_id,
            cmo.contenedor_maritimo_id,
            cm.numero_contenedor,
            COALESCE(cmo.bultos,0)  AS bultos_totales,
            -- asignados a ferros
            COALESCE((
                SELECT SUM(cmf.bultos_asignados)
                  FROM contenedor_maritimo_ferro cmf
                 WHERE cmf.cont_maritimo_operacion_id = cmo.id
            ),0) AS bultos_asignados
        FROM operaciones o
        INNER JOIN clientes cli ON cli.id_cliente = o.cliente_id
        INNER JOIN contenedores_maritimos_operacion cmo
                ON cmo.operacion_id = o.id_operacion
        INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        {$where}
        ORDER BY o.id_operacion DESC
        LIMIT " . max(1, (int)$limit);

        $rows = $this->selectAll($sql, $args) ?: [];
        // añade campo restantes y acomoda para el front
        return array_map(function ($r) {
            $tot = (int)$r['bultos_totales'];
            $asig = (int)$r['bultos_asignados'];
            return [
                'operacion_id'           => (int)$r['id_operacion'],
                'numero_operacion'       => (string)$r['numero_operacion'],
                'cliente_id'             => (int)$r['id_cliente'],
                'cliente'                => (string)$r['cliente'],
                'cmo_id'                 => (int)$r['cmo_id'],
                'contenedor_maritimo_id' => (int)$r['contenedor_maritimo_id'],
                'numero_contenedor'      => (string)$r['numero_contenedor'],
                'bultos_totales'         => $tot,
                'bultos_asignados'       => $asig,
                'bultos_restantes'       => max(0, $tot - $asig),
            ];
        }, $rows);
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
        if ($dateFrom !== null) {
            $whereDate .= " AND ofx.fecha >= ? ";
            $argsDate[] = $dateFrom;
        }
        if ($dateTo   !== null) {
            $whereDate .= " AND ofx.fecha <= ? ";
            $argsDate[] = $dateTo;
        }

        // ---- Subquery: 1 fila por FX (operacion_ferro_id) ----
        $sub = "
            SELECT
                ofx.id_operacion_ferro                                        AS id_row,
                ofx.numero_operacion                                          AS numero_operacion,

                o.id_operacion                                                AS operacion_id,

                COALESCE(GROUP_CONCAT(DISTINCT cm.numero_contenedor
                                    ORDER BY cm.numero_contenedor
                                    SEPARATOR ', '), '')                     AS contenedores_maritimos,

                COALESCE(SUM(cmo.bultos), 0)                                  AS bultos_maritimo,

                COALESCE(cli.nombre, '')                                      AS cliente,
                COALESCE(tr.nombre, '')                                       AS transportista,
                cf.numero_ferro                                               AS ferro,

                COALESCE(GROUP_CONCAT(
                    cmf.bultos_asignados
                    ORDER BY cm.numero_contenedor
                    SEPARATOR ' | '
                ), '')                                                         AS division_bultos,

                COALESCE(cd.nombre_ciudad, '')                                AS destino,

                COALESCE(SUM(cmf.bultos_asignados), 0)                        AS bultos_asignados_total,

                ofx.fecha                                                     AS fecha_header,

                COALESCE(es.nombre, '')                                       AS estatus   

            FROM operaciones_ferroviarias ofx
            INNER JOIN contenedor_maritimo_ferro cmf
                    ON cmf.operacion_ferro_id = ofx.id_operacion_ferro
            INNER JOIN contenedores_fisicos cf
                    ON cf.id_fisico = ofx.contenedor_fisico_id
            INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id
            INNER JOIN contenedores_maritimos_operacion cmo
                    ON cmo.id = cmf.cont_maritimo_operacion_id
            INNER JOIN operaciones o
                    ON o.id_operacion = cmo.operacion_id
            LEFT  JOIN clientes cli
                    ON cli.id_cliente = o.cliente_id
            LEFT  JOIN transportistas tr
                    ON tr.id_transportista = ofx.transportista_id
            LEFT  JOIN ciudades cd
                    ON cd.id_ciudad = ofx.destino_id
            LEFT  JOIN estatus es
                    ON es.id_estatus = ofx.estatus_id               
            WHERE ofx.estatus_id IN (1,5,6,7,9,10,11,12,13)
            AND o.estatus_id   IN (1,5,6,7,9,10,11,12,13)
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
                ofx.fecha,
                es.nombre                                            
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



    public function sugerenciasTransportistas(string $q = '', int $limit = 15, array $tipos = ['ferroviario', 'terrestre']): array
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
        // Espera:
        // contenedor_fisico_id, destino_id, transportista_id, fecha (Y-m-d),
        // estatus_id (opcional, default 9), comentario (opcional), creado_por (opcional),
        // asignaciones: [ { cmo_id, bultos_asignados, comentario? }, ... ]
        $reqH = ['contenedor_fisico_id', 'destino_id', 'transportista_id', 'fecha', 'asignaciones'];
        foreach ($reqH as $k) {
            if (!isset($in[$k]) || $in[$k] === '' || $in[$k] === null) {
                return ['ok' => false, 'msg' => "Falta el campo requerido: {$k}"];
            }
        }

        $contenedor_fisico_id = (int)$in['contenedor_fisico_id'];
        $destino_id           = (int)$in['destino_id'];
        $transportista_id     = (int)$in['transportista_id'];
        $fecha                = (string)$in['fecha'];
        $estatus_id           = isset($in['estatus_id']) ? (int)$in['estatus_id'] : 9; // Abierta
        $comentario_header    = trim((string)($in['comentario'] ?? ''));
        $creado_por           = isset($in['creado_por']) ? (int)$in['creado_por'] : null;

        // Lista de asignaciones
        $asignaciones = is_array($in['asignaciones']) ? $in['asignaciones'] : [];
        if (empty($asignaciones)) {
            return ['ok' => false, 'msg' => 'Debes enviar al menos una asignación.'];
        }

        // === 1) Derivar CLIENTE desde la PRIMERA asignación (cmo_id) ===
        $prim = $asignaciones[0];
        if (!isset($prim['cmo_id'])) return ['ok' => false, 'msg' => 'Falta cmo_id en la primera asignación.'];

        $cliRow = $this->select(
            "SELECT o.cliente_id
           FROM contenedores_maritimos_operacion cmo
           INNER JOIN operaciones o ON o.id_operacion = cmo.operacion_id
          WHERE cmo.id = ?
          LIMIT 1",
            [(int)$prim['cmo_id']]
        );
        $cliente_id = (int)($cliRow['cliente_id'] ?? 0);

        // === 2) Crear HEADER de operación ferroviaria con número FO-## ===
        // Config catálogo (ajústalo si cambia)
        $subtipo_fo_id    = 26; // Subtipo FO
        $tipo_operacion_id = 2;  // Tipo operación para FO

        // Generar número con SP: CALL GenerarNumeroOperacionFerro(subtipo, @p); SELECT @p
        // Usamos save/select porque tu Query no expone transacciones ni múltiples resultados.
        $this->save("CALL GenerarNumeroOperacionFerro(?, @p_num)", [$subtipo_fo_id]);
        $outNum = $this->select("SELECT @p_num AS numero");
        $numero_fo = trim((string)($outNum['numero'] ?? ''));

        if ($numero_fo === '') {
            return ['ok' => false, 'msg' => 'No se pudo generar el número de operación FO-##.'];
        }

        $id_operacion_ferro = (int)$this->insertar(
            "INSERT INTO operaciones_ferroviarias
           (numero_operacion, contenedor_fisico_id, destino_id, transportista_id, fecha,
            estatus_id, comentarios, cliente_id, tipo_operacion_id, subtipo_operacion_id, creado_por, bultos_total)
         VALUES (?,?,?,?,?,?,?,?,?,?,?, 0)",
            [
                $numero_fo,
                $contenedor_fisico_id,
                $destino_id,
                $transportista_id,
                $fecha,
                $estatus_id,
                $comentario_header !== '' ? $comentario_header : null,
                $cliente_id ?: null,
                $tipo_operacion_id,
                $subtipo_fo_id,
                $creado_por
            ]
        );
        if ($id_operacion_ferro <= 0) {
            return ['ok' => false, 'msg' => 'No se pudo crear la operación ferroviaria.'];
        }


        // === 3) Insertar LÍNEAS con VALIDACIÓN ATÓMICA (anti-concurrencia) ===
        $total_bultos = 0;

        // 🚀 Iniciar transacción manualmente
        $this->save("START TRANSACTION", []);

        try {
            foreach ($asignaciones as $i => $a) {
                $cmo_id = (int)($a['cmo_id'] ?? 0);
                $cant   = (int)($a['bultos_asignados'] ?? 0);
                $comLin = trim((string)($a['comentario'] ?? ''));

                if ($cmo_id <= 0 || $cant <= 0) {
                    $this->save("ROLLBACK", []);
                    return ['ok' => false, 'msg' => "Asignación #" . ($i + 1) . " inválida (cmo_id/cantidad)."];
                }

                // 🔥 Obtener contenedor_maritimo_id
                $row = $this->select(
                    "SELECT cmo.contenedor_maritimo_id
               FROM contenedores_maritimos_operacion cmo
              WHERE cmo.id = ?
              LIMIT 1",
                    [$cmo_id]
                );

                if (!$row) {
                    $this->save("ROLLBACK", []);
                    return ['ok' => false, 'msg' => "CMO #{$cmo_id} no encontrado."];
                }

                // 🚀 INSERTAR CON VALIDACIÓN ATÓMICA
                $resultado = $this->insertarLineaConValidacion(
                    $id_operacion_ferro,
                    $cmo_id,
                    (int)$row['contenedor_maritimo_id'],
                    $contenedor_fisico_id,
                    $cant,
                    $comLin !== '' ? $comLin : null
                );

                // ❌ Si falló la validación, hacer ROLLBACK y detener
                if (!$resultado['ok']) {
                    $this->save("ROLLBACK", []);
                    return [
                        'ok' => false,
                        'msg' => "Asignación #" . ($i + 1) . ": " . $resultado['msg'],
                        'detalles' => $resultado
                    ];
                }

                $total_bultos += $cant;
            }

            // ✅ Si todo salió bien, confirmar transacción
            $this->save("COMMIT", []);
        } catch (Exception $e) {
            $this->save("ROLLBACK", []);
            error_log("Error en registrarAsignacionFerro: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error inesperado al procesar las asignaciones.'];
        }

        // === 4) Actualizar totales del HEADER (nivel código, no trigger) ===
        $this->save(
            "UPDATE operaciones_ferroviarias
            SET bultos_total = COALESCE((
                    SELECT SUM(cmf.bultos_asignados)
                      FROM contenedor_maritimo_ferro cmf
                     WHERE cmf.operacion_ferro_id = ?
                ),0)
          WHERE id_operacion_ferro = ?",
            [$id_operacion_ferro, $id_operacion_ferro]
        );

        return [
            'ok'   => true,
            'msg'  => 'Operación ferroviaria creada y asignaciones registradas.',
            'ids'  => [
                'operacion_ferro_id' => $id_operacion_ferro,
            ],
            'numero_operacion_ferro' => $numero_fo,
            'total_bultos' => $total_bultos
        ];
    }



    /**
     * Saldo por ID del registro en contenedores_maritimos_operacion (cmo.id)
     * Devuelve: [ok, id_cmo, operacion_id, contenedor_maritimo_id, numero_contenedor, total, asignados, saldo]
     */
    public function getSaldoPorCmoId(int $cmo_id): array
    {
        // Total de bultos y datos del MG
        $row = $this->select(
            "SELECT 
             cmo.id,
             cmo.operacion_id,
             cmo.contenedor_maritimo_id,
             COALESCE(cmo.bultos,0)              AS b_tot,
             cm.numero_contenedor
         FROM contenedores_maritimos_operacion cmo
         INNER JOIN contenedores_maritimos cm
                 ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        WHERE cmo.id = ?
        LIMIT 1",
            [$cmo_id]
        );
        if (!$row) return ['ok' => false, 'msg' => 'MG no encontrado'];

        // Suma asignada a ferros desde ese MG
        $sum = $this->select(
            "SELECT COALESCE(SUM(bultos_asignados),0) AS b_asig
           FROM contenedor_maritimo_ferro
          WHERE cont_maritimo_operacion_id = ?",
            [$cmo_id]
        );

        $tot  = (int)($row['b_tot'] ?? 0);
        $asig = (int)($sum['b_asig'] ?? 0);
        $saldo = max(0, $tot - $asig);

        return [
            'ok'                     => true,
            'id_cmo'                 => (int)$row['id'],
            'operacion_id'           => (int)$row['operacion_id'],
            'contenedor_maritimo_id' => (int)$row['contenedor_maritimo_id'],
            'numero_contenedor'      => (string)$row['numero_contenedor'],
            'total'                  => $tot,
            'asignados'              => $asig,
            'saldo'                  => $saldo,
        ];
    }

    /**
     * Listado de saldos por operación (todas las filas de contenedores_maritimos_operacion de esa operación)
     * Útil para pintar la vista: MG, total, asignados, saldo.
     */
    public function listarSaldosMGPorOperacion(int $operacion_id): array
    {
        $sql = "
    SELECT 
        cmo.id                                         AS id_cmo,
        cmo.operacion_id,
        cmo.contenedor_maritimo_id,
        cm.numero_contenedor,
        COALESCE(cmo.bultos,0)                         AS bultos_totales,
        COALESCE(SUM(cmf.bultos_asignados),0)          AS bultos_asignados,
        (COALESCE(cmo.bultos,0) - COALESCE(SUM(cmf.bultos_asignados),0)) AS bultos_restantes
    FROM contenedores_maritimos_operacion cmo
    INNER JOIN contenedores_maritimos cm
            ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
    LEFT JOIN contenedor_maritimo_ferro cmf
           ON cmf.cont_maritimo_operacion_id = cmo.id
    WHERE cmo.operacion_id = ?
    GROUP BY cmo.id, cmo.operacion_id, cmo.contenedor_maritimo_id, cm.numero_contenedor, cmo.bultos
    ORDER BY cm.numero_contenedor ASC";
        return $this->selectAll($sql, [$operacion_id]) ?: [];
    }
    // === Helpers nuevos en el modelo ===
    private function normalizarNumeroFerro(string $num): string
    {
        $num = trim($num);
        // Mayúsculas, sin espacios dobles
        $num = mb_strtoupper(preg_replace('/\s+/', ' ', $num), 'UTF-8');
        return $num;
    }

    public function getFerroPorNumero(string $numero): ?array
    {
        $numero = $this->normalizarNumeroFerro($numero);
        $row = $this->select(
            "SELECT id_fisico, numero_ferro 
           FROM contenedores_fisicos 
          WHERE LOWER(numero_ferro) = LOWER(?) 
          LIMIT 1",
            [$numero]
        );
        return $row ?: null;
    }

    public function crearFerro(string $numero): array
    {
        $numero = $this->normalizarNumeroFerro($numero);

        if ($numero === '' || mb_strlen($numero) < 2) {
            return ['ok' => false, 'msg' => 'Número de ferro/caja inválido.'];
        }
        // Regla básica de formato; ajusta a tu gusto
        if (!preg_match('/^[A-Z0-9-_.]{2,30}$/', $numero)) {
            return ['ok' => false, 'msg' => 'Formato de ferro/caja no permitido.'];
        }

        // Unicidad lógica
        $ex = $this->getFerroPorNumero($numero);
        if ($ex) {
            return ['ok' => true, 'id_fisico' => (int)$ex['id_fisico'], 'label' => $ex['numero_ferro'], 'created' => false];
        }

        $id = (int)$this->insertar(
            "INSERT INTO contenedores_fisicos (numero_ferro, estatus) VALUES (?, 1)",
            [$numero]
        );
        if ($id <= 0) return ['ok' => false, 'msg' => 'No se pudo crear el ferro/caja.'];

        return ['ok' => true, 'id_fisico' => $id, 'label' => $numero, 'created' => true];
    }

    /**
     * Crea si no existe; si existe regresa el existente.
     */
    public function upsertFerro(string $numero): array
    {
        $numero = $this->normalizarNumeroFerro($numero);
        $ex = $this->getFerroPorNumero($numero);
        if ($ex) {
            return ['ok' => true, 'id_fisico' => (int)$ex['id_fisico'], 'label' => $ex['numero_ferro'], 'created' => false];
        }
        return $this->crearFerro($numero);
    }
    // === Helpers para número FO (preview) ===
    private function getPrefijoSubtipo(int $subtipo_id): string
    {
        $row = $this->select(
            "SELECT COALESCE(prefijo_codigo,'') AS prefijo
           FROM subtipos_operacion
          WHERE id_subtipo = ?
          LIMIT 1",
            [$subtipo_id]
        );
        return trim((string)($row['prefijo'] ?? ''));
    }

    /**
     * Calcula el siguiente número tipo "FO-01" sin tocar el consecutivo real.
     * Útil solo para mostrar en el front; el definitivo lo genera el SP en registrarAsignacionFerro().
     */
    public function previewNumeroOperacionFerro(int $subtipo_id = 26): array
    {
        // 1) prefijo del subtipo (ej. 'FO')
        $prefijo = $this->getPrefijoSubtipo($subtipo_id);
        if ($prefijo === '') {
            return ['ok' => false, 'msg' => 'Prefijo no definido para el subtipo.'];
        }

        // 2) calcular el siguiente consecutivo a partir de operaciones ya creadas
        //    Busca el máximo sufijo numérico de "FO-##"
        $row = $this->select(
            "SELECT 
            COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_operacion, '-', -1) AS UNSIGNED)), 0) AS max_seq
         FROM operaciones_ferroviarias
         WHERE numero_operacion LIKE CONCAT(?, '-%')",
            [$prefijo]
        );
        $next = (int)($row['max_seq'] ?? 0) + 1;

        // 3) formateo (02 dígitos, ajusta si quieres 3+)
        $num = sprintf('%s-%02d', $prefijo, $next);

        return ['ok' => true, 'numero' => $num];
    }


    //editar

    public function actualizarEstatusYLineasOperacionFerro(int $operacion_ferro_id, array $in): array
    {
        // 0) Verificar que exista la operación y leer valores que NO podremos cambiar
        $hdr = $this->select("
        SELECT id_operacion_ferro, numero_operacion, estatus_id, comentarios,
               contenedor_fisico_id
        FROM operaciones_ferroviarias
        WHERE id_operacion_ferro = ?
        LIMIT 1
    ", [$operacion_ferro_id]);
        if (!$hdr) return ['ok' => false, 'msg' => 'Operación ferroviaria no encontrada.'];

        $contenedor_fisico_id = (int)$hdr['contenedor_fisico_id']; // se conserva (no editable)

        // 1) Normalizar inputs permitidos
        $estatus_id  = array_key_exists('estatus_id', $in) ? (int)$in['estatus_id'] : (int)$hdr['estatus_id'];
        $comentarios = isset($in['comentarios']) ? trim((string)$in['comentarios']) : (string)$hdr['comentarios'];
        $usuario_id  = isset($in['actualizado_por']) ? (int)$in['actualizado_por'] : null;

        $lineas = isset($in['lineas']) && is_array($in['lineas']) ? $in['lineas'] : [];

        // Si el front manda líneas, que el total sea > 0
        if (!empty($lineas)) {
            $total = 0;
            foreach ($lineas as $i => $a) {
                $cant = (int)($a['bultos_asignados'] ?? 0);
                if ($cant < 0) return ['ok' => false, 'msg' => "Línea #" . ($i + 1) . ": bultos inválidos."];
                $total += $cant;
            }
            if ($total <= 0) return ['ok' => false, 'msg' => 'El total de bultos debe ser > 0.'];
        }

        // 2) Transacción
        $this->save("START TRANSACTION", []);
        try {
            // 2.1) Actualizar SOLO estatus y comentarios (nada más)
            $okH = $this->save("
            UPDATE operaciones_ferroviarias
            SET estatus_id = ?,
                comentarios = ?,
                updated_at = NOW()
            WHERE id_operacion_ferro = ?
            LIMIT 1
        ", [
                $estatus_id,
                ($comentarios !== '' ? $comentarios : null),
                $operacion_ferro_id
            ]);

            // Si $okH es false => error de SQL. Si es 0 o 1, continuamos (0 = sin cambios efectivos)
            if ($okH === false) {
                $this->save("ROLLBACK", []);
                return ['ok' => false, 'msg' => 'No fue posible actualizar estatus/comentarios.'];
            }

            // 2.2) Reemplazar líneas SOLO si el front envió el arreglo
            //     (si no manda 'lineas', conservamos las existentes)
            if (is_array($lineas)) {
                // Eliminar líneas actuales
                $this->save("
                DELETE FROM contenedor_maritimo_ferro
                WHERE operacion_ferro_id = ?
            ", [$operacion_ferro_id]);

                // Reinsertar validando saldos CMO
                $totB = 0;
                $acumPorCmo = []; // evita doble uso de saldo si el mismo CMO aparece repetido

                foreach ($lineas as $i => $a) {
                    $cmo_id = (int)($a['cmo_id'] ?? 0);
                    $cant   = (int)($a['bultos_asignados'] ?? 0);
                    $comLin = isset($a['comentario']) ? trim((string)$a['comentario']) : null;

                    if ($cmo_id <= 0 || $cant < 0) {
                        $this->save("ROLLBACK", []);
                        return ['ok' => false, 'msg' => "Línea #" . ($i + 1) . ": datos inválidos."];
                    }
                    if ($cant === 0) continue; // omite líneas 0

                    // Datos base del CMO
                    $row = $this->select("
                    SELECT cmo.id, cmo.contenedor_maritimo_id, COALESCE(cmo.bultos,0) AS b_tot
                    FROM contenedores_maritimos_operacion cmo
                    WHERE cmo.id = ?
                    LIMIT 1
                ", [$cmo_id]);
                    if (!$row) {
                        $this->save("ROLLBACK", []);
                        return ['ok' => false, 'msg' => "Línea #" . ($i + 1) . ": CMO no encontrado."];
                    }

                    // Asignado en otras FO (excluimos esta, porque borramos sus líneas)
                    $sumOtras = $this->select("
                    SELECT COALESCE(SUM(bultos_asignados),0) AS b_asig
                      FROM contenedor_maritimo_ferro
                     WHERE cont_maritimo_operacion_id = ?
                       AND operacion_ferro_id <> ?
                ", [$cmo_id, $operacion_ferro_id]);
                    $asigOtras = (int)($sumOtras['b_asig'] ?? 0);

                    if (!isset($acumPorCmo[$cmo_id])) $acumPorCmo[$cmo_id] = 0;

                    $tot   = (int)$row['b_tot'];
                    $saldo = max(0, $tot - $asigOtras - $acumPorCmo[$cmo_id]);

                    if ($cant > $saldo) {
                        $this->save("ROLLBACK", []);
                        return ['ok' => false, 'msg' => "Línea #" . ($i + 1) . ": saldo insuficiente. Disponible: {$saldo}."];
                    }

                    // Insertar línea
                    $idLin = (int)$this->insertar("
                    INSERT INTO contenedor_maritimo_ferro
                        (operacion_ferro_id, contenedor_maritimo_id, cont_maritimo_operacion_id,
                         contenedor_fisico_id, comentario, bultos_asignados)
                    VALUES (?,?,?,?,?,?)
                ", [
                        $operacion_ferro_id,
                        (int)$row['contenedor_maritimo_id'],
                        $cmo_id,
                        $contenedor_fisico_id,
                        ($comLin !== '' ? $comLin : null),
                        $cant
                    ]);
                    if ($idLin <= 0) {
                        $this->save("ROLLBACK", []);
                        return ['ok' => false, 'msg' => "Línea #" . ($i + 1) . ": no se pudo guardar."];
                    }

                    $acumPorCmo[$cmo_id] += $cant;
                    $totB += $cant;
                }

                // Recalcular total de bultos del header
                $this->save("
                UPDATE operaciones_ferroviarias
                   SET bultos_total = COALESCE((
                        SELECT SUM(bultos_asignados)
                          FROM contenedor_maritimo_ferro
                         WHERE operacion_ferro_id = ?
                   ),0)
                 WHERE id_operacion_ferro = ?
            ", [$operacion_ferro_id, $operacion_ferro_id]);
            }

            $this->save("COMMIT", []);
            return ['ok' => true, 'msg' => 'Operación actualizada.', 'data' => ['operacion_ferro_id' => $operacion_ferro_id]];
        } catch (Throwable $e) {
            $this->save("ROLLBACK", []);
            error_log('Actualizar FO (estatus/lineas) error: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error inesperado al actualizar la operación.'];
        }
    }
    // Busca el id_operacion_ferro por folio (FO-##)
    public function findOperacionFerroIdByNumero(string $numero): ?int
    {
        $row = $this->select(
            "SELECT id_operacion_ferro 
           FROM operaciones_ferroviarias 
          WHERE numero_operacion = ?
          LIMIT 1",
            [trim($numero)]
        );
        return $row ? (int)$row['id_operacion_ferro'] : null;
    }

    /**
     * Devuelve la operación ferroviaria lista para editar:
     * - header: datos generales (estatus, comentarios, ferro, transportista, destino, fecha, etc.)
     * - lineas: las líneas actuales (cmo_id, contenedor, bultos_asignados, comentario)
     */
    public function getOperacionFerroEditable(int $operacion_ferro_id): array
    {
        // HEADER
        $hdr = $this->select("
        SELECT 
            ofx.id_operacion_ferro     AS id,
            ofx.numero_operacion       AS numero,
            ofx.estatus_id,
            COALESCE(es.nombre,'')     AS estatus_nombre,
            ofx.comentarios,
            ofx.fecha,
            ofx.contenedor_fisico_id,
            cf.numero_ferro,
            ofx.transportista_id,
            COALESCE(tr.nombre,'')     AS transportista,
            ofx.destino_id,
            COALESCE(cd.nombre_ciudad,'') AS destino,
            COALESCE(ofx.bultos_total,0)  AS bultos_total
        FROM operaciones_ferroviarias ofx
        LEFT JOIN estatus es       ON es.id_estatus = ofx.estatus_id
        LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = ofx.contenedor_fisico_id
        LEFT JOIN transportistas tr      ON tr.id_transportista = ofx.transportista_id
        LEFT JOIN ciudades cd            ON cd.id_ciudad = ofx.destino_id
        WHERE ofx.id_operacion_ferro = ?
        LIMIT 1
    ", [$operacion_ferro_id]);

        if (!$hdr) return ['ok' => false, 'msg' => 'Operación no encontrada.'];

        // LÍNEAS (las asignaciones actuales)
        // LÍNEAS (las asignaciones actuales)
        $lineas = $this->selectAll("
        SELECT 
            cmf.id                         AS id_linea,
            cmf.cont_maritimo_operacion_id AS cmo_id,
            cmf.contenedor_maritimo_id,
            cm.numero_contenedor,
            cmf.bultos_asignados,
            cmf.comentario,

            -- 👇 NUEVO: info de la operación marítima
            cmo.operacion_id,
            o.numero_operacion             AS numero_operacion_maritima,
            COALESCE(cli.nombre,'')        AS cliente

        FROM contenedor_maritimo_ferro cmf
        INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id
        INNER JOIN contenedores_maritimos_operacion cmo
                ON cmo.id = cmf.cont_maritimo_operacion_id
        INNER JOIN operaciones o
                ON o.id_operacion = cmo.operacion_id
        LEFT  JOIN clientes cli
                ON cli.id_cliente = o.cliente_id
        WHERE cmf.operacion_ferro_id = ?
        ORDER BY cm.numero_contenedor
    ", [$operacion_ferro_id]) ?: [];


        // (Opcional) puedes anexar el “saldo disponible” de cada CMO para ayudar en la UI
        // calculándolo contra otras FO:
        foreach ($lineas as &$ln) {
            $cmoId = (int)$ln['cmo_id'];

            // total del CMO
            $rowTot = $this->select("
            SELECT COALESCE(cmo.bultos,0) AS total
              FROM contenedores_maritimos_operacion cmo
             WHERE cmo.id = ?
             LIMIT 1
        ", [$cmoId]);
            $tot = (int)($rowTot['total'] ?? 0);

            // ya asignado en otras FO (excluye esta FO)
            $rowAsig = $this->select("
            SELECT COALESCE(SUM(bultos_asignados),0) AS asignado_otras
              FROM contenedor_maritimo_ferro
             WHERE cont_maritimo_operacion_id = ?
               AND operacion_ferro_id <> ?
        ", [$cmoId, $operacion_ferro_id]);
            $asigOtras = (int)($rowAsig['asignado_otras'] ?? 0);

            // más lo que ya tiene esta FO (la propia línea)
            $asigEsta = (int)$ln['bultos_asignados'];

            $ln['cmo_total']        = $tot;
            $ln['cmo_asignado_otras'] = $asigOtras;
            $ln['cmo_saldo_actual'] = max(0, $tot - $asigOtras); // lo disponible para (re)distribuir dentro de esta FO
        }
        unset($ln);

        return [
            'ok'     => true,
            'header' => [
                'operacion_ferro_id'  => (int)$hdr['id'],
                'numero'              => (string)$hdr['numero'],
                'estatus_id'          => (int)$hdr['estatus_id'],
                'estatus'             => (string)$hdr['estatus_nombre'],
                'comentarios'         => (string)($hdr['comentarios'] ?? ''),
                'fecha'               => (string)$hdr['fecha'],
                'contenedor_fisico_id' => (int)$hdr['contenedor_fisico_id'],
                'numero_ferro'        => (string)$hdr['numero_ferro'],
                'transportista_id'    => (int)$hdr['transportista_id'],
                'transportista'       => (string)$hdr['transportista'],
                'destino_id'          => (int)$hdr['destino_id'],
                'destino'             => (string)$hdr['destino'],
                'bultos_total'        => (int)$hdr['bultos_total'],
            ],
            'lineas' => array_map(function ($r) {
                return [
                    'id_linea'               => (int)$r['id_linea'],
                    'cmo_id'                 => (int)$r['cmo_id'],
                    'contenedor_maritimo_id' => (int)$r['contenedor_maritimo_id'],
                    'numero_contenedor'      => (string)$r['numero_contenedor'],
                    'bultos_asignados'       => (int)$r['bultos_asignados'],
                    'comentario'             => (string)($r['comentario'] ?? ''),

                    // 👇 NUEVOS (para que el front muestre el folio marítimo y cliente)
                    'operacion_id'              => isset($r['operacion_id']) ? (int)$r['operacion_id'] : 0,
                    'numero_operacion_maritima' => isset($r['numero_operacion_maritima']) ? (string)$r['numero_operacion_maritima'] : '',
                    'cliente'                   => isset($r['cliente']) ? (string)$r['cliente'] : '',

                    // opcionales que ya calculas luego
                    'cmo_total'              => isset($r['cmo_total']) ? (int)$r['cmo_total'] : null,
                    'cmo_asignado_otras'     => isset($r['cmo_asignado_otras']) ? (int)$r['cmo_asignado_otras'] : null,
                    'cmo_saldo_actual'       => isset($r['cmo_saldo_actual']) ? (int)$r['cmo_saldo_actual'] : null,
                ];
            }, $lineas),

        ];
    }


    /**
     * 🔒 Insertar línea con validación atómica (anti-concurrencia)
     * Usa transacción + FOR UPDATE para evitar race conditions
     */
    private function insertarLineaConValidacion(
        int $operacion_ferro_id,
        int $cmo_id,
        int $contenedor_maritimo_id,
        int $contenedor_fisico_id,
        int $bultos_asignar,
        ?string $comentario = null
    ): array {
        try {
            // 🔒 BLOQUEO PESIMISTA: Lee y bloquea el CMO
            $sqlCheck = "
            SELECT 
                cmo.id,
                COALESCE(cmo.bultos, 0) AS bultos_totales,
                COALESCE((
                    SELECT SUM(cmf.bultos_asignados)
                    FROM contenedor_maritimo_ferro cmf
                    WHERE cmf.cont_maritimo_operacion_id = cmo.id
                ), 0) AS bultos_asignados
            FROM contenedores_maritimos_operacion cmo
            WHERE cmo.id = ?
            FOR UPDATE
        ";

            $resultado = $this->select($sqlCheck, [$cmo_id]);

            if (!$resultado) {
                return [
                    'ok' => false,
                    'msg' => 'La operación marítima (CMO) no existe.',
                    'code' => 'CMO_NOT_FOUND'
                ];
            }

            // ✅ Calcular bultos disponibles
            $bultosDisponibles = (int)$resultado['bultos_totales'] - (int)$resultado['bultos_asignados'];

            // ❌ Validar si hay suficientes bultos
            if ($bultos_asignar > $bultosDisponibles) {
                return [
                    'ok' => false,
                    'msg' => "Solo hay {$bultosDisponibles} bultos disponibles. Otro usuario pudo haber asignado bultos mientras trabajabas.",
                    'code' => 'INSUFFICIENT_BULTOS',
                    'disponibles' => $bultosDisponibles,
                    'solicitados' => $bultos_asignar
                ];
            }

            // 💾 Insertar la línea (bultos validados)
            $id_linea = (int)$this->insertar(
                "INSERT INTO contenedor_maritimo_ferro
               (operacion_ferro_id, contenedor_maritimo_id, cont_maritimo_operacion_id,
                contenedor_fisico_id, comentario, bultos_asignados)
             VALUES (?,?,?,?,?,?)",
                [
                    $operacion_ferro_id,
                    $contenedor_maritimo_id,
                    $cmo_id,
                    $contenedor_fisico_id,
                    $comentario,
                    $bultos_asignar
                ]
            );

            if ($id_linea <= 0) {
                return [
                    'ok' => false,
                    'msg' => 'Error al insertar la asignación.',
                    'code' => 'INSERT_FAILED'
                ];
            }

            return [
                'ok' => true,
                'msg' => 'Asignación guardada exitosamente.',
                'id' => $id_linea,
                'bultos_restantes' => $bultosDisponibles - $bultos_asignar
            ];
        } catch (PDOException $e) {
            error_log("Error en insertarLineaConValidacion: " . $e->getMessage());
            return [
                'ok' => false,
                'msg' => 'Error inesperado en la base de datos.',
                'code' => 'DB_ERROR',
                'error' => $e->getMessage()
            ];
        }
    }
}
