<?php
class Operaciones_maritimo_ferro_trazabilidadModel extends Query
{

    public function listarPaginado(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $page = max(1, (int)$page);

        // Soporta "Todos" con valores enormes (como 10000000)
        $perPage = (int)$perPage;
        if ($perPage <= 0) $perPage = 10;

        $isAll = ($perPage >= 10000000);
        $offset = ($page - 1) * $perPage;

        $where = "WHERE cmf.estatus = 1";
        $args  = [];

        // =========================
        // Buscador (operación / ferro / cliente / destino / contenedor / ubicación / transportista)
        // =========================
        $raw = trim((string)($filters['term'] ?? ''));
        if ($raw !== '') {
            $terms = array_values(array_filter(array_map(
                fn($t) => mb_strtolower(trim($t), 'UTF-8'),
                explode(',', $raw)
            ), fn($t) => $t !== ''));
            $terms = array_slice($terms, 0, 5);

            foreach ($terms as $t) {
                $needle = '%' . $t . '%';
                $where .= " AND (
                LOWER(o.numero_operacion) LIKE ?
                OR LOWER(cm.numero_contenedor) LIKE ?
                OR LOWER(cf.numero_ferro) LIKE ?
                OR LOWER(cli.nombre) LIKE ?
                OR LOWER(COALESCE(dest_last.nombre_ciudad, dest_ofe.nombre_ciudad, '')) LIKE ?
                OR LOWER(COALESCE(ubi_last.nombre_ciudad, '')) LIKE ?
                OR LOWER(COALESCE(puerto_last.nombre, puerto_st.nombre, '')) LIKE ?
                OR LOWER(COALESCE(tr.nombre, '')) LIKE ?
            )";
                array_push($args, $needle, $needle, $needle, $needle, $needle, $needle, $needle, $needle);
            }
        }

        // =========================
        // Fechas (usa fecha de último evento; si no hay, usa fecha de salida FO)
        // =========================
        $fi = trim((string)($filters['fecha_inicio'] ?? ''));
        $ff = trim((string)($filters['fecha_fin'] ?? ''));
        $isDate = static function (string $d): bool {
            return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        };
        if ($fi !== '' && !$isDate($fi)) $fi = '';
        if ($ff !== '' && !$isDate($ff)) $ff = '';
        if ($fi !== '' && $ff !== '' && $fi > $ff) [$fi, $ff] = [$ff, $fi];

        // Fecha efectiva = COALESCE(tf_last.fecha_evento, ofe.fecha)
        if ($fi !== '' && $ff !== '') {
            $where .= " AND DATE(COALESCE(tf_last.fecha_evento, ofe.fecha)) BETWEEN ? AND ? ";
            array_push($args, $fi, $ff);
        } elseif ($fi !== '') {
            $where .= " AND DATE(COALESCE(tf_last.fecha_evento, ofe.fecha)) >= ? ";
            $args[] = $fi;
        } elseif ($ff !== '') {
            $where .= " AND DATE(COALESCE(tf_last.fecha_evento, ofe.fecha)) <= ? ";
            $args[] = $ff;
        }

        // =========================
        // Filtros SELECT (cliente / origen / ubicación / destino / transportista)
        // =========================

        // Cliente
        $clienteId = (int)($filters['cliente_id'] ?? $filters['cliente'] ?? 0);
        if ($clienteId > 0) {
            $where .= " AND o.cliente_id = ? ";
            $args[] = $clienteId;
        }

        // Origen (puerto)
        $origenId = (int)($filters['origen_id'] ?? $filters['origen'] ?? 0);
        if ($origenId > 0) {
            $where .= " AND COALESCE(tf_last.origen_puerto_id, st.puerto_arribo_default_id) = ? ";
            $args[] = $origenId;
        }

        // Ubicación actual (ciudad)
        $ubicacionId = (int)($filters['ubicacion_id'] ?? $filters['ubicacion'] ?? 0);
        if ($ubicacionId > 0) {
            $where .= " AND tf_last.ubicacion_id = ? ";
            $args[] = $ubicacionId;
        }

        // Destino (ciudad)
        $destinoId = (int)($filters['destino_id'] ?? $filters['destino'] ?? 0);
        if ($destinoId > 0) {
            $where .= " AND COALESCE(tf_last.destino_id, ofe.destino_id) = ? ";
            $args[] = $destinoId;
        }

        // ✅ Transportista (de operaciones_ferroviarias)
        $transportistaId = (int)($filters['transportista_id'] ?? $filters['transportista'] ?? 0);
        if ($transportistaId > 0) {
            $where .= " AND ofe.transportista_id = ? ";
            $args[] = $transportistaId;
        }

