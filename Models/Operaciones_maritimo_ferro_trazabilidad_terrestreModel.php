<?php

class Operaciones_maritimo_ferro_trazabilidad_terrestreModel extends Query
{
    /**
     * 1) Obtener FO (viaje terrestre) del ferro/caja por fecha (tu "fecha_salida").
     *    En tu BD: operaciones_ferroviarias tiene UNIQUE (contenedor_fisico_id, fecha)
     */
    public function obtenerFOporFisicoFecha(int $contenedorFisicoId, string $fechaSalida): ?array
    {
        $sql = "
            SELECT
                ofo.id_operacion_ferro,
                ofo.destino_id,
                cd.nombre_ciudad AS destino_nombre
            FROM operaciones_ferroviarias ofo
            LEFT JOIN ciudades cd ON cd.id_ciudad = ofo.destino_id
            WHERE ofo.contenedor_fisico_id = ?
              AND ofo.fecha = ?
            LIMIT 1
        ";
        $row = $this->select($sql, [$contenedorFisicoId, $fechaSalida]);
        return $row ?: null;
    }

    /**
     * 2) Origen (Puerto) desde la operación marítima NT actual
     *    operaciones.subtipo_operacion_id -> subtipos_operacion.puerto_arribo_default_id -> puertos
     */
    public function obtenerOrigenPuertoPorOperacion(int $operacionId): ?array
    {
        $sql = "
            SELECT
                p.id_puerto,
                p.nombre AS puerto_nombre
            FROM operaciones o
            LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN puertos p ON p.id_puerto = st.puerto_arribo_default_id
            WHERE o.id_operacion = ?
            LIMIT 1
        ";
        $row = $this->select($sql, [$operacionId]);
        if (!$row || empty($row['id_puerto'])) return null;
        return $row;
    }

    /**
     * 3) Última ubicación (GLOBAL por viaje FO) para el panel.
     *    IMPORTANTE: se filtra por (contenedor_fisico_id + operacion_ferro_id)
     *    => así NT-25 y NT-26 se sincronizan si comparten la misma FO.
     */
    public function obtenerUltimaUbicacion(int $contenedorFisicoId, int $operacionFerroId): ?array
    {
        $sql = "
            SELECT
                tf.id_traza,
                tf.ubicacion_id,
                cu.nombre_ciudad AS ubicacion_nombre,
                tf.fecha_evento,
                tf.referencia,
                tf.notas,
                tf.created_at
            FROM trazabilidad_ferro tf
            LEFT JOIN ciudades cu ON cu.id_ciudad = tf.ubicacion_id
            WHERE tf.contenedor_fisico_id = ?
              AND tf.operacion_ferro_id = ?
            ORDER BY tf.created_at DESC
            LIMIT 1
        ";
        $row = $this->select($sql, [$contenedorFisicoId, $operacionFerroId]);
        return $row ?: null;
    }

    /**
     * 4) Historial de trazabilidad (por viaje FO) opcional para una tablita o timeline.
     */
    public function listarHistorial(int $contenedorFisicoId, int $operacionFerroId, int $limit = 50): array
    {
        $limit = max(1, min(200, (int)$limit));

        $sql = "
            SELECT
                tf.id_traza,
                tf.fecha_evento,
                cu.nombre_ciudad AS ubicacion,
                tf.referencia,
                tf.notas,
                tf.created_at
            FROM trazabilidad_ferro tf
            LEFT JOIN ciudades cu ON cu.id_ciudad = tf.ubicacion_id
            WHERE tf.contenedor_fisico_id = ?
              AND tf.operacion_ferro_id = ?
            ORDER BY tf.created_at DESC
            LIMIT {$limit}
        ";
        return $this->selectAll($sql, [$contenedorFisicoId, $operacionFerroId]) ?: [];
    }

