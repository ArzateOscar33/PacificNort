<?php
class Operaciones_maritimo_ferro_resumenModel extends Query
{
    /**
     * Autocomplete de operaciones para el módulo Marítimo-Ferroviario y Terrestre.
     * Incluye operaciones con tipo_operacion_id IN (11, 2):
     *  - 11 = Maritimo-Ferroviario
     *  - 2  = Terrestre
     */
   public function buscarOperacionesConContenedores(string $term): array
{
    // Normaliza el término de búsqueda
    $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

    $sql = "
        /* SUGERENCIAS sin duplicados:
           - OP: operaciones (LBMF / LBS / etc.)
           - FO: operaciones_ferroviarias (FO-xx)
        */
        SELECT 
          o.id_operacion     AS id,
          o.numero_operacion AS numero,
          cl.nombre          AS cliente,
          CONCAT(o.numero_operacion, ' — ', cl.nombre) AS label,
          'OP'               AS origen,
          o.tipo_operacion_id
        FROM operaciones o
        JOIN clientes cl ON cl.id_cliente = o.cliente_id
        WHERE 
          o.tipo_operacion_id IN (11, 2)  -- 11 = Marítimo/Ferro, 2 = Terrestre
          AND (
            LOWER(o.numero_operacion) LIKE CONCAT('%', LOWER(?), '%')
            OR LOWER(cl.nombre)       LIKE CONCAT('%', LOWER(?), '%')
          )

        UNION ALL

        SELECT
          of.id_operacion_ferro AS id,
          of.numero_operacion   AS numero,
          cl.nombre             AS cliente,
          CONCAT(of.numero_operacion, ' — ', cl.nombre) AS label,
          'FO'                  AS origen,
          of.tipo_operacion_id
        FROM operaciones_ferroviarias of
        JOIN clientes cl ON cl.id_cliente = of.cliente_id
        WHERE 
          of.tipo_operacion_id = 2   -- Terrestre
          AND (
            LOWER(of.numero_operacion) LIKE CONCAT('%', LOWER(?), '%')
            OR LOWER(cl.nombre)        LIKE CONCAT('%', LOWER(?), '%')
          )

        ORDER BY 
          CASE 
            WHEN LOWER(numero) LIKE CONCAT(LOWER(?), '%') THEN 1  -- prefijo primero
            ELSE 2
          END,
          numero
        LIMIT 10;
    ";

    // Orden de parámetros:
    //  1-2: contains en operaciones
    //  3-4: contains en operaciones_ferroviarias
    //  5  : prefijo para ORDER BY
    return $this->selectAll($sql, [
        $needle, $needle,   // operaciones
        $needle, $needle,   // operaciones_ferroviarias
        $term               // prefijo
    ]);
}
/**
 * Contenedores ligados a una operación FERROVIARIA (FO-xx)
 * Se apoya en contenedor_maritimo_ferro → contenedores_fisicos
 */
public function getContenedoresPorOperacionFerro(int $idOperacionFerro): array
{
    $sql = "
        SELECT
          of.id_operacion_ferro AS id_operacion,
          of.numero_operacion   AS numero_operacion,
          cl.nombre             AS nombre_cliente,
          'Ferro'               AS tipo_contenedor,
          cf.id_fisico          AS id_contenedor,
          cf.numero_ferro       AS numero_contenedor
        FROM operaciones_ferroviarias of
        JOIN clientes cl
          ON cl.id_cliente = of.cliente_id
        JOIN contenedor_maritimo_ferro cmf
          ON cmf.operacion_ferro_id = of.id_operacion_ferro
        JOIN contenedores_fisicos cf
          ON cf.id_fisico = cmf.contenedor_fisico_id
        WHERE of.id_operacion_ferro = ?
          AND cmf.estatus = 1
        GROUP BY
          of.id_operacion_ferro,
          of.numero_operacion,
          cl.nombre,
          cf.id_fisico,
          cf.numero_ferro
        ORDER BY cf.numero_ferro;
    ";

    return $this->selectAll($sql, [$idOperacionFerro]) ?: [];
}

/**
 * Detalle de CONTENEDOR FÍSICO en una operación FO (operaciones_ferroviarias)
 * Se usa cuando origen = 'FO' en el módulo de resumen.
 */
public function getDetalleContenedorFerroFO(int $operacionFerroId, int $idFisico): array
{
    $sql = "
        SELECT
          of.id_operacion_ferro            AS operacion_id,
          of.numero_operacion,
          of.fecha                         AS fecha_operacion,
          of.bultos_total,
          of.comentarios                   AS comentarios_operacion,
          cf.id_fisico,
          cf.numero_ferro                  AS numero_contenedor
        FROM operaciones_ferroviarias of
        JOIN contenedor_maritimo_ferro cmf
          ON cmf.operacion_ferro_id = of.id_operacion_ferro
        JOIN contenedores_fisicos cf
          ON cf.id_fisico = cmf.contenedor_fisico_id
        WHERE of.id_operacion_ferro = ?
          AND cf.id_fisico          = ?
        LIMIT 1;
    ";

    $row = $this->select($sql, [$operacionFerroId, $idFisico]);
    return $row ? [$row] : [];
}


