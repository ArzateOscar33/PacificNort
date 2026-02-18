<?php
class Operaciones_maritimo_ferro_asignacion_ferroModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }



    /* =========================================================
       1) Helpers base: CMO único y Ferro físico
    ========================================================= */

    // Tu MF tiene 1 contenedor marítimo por operación => 1 solo CMO
    public function obtenerCMOUnicoPorOperacion(int $operacionId): ?array
    {
        $sql = "SELECT id, operacion_id, contenedor_maritimo_id, bultos
                FROM contenedores_maritimos_operacion
                WHERE operacion_id = ?
                LIMIT 1";
        $row = $this->select($sql, [$operacionId]);
        return ($row && !empty($row['id'])) ? $row : null;
    }

    public function obtenerFerroPorNumero(string $numeroFerro): ?array
    {
        $sql = "SELECT id_fisico, numero_ferro, estatus
                FROM contenedores_fisicos
                WHERE numero_ferro = ?
                LIMIT 1";
        $row = $this->select($sql, [trim($numeroFerro)]);
        return ($row && !empty($row['id_fisico'])) ? $row : null;
    }

    public function crearFerro(string $numeroFerro): int
    {
        $sql = "INSERT INTO contenedores_fisicos (numero_ferro, estatus)
                VALUES (?, 1)";
        return (int)$this->insertar($sql, [trim($numeroFerro)]);
    }

    /**
     * Get or Create ferro por número.
     */
    public function getOrCreateFerroId(string $numeroFerro): int
    {
        $numeroFerro = trim($numeroFerro);
        if ($numeroFerro === '') return 0;

        $ex = $this->obtenerFerroPorNumero($numeroFerro);
        if ($ex) return (int)$ex['id_fisico'];

        return $this->crearFerro($numeroFerro);
    }





    /* =========================================================
       2) FO (viaje interno) por (ferro, fecha)
       - destino inmutable
       - transportista editable (aplica a todas)
       - uq_ferro_fecha ya existe en tu BD
    ========================================================= */

    public function obtenerFOPorFerroFecha(int $contenedorFisicoId, string $fecha): ?array
    {
        $sql = "SELECT id_operacion_ferro, numero_operacion, contenedor_fisico_id, destino_id,
                       transportista_id, fecha, bultos_total, estatus_id, comentarios
                FROM operaciones_ferroviarias
                WHERE contenedor_fisico_id = ? AND fecha = ?
                LIMIT 1";
        $row = $this->select($sql, [$contenedorFisicoId, $fecha]);
        return ($row && !empty($row['id_operacion_ferro'])) ? $row : null;
    }

    /**
     * Genera número de operación FO usando tu procedimiento:
     * CALL GenerarNumeroOperacionFerro(p_subtipo_id, OUT p_numero_operacion)
     * Nota: requiere un subtipo_id válido (si no, usa 0 o uno “FO” que exista en tu catálogo).
     */
    public function generarNumeroFO(int $subtipoId): string
    {
        $subtipoId = (int)$subtipoId;
        if ($subtipoId <= 0) $subtipoId = 0; // fallback: el SP usa prefijo FO si no hay prefijo

        // CALL ... OUT en MariaDB vía variable de sesión
        $ok = $this->save("CALL GenerarNumeroOperacionFerro(?, @p_num)", [$subtipoId]);
        if (!$ok) return "";

        $r = $this->select("SELECT @p_num AS numero");
        return ($r && !empty($r['numero'])) ? (string)$r['numero'] : "";
    }

    /**
     * Crea FO (viaje) con destino inmutable.
     * - destino_id se fija al crear
     * - transportista_id se puede editar después
     */
    public function crearFO(array $data): int
    {
        $contenedorFisicoId = (int)($data['contenedor_fisico_id'] ?? 0);
        $fecha              = (string)($data['fecha'] ?? '');
        $destinoId          = (int)($data['destino_id'] ?? 0);
        $transportistaId    = (int)($data['transportista_id'] ?? 0);
        $comentarios        = (string)($data['comentarios'] ?? '');
        $creadoPor          = (int)($data['creado_por'] ?? 0);

        // Subtipo FO si lo manejas (si no, se queda null)
        $subtipoFOId = (int)($data['subtipo_operacion_id'] ?? 0);
        $numeroFO    = $this->generarNumeroFO($subtipoFOId);

        if ($numeroFO === '') {
            // fallback muy simple (por si falla SP)
            $numeroFO = "FO-" . date("ymd") . "-" . substr((string)time(), -4);
        }

        $sql = "INSERT INTO operaciones_ferroviarias
                    (numero_operacion, contenedor_fisico_id, destino_id, transportista_id, fecha,
                     estatus_id, comentarios, bultos_total, tipo_operacion_id, subtipo_operacion_id,
                     creado_por)
                VALUES
                    (?, ?, ?, ?, ?, 9, ?, 0, 2, ?, ?)";

        return (int)$this->insertar($sql, [
            $numeroFO,
            $contenedorFisicoId ?: null,
            $destinoId ?: null,
            $transportistaId ?: null,
            $fecha,
            $comentarios !== '' ? $comentarios : null,
            $subtipoFOId ?: null,
            $creadoPor ?: null
        ]);
    }

    /**
     * Si existe FO para (ferro, fecha):
     * - destino NO se puede cambiar (si llega distinto => error)
     * - transportista sí puede cambiar => update
     *
     * @return array ['ok'=>bool,'fo'=>array|null,'msg'=>string]
     */
    public function getOrCreateFOConReglas(
        int $contenedorFisicoId,
        string $fecha,
        int $destinoId,
        int $transportistaId,
        string $comentarios = '',
        int $creadoPor = 0,
        int $subtipoFOId = 0
    ): array {
        $fecha = trim($fecha);

        $fo = $this->obtenerFOPorFerroFecha($contenedorFisicoId, $fecha);

        if (!$fo) {
            $id = $this->crearFO([
                'contenedor_fisico_id'   => $contenedorFisicoId,
                'fecha'                  => $fecha,
                'destino_id'             => $destinoId,
                'transportista_id'       => $transportistaId,
                'comentarios'            => $comentarios,
                'creado_por'             => $creadoPor,
                'subtipo_operacion_id'   => $subtipoFOId,
            ]);
            if ($id <= 0) return ['ok' => false, 'fo' => null, 'msg' => 'No se pudo crear el viaje (FO).'];

            $fo = $this->select("SELECT * FROM operaciones_ferroviarias WHERE id_operacion_ferro = ?", [$id]);
            return ['ok' => true, 'fo' => $fo, 'msg' => 'FO creada.'];
        }

        // destino inmutable
        $destinoExistente = (int)($fo['destino_id'] ?? 0);
        if ($destinoExistente > 0 && $destinoId > 0 && $destinoExistente !== $destinoId) {
            return ['ok' => false, 'fo' => $fo, 'msg' => 'El destino no se puede modificar en un viaje ya creado.'];
        }

        // transportista editable (si cambia, actualiza)
        $transportistaExistente = (int)($fo['transportista_id'] ?? 0);
        if ($transportistaId > 0 && $transportistaId !== $transportistaExistente) {
            $this->actualizarTransportistaFO((int)$fo['id_operacion_ferro'], $transportistaId);
            $fo['transportista_id'] = $transportistaId;
        }

        // comentarios opcionales: puedes anexar si llega algo nuevo
        if (trim($comentarios) !== '') {
            $this->save(
                "UPDATE operaciones_ferroviarias SET comentarios = ? WHERE id_operacion_ferro = ?",
                [$comentarios, (int)$fo['id_operacion_ferro']]
            );
            $fo['comentarios'] = $comentarios;
        }

        return ['ok' => true, 'fo' => $fo, 'msg' => 'FO reutilizada.'];
    }

    public function actualizarTransportistaFO(int $foId, int $transportistaId): int
    {
        $sql = "UPDATE operaciones_ferroviarias
                SET transportista_id = ?
                WHERE id_operacion_ferro = ?";
        return (int)$this->save($sql, [$transportistaId ?: null, $foId]);
    }

    public function recalcularBultosTotalFO(int $foId): int
    {
        $row = $this->select(
            "SELECT COALESCE(SUM(bultos_asignados),0) AS total
             FROM contenedor_maritimo_ferro
             WHERE operacion_ferro_id = ? AND estatus = 1",
            [$foId]
        );
        $total = (int)($row['total'] ?? 0);

        $this->save(
            "UPDATE operaciones_ferroviarias SET bultos_total = ? WHERE id_operacion_ferro = ?",
            [$total, $foId]
        );

        return $total;
    }

    public function contarAsignacionesFO(int $foId): int
    {
        $r = $this->select(
            "SELECT COUNT(*) AS c
             FROM contenedor_maritimo_ferro
             WHERE operacion_ferro_id = ? AND estatus = 1",
            [$foId]
        );
        return (int)($r['c'] ?? 0);
    }

    public function eliminarFO(int $foId): int
    {
        // borra primero detalle (por seguridad)
        $this->save("DELETE FROM contenedor_maritimo_ferro WHERE operacion_ferro_id = ?", [$foId]);
        // borra cabecera opcional
        $this->save("DELETE FROM operacion_ferro_operacion WHERE operacion_ferro_id = ?", [$foId]);
        // borra FO
        return (int)$this->save("DELETE FROM operaciones_ferroviarias WHERE id_operacion_ferro = ?", [$foId]);
    }

    /**
     * Si se queda sin asignaciones => borrar FO (requisito #4).
     */
    public function eliminarFOsiVacia(int $foId): bool
    {
        $n = $this->contarAsignacionesFO($foId);
        if ($n > 0) return false;
        $this->eliminarFO($foId);
        return true;
    }


    /* =========================================================
       3) Validación de parcialidades (bultos)
       - no exceder total del CMO
       - edición: excluir la asignación actual (si existe)
    ========================================================= */

    /**
     * Devuelve bultos totales del CMO (contenedor marítimo de la operación).
     */
    public function obtenerBultosTotalesCMO(int $cmoId): int
    {
        $r = $this->select("SELECT bultos FROM contenedores_maritimos_operacion WHERE id = ? LIMIT 1", [$cmoId]);
        return (int)($r['bultos'] ?? 0);
    }

    /**
     * Suma de bultos asignados del CMO en otros viajes (excluyendo el FO actual).
     */
    public function sumaAsignadoCMOExcluyendoFO(int $cmoId, int $foId): int
    {
        $r = $this->select(
            "SELECT COALESCE(SUM(bultos_asignados),0) AS s
             FROM contenedor_maritimo_ferro
             WHERE cont_maritimo_operacion_id = ?
               AND estatus = 1
               AND operacion_ferro_id <> ?",
            [$cmoId, $foId]
        );
        return (int)($r['s'] ?? 0);
    }

    /**
     * Valida si nuevoBultos cabe considerando parcialidades en otros ferros/fechas.
     */
    public function validarBultosDisponibleParaFO(int $cmoId, int $foId, int $nuevoBultos): array
    {
        $nuevoBultos = max(0, (int)$nuevoBultos);

        $total = $this->obtenerBultosTotalesCMO($cmoId);
        $otros = $this->sumaAsignadoCMOExcluyendoFO($cmoId, $foId);

        $disponible = $total - $otros;
        if ($nuevoBultos > $disponible) {
            return [
                'ok' => false,
                'total' => $total,
                'otros' => $otros,
                'disponible' => $disponible,
                'msg' => "Bultos inválidos: disponible {$disponible} (total {$total}, asignado en otros {$otros})."
            ];
        }

        return [
            'ok' => true,
            'total' => $total,
            'otros' => $otros,
            'disponible' => $disponible,
            'msg' => 'OK'
        ];
    }


    /* =========================================================
       4) Asignación editable (detalle) + cabecera opcional
       - 1 operación MF = 1 CMO
       - edición: update por (FO, CMO)
    ========================================================= */

    public function obtenerAsignacionDetalle(int $foId, int $cmoId): ?array
    {
        $sql = "SELECT id, operacion_ferro_id, cont_maritimo_operacion_id, bultos_asignados, comentario, estatus
                FROM contenedor_maritimo_ferro
                WHERE operacion_ferro_id = ?
                  AND cont_maritimo_operacion_id = ?
                  AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$foId, $cmoId]);
        return ($row && !empty($row['id'])) ? $row : null;
    }

    /**
     * UPSERT detalle:
     * - si ya existe (FO, CMO) => UPDATE bultos/comentario
     * - si no existe => INSERT
     * Regla: si bultos = 0 => elimina la asignación
     *
     * @return array ['ok'=>bool,'action'=>'insert|update|delete','msg'=>string]
     */
    public function upsertAsignacionDetalle(
        int $foId,
        int $cmoId,
        int $contenedorFisicoId,
        int $bultos,
        string $comentario = ''
    ): array {
        $bultos = max(0, (int)$bultos);

        // Si bultos=0 => borrar asignación y si FO queda vacía => borrar FO
        if ($bultos === 0) {
            $this->eliminarAsignacionDetalle($foId, $cmoId);
            $this->recalcularBultosTotalFO($foId);
            $this->eliminarFOsiVacia($foId);
            return ['ok' => true, 'action' => 'delete', 'msg' => 'Asignación eliminada.'];
        }

        // Validación parcialidades
        $val = $this->validarBultosDisponibleParaFO($cmoId, $foId, $bultos);
        if (!$val['ok']) {
            return ['ok' => false, 'action' => 'none', 'msg' => $val['msg']];
        }

        $ex = $this->obtenerAsignacionDetalle($foId, $cmoId);
        if ($ex) {
            $sql = "UPDATE contenedor_maritimo_ferro
                    SET bultos_asignados = ?, comentario = ?, contenedor_fisico_id = ?
                    WHERE id = ?";
            $ok = $this->save($sql, [
                $bultos,
                trim($comentario) !== '' ? trim($comentario) : null,
                $contenedorFisicoId ?: null,
                (int)$ex['id']
            ]);

            $this->recalcularBultosTotalFO($foId);
            return ['ok' => (bool)$ok, 'action' => 'update', 'msg' => $ok ? 'Asignación actualizada.' : 'No se pudo actualizar.'];
        }

        // INSERT nuevo
        $sql = "INSERT INTO contenedor_maritimo_ferro
                    (operacion_ferro_id, cont_maritimo_operacion_id, contenedor_fisico_id, bultos_asignados, comentario, estatus)
                VALUES
                    (?, ?, ?, ?, ?, 1)";
        $id = $this->insertar($sql, [
            $foId,
            $cmoId,
            $contenedorFisicoId ?: null,
            $bultos,
            trim($comentario) !== '' ? trim($comentario) : null
        ]);

        $this->recalcularBultosTotalFO($foId);
        return ['ok' => $id > 0, 'action' => 'insert', 'msg' => $id > 0 ? 'Asignación creada.' : 'No se pudo crear.'];
    }

    public function eliminarAsignacionDetalle(int $foId, int $cmoId): int
    {
        // Hard delete para cumplir tu regla #4 de “si queda vacío se borra”
        return (int)$this->save(
            "DELETE FROM contenedor_maritimo_ferro
             WHERE operacion_ferro_id = ? AND cont_maritimo_operacion_id = ?",
            [$foId, $cmoId]
        );
    }

    /**
     * Cabecera opcional: una operación en un FO (útil para listados).
     * uq_op_fo ya existe: (operacion_id, operacion_ferro_id)
     */
    public function upsertCabeceraOperacionEnFO(int $operacionId, int $foId, int $bultos, string $notas = ''): array
    {
        $operacionId = (int)$operacionId;
        $foId        = (int)$foId;
        $bultos      = max(0, (int)$bultos);

        // ¿existe?
        $ex = $this->select(
            "SELECT id FROM operacion_ferro_operacion
             WHERE operacion_id = ? AND operacion_ferro_id = ?
             LIMIT 1",
            [$operacionId, $foId]
        );

        if ($bultos === 0) {
            // si bultos=0, quitar cabecera
            $this->save("DELETE FROM operacion_ferro_operacion WHERE operacion_id = ? AND operacion_ferro_id = ?", [$operacionId, $foId]);
            return ['ok' => true, 'action' => 'delete', 'msg' => 'Cabecera eliminada.'];
        }

        if ($ex && !empty($ex['id'])) {
            $ok = $this->save(
                "UPDATE operacion_ferro_operacion
                 SET bultos_asignados = ?, notas = ?
                 WHERE id = ?",
                [$bultos, trim($notas) !== '' ? trim($notas) : null, (int)$ex['id']]
            );
            return ['ok' => (bool)$ok, 'action' => 'update', 'msg' => $ok ? 'Cabecera actualizada.' : 'No se pudo actualizar cabecera.'];
        }

        $id = $this->insertar(
            "INSERT INTO operacion_ferro_operacion (operacion_id, operacion_ferro_id, bultos_asignados, notas)
             VALUES (?, ?, ?, ?)",
            [$operacionId, $foId, $bultos, trim($notas) !== '' ? trim($notas) : null]
        );
        return ['ok' => $id > 0, 'action' => 'insert', 'msg' => $id > 0 ? 'Cabecera creada.' : 'No se pudo crear cabecera.'];
    }


    /* =========================================================
       5) Consultas para tu Modal (izquierda y derecha)
    ========================================================= */

    /**
     * Izquierda: “Ferros/Cajas de esta operación”
     * Agrupa por ferro + fecha (viaje) y suma bultos (como tú lo muestras).
     */
    public function listarFerrosDeOperacion(int $operacionId): array
    {
        $sql = "SELECT
                    ofe.id_operacion_ferro AS fo_id,
                    cf.numero_ferro,
                    ofe.fecha,
                    ofe.destino_id,
                    ci.nombre_ciudad AS destino_nombre,
                    ofe.transportista_id,
                    t.nombre AS transportista_nombre,
                    SUM(cmf.bultos_asignados) AS bultos
                FROM contenedor_maritimo_ferro cmf
                INNER JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = cmf.operacion_ferro_id
                INNER JOIN contenedores_fisicos cf ON cf.id_fisico = ofe.contenedor_fisico_id
                INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
                LEFT JOIN ciudades ci ON ci.id_ciudad = ofe.destino_id
                LEFT JOIN transportistas t ON t.id_transportista = ofe.transportista_id
                WHERE cmo.operacion_id = ?
                  AND cmf.estatus = 1
                GROUP BY ofe.id_operacion_ferro, cf.numero_ferro, ofe.fecha, ofe.destino_id, ci.nombre_ciudad, ofe.transportista_id, t.nombre
                ORDER BY ofe.fecha DESC, cf.numero_ferro ASC";
        $rows = $this->selectAll($sql, [$operacionId]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Derecha: “Operaciones en el Ferro/Caja seleccionado” (por ferro + fecha).
     */
    public function listarOperacionesEnFerroFecha(string $numeroFerro, string $fecha): array
    {
        $sql = "SELECT DISTINCT
                    o.id_operacion,
                    o.numero_operacion AS codigo,
                    c.nombre AS cliente,
                    o.eta,
                    st.nombre AS subtipo
                FROM operaciones_ferroviarias ofe
                INNER JOIN contenedores_fisicos cf ON cf.id_fisico = ofe.contenedor_fisico_id
                INNER JOIN contenedor_maritimo_ferro cmf ON cmf.operacion_ferro_id = ofe.id_operacion_ferro AND cmf.estatus = 1
                INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
                INNER JOIN operaciones o ON o.id_operacion = cmo.operacion_id
                LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
                LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
                WHERE cf.numero_ferro = ?
                  AND ofe.fecha = ?
                ORDER BY o.numero_operacion ASC";
        $rows = $this->selectAll($sql, [trim($numeroFerro), $fecha]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Para tu tabla principal: obtener “la asignación más reciente” de una operación
     * (ferro, destino, fecha). Útil para pintar columnas “Caja/Ferro, Destino, Fecha”.
     */
    public function obtenerAsignacionMasRecienteDeOperacion(int $operacionId): ?array
    {
        $sql = "SELECT
                    cf.numero_ferro,
                    ofe.fecha,
                    ofe.destino_id,
                    ci.nombre_ciudad AS destino_nombre
                FROM contenedor_maritimo_ferro cmf
                INNER JOIN contenedores_maritimos_operacion cmo ON cmo.id = cmf.cont_maritimo_operacion_id
                INNER JOIN operaciones_ferroviarias ofe ON ofe.id_operacion_ferro = cmf.operacion_ferro_id
                INNER JOIN contenedores_fisicos cf ON cf.id_fisico = ofe.contenedor_fisico_id
                LEFT JOIN ciudades ci ON ci.id_ciudad = ofe.destino_id
                WHERE cmo.operacion_id = ?
                  AND cmf.estatus = 1
                ORDER BY ofe.fecha DESC, cmf.fecha_asignacion DESC
                LIMIT 1";
        $row = $this->select($sql, [$operacionId]);
        return ($row && !empty($row['numero_ferro'])) ? $row : null;
    }
}
