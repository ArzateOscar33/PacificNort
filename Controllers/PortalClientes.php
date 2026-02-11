<?php

class PortalClientes extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->requireRoles([3]);

        if (empty($_SESSION['cliente_id']) || (int)$_SESSION['cliente_id'] <= 0) {
            header('Location: ' . BASE_URL . 'admin/salir');
            exit;
        }
    }

    public function index()
    {
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        $data['title'] = 'Portal Cliente';
        $data['nombre_cliente'] = $this->model->getNombreCliente();
        $data['nombre_usuario'] = $this->model->getNombreUsuario();
        $data['estatus_op'] = $this->model->getEstatusOp();

        $this->views->getView('PortalClientes', 'index', $data);
    }

    // ✅ NUEVO: listar operaciones (Marítimas/LBMF) por cliente (JSON)
    public function listarOperacionesCliente()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            // Puedes recibir por POST (FormData) o por GET.
            $in = $_POST ?: $_GET;

            $payload = [
                'cliente_id' => $clienteId,
                'search'     => $in['search'] ?? '',
                'tipo'       => $in['tipo'] ?? '',        // "MAR" | "LBMF" | ""
                'estatus'    => $in['estatus'] ?? 0,      // 0 = todos
                'eta_ini'    => $in['eta_ini'] ?? '',
                'eta_fin'    => $in['eta_fin'] ?? '',
                'page'       => $in['page'] ?? 1,
                'page_size'  => $in['page_size'] ?? 15,
            ];

            $res = $this->model->listarOperacionesCliente($payload);

            echo json_encode([
                'ok'    => true,
                'rows'  => $res['rows'],
                'total' => $res['total'],
            ]);
        } catch (Throwable $e) {
            error_log("PortalClientes::listarOperacionesCliente ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al listar operaciones.']);
        }
    }

    // ✅ NUEVO: detalle de operación Marítima/LBMF + eventos (JSON)
    // Uso: POST/GET PortalClientes/detalleMaritima con { id_operacion }
    public function detalleMaritima()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            $in = $_POST ?: $_GET;
            $opId = (int)($in['id_operacion'] ?? 0);

            if ($opId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID de operación inválido.']);
                return;
            }

            $res = $this->model->obtenerDetalleMaritimaConEventos($clienteId, $opId);
            echo json_encode($res);
        } catch (Throwable $e) {
            error_log("PortalClientes::detalleMaritima ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al obtener detalle.']);
        }
    }


    public function eventosMaritima()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            $in = $_POST ?: $_GET;
            $operacionId = (int)($in['id_operacion'] ?? ($in['id'] ?? 0));
            if ($operacionId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID de operación inválido.']);
                return;
            }

            $rows = $this->model->listarEventosOperacion($clienteId, $operacionId);
            echo json_encode(['ok' => true, 'rows' => $rows]);
        } catch (Throwable $e) {
            error_log("PortalClientes::eventosMaritima ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al listar eventos.']);
        }
    }
}