    /**
     * Lista de contenedores ligados a una operación.
     * - Marítimos de la propia operación
     * - Físicos (ferro/terrestre) de la misma operación
     */
    public function getContenedoresPorOperacion(int $id): array
    {
        $sql = "
            -- Contenedores MARÍTIMOS de la operación
            SELECT 
              o.id_operacion,
              o.numero_operacion,
              cl.nombre AS nombre_cliente,
              'Maritimo'  AS tipo_contenedor,
              cm.id_contenedor_maritimo AS id_contenedor,
              cm.numero_contenedor      AS numero_contenedor
            FROM operaciones o
            JOIN clientes cl ON cl.id_cliente = o.cliente_id
            JOIN contenedores_maritimos_operacion cmo 
              ON cmo.operacion_id = o.id_operacion
            JOIN contenedores_maritimos cm 
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE o.id_operacion = ?

            UNION ALL

            -- Contenedores FÍSICOS (ferro/terrestre) de la operación
            SELECT 
              o.id_operacion,
              o.numero_operacion,
              cl.nombre AS nombre_cliente,
              'Ferro'   AS tipo_contenedor,
              cf.id_fisico AS id_contenedor,
              cf.numero_ferro AS numero_contenedor
            FROM operaciones o
            JOIN clientes cl ON cl.id_cliente = o.cliente_id
            JOIN contenedores_operacion co 
              ON co.operacion_id = o.id_operacion
            JOIN contenedores_fisicos cf 
              ON cf.id_fisico = co.id_fisico
            WHERE o.id_operacion = ?
            ORDER BY tipo_contenedor, numero_contenedor;
        ";

        return $this->selectAll($sql, [$id, $id]) ?: [];
    }

    /**
     * Detalle de contenedor MARÍTIMO por operación + id_contenedor_maritimo
     */
    public function getDetalleContenedorMaritimo(int $operacionId, int $contenedorMaritimoId): array
    {
        $sql = "
            SELECT
              o.id_operacion,
              o.numero_operacion,
              o.etd,
              o.eta,
              o.numero_bl,
              o.notas                              AS comentarios_operacion,
              so.id_subtipo,
              p.nombre                             AS puerto,
              cmo.id                               AS cont_maritimo_operacion_id,
              cm.id_contenedor_maritimo,
              cm.numero_contenedor,
              cm.tipo                              AS tipo_contenedor,
              cm.observaciones                     AS observaciones_contenedor
            FROM contenedores_maritimos_operacion cmo
            JOIN operaciones o
              ON o.id_operacion = cmo.operacion_id
            JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            LEFT JOIN subtipos_operacion so
              ON so.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN puertos p
              ON p.id_puerto = so.puerto_arribo_default_id
            WHERE cmo.operacion_id = ?
              AND cmo.contenedor_maritimo_id = ?
            LIMIT 1;
        ";

        return $this->selectAll($sql, [$operacionId, $contenedorMaritimoId]) ?: [];
    }

