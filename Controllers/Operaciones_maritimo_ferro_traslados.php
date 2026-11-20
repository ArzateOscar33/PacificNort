<?php
/**
 * PacificNort Suite - Controlador de Traslados de Mercancía (MF).
 *
 * Compatible con el MVC de la rama `despliegue`:
 *   - Controller carga automáticamente Operaciones_maritimo_ferro_trasladosModel.
 *   - Las rutas siguen el esquema BASE_URL + Controlador/metodo.
 *   - Se mantienen los permisos MF [1, 11, 2] y las respuestas JSON
 *     con status: success | warning | error.
 *
 * La gestión de costos y las transacciones pertenecen EXCLUSIVAMENTE al modelo.
 * No duplicar INSERT/UPDATE de costos_operacion en este controlador.
 */
class Operaciones_maritimo_ferro_traslados extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // Los mismos roles autorizados del módulo Operaciones MF.
        $this->requireRoles([1, 11, 2]);
    }

    /* ==========================================================
     * CONSULTAS PARA EL MODAL (GET)
     * ========================================================== */

    /**
     * GET /Operaciones_maritimo_ferro_traslados/inicializar?operacion_id=XX
     *
     * Devuelve en una petición todo lo necesario para abrir el modal:
     * cabecera, contenedores, catálogos, concepto USD, tramos y resumen.
     */
    public function inicializar(): void
    {
        $this->ejecutarEndpoint('GET', function (): array {
            $operacionId = $this->idRequerido($_GET['operacion_id'] ?? null, 'Operación');
            $cabecera = $this->model->obtenerCabeceraOperacion($operacionId);

            if (!$cabecera) {
                return $this->advertencia('La operación marítimo-ferroviaria no existe.');
            }

            $concepto = $this->model->obtenerConceptoTransporteUsd();

            return [
                'status' => 'success',
                'message' => 'Información de traslados cargada.',
                'cabecera' => $cabecera,
                'catalogos' => [
                    'ubicaciones' => $this->model->listarUbicaciones(),
                    'transportistas' => $this->model->listarTransportistas(),
                ],
                'concepto_transporte_usd' => $concepto,
                // El frontend deshabilitará Guardar mientras falte el concepto.
                'puede_registrar' => $concepto !== null,
                'advertencia' => $concepto === null
                    ? 'No existe un concepto único y activo de Transporte (gasto) en DLLS.'
                    : null,
                'rows' => $this->model->listarTramos($operacionId),
                'resumen' => $this->model->obtenerResumenTraslados($operacionId),
            ];
        });
    }

    /** GET /Operaciones_maritimo_ferro_traslados/cabecera?operacion_id=XX */
    public function cabecera(): void
    {
        $this->ejecutarEndpoint('GET', function (): array {
            $operacionId = $this->idRequerido($_GET['operacion_id'] ?? null, 'Operación');
            $cabecera = $this->model->obtenerCabeceraOperacion($operacionId);

            if (!$cabecera) {
                return $this->advertencia('Operación marítimo-ferroviaria no encontrada.');
            }

            return [
                'status' => 'success',
                'data' => $cabecera,
            ];
        });
    }

    /** GET /Operaciones_maritimo_ferro_traslados/catalogos */
    public function catalogos(): void
    {
        $this->ejecutarEndpoint('GET', function (): array {
            $concepto = $this->model->obtenerConceptoTransporteUsd();

            return [
                'status' => 'success',
                'ubicaciones' => $this->model->listarUbicaciones(),
                'transportistas' => $this->model->listarTransportistas(),
                'concepto_transporte_usd' => $concepto,
                'puede_registrar' => $concepto !== null,
            ];
        });
    }

    /**
     * GET /Operaciones_maritimo_ferro_traslados/listar?operacion_id=XX
     * Opcional: incluir_cancelados=1 para mostrar el historial completo.
     */
    public function listar(): void
    {
        $this->ejecutarEndpoint('GET', function (): array {
            $operacionId = $this->idRequerido($_GET['operacion_id'] ?? null, 'Operación');
            $cabecera = $this->model->obtenerCabeceraOperacion($operacionId);
            if (!$cabecera) {
                return $this->advertencia('Operación marítimo-ferroviaria no encontrada.');
            }

            $incluirCancelados = $this->esUno($_GET['incluir_cancelados'] ?? null);
            $rows = $this->model->listarTramos($operacionId, $incluirCancelados);

            return [
                'status' => 'success',
                'rows' => $rows,
                'total' => count($rows),
                'resumen' => $this->model->obtenerResumenTraslados($operacionId),
            ];
        });
    }

    /**
     * GET /Operaciones_maritimo_ferro_traslados/obtenerUno
     * Parámetros: operacion_id, id_traslado.
     */
    public function obtenerUno(): void
    {
        $this->ejecutarEndpoint('GET', function (): array {
            $operacionId = $this->idRequerido($_GET['operacion_id'] ?? null, 'Operación');
            $trasladoId = $this->idRequerido($_GET['id_traslado'] ?? null, 'Traslado');

            $fila = $this->model->obtenerTramo($operacionId, $trasladoId);
            if (!$fila) {
                return $this->advertencia('No se encontró el traslado dentro de la operación indicada.');
            }

            return [
                'status' => 'success',
                'data' => $fila,
            ];
        });
    }

    /* ==========================================================
     * CAMBIOS (POST)
     * ========================================================== */

    /**
     * POST /Operaciones_maritimo_ferro_traslados/guardar
     *
     * Campos obligatorios:
     *   operacion_id, origen_bodega_id, destino_bodega_id,
     *   transportista_id, monto (se admite "0" o "0.00").
     *
     * Opcional: id_traslado > 0 => editar; sin ID => registrar.
     * El modelo preserva el orden histórico y el costo vinculado al editar.
     */
    public function guardar(): void
    {
        $this->ejecutarEndpoint('POST', function (): array {
            $entrada = $this->datosPost();
            $operacionId = $this->idRequerido($entrada['operacion_id'] ?? null, 'Operación');
            $trasladoId = $this->idOpcional($entrada['id_traslado'] ?? null, 'Traslado');
            $datos = $this->datosDelTramo($entrada);
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

            if ($trasladoId > 0) {
                return $this->model->editarTramo($operacionId, $trasladoId, $datos, $usuarioId);
            }

            return $this->model->registrarTramo($operacionId, $datos, $usuarioId);
        });
    }

    /**
     * POST /Operaciones_maritimo_ferro_traslados/guardarLote
     *
     * Campos: operacion_id y renglones.
     * renglones admite un arreglo PHP o una cadena JSON con arreglo de tramos.
     * Todos se registran en una misma transacción dentro del modelo.
     */
    public function guardarLote(): void
    {
        $this->ejecutarEndpoint('POST', function (): array {
            $entrada = $this->datosPost();
            $operacionId = $this->idRequerido($entrada['operacion_id'] ?? null, 'Operación');
            $renglones = $entrada['renglones'] ?? null;

            if (is_string($renglones)) {
                $renglones = json_decode($renglones, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Los renglones no contienen un JSON válido.');
                }
            }

            if (!is_array($renglones) || !$this->esLista($renglones)
                || count($renglones) < 1 || count($renglones) > 100) {
                throw new InvalidArgumentException('Debes enviar una lista de entre 1 y 100 tramos.');
            }

            $normalizados = [];
            foreach ($renglones as $indice => $fila) {
                if (!is_array($fila)) {
                    throw new InvalidArgumentException('El renglón ' . ($indice + 1) . ' es inválido.');
                }
                $normalizados[] = $this->datosDelTramo($fila);
            }

            return $this->model->registrarTramos(
                $operacionId,
                $normalizados,
                (int)($_SESSION['id_usuario'] ?? 0)
            );
        });
    }

    /**
     * POST /Operaciones_maritimo_ferro_traslados/cancelar
     * Campos: operacion_id, id_traslado; motivo opcional (máx. 250 bytes).
     * No borra físicamente el tramo ni su costo y no reutiliza el orden.
     */
    public function cancelar(): void
    {
        $this->ejecutarEndpoint('POST', function (): array {
            $entrada = $this->datosPost();
            $operacionId = $this->idRequerido($entrada['operacion_id'] ?? null, 'Operación');
            $trasladoId = $this->idRequerido($entrada['id_traslado'] ?? null, 'Traslado');
            $motivo = $entrada['motivo'] ?? '';

            if (!is_string($motivo)) {
                throw new InvalidArgumentException('El motivo de cancelación no es válido.');
            }

            return $this->model->cancelarTramo(
                $operacionId,
                $trasladoId,
                (int)($_SESSION['id_usuario'] ?? 0),
                trim($motivo)
            );
        });
    }

    /* ==========================================================
     * HELPERS DE CONTROLADOR: HTTP / VALIDACIÓN / JSON
     * ========================================================== */

    private function ejecutarEndpoint(string $metodoEsperado, callable $accion): void
    {
        $metodoActual = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($metodoActual !== $metodoEsperado) {
            header('Allow: ' . $metodoEsperado);
            $this->responder([
                'status' => 'error',
                'message' => 'Método HTTP no permitido. Utiliza ' . $metodoEsperado . '.',
            ], 405);
        }

        try {
            $resultado = $accion();
            $this->responder($resultado);
        } catch (InvalidArgumentException $e) {
            $this->responder($this->advertencia($e->getMessage()));
        } catch (Throwable $e) {
            error_log('Traslados MF / controlador: ' . $e->getMessage());
            $this->responder([
                'status' => 'error',
                'message' => 'Error interno al procesar la solicitud de traslados.',
            ], 500);
        }
    }

    private function responder(array $respuesta, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function advertencia(string $mensaje): array
    {
        return [
            'status' => 'warning',
            'message' => $mensaje,
        ];
    }

    private function datosPost(): array
    {
        $tipoContenido = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));

        if (strpos($tipoContenido, 'application/json') !== false) {
            $raw = file_get_contents('php://input', false, null, 0, 1024 * 1024 + 1);
            if ($raw === false || strlen($raw) > 1024 * 1024) {
                throw new InvalidArgumentException('La solicitud supera el tamaño permitido (1 MB).');
            }
            $datos = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($datos)) {
                throw new InvalidArgumentException('El cuerpo JSON de la solicitud es inválido.');
            }
            return $datos;
        }

        // Compatible con FormData, que ya se utiliza en otros controladores MF.
        return $_POST;
    }

    private function datosDelTramo(array $entrada): array
    {
        if (!array_key_exists('monto', $entrada)) {
            throw new InvalidArgumentException('Debes indicar el importe, aunque sea 0.00.');
        }

        // No incluir operacion_id, orden, costo_operacion_id, estatus ni Pagado
        // recibidos del navegador: esos datos los controla el modelo.
        return [
            'origen_bodega_id' => $entrada['origen_bodega_id'] ?? null,
            'destino_bodega_id' => $entrada['destino_bodega_id'] ?? null,
            'transportista_id' => $entrada['transportista_id'] ?? null,
            'monto' => $entrada['monto'],
        ];
    }

    private function idRequerido($valor, string $campo): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) {
            throw new InvalidArgumentException($campo . ': identificador no válido.');
        }
        return (int)$id;
    }

    private function idOpcional($valor, string $campo): int
    {
        if ($valor === null || $valor === '' || $valor === 0 || $valor === '0') {
            return 0;
        }
        return $this->idRequerido($valor, $campo);
    }

    private function esUno($valor): bool
    {
        return $valor === 1 || $valor === '1';
    }

    private function esLista(array $items): bool
    {
        return array_keys($items) === range(0, count($items) - 1);
    }
}