    /**
     * 5) Obtener el resumen completo del panel:
     *    - Origen (puerto) de la NT actual (operacionId)
     *    - Destino (ciudad) desde FO resuelta por (fisicoId, fechaSalida)
     *    - Ubicación actual (última traza) por (fisicoId, operacionFerroId)
     */
    public function obtenerPanelTrazabilidad(int $operacionId, int $contenedorFisicoId, string $fechaSalida): array
    {
        $origen = $this->obtenerOrigenPuertoPorOperacion($operacionId);
        $fo     = $this->obtenerFOporFisicoFecha($contenedorFisicoId, $fechaSalida);

        $panel = [
            'origen_puerto_id'   => $origen['id_puerto'] ?? null,
            'origen_puerto'      => $origen['puerto_nombre'] ?? '',
            'operacion_ferro_id' => $fo['id_operacion_ferro'] ?? null,
            'destino_id'         => $fo['destino_id'] ?? null,
            'destino'            => $fo['destino_nombre'] ?? '',
            'ubicacion_actual'   => '',
            'ubicacion_id'       => null,
            'fecha_ubicacion'    => null,
            'referencia'         => null,
            'notas'              => null,
        ];

        if (!empty($panel['operacion_ferro_id'])) {
            $ultima = $this->obtenerUltimaUbicacion($contenedorFisicoId, (int)$panel['operacion_ferro_id']);
            if ($ultima) {
                $panel['ubicacion_actual'] = $ultima['ubicacion_nombre'] ?? '';
                $panel['ubicacion_id']     = $ultima['ubicacion_id'] ?? null;
                $panel['fecha_ubicacion']  = $ultima['fecha_evento'] ?? null;
                $panel['referencia']       = $ultima['referencia'] ?? null;
                $panel['notas']            = $ultima['notas'] ?? null;
            }
        }

        return $panel;
    }

    /**
     * 6) Insertar trazabilidad (una "parada") en trazabilidad_ferro.
     *    Reglas:
     *    - operacion_ferro_id se RESUELVE por (fisicoId, fechaSalida)
     *    - destino_id se toma de la FO
     *    - origen_puerto_id se toma de la NT actual
     *    - última ubicación se consultará luego por (fisicoId, operacion_ferro_id)
     */
    public function insertarTrazabilidad(array $in, int $usuarioId): array
    {
        $contenedorFisicoId = isset($in['contenedor_fisico_id']) ? (int)$in['contenedor_fisico_id'] : 0;
        $operacionId        = isset($in['operacion_id']) ? (int)$in['operacion_id'] : 0;
        $fechaSalida        = trim((string)($in['fecha_salida'] ?? '')); // viene de la fila seleccionada
        $ubicacionId        = isset($in['ubicacion_id']) ? (int)$in['ubicacion_id'] : 0;
        $fechaEvento        = trim((string)($in['fecha_evento'] ?? ''));
        $referencia         = isset($in['referencia']) ? trim((string)$in['referencia']) : null;
        $notas              = isset($in['notas']) ? trim((string)$in['notas']) : null;

        if ($contenedorFisicoId <= 0) return ['status' => 'error', 'msg' => 'Ferro/Caja inválido'];
        if ($operacionId <= 0)        return ['status' => 'error', 'msg' => 'Operación inválida'];
        if ($ubicacionId <= 0)        return ['status' => 'error', 'msg' => 'Ubicación requerida'];
        if ($fechaEvento === '')      return ['status' => 'error', 'msg' => 'Fecha requerida'];
        if ($fechaSalida === '')      return ['status' => 'error', 'msg' => 'Fecha salida requerida para identificar el viaje'];

        // A) Resolver FO por fisico + fecha_salida (fecha)
        $fo = $this->obtenerFOporFisicoFecha($contenedorFisicoId, $fechaSalida);
        if (!$fo || empty($fo['id_operacion_ferro'])) {
            return ['status' => 'error', 'msg' => 'No se encontró FO/viaje para ese Ferro/Caja y fecha de salida'];
        }
        $operacionFerroId = (int)$fo['id_operacion_ferro'];
        $destinoId        = !empty($fo['destino_id']) ? (int)$fo['destino_id'] : null;

        // B) Origen puerto desde operación marítima
        $origen = $this->obtenerOrigenPuertoPorOperacion($operacionId);
        $origenPuertoId = $origen ? (int)$origen['id_puerto'] : null;

        // C) Insertar evento
        $sql = "
            INSERT INTO trazabilidad_ferro
            (contenedor_fisico_id, operacion_id, operacion_ferro_id, origen_puerto_id, destino_id,
             ubicacion_id, fecha_evento, referencia, notas, creado_por)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $id = $this->insertar($sql, [
            $contenedorFisicoId,
            $operacionId,
            $operacionFerroId,
            $origenPuertoId,
            $destinoId,
            $ubicacionId,
            $fechaEvento,
            ($referencia !== '' ? $referencia : null),
            ($notas !== '' ? $notas : null),
            $usuarioId,
        ]);

        if ((int)$id <= 0) {
            return ['status' => 'error', 'msg' => 'No se pudo guardar la trazabilidad'];
        }

        return [
            'status' => 'success',
            'msg'    => 'Ubicación guardada correctamente',
            'id'     => (int)$id,
            'operacion_ferro_id' => $operacionFerroId,
        ];
    }
}
