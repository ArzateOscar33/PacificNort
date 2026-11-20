<?php
/**
 * PacificNort - Traslados de Mercancia (Maritimo-Ferroviario)
 * Modelo exclusivo compatible con Config/App/Query.php (rama despliegue).
 *
 * Requiere:
 *  - traslados_mf_tramos
 *  - operaciones, contenedores_maritimos_operacion, contenedores_maritimos
 *  - bodegas (codigo, tipo_ubicacion), transportistas (codigo)
 *  - costos_operacion, tipos_movimiento y operaciones_log
 *
 * Todas las escrituras usan la MISMA conexion PDO por transaccion.
 */
class Operaciones_maritimo_ferro_trasladosModel extends Query
{
    private const TIPO_OPERACION_MF = 11;
    private const MAX_RENGLONES_LOTE = 100;

    /* ===========================================================
     * CONSULTAS PARA EL MODAL
     * =========================================================== */

    public function obtenerCabeceraOperacion(int $operacionId): ?array
    {
        if ($operacionId <= 0) {
            return null;
        }

        $operacion = $this->select(
            'SELECT id_operacion, numero_operacion, tipo_operacion_id
             FROM operaciones
             WHERE id_operacion = ? AND tipo_operacion_id = ?
             LIMIT 1',
            [$operacionId, self::TIPO_OPERACION_MF]
        );
        if (!$operacion) {
            return null;
        }

        $contenedores = $this->selectAll(
            'SELECT cm.id_contenedor_maritimo,
                    cm.numero_contenedor,
                    cm.tipo
             FROM contenedores_maritimos_operacion cmo
             INNER JOIN contenedores_maritimos cm
                ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
             WHERE cmo.operacion_id = ?
             ORDER BY cm.numero_contenedor ASC',
            [$operacionId]
        );

        $operacion['contenedores'] = is_array($contenedores) ? $contenedores : [];
        return $operacion;
    }

    public function listarUbicaciones(): array
    {
        $filas = $this->selectAll(
            'SELECT b.id_bodega, b.codigo, b.nombre, b.tipo_ubicacion,
                    b.ciudad_id, c.nombre_ciudad
             FROM bodegas b
             LEFT JOIN ciudades c ON c.id_ciudad = b.ciudad_id
             WHERE b.estatus = 1
               AND b.codigo IS NOT NULL
               AND CHAR_LENGTH(TRIM(b.codigo)) = 4
             ORDER BY b.codigo ASC'
        );
        return is_array($filas) ? $filas : [];
    }

    public function listarTransportistas(): array
    {
        $filas = $this->selectAll(
            'SELECT id_transportista, codigo, nombre, tipo
             FROM transportistas
             WHERE estatus = 1
               AND codigo IS NOT NULL
               AND CHAR_LENGTH(TRIM(codigo)) = 4
             ORDER BY codigo ASC'
        );
        return is_array($filas) ? $filas : [];
    }

    /** El catalogo usa DLLS; no confundir tipo_operacion_id=11 con el del concepto. */
    public function obtenerConceptoTransporteUsd(): ?array
    {
        $filas = $this->selectAll(self::sqlConceptoTransporteUsd());
        return is_array($filas) && count($filas) === 1 ? $filas[0] : null;
    }

    public function listarTramos(int $operacionId, bool $incluirCancelados = false): array
    {
        if ($operacionId <= 0) {
            return [];
        }

        $sql = self::sqlDetalleTramos()
             . ' WHERE t.operacion_id = ?';
        if (!$incluirCancelados) {
            $sql .= ' AND t.estatus = 1';
        }
        $sql .= ' ORDER BY t.orden ASC, t.id_traslado ASC';

        $filas = $this->selectAll($sql, [$operacionId]);
        if (!is_array($filas)) {
            return [];
        }

        $numeroVisible = 0;
        foreach ($filas as &$fila) {
            // La numeracion historica de BD permanece intacta.
            $fila['numero_visual'] = (int)$fila['estatus'] === 1
                ? ++$numeroVisible
                : null;
        }
        unset($fila);

        return $filas;
    }