    /**
     * Detalle de CONTENEDOR FÍSICO (ferro/terrestre) por operación + id_fisico
     */
    public function getDetalleContenedorFisico(int $operacionId, int $idFisico): array
    {
        $sql = "
            SELECT
              o.id_operacion,
              o.numero_operacion,
              cf.id_fisico,
              cf.numero_ferro,
              co.id_contenedor                     AS contenedor_operacion_id,
              co.bultos,
              co.comentarios                       AS comentarios_contenedor,
              MAX(
                CASE 
                  WHEN el.tipo_evento_id = 12 AND el.estatus = 1 
                  THEN el.fecha 
                END
              ) AS arribo_a_puerto
            FROM contenedores_operacion co
            JOIN operaciones o
              ON o.id_operacion = co.operacion_id
            JOIN contenedores_fisicos cf
              ON cf.id_fisico = co.id_fisico
            LEFT JOIN eventos_logisticos el
              ON el.contenedor_operacion_id = co.id_contenedor
            WHERE co.operacion_id = ?
              AND co.id_fisico   = ?
            GROUP BY
              o.id_operacion, o.numero_operacion,
              cf.id_fisico, cf.numero_ferro,
              co.id_contenedor, co.bultos, co.comentarios;
        ";

        return $this->selectAll($sql, [$operacionId, $idFisico]) ?: [];
    }

    /**
     * Documentos faltantes para un contenedor (marítimo o físico).
     */
    public function faltantesPorContenedor(
        int $operacionId,
        int $idBase,
        string $tipoUI,
        bool $soloActivos = true,
        ?string $busca = null
    ): array {
        $t = mb_strtoupper(trim($tipoUI), 'UTF-8');

        // Construye el pivot según el tipo que viene de la UI
        if ($t === 'FERRO' || $t === 'FISICO' || $t === 'FÍSICO' || $t === 'F') {
            // FÍSICO: base = cf.id_fisico  → pivot = co.id_contenedor
            $joinPivot = "
                JOIN contenedores_operacion co
                  ON co.operacion_id = ? AND co.id_fisico = ?
            ";
            $params   = [$operacionId, $idBase];
            $docJoin  = "
                LEFT JOIN documentos_operacion d
                  ON d.tipo_documento_id       = t.id_tipo_documento
                 AND d.operacion_id           = co.operacion_id
                 AND d.contenedor_operacion_id = co.id_contenedor
            ";
            $aplicaIn = "('contenedor_fisico','cualquiera')";
        } else {
            // MARÍTIMO: base = cm.id_contenedor_maritimo → pivot = cmo.id
            $joinPivot = "
                JOIN contenedores_maritimos_operacion cmo
                  ON cmo.operacion_id = ? AND cmo.contenedor_maritimo_id = ?
            ";
            $params   = [$operacionId, $idBase];
            $docJoin  = "
                LEFT JOIN documentos_operacion d
                  ON d.tipo_documento_id         = t.id_tipo_documento
                 AND d.operacion_id             = cmo.operacion_id
                 AND d.cont_maritimo_operacion_id = cmo.id
            ";
            $aplicaIn = "('contenedor_maritimo','cualquiera')";
        }

        // WHERE dinámico
        $where = [];
        $where[] = "t.aplica_sobre IN {$aplicaIn}";
        if ($soloActivos) {
            $where[] = "t.activo = 1";
        }
        if ($busca !== null && $busca !== '') {
            $where[]  = "(LOWER(t.nombre) LIKE CONCAT('%', LOWER(?), '%') 
                       OR LOWER(t.clave) LIKE CONCAT('%', LOWER(?), '%'))";
            $params[] = $busca;
            $params[] = $busca;
        }
        $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT
              t.id_tipo_documento AS id,
              t.nombre,
              t.clave,
              t.aplica_sobre
            FROM tipos_documento t
            {$joinPivot}
            {$docJoin}
            {$whereSql}
            GROUP BY t.id_tipo_documento, t.nombre, t.clave, t.aplica_sobre
            HAVING COUNT(d.id_documento) = 0
            ORDER BY t.nombre ASC
            LIMIT 500
        ";

