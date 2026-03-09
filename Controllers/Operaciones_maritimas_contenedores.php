<?php
require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimas_contenedores extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->model = new Operaciones_maritimas_contenedoresModel();
        $this->opLog = new OperacionesLogModel();
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2]);
    }

    /* ===== Helpers de auditoría ===== */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        if ($operacionId <= 0) return;
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) {
                error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}");
            }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: " . $e->getMessage());
        }
    }
    private function makeDesc(string $base, array $info = []): string
    {
        if (empty($info)) return $base;
        $kv = [];
        foreach ($info as $k => $v) {
            $kv[] = "$k=$v";
        }
        return $base . ' (' . implode(', ', $kv) . ')';
    }

    /* ===========================================================
     *  REGISTRAR CONTENEDOR FÍSICO (terrestre)
     * =========================================================== */
    public function registrarFisico()
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $operacion_id = (int)($_POST['operacion_id'] ?? 0);
            $numero_ferro = isset($_POST['numero_ferro']) ? trim($_POST['numero_ferro']) : '';
            $bultos       = isset($_POST['bultosContenedores']) ? (int)$_POST['bultosContenedores'] : null;

            // viene desde la vista pero lo validaremos contra la operación
            $cliente_id_in = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;

            if ($operacion_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Falta operación']);
                return;
            }
            if ($numero_ferro === '') {
                echo json_encode(['status' => 'warning', 'msg' => 'Captura o selecciona el contenedor físico']);
                return;
            }
            if ($bultos !== null && $bultos < 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Bultos inválidos']);
                return;
            }

            $dupeNum = $this->model->existsFerroEnOperacionPorNumero($operacion_id, $numero_ferro);
            if (!empty($dupeNum)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Este contenedor físico (FERRO) ya está asignado a la operación']);
                return;
            }

            // ✅ Forzar cliente desde la operación (fuente de la verdad)
            $cliRow = $this->model->getClienteDeOperacion($operacion_id);
            if (empty($cliRow) || (int)$cliRow['cliente_id'] <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'La operación no tiene cliente asociado']);
                return;
            }
            $cliente_id = (int)$cliRow['cliente_id'];
            if ($cliente_id_in > 0 && $cliente_id_in !== $cliente_id) {
                // opcional: log/aviso
            }

            // Asegurar contenedor
            $rowFisico = $this->model->findContenedorFisicoByNumero($numero_ferro);
            if (!empty($rowFisico) && isset($rowFisico['estatus']) && (int)$rowFisico['estatus'] === 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'El contenedor existe pero está INACTIVO. Reactívalo primero.']);
                return;
            }
            $fisicoNuevo = false;
            if (!empty($rowFisico)) {
                $id_fisico = (int)$rowFisico['id_fisico'];
            } else {
                $id_fisico = (int)$this->model->insertContenedorFisico($numero_ferro);
                $fisicoNuevo = ($id_fisico > 0);
            }
            if ($id_fisico <= 0) {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo crear/obtener el contenedor físico']);
                return;
            }

            // Duplicado vínculo
            $dupe = $this->model->existsContenedorFisicoOperacion($operacion_id, $id_fisico);
            if (!empty($dupe)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Este contenedor ya está vinculado a la operación']);
                return;
            }

            // Insertar vínculo con cliente de la operación
            $id_vinculo = $this->model->insertContenedorFisicoOperacion($operacion_id, $id_fisico, $cliente_id, $bultos, null);
            if ($id_vinculo <= 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Error al vincular el contenedor a la operación']);
                return;
            }

            // ===== LOG: Físico vinculado (y posible alta de físico) =====
            $desc = $this->makeDesc('Contenedor FISICO vinculado a operación', [
                'cont_op_id'  => $id_vinculo,
                'id_fisico'   => $id_fisico,
                'numero'      => $numero_ferro,
                'bultos'      => ($bultos !== null ? $bultos : '-'),
                'cliente_id'  => $cliente_id,
                'nuevo_fisico' => $fisicoNuevo ? 'SI' : 'NO'
            ]);
            $this->logOp($operacion_id, 'actualizacion', $desc);

            echo json_encode([
                'status' => 'success',
                'msg' => 'Contenedor físico registrado y vinculado',
                'data' => [
                    'id_contenedor_operacion' => $id_vinculo,
                    'id_fisico' => $id_fisico,
                    'numero_ferro' => $numero_ferro,
                    'cliente_id' => $cliente_id
                ]
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]); // en prod: mensaje genérico
        }
    }

    /* ===========================================================
     *  REGISTRAR CONTENEDOR MARÍTIMO (solo vínculo)
     * =========================================================== */
    public function registrarMaritimo()
    {
        try {
            $operacion_id           = (int)($_POST['operacion_id'] ?? 0);
            $contenedor_maritimo_id = (int)($_POST['contenedor_maritimo_id'] ?? 0);

            if ($operacion_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Falta operación']);
                return;
            }
            if ($contenedor_maritimo_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un contenedor marítimo válido']);
                return;
            }

            // Duplicado
            $dupe = $this->model->existsContenedorMaritimoOperacion($operacion_id, $contenedor_maritimo_id);
            if (!empty($dupe)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Este contenedor marítimo ya está vinculado a la operación']);
                return;
            }

            $id_vinculo = $this->model->insertContenedorMaritimoOperacion($operacion_id, $contenedor_maritimo_id);
            if ($id_vinculo <= 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Error al vincular el contenedor marítimo']);
                return;
            }

            // ===== LOG: Marítimo vinculado =====
            $desc = $this->makeDesc('Contenedor MARITIMO vinculado a operación', [
                'cmo_op_id' => $id_vinculo,
                'cmo_id'    => $contenedor_maritimo_id
            ]);
            $this->logOp($operacion_id, 'actualizacion', $desc);

            echo json_encode([
                'status' => 'success',
                'msg'    => 'Contenedor marítimo vinculado correctamente',
                'data'   => [
                    'id'                      => $id_vinculo,
                    'contenedor_maritimo_id'  => $contenedor_maritimo_id
                ]
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
        }
    }

    /* ===========================================================
     *  Listar (paginado)
     * =========================================================== */
    /* ===========================================================
 *  Listar (paginado) con búsqueda, tipo y rango de fechas
 *  - date_from / date_to vienen como YYYY-MM-DD (o con hora)
 *  - si date_to viene sin hora, se extiende a 23:59:59
 * =========================================================== */
    public function listar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Acepta tanto ?term= como ?q= para ser tolerante con el front
        $term = isset($_GET['term']) ? trim((string)$_GET['term'])
            : (isset($_GET['q'])   ? trim((string)$_GET['q'])   : '');

        // Normaliza fechas (si vienen vacías, quedan en null)
        $date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? trim((string)$_GET['date_from']) : null;
        $date_to   = isset($_GET['date_to'])   && $_GET['date_to']   !== '' ? trim((string)$_GET['date_to'])   : null;

        // Si date_to viene solo con fecha (sin tiempo), lo extendemos a fin de día
        if ($date_to !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
            $date_to .= ' 23:59:59';
        }

        $filters = [
            'tipo'      => isset($_GET['tipo']) ? trim(strtolower((string)$_GET['tipo'])) : '',
            'term'      => $term,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        // Paginación segura
        $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $per_page = max(1, min($per_page, 200));

        try {
            $res = $this->model->listarPaginado($filters, $page, $per_page);
            echo json_encode([
                'status' => 'success',
                'data'   => $res['data'],
                'meta'   => $res['meta'],
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'Excepción: ' . $e->getMessage()
            ]);
        }
    }


    /* ===========================================================
     *  DETALLE PARA EDITAR
     * =========================================================== */
    public function detalle()
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $tipo   = isset($_GET['tipo']) ? trim(strtolower($_GET['tipo'])) : '';
            $row_id = (int)($_GET['row_id'] ?? 0);

            if ($tipo === '' || $row_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Parámetros incompletos']);
                return;
            }

            $row = $this->model->getDetalleParaEditar($tipo, $row_id);
            if (!$row) {
                echo json_encode(['status' => 'error', 'msg' => 'Registro no encontrado']);
                return;
            }

            if (isset($row['editable']) && $row['editable'] === false) {
                echo json_encode([
                    'status' => 'warning',
                    'msg'    => $row['motivo_no_editable'] ?? 'Este contenedor se edita en el módulo de Operaciones',
                    'data'   => $row
                ]);
                return;
            }

            echo json_encode([
                'status' => 'success',
                'msg'    => 'Detalle listo',
                'data'   => $row
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
        }
    }

    /* ===========================================================
     *  ACTUALIZAR CONTENEDOR FÍSICO (TERRESTRE)
     * =========================================================== */
    public function actualizarFisico()
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $row_id       = (int)($_POST['row_id'] ?? 0);           // id de contenedores_operacion
            $operacion_id = (int)($_POST['operacion_id'] ?? 0);     // para validar duplicados
            $numero_ferro = isset($_POST['numero_ferro']) ? trim($_POST['numero_ferro']) : '';
            $bultos       = (isset($_POST['bultosContenedores']) && $_POST['bultosContenedores'] !== '') ? (int)$_POST['bultosContenedores'] : null;
            $comentarios  = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : null;

            if ($row_id <= 0 || $operacion_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Faltan identificadores']);
                return;
            }
            if ($numero_ferro === '') {
                echo json_encode(['status' => 'warning', 'msg' => 'Captura el número de contenedor físico']);
                return;
            }
            if ($bultos !== null && $bultos < 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Bultos inválidos']);
                return;
            }

            $dupeNum = $this->model->existsFerroEnOperacionPorNumeroExcept($operacion_id, $numero_ferro, $row_id);
            if (!empty($dupeNum)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ya existe otro contenedor con ese número en la operación']);
                return;
            }

            // Actualiza (el modelo valida/asegura el físico y el vínculo)
            $res = $this->model->actualizarTerrestreByNumero(
                $row_id,
                $operacion_id,
                $numero_ferro,
                $bultos,
                $comentarios
            );

            // LOG si el modelo reporta éxito
            if (is_array($res) && ($res['status'] ?? '') === 'success') {
                $desc = $this->makeDesc('Contenedor FISICO actualizado', [
                    'cont_op_id' => $row_id,
                    'numero'     => $numero_ferro,
                    'bultos'     => ($bultos !== null ? $bultos : '-'),
                    'coment'     => ($comentarios !== null && $comentarios !== '' ? mb_substr($comentarios, 0, 60) . '…' : '')
                ]);
                $this->logOp($operacion_id, 'actualizacion', $desc);
            }

            echo json_encode($res);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
        }
    }
}