        // =========================
        // COUNT (1 fila = 1 asignación cmf.id)
        // =========================
        $sqlCount = "
        SELECT COUNT(*) AS total
        FROM contenedor_maritimo_ferro cmf
        INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
        INNER JOIN operaciones o ON o.id_operacion = cmo.operacion_id
        LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        INNER JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = cmf.operacion_ferro_id
        LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = ofe.contenedor_fisico_id

        LEFT JOIN transportistas tr ON tr.id_transportista = ofe.transportista_id

        LEFT JOIN clientes cli ON cli.id_cliente = o.cliente_id
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN puertos puerto_st ON puerto_st.id_puerto = st.puerto_arribo_default_id

        /* última traza por operacion_ferro_id */
        LEFT JOIN (
            SELECT
                tf.operacion_ferro_id,
                STR_TO_DATE(
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(DATE_FORMAT(tf.fecha_evento,'%Y-%m-%d')
                            ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                        ),
                        ',', 1
                    ),
                    '%Y-%m-%d'
                ) AS fecha_evento,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.origen_puerto_id
                        ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                    ),
                    ',', 1
                ) AS UNSIGNED) AS origen_puerto_id,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.destino_id
                        ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                    ),
                    ',', 1
                ) AS UNSIGNED) AS destino_id,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.ubicacion_id
                        ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                    ),
                    ',', 1
                ) AS UNSIGNED) AS ubicacion_id
            FROM trazabilidad_ferro tf
            WHERE tf.operacion_ferro_id IS NOT NULL
            GROUP BY tf.operacion_ferro_id
        ) tf_last ON tf_last.operacion_ferro_id = ofe.id_operacion_ferro

        LEFT JOIN puertos puerto_last ON puerto_last.id_puerto = tf_last.origen_puerto_id
        LEFT JOIN ciudades dest_last ON dest_last.id_ciudad = tf_last.destino_id
        LEFT JOIN ciudades dest_ofe  ON dest_ofe.id_ciudad  = ofe.destino_id
        LEFT JOIN ciudades ubi_last  ON ubi_last.id_ciudad  = tf_last.ubicacion_id

        $where
    ";
        $rowCount = $this->select($sqlCount, $args) ?: ['total' => 0];
        $total = (int)$rowCount['total'];

        // =========================
        // DATA
        // =========================
        $limitSql = $isAll ? "" : " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

        $sqlData = "
        SELECT
            cmf.id AS asignacion_id,

            o.id_operacion,
            o.numero_operacion AS operacion_maritima,

            cm.id_contenedor_maritimo,
            cm.numero_contenedor AS contenedor_maritimo,

            ofe.id_operacion_ferro,
            cf.id_fisico,
            cf.numero_ferro AS ferro_caja,

            
            ofe.transportista_id,
            COALESCE(tr.nombre, '—') AS transportista,

            -- ids para lógica
            COALESCE(tf_last.destino_id, ofe.destino_id) AS destino_id_efectivo,
            tf_last.ubicacion_id AS ubicacion_id_last,

            -- bandera de llegada (solo si hay ubicación)
            CASE
                WHEN tf_last.ubicacion_id IS NOT NULL
                AND tf_last.ubicacion_id = COALESCE(tf_last.destino_id, ofe.destino_id)
                THEN 1 ELSE 0
            END AS llego_destino,

            cli.nombre AS cliente,

            COALESCE(puerto_last.nombre, puerto_st.nombre, '—') AS origen,
            COALESCE(ubi_last.nombre_ciudad, '—') AS ubicacion_actual,
            COALESCE(dest_last.nombre_ciudad, dest_ofe.nombre_ciudad, '—') AS destino,

            COALESCE(tf_last.fecha_evento, ofe.fecha) AS fecha_referencia

        FROM contenedor_maritimo_ferro cmf
        INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
        INNER JOIN operaciones o ON o.id_operacion = cmo.operacion_id
        LEFT JOIN contenedores_maritimos cm ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id

        INNER JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = cmf.operacion_ferro_id
        LEFT JOIN contenedores_fisicos cf ON cf.id_fisico = ofe.contenedor_fisico_id

        LEFT JOIN transportistas tr ON tr.id_transportista = ofe.transportista_id

        LEFT JOIN clientes cli ON cli.id_cliente = o.cliente_id
        LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
        LEFT JOIN puertos puerto_st ON puerto_st.id_puerto = st.puerto_arribo_default_id

        /* última traza por operacion_ferro_id */
        LEFT JOIN (
            SELECT
                tf.operacion_ferro_id,
                STR_TO_DATE(
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(DATE_FORMAT(tf.fecha_evento,'%Y-%m-%d')
                            ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                        ),
                        ',', 1
                    ),
                    '%Y-%m-%d'
                ) AS fecha_evento,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.origen_puerto_id
                        ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                    ),
                    ',', 1
                ) AS UNSIGNED) AS origen_puerto_id,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.destino_id
                        ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                    ),
                    ',', 1
                ) AS UNSIGNED) AS destino_id,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.ubicacion_id
                        ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC
                    ),
                    ',', 1
                ) AS UNSIGNED) AS ubicacion_id
            FROM trazabilidad_ferro tf
            WHERE tf.operacion_ferro_id IS NOT NULL
            GROUP BY tf.operacion_ferro_id
        ) tf_last ON tf_last.operacion_ferro_id = ofe.id_operacion_ferro

        LEFT JOIN puertos puerto_last ON puerto_last.id_puerto = tf_last.origen_puerto_id
        LEFT JOIN ciudades dest_last ON dest_last.id_ciudad = tf_last.destino_id
        LEFT JOIN ciudades dest_ofe  ON dest_ofe.id_ciudad  = ofe.destino_id
        LEFT JOIN ciudades ubi_last  ON ubi_last.id_ciudad  = tf_last.ubicacion_id

        $where
        ORDER BY COALESCE(tf_last.fecha_evento, ofe.fecha) DESC, cmf.id DESC
        $limitSql
    ";

        $rows = $this->selectAll($sqlData, $args) ?: [];

        $totalPages = $isAll ? 1 : max(1, (int)ceil($total / max(1, $perPage)));

        return [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }
    public function listarHistorial(int $contenedorFisicoId, int $operacionFerroId, int $limit = 50): array
    {
        $limit = max(1, min(200, (int)$limit));

        // --- rows del historial (igual que ya lo tienes, pero agregamos ids por si quieres usarlos después)
        $sqlRows = "
        SELECT
            tf.id_traza,
            tf.fecha_evento,
            tf.ubicacion_id,
            tf.destino_id,
            cu.nombre_ciudad AS ubicacion,
            tf.referencia,
            tf.notas,
            tf.created_at
        FROM trazabilidad_ferro tf
        LEFT JOIN ciudades cu ON cu.id_ciudad = tf.ubicacion_id
        WHERE tf.contenedor_fisico_id = ?
          AND tf.operacion_ferro_id = ?
        ORDER BY tf.created_at ASC
        LIMIT {$limit}
    ";
        $rows = $this->selectAll($sqlRows, [$contenedorFisicoId, $operacionFerroId]) ?: [];

        // --- meta: “última traza” + destino efectivo (si no hay traza, toma destino de ofe)
        $sqlMeta = "
        SELECT
            ofe.id_operacion_ferro AS operacion_ferro_id,
            ofe.contenedor_fisico_id AS contenedor_fisico_id,

            -- destino efectivo: si hay tf_last.destino_id úsalo; si no, ofe.destino_id
            COALESCE(tf_last.destino_id, ofe.destino_id) AS destino_id_efectivo,
            tf_last.ubicacion_id AS ubicacion_id_last,

            d.nombre_ciudad AS destino_nombre,
            u.nombre_ciudad AS ubicacion_nombre_last,

            CASE
              WHEN tf_last.ubicacion_id IS NOT NULL
               AND tf_last.ubicacion_id = COALESCE(tf_last.destino_id, ofe.destino_id)
              THEN 1 ELSE 0
            END AS llego_destino

        FROM operaciones_ferroviarias ofe

        LEFT JOIN (
            SELECT
                tf.operacion_ferro_id,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.destino_id ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC),
                    ',', 1
                ) AS UNSIGNED) AS destino_id,
                CAST(SUBSTRING_INDEX(
                    GROUP_CONCAT(tf.ubicacion_id ORDER BY tf.fecha_evento DESC, tf.created_at DESC, tf.id_traza DESC),
                    ',', 1
                ) AS UNSIGNED) AS ubicacion_id
            FROM trazabilidad_ferro tf
            WHERE tf.operacion_ferro_id IS NOT NULL
            GROUP BY tf.operacion_ferro_id
        ) tf_last ON tf_last.operacion_ferro_id = ofe.id_operacion_ferro

        LEFT JOIN ciudades d ON d.id_ciudad = COALESCE(tf_last.destino_id, ofe.destino_id)
        LEFT JOIN ciudades u ON u.id_ciudad = tf_last.ubicacion_id

        WHERE ofe.id_operacion_ferro = ?
          AND ofe.contenedor_fisico_id = ?
        LIMIT 1
    ";
        $meta = $this->select($sqlMeta, [$operacionFerroId, $contenedorFisicoId]) ?: [
            'operacion_ferro_id' => $operacionFerroId,
            'contenedor_fisico_id' => $contenedorFisicoId,
            'destino_id_efectivo' => null,
            'ubicacion_id_last' => null,
            'destino_nombre' => null,
            'ubicacion_nombre_last' => null,
            'llego_destino' => 0,
        ];

        return ['rows' => $rows, 'meta' => $meta];
    }
}