        return $this->selectAll($sql, $params) ?: [];
    }

    /** Conteo rápido de faltantes para un contenedor */
    public function contarFaltantesPorContenedor(int $operacionId, int $idBase, string $tipoUI): int
    {
        $rows = $this->faltantesPorContenedor($operacionId, $idBase, $tipoUI, true, null);
        return is_array($rows) ? count($rows) : 0;
    }

    /** Total de COSTOS de un contenedor FÍSICO (solo movimientos tipo GASTO) */
    public function getCostosTotalesContenedor(int $operacionId, int $idFisico): float
    {
        $sql = "
            SELECT SUM(c.monto) AS total
            FROM contenedores_operacion co
            JOIN costos_contenedor_operacion c
              ON c.contenedor_operacion_id = co.id_contenedor
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = c.tipo_movimiento_id
            WHERE co.operacion_id = ?
              AND co.id_fisico    = ?
              AND tm.tipo         = 'GASTO'
        ";

        $row = $this->select($sql, [$operacionId, $idFisico]);
        return isset($row['total']) ? (float)$row['total'] : 0.0;
    }

    /** Desglose de COSTOS de un contenedor FÍSICO (solo GASTO) */
    public function getCostosDesglosadosContenedor(int $operacionId, int $idFisico): array
    {
        $sql = "
            SELECT 
              c.id_costo_contenedor,
              c.tipo_movimiento_id,
              tm.nombre   AS nombre_movimiento,
              c.monto,
              tm.moneda   AS moneda,
              c.comentario,
              c.fecha_creacion
            FROM contenedores_operacion co
            JOIN costos_contenedor_operacion c
              ON c.contenedor_operacion_id = co.id_contenedor
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = c.tipo_movimiento_id
            WHERE co.operacion_id = ?
              AND co.id_fisico    = ?
              AND tm.tipo         = 'GASTO'
            ORDER BY c.fecha_creacion ASC;
        ";

        $rows = $this->selectAll($sql, [$operacionId, $idFisico]);
        return is_array($rows) ? $rows : [];
    }

    /** Total de COSTOS de la operación (solo GASTO) */
    public function getCostosTotalesOperacion(int $operacionId): float
    {
        $sql = "
            SELECT SUM(co.monto) AS total
            FROM costos_operacion co
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            WHERE co.operacion_id = ?
              AND co.estatus      = 1
              AND tm.tipo         = 'GASTO'
        ";

        $row = $this->select($sql, [$operacionId]);
        return isset($row['total']) ? (float)$row['total'] : 0.0;
    }

    /** Desglose de COSTOS de la operación (solo GASTO) */
    public function getCostosDesglosadosOperacion(int $operacionId): array
    {
        $sql = "
            SELECT 
              co.id_costo_operacion,
              co.tipo_movimiento_id,
              tm.nombre   AS nombre_movimiento,
              co.monto,
              tm.moneda   AS moneda,
              co.comentario,
              co.fecha_creacion
            FROM costos_operacion co
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = co.tipo_movimiento_id
            WHERE co.operacion_id = ?
              AND co.estatus      = 1
              AND tm.tipo         = 'GASTO'
            ORDER BY co.fecha_creacion ASC;
        ";

        $rows = $this->selectAll($sql, [$operacionId]);
        return is_array($rows) ? $rows : [];
    }

    /** Eventos logísticos de un CONTENEDOR FÍSICO (id_fisico + operacion_id) */
    public function getEventosLogisticosFisicoByFisico(int $operacionId, int $idFisico): array
    {
        $sql = "
            SELECT 
              el.id_evento,
              el.operacion_id,
              el.contenedor_operacion_id,
              el.tipo_evento_id,
              tel.nombre   AS nombre_evento,
              el.fecha,
              el.comentario,
              el.estatus
            FROM contenedores_operacion co
            JOIN eventos_logisticos el
              ON el.operacion_id           = co.operacion_id
             AND el.contenedor_operacion_id = co.id_contenedor
            JOIN tipos_evento_logistico tel
              ON tel.id_tipo_evento = el.tipo_evento_id
            WHERE co.operacion_id = ?
              AND co.id_fisico    = ?
              AND el.estatus      = 1
            ORDER BY (el.fecha IS NULL), el.fecha ASC, el.id_evento ASC;
        ";

        return $this->selectAll($sql, [$operacionId, $idFisico]) ?: [];
    }

    /** Eventos logísticos de un CONTENEDOR MARÍTIMO */
    public function getEventosLogisticosMaritimoByMar(int $operacionId, int $idContenedorMaritimo): array
    {
        $sql = "
            SELECT 
              el.id_evento,
              el.operacion_id,
              el.cont_maritimo_operacion_id,
              el.tipo_evento_id,
              tel.nombre   AS nombre_evento,
              el.fecha,
              el.comentario,
              el.estatus
            FROM contenedores_maritimos_operacion cmo
            JOIN eventos_logisticos el
              ON el.operacion_id              = cmo.operacion_id
             AND el.cont_maritimo_operacion_id = cmo.id
            JOIN tipos_evento_logistico tel
              ON tel.id_tipo_evento = el.tipo_evento_id
            WHERE cmo.operacion_id           = ?
              AND cmo.contenedor_maritimo_id = ?
              AND el.estatus                 = 1
            ORDER BY (el.fecha IS NULL), el.fecha ASC, el.id_evento ASC;
        ";

        return $this->selectAll($sql, [$operacionId, $idContenedorMaritimo]) ?: [];
    }

    /** Wrapper unificado para front: tipo 'Ferro' | 'Maritimo' + id_contenedor */
    public function getEventosLogisticosPorContenedor(int $operacionId, string $tipoContenedor, int $idContenedor): array
    {
        $t = mb_strtoupper(trim($tipoContenedor), 'UTF-8');

        if ($t === 'FERRO' || $t === 'FISICO' || $t === 'FÍSICO' || $t === 'F') {
            // id_contenedor = id_fisico
            return $this->getEventosLogisticosFisicoByFisico($operacionId, $idContenedor);
        }

        // Marítimo: id_contenedor = id_contenedor_maritimo
        return $this->getEventosLogisticosMaritimoByMar($operacionId, $idContenedor);
    }

    /** Progreso de eventos para contenedor FÍSICO (usa tipos_evento_logistico.id_tipo_operacion = 2) */
    public function getEventosProgresoFisico(int $operacionId, int $idFisico): array
    {
        // Completados: cuántos tipos de evento distintos tiene ese contenedor físico
        $sqlDone = "
            SELECT COUNT(DISTINCT el.tipo_evento_id) AS completados
            FROM contenedores_operacion co
            JOIN eventos_logisticos el
              ON el.operacion_id           = co.operacion_id
             AND el.contenedor_operacion_id = co.id_contenedor
             AND el.estatus                = 1
            WHERE co.operacion_id = ?
              AND co.id_fisico    = ?
        ";
        $rowDone = $this->select($sqlDone, [$operacionId, $idFisico]);
        $completados = (int)($rowDone['completados'] ?? 0);

        // Totales: tipos de evento activos para operaciones TERRESTRES (id_tipo_operacion = 2)
        $sqlTotal = "
            SELECT COUNT(*) AS total
            FROM tipos_evento_logistico tel
            WHERE tel.estatus          = 1
              AND tel.id_tipo_operacion = 2
        ";
        $rowTotal = $this->select($sqlTotal, []);
        $total = (int)($rowTotal['total'] ?? 0);

        return [
            'completados' => $completados,
            'total'       => $total,
            'restantes'   => max($total - $completados, 0),
        ];
    }

    /** Progreso de eventos para contenedor MARÍTIMO (usa tipos_evento_logistico.id_tipo_operacion = 1) */
    public function getEventosProgresoMaritimo(int $operacionId, int $contenedorMaritimoId): array
    {
        $sqlDone = "
            SELECT COUNT(DISTINCT el.tipo_evento_id) AS completados
            FROM contenedores_maritimos_operacion cmo
            JOIN eventos_logisticos el
              ON el.operacion_id              = cmo.operacion_id
             AND el.cont_maritimo_operacion_id = cmo.id
             AND el.estatus                   = 1
            WHERE cmo.operacion_id           = ?
              AND cmo.contenedor_maritimo_id = ?
        ";
        $rowDone = $this->select($sqlDone, [$operacionId, $contenedorMaritimoId]);
        $completados = (int)($rowDone['completados'] ?? 0);

        // Totales: tipos de evento activos para operaciones MARÍTIMAS (id_tipo_operacion = 1)
        $sqlTotal = "
            SELECT COUNT(*) AS total
            FROM tipos_evento_logistico tel
            WHERE tel.estatus          = 1
              AND tel.id_tipo_operacion = 1
        ";
        $rowTotal = $this->select($sqlTotal, []);
        $total = (int)($rowTotal['total'] ?? 0);

        return [
            'completados' => $completados,
            'total'       => $total,
            'restantes'   => max($total - $completados, 0),
        ];
    }


        /**
     * Contenedores marítimos que viajan en un FERRO concreto
     * (operacion_ferro_id + contenedor_fisico_id).
     *
     * Devuelve: id_relacion, id_contenedor_maritimo, numero_contenedor,
     *           bultos_asignados (en el ferro) y, si existe, bultos del contenedor marítimo.
     */
    public function getContenedoresMaritimosDeFerro(int $operacionFerroId, int $contenedorFisicoId): array
    {
        $sql = "
            SELECT
              cmf.id                      AS id_relacion,
              cmf.contenedor_maritimo_id,
              cmf.cont_maritimo_operacion_id,
              cm.numero_contenedor,
              cmf.bultos_asignados,
              cmo.bultos                  AS bultos_contenedor
            FROM contenedor_maritimo_ferro cmf
            JOIN contenedores_maritimos cm
              ON cm.id_contenedor_maritimo = cmf.contenedor_maritimo_id
            LEFT JOIN contenedores_maritimos_operacion cmo
              ON cmo.id = cmf.cont_maritimo_operacion_id
            WHERE cmf.operacion_ferro_id   = ?
              AND cmf.contenedor_fisico_id = ?
              AND cmf.estatus              = 1
            ORDER BY cm.numero_contenedor ASC
        ";

        return $this->selectAll($sql, [$operacionFerroId, $contenedorFisicoId]) ?: [];
    }
    /**
     * Eventos ferroviarios de un contenedor FÍSICO dentro de una operación ferroviaria.
     * Usa eventos_ferroviarios + tipos_evento_logistico.
     */