    /** Total contable unicamente de traslados activos de la operacion. */
    public function obtenerResumenTraslados(int $operacionId): array
    {
        if ($operacionId <= 0) {
            return ['total_tramos' => 0, 'total_usd' => '0.00'];
        }
        $fila = $this->select(
            'SELECT COUNT(*) AS total_tramos,
                    COALESCE(SUM(c.monto), 0) AS total_usd
             FROM traslados_mf_tramos t
             INNER JOIN costos_operacion c
                ON c.id_costo_operacion = t.costo_operacion_id
               AND c.operacion_id = t.operacion_id
             WHERE t.operacion_id = ?
               AND t.estatus = 1 AND c.estatus = 1',
            [$operacionId]
        );
        return $fila ?: ['total_tramos' => 0, 'total_usd' => '0.00'];
    }

    public function obtenerTramo(int $operacionId, int $trasladoId): ?array
    {
        if ($operacionId <= 0 || $trasladoId <= 0) {
            return null;
        }
        $fila = $this->select(
            self::sqlDetalleTramos()
            . ' WHERE t.operacion_id = ? AND t.id_traslado = ? LIMIT 1',
            [$operacionId, $trasladoId]
        );
        return $fila ?: null;
    }

    /* ===========================================================
     * REGISTRO: 1 O VARIOS TRAMOS (TODO O NADA)
     * =========================================================== */

    public function registrarTramo(int $operacionId, array $datos, int $usuarioId): array
    {
        $resultado = $this->registrarTramos($operacionId, [$datos], $usuarioId);
        if (($resultado['status'] ?? '') === 'success') {
            $primero = $resultado['registros'][0];
            $resultado['id_traslado'] = $primero['id_traslado'];
            $resultado['costo_operacion_id'] = $primero['costo_operacion_id'];
            $resultado['orden'] = $primero['orden'];
        }
        return $resultado;
    }