public function getEventosFerroviariosPorContenedor(int $operacionFerroId, int $contenedorFisicoId): array
{
    $sql = "
        SELECT
          ef.id_evento           AS id_evento,
          ef.operacion_ferro_id,
          ef.contenedor_fisico_id,
          ef.tipo_evento_id,
          tel.nombre             AS nombre_evento,
          ef.fecha,
          ef.comentario,
          ef.estatus
        FROM eventos_ferroviarios ef
        JOIN tipos_evento_logistico tel
          ON tel.id_tipo_evento = ef.tipo_evento_id
        WHERE ef.operacion_ferro_id   = ?
          AND ef.contenedor_fisico_id = ?
          AND ef.estatus              = 1
        ORDER BY (ef.fecha IS NULL), ef.fecha ASC, ef.id_evento ASC
    ";

    return $this->selectAll($sql, [$operacionFerroId, $contenedorFisicoId]) ?: [];
}

/**
 * Documentos FALTANTES para una operación ferroviaria en un contenedor físico concreto.
 *
 * Se apoya en tipos_documento.aplica_sobre IN ('contenedor_fisico','cualquiera')
 * y documentos_operacion (operacion_id = operacion_ferro_id, contenedor_operacion_id = id_fisico).
 */
public function faltantesPorOperacionFerro(int $operacionFerroId, int $contenedorFisicoId, ?string $busqueda = null): array
{
    $params = [$operacionFerroId, $contenedorFisicoId];

    $filtroBusqueda = '';
    if ($busqueda !== null && $busqueda !== '') {
        $like = '%' . $busqueda . '%';
        $filtroBusqueda = " AND (td.nombre LIKE ? OR td.descripcion LIKE ?) ";
        $params[] = $like;
        $params[] = $like;
    }

    $sql = "
        SELECT 
          td.id_tipo_documento     AS id_tipo,
          td.nombre                AS nombre,
          td.aplica_sobre,
          COUNT(do.id_documento)   AS docs_cargados
        FROM tipos_documento td
        LEFT JOIN documentos_operacion do
          ON do.tipo_documento_id      = td.id_tipo_documento
         AND do.operacion_id           = ?
         AND (do.contenedor_operacion_id = ? OR do.contenedor_operacion_id IS NULL)
        WHERE td.activo = 1                         
          AND td.aplica_sobre IN ('contenedor_fisico','cualquiera')
          {$filtroBusqueda}
        GROUP BY td.id_tipo_documento, td.nombre, td.aplica_sobre
        HAVING docs_cargados = 0
        ORDER BY td.nombre ASC
    ";

    return $this->selectAll($sql, $params) ?: [];
}

        /** TOTAL de costos de una operación ferroviaria (solo GASTO) */
    public function getCostosTotalesOperacionFerro(int $operacionFerroId): float
    {
        $sql = "
            SELECT SUM(cf.monto) AS total
            FROM costos_operacion_ferro cf
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
            WHERE cf.operacion_ferro_id = ?
              AND cf.estatus           = 1
              AND tm.tipo              = 'GASTO'
        ";
        $row = $this->select($sql, [$operacionFerroId]);
        return isset($row['total']) ? (float)$row['total'] : 0.0;
    }

    /** DESGLOSE de costos de una operación ferroviaria (solo GASTO) */
    public function getCostosDesglosadosOperacionFerro(int $operacionFerroId): array
    {
        $sql = "
            SELECT 
              cf.id_costo_ferro       AS id_costo_operacion,
              cf.tipo_movimiento_id,
              tm.nombre               AS nombre_movimiento,
              cf.monto,
              tm.moneda               AS moneda,
              cf.comentario,
              cf.fecha_creacion
            FROM costos_operacion_ferro cf
            LEFT JOIN tipos_movimiento tm
              ON tm.id_tipo_movimiento = cf.tipo_movimiento_id
            WHERE cf.operacion_ferro_id = ?
              AND cf.estatus            = 1
              AND tm.tipo               = 'GASTO'
            ORDER BY cf.fecha_creacion ASC, cf.id_costo_ferro ASC
        ";
        return $this->selectAll($sql, [$operacionFerroId]) ?: [];
    }
/** Progreso de eventos para contenedor FÍSICO en operación FERROVIARIA (FO) */
public function getEventosProgresoFerroFO(int $operacionFerroId, int $contenedorFisicoId): array
{
    // Completados: cuántos tipos de evento distintos tiene ese contenedor físico en eventos_ferroviarios
    $sqlDone = "
        SELECT COUNT(DISTINCT ef.tipo_evento_id) AS completados
        FROM eventos_ferroviarios ef
        WHERE ef.operacion_ferro_id   = ?
          AND ef.contenedor_fisico_id = ?
          AND ef.estatus              = 1
    ";
    $rowDone = $this->select($sqlDone, [$operacionFerroId, $contenedorFisicoId]);
    $completados = (int)($rowDone['completados'] ?? 0);

    // Totales: tipos de evento activos para operaciones TERRESTRES (id_tipo_operacion = 2)
    // (los mismos que usas para contenedor físico normal)
    $sqlTotal = "
        SELECT COUNT(*) AS total
        FROM tipos_evento_logistico tel
        WHERE tel.estatus           = 1
          AND tel.id_tipo_operacion = 2
    ";
    $rowTotal = $this->select($sqlTotal, []);
    $total = (int)($rowTotal['total'] ?? 0);

    return [
        'completados' => $completados,
        'total'       => $total,
        'restantes'   => max($total - $completados, 0),
    ];
}


}