    /**
     * Cada elemento de $renglones debe contener:
     * origen_bodega_id, destino_bodega_id, transportista_id, monto.
     * El monto es obligatorio, pero puede ser 0.00.
     */
    public function registrarTramos(int $operacionId, array $renglones, int $usuarioId): array
    {
        return $this->ejecutarTransaccion(function (PDO $pdo) use ($operacionId, $renglones, $usuarioId): array {
            $this->validarUsuario($usuarioId);
            if (!$renglones || count($renglones) > self::MAX_RENGLONES_LOTE) {
                throw new InvalidArgumentException('Ingresa entre 1 y 100 tramos por solicitud.');
            }

            // Serializa los INSERT de esta operacion entre usuarios concurrentes.
            $this->bloquearOperacion($pdo, $operacionId);
            $tipoMovimientoId = $this->resolverConceptoUsdTx($pdo);

            $stmt = $pdo->prepare(
                'SELECT COALESCE(MAX(orden), 0) AS ultimo_orden
                 FROM traslados_mf_tramos
                 WHERE operacion_id = ?'
            );
            $stmt->execute([$operacionId]);
            $orden = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'SELECT destino_bodega_id
                 FROM traslados_mf_tramos
                 WHERE operacion_id = ? AND estatus = 1
                 ORDER BY orden DESC LIMIT 1'
            );
            $stmt->execute([$operacionId]);
            $ultimoDestino = $stmt->fetchColumn();
            $ultimoDestino = $ultimoDestino === false ? null : (int)$ultimoDestino;

            $insertCosto = $pdo->prepare(
                'INSERT INTO costos_operacion
                    (operacion_id, tipo_movimiento_id, monto, Pagado, estatus, fecha_creacion)
                 VALUES (?, ?, ?, 0, 1, NOW())'
            );
            $insertTramo = $pdo->prepare(
                'INSERT INTO traslados_mf_tramos
                    (operacion_id, orden, origen_bodega_id, destino_bodega_id,
                     transportista_id, costo_operacion_id, estatus)
                 VALUES (?, ?, ?, ?, ?, ?, 1)'
            );

            $creados = [];
            foreach ($renglones as $indice => $datos) {
                if (!is_array($datos)) {
                    throw new InvalidArgumentException('El tramo ' . ($indice + 1) . ' no tiene un formato valido.');
                }

                $tramo = $this->validarDatosTramo($pdo, $datos);
                if ($ultimoDestino !== null && $ultimoDestino !== $tramo['origen_bodega_id']) {
                    throw new DomainException(
                        'El origen del tramo ' . ($indice + 1)
                        . ' debe coincidir con el destino del ultimo tramo activo.'
                    );
                }

                ++$orden;
                $insertCosto->execute([$operacionId, $tipoMovimientoId, $tramo['monto']]);
                $costoId = (int)$pdo->lastInsertId();
                $insertTramo->execute([
                    $operacionId, $orden, $tramo['origen_bodega_id'],
                    $tramo['destino_bodega_id'], $tramo['transportista_id'], $costoId
                ]);
                $trasladoId = (int)$pdo->lastInsertId();

                $this->registrarLogTx(
                    $pdo, $operacionId, $usuarioId, 'creacion',
                    sprintf(
                        'Traslado creado (id=%d, orden=%d, origen=%d, destino=%d, transportista=%d, costo_id=%d, monto_usd=%s)',
                        $trasladoId, $orden, $tramo['origen_bodega_id'],
                        $tramo['destino_bodega_id'], $tramo['transportista_id'],
                        $costoId, $tramo['monto']
                    )
                );

                $creados[] = [
                    'id_traslado' => $trasladoId,
                    'costo_operacion_id' => $costoId,
                    'orden' => $orden,
                ];
                $ultimoDestino = $tramo['destino_bodega_id'];
            }

            return [
                'status' => 'success',
                'message' => 'Traslados registrados correctamente.',
                'registros' => $creados,
                'total' => count($creados),
            ];
        });
    }

    /* ===========================================================
     * EDICION: NUNCA CAMBIA ORDEN NI REFERENCIAS DE COSTO
     * =========================================================== */

    public function editarTramo(int $operacionId, int $trasladoId, array $datos, int $usuarioId): array
    {
        return $this->ejecutarTransaccion(function (PDO $pdo) use ($operacionId, $trasladoId, $datos, $usuarioId): array {
            $this->validarUsuario($usuarioId);
            $this->bloquearOperacion($pdo, $operacionId);
            $actual = $this->bloquearTramoYCosto($pdo, $operacionId, $trasladoId);
            $this->validarEditable($actual);
            $nuevo = $this->validarDatosTramo($pdo, $datos);

            $cambioRecorrido =
                (int)$actual['origen_bodega_id'] !== $nuevo['origen_bodega_id'] ||
                (int)$actual['destino_bodega_id'] !== $nuevo['destino_bodega_id'];
            if ($cambioRecorrido) {
                $this->validarContinuidadEdicion($pdo, $operacionId, (int)$actual['orden'], $nuevo);
            }

            $stmt = $pdo->prepare(
                'UPDATE traslados_mf_tramos
                 SET origen_bodega_id = ?, destino_bodega_id = ?, transportista_id = ?
                 WHERE id_traslado = ? AND operacion_id = ? AND estatus = 1'
            );
            $stmt->execute([
                $nuevo['origen_bodega_id'], $nuevo['destino_bodega_id'],
                $nuevo['transportista_id'], $trasladoId, $operacionId
            ]);

            $stmt = $pdo->prepare(
                'UPDATE costos_operacion
                 SET monto = ?
                 WHERE id_costo_operacion = ? AND operacion_id = ?
                   AND Pagado = 0 AND estatus = 1'
            );
            $stmt->execute([$nuevo['monto'], (int)$actual['costo_operacion_id'], $operacionId]);

            $this->registrarLogTx(
                $pdo, $operacionId, $usuarioId, 'actualizacion',
                sprintf(
                    'Traslado editado (id=%d, orden=%d, origen=%d->%d, destino=%d->%d, transportista=%d->%d, monto_usd=%s->%s)',
                    $trasladoId, (int)$actual['orden'],
                    (int)$actual['origen_bodega_id'], $nuevo['origen_bodega_id'],
                    (int)$actual['destino_bodega_id'], $nuevo['destino_bodega_id'],
                    (int)$actual['transportista_id'], $nuevo['transportista_id'],
                    (string)$actual['monto'], $nuevo['monto']
                )
            );

            return [
                'status' => 'success',
                'message' => 'Traslado actualizado correctamente.',
                'id_traslado' => $trasladoId,
                'orden' => (int)$actual['orden'],
            ];
        });
    }

    /* ===========================================================
     * BAJA LOGICA: MANTIENE EL ORDEN HISTORICO
     * =========================================================== */

    public function cancelarTramo(int $operacionId, int $trasladoId, int $usuarioId, string $motivo = ''): array
    {
        return $this->ejecutarTransaccion(function (PDO $pdo) use ($operacionId, $trasladoId, $usuarioId, $motivo): array {
            $this->validarUsuario($usuarioId);
            $this->bloquearOperacion($pdo, $operacionId);
            $actual = $this->bloquearTramoYCosto($pdo, $operacionId, $trasladoId);
            $this->validarEditable($actual);

            $stmt = $pdo->prepare(
                'UPDATE traslados_mf_tramos
                 SET estatus = 0
                 WHERE id_traslado = ? AND operacion_id = ? AND estatus = 1'
            );
            $stmt->execute([$trasladoId, $operacionId]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('No se pudo cancelar el tramo.');
            }

            $stmt = $pdo->prepare(
                'UPDATE costos_operacion
                 SET estatus = 0
                 WHERE id_costo_operacion = ? AND operacion_id = ?
                   AND Pagado = 0 AND estatus = 1'
            );
            $stmt->execute([(int)$actual['costo_operacion_id'], $operacionId]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('No se pudo cancelar el costo vinculado.');
            }

            $vecinos = $this->obtenerVecinosActivos($pdo, $operacionId, (int)$actual['orden']);
            $continuidadRota = $vecinos['anterior'] && $vecinos['siguiente']
                && (int)$vecinos['anterior']['destino_bodega_id']
                   !== (int)$vecinos['siguiente']['origen_bodega_id'];

            $motivo = trim($motivo);
            if (strlen($motivo) > 250) {
                throw new InvalidArgumentException('El motivo debe tener maximo 250 bytes.');
            }
            $detalleMotivo = $motivo !== '' ? ', motivo=' . $motivo : '';
            $this->registrarLogTx(
                $pdo, $operacionId, $usuarioId, 'cancelacion',
                sprintf(
                    'Traslado cancelado (id=%d, orden=%d, costo_id=%d, monto_usd=%s%s)',
                    $trasladoId, (int)$actual['orden'], (int)$actual['costo_operacion_id'],
                    (string)$actual['monto'], $detalleMotivo
                )
            );

            return [
                'status' => 'success',
                'message' => 'Traslado cancelado sin modificar el orden historico.',
                'id_traslado' => $trasladoId,
                'orden' => (int)$actual['orden'],
                'advertencia_continuidad' => (bool)$continuidadRota,
            ];
        });
    }

    /* ===========================================================
     * SQL Y VALIDACIONES INTERNAS
     * =========================================================== */

    private static function sqlDetalleTramos(): string
    {
        return 'SELECT
                    t.id_traslado, t.operacion_id, o.numero_operacion,
                    t.orden, t.origen_bodega_id, t.destino_bodega_id,
                    t.transportista_id, t.costo_operacion_id, t.estatus,
                    t.created_at, t.updated_at,
                    bo.codigo AS codigo_origen, bo.nombre AS nombre_origen,
                    bo.tipo_ubicacion AS tipo_origen,
                    bd.codigo AS codigo_destino, bd.nombre AS nombre_destino,
                    bd.tipo_ubicacion AS tipo_destino,
                    tr.codigo AS codigo_transportista, tr.nombre AS nombre_transportista,
                    c.monto, c.Pagado AS pagado, c.estatus AS estatus_costo,
                    tm.nombre AS concepto, tm.moneda
                FROM traslados_mf_tramos t
                INNER JOIN operaciones o ON o.id_operacion = t.operacion_id
                INNER JOIN bodegas bo ON bo.id_bodega = t.origen_bodega_id
                INNER JOIN bodegas bd ON bd.id_bodega = t.destino_bodega_id
                INNER JOIN transportistas tr ON tr.id_transportista = t.transportista_id
                INNER JOIN costos_operacion c ON c.id_costo_operacion = t.costo_operacion_id
                LEFT JOIN tipos_movimiento tm ON tm.id_tipo_movimiento = c.tipo_movimiento_id';
    }

    private static function sqlConceptoTransporteUsd(): string
    {
        return "SELECT id_tipo_movimiento, nombre, tipo, moneda
                FROM tipos_movimiento
                WHERE estatus = 1
                  AND LOWER(TRIM(tipo)) = 'gasto'
                  AND UPPER(TRIM(moneda)) = 'DLLS'
                  AND UPPER(TRIM(nombre)) IN
                      ('TRANSPORTE', 'TRANSPORTE EN DOLARES', 'TRANSPORTE EN DÓLARES')
                ORDER BY id_tipo_movimiento ASC";
    }

    private function conexionEscritura(): PDO
    {
        // Query::$con es private, por lo que save() e insertar() no deben
        // mezclarse con esta transaccion. Conexion utiliza el mismo DSN.
        return (new Conexion())->conect();
    }

    private function ejecutarTransaccion(callable $accion): array
    {
        $pdo = null;
        try {
            $pdo = $this->conexionEscritura();
            $pdo->beginTransaction();
            $resultado = $accion($pdo);
            $pdo->commit();
            return $resultado;
        } catch (InvalidArgumentException $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            return ['status' => 'warning', 'message' => $e->getMessage()];
        } catch (DomainException $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            return ['status' => 'warning', 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            error_log('Traslados MF / transaccion: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'No fue posible completar la operacion. No se guardaron cambios.'];
        }
    }

    private function validarUsuario(int $usuarioId): void
    {
        if ($usuarioId <= 0) {
            throw new DomainException('La sesion no identifica a un usuario valido.');
        }
    }

    private function bloquearOperacion(PDO $pdo, int $operacionId): void
    {
        if ($operacionId <= 0) {
            throw new InvalidArgumentException('Operacion no valida.');
        }
        $stmt = $pdo->prepare(
            'SELECT id_operacion
             FROM operaciones
             WHERE id_operacion = ? AND tipo_operacion_id = ?
             FOR UPDATE'
        );
        $stmt->execute([$operacionId, self::TIPO_OPERACION_MF]);
        if (!$stmt->fetch()) {
            throw new DomainException('La operacion maritimo-ferroviaria no existe.');
        }
    }

    private function resolverConceptoUsdTx(PDO $pdo): int
    {
        $filas = $pdo->query(self::sqlConceptoTransporteUsd())->fetchAll(PDO::FETCH_ASSOC);
        if (count($filas) !== 1) {
            throw new DomainException(
                'Debe existir exactamente un concepto activo de Transporte en DLLS (gasto).'
            );
        }
        return (int)$filas[0]['id_tipo_movimiento'];
    }

    private function validarDatosTramo(PDO $pdo, array $datos): array
    {
        $origen = $this->idValido($datos['origen_bodega_id'] ?? null, 'Origen');
        $destino = $this->idValido($datos['destino_bodega_id'] ?? null, 'Destino');
        $transportista = $this->idValido($datos['transportista_id'] ?? null, 'Transportista');
        if ($origen === $destino) {
            throw new InvalidArgumentException('El origen y el destino deben ser diferentes.');
        }
        if (!array_key_exists('monto', $datos)) {
            throw new InvalidArgumentException('Debes indicar el monto, aunque sea 0.00.');
        }
        $monto = $this->normalizarMonto($datos['monto']);

        $checkBodega = $pdo->prepare(
            'SELECT id_bodega FROM bodegas
             WHERE id_bodega = ? AND estatus = 1
               AND codigo IS NOT NULL
               AND CHAR_LENGTH(TRIM(codigo)) = 4'
        );
        foreach ([$origen, $destino] as $id) {
            $checkBodega->execute([$id]);
            if (!$checkBodega->fetch()) {
                throw new DomainException('Origen o destino inexistente, inactivo o sin codigo de cuatro letras.');
            }
        }

        $checkTransportista = $pdo->prepare(
            'SELECT id_transportista FROM transportistas
             WHERE id_transportista = ? AND estatus = 1
               AND codigo IS NOT NULL
               AND CHAR_LENGTH(TRIM(codigo)) = 4'
        );
        $checkTransportista->execute([$transportista]);
        if (!$checkTransportista->fetch()) {
            throw new DomainException('El transportista no existe, esta inactivo o no tiene codigo de cuatro letras.');
        }

        return [
            'origen_bodega_id' => $origen,
            'destino_bodega_id' => $destino,
            'transportista_id' => $transportista,
            'monto' => $monto,
        ];
    }

    private function idValido($valor, string $campo): int
    {
        if (!is_int($valor) && !is_string($valor)) {
            throw new InvalidArgumentException($campo . ' invalido.');
        }
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException($campo . ' invalido.');
        }
        return (int)$id;
    }

    private function normalizarMonto($valor): string
    {
        if (!is_string($valor) && !is_int($valor) && !is_float($valor)) {
            throw new InvalidArgumentException('Monto invalido.');
        }
        $valor = trim((string)$valor);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/D', $valor)) {
            throw new InvalidArgumentException('Monto invalido: usa USD con maximo dos decimales y valor mayor o igual a 0.');
        }
        $partes = explode('.', $valor, 2);
        return $partes[0] . '.' . str_pad($partes[1] ?? '', 2, '0');
    }

    private function bloquearTramoYCosto(PDO $pdo, int $operacionId, int $trasladoId): array
    {
        if ($trasladoId <= 0) {
            throw new InvalidArgumentException('ID de traslado invalido.');
        }
        $stmt = $pdo->prepare(
            'SELECT t.id_traslado, t.operacion_id, t.orden,
                    t.origen_bodega_id, t.destino_bodega_id,
                    t.transportista_id, t.costo_operacion_id, t.estatus,
                    c.operacion_id AS operacion_costo_id,
                    c.monto, c.Pagado AS pagado,
                    c.estatus AS estatus_costo
             FROM traslados_mf_tramos t
             INNER JOIN costos_operacion c
               ON c.id_costo_operacion = t.costo_operacion_id
             WHERE t.operacion_id = ? AND t.id_traslado = ?
             FOR UPDATE'
        );
        $stmt->execute([$operacionId, $trasladoId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new DomainException('El traslado no existe en esta operacion.');
        }
        if ((int)$fila['operacion_costo_id'] !== $operacionId) {
            throw new DomainException('El costo del traslado pertenece a otra operacion.');
        }
        return $fila;
    }

    private function validarEditable(array $fila): void
    {
        if ((int)$fila['estatus'] !== 1 || (int)$fila['estatus_costo'] !== 1) {
            throw new DomainException('El traslado o su costo ya estan cancelados.');
        }
        if ($fila['pagado'] === null || (int)$fila['pagado'] !== 0) {
            throw new DomainException('No se puede modificar ni cancelar un traslado cuyo costo esta pagado.');
        }
    }

    private function obtenerVecinosActivos(PDO $pdo, int $operacionId, int $orden): array
    {
        $anterior = $pdo->prepare(
            'SELECT origen_bodega_id, destino_bodega_id
             FROM traslados_mf_tramos
             WHERE operacion_id = ? AND estatus = 1 AND orden < ?
             ORDER BY orden DESC LIMIT 1'
        );
        $anterior->execute([$operacionId, $orden]);

        $siguiente = $pdo->prepare(
            'SELECT origen_bodega_id, destino_bodega_id
             FROM traslados_mf_tramos
             WHERE operacion_id = ? AND estatus = 1 AND orden > ?
             ORDER BY orden ASC LIMIT 1'
        );
        $siguiente->execute([$operacionId, $orden]);

        return [
            'anterior' => $anterior->fetch(PDO::FETCH_ASSOC) ?: null,
            'siguiente' => $siguiente->fetch(PDO::FETCH_ASSOC) ?: null,
        ];
    }

    private function validarContinuidadEdicion(PDO $pdo, int $operacionId, int $orden, array $nuevo): void
    {
        $vecinos = $this->obtenerVecinosActivos($pdo, $operacionId, $orden);
        if ($vecinos['anterior'] &&
            (int)$vecinos['anterior']['destino_bodega_id'] !== $nuevo['origen_bodega_id']) {
            throw new DomainException('El nuevo origen no coincide con el destino del tramo activo anterior.');
        }
        if ($vecinos['siguiente'] &&
            $nuevo['destino_bodega_id'] !== (int)$vecinos['siguiente']['origen_bodega_id']) {
            throw new DomainException('El nuevo destino no coincide con el origen del tramo activo siguiente.');
        }
    }

    private function registrarLogTx(PDO $pdo, int $operacionId, int $usuarioId, string $accion, string $descripcion): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO operaciones_log
                (operacion_id, usuario_id, accion, descripcion, fecha)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$operacionId, $usuarioId, $accion, $descripcion]);
    }
}
