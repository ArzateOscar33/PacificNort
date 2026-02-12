<?php

class PortalClientes extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Solo rol Cliente
        $this->requireRoles([3]);

        // Si no hay sesión de usuario, al login
        if (empty($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        // Detectar ruta actual (según tu router ?url=Controller/metodo)
        $accion = trim($_GET['url'] ?? '', '/'); // ej: "PortalClientes/pendiente"

        $permitidasSinCliente = ['PortalClientes/pendiente', 'PortalClientes/salir', 'PortalClientes/verificarEstado'];

        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);

        if ($clienteId <= 0 && !in_array($accion, $permitidasSinCliente, true)) {
            header('Location: ' . BASE_URL . 'PortalClientes/pendiente');
            exit;
        }
    }


    public function salir()
    {
        // Si hay sesión activa, destruimos todo
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vacía variables
        $_SESSION = [];

        // Borra cookie de sesión (si existe)
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destruye sesión
        session_destroy();

        // Redirige al login (ajusta a tu ruta real)
        header('Location: ' . BASE_URL . 'admin');
        exit;
    }

    public function pendiente()
    {

        $data['title'] = 'Cuenta pendiente de vinculación';
        $data['nombre_usuario'] = $this->model->getNombreUsuario();
        $this->views->getView('PortalClientes', 'pendiente', $data);
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

    public function verificarEstado()
    {
        header('Content-Type: application/json; charset=utf-8');

        $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);

        if ($idUsuario <= 0) {
            echo json_encode(['ok' => false]);
            exit;
        }

        $usuario = $this->model->getUsuarioById($idUsuario);

        if (empty($usuario)) {
            echo json_encode(['ok' => false]);
            exit;
        }

        $clienteId = (int)($usuario['cliente_id'] ?? 0);

        // 🔄 Si ya fue vinculado, actualizamos sesión
        if ($clienteId > 0) {
            $_SESSION['cliente_id'] = $clienteId;
        }

        echo json_encode([
            'ok' => true,
            'vinculado' => $clienteId > 0
        ]);
        exit;
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
    // ✅ NUEVO: listar operaciones FO (Terrestres/Ferro) por cliente (JSON)
    public function listarOperacionesFerroCliente()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            // Puedes recibir por POST (FormData) o por GET
            $in = $_POST ?: $_GET;

            // Page + page_size (si quieres paginar desde el Portal)
            $page     = max(1, (int)($in['page'] ?? 1));
            $pageSize = max(1, min(200, (int)($in['page_size'] ?? 15))); // cap para no matar el server

            // Con tus métodos actuales (sin OFFSET), usamos LIMIT = page * pageSize
            // (Esto no pagina perfecto, pero funciona y no rompe nada. Luego mejoramos el Model con LIMIT/OFFSET)
            $limit = $page * $pageSize;

            $rows  = $this->model->listarOperacionesFerroCliente($clienteId, $limit);
            $total = $this->model->contarOperacionesFerroCliente($clienteId);

            echo json_encode([
                'ok'       => true,
                'rows'     => $rows,
                'total'    => $total,
                'page'     => $page,
                'page_size' => $pageSize,
            ]);
        } catch (Throwable $e) {
            error_log("PortalClientes::listarOperacionesFerroCliente ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al listar operaciones FO.']);
        }
    }

    public function detalleFerro()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            $in = $_POST ?: $_GET;
            $opFerroId = (int)($in['id_operacion_ferro'] ?? 0);

            if ($opFerroId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID FO inválido.']);
                return;
            }

            // Aquí llamarías algo tipo:
            // $res = $this->model->obtenerDetalleFerroCliente($clienteId, $opFerroId);
            // echo json_encode($res);

            echo json_encode(['ok' => false, 'msg' => 'Pendiente: implementar obtenerDetalleFerroCliente en el Model.']);
        } catch (Throwable $e) {
            error_log("PortalClientes::detalleFerro ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al obtener detalle FO.']);
        }
    }


    public function asignacionesFO()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            $in = $_POST ?: $_GET;
            $opFerroId = (int)($in['id_operacion_ferro'] ?? 0);
            if ($opFerroId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID FO inválido.']);
                return;
            }

            $rows = $this->model->listarAsignacionesMaritimasFO($clienteId, $opFerroId);
            echo json_encode(['ok' => true, 'rows' => $rows]);
        } catch (Throwable $e) {
            error_log("PortalClientes::asignacionesFO ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al listar asignaciones.']);
        }
    }
    public function eventosFO()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            $in = $_POST ?: $_GET;
            $opFerroId = (int)($in['id_operacion_ferro'] ?? 0);
            if ($opFerroId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID FO inválido.']);
                return;
            }

            $rows = $this->model->listarEventosFO($clienteId, $opFerroId);
            echo json_encode(['ok' => true, 'rows' => $rows]);
        } catch (Throwable $e) {
            error_log("PortalClientes::eventosFO ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al listar eventos.']);
        }
    }


    //listar documentos 
    public function listarDocsOperacion()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (empty($_SESSION['cliente_id'])) {
                echo json_encode([
                    'ok'  => false,
                    'msg' => 'Sesión sin cliente válido.'
                ]);
                return;
            }

            $clienteId = (int) $_SESSION['cliente_id'];

            $in = $_POST ?: $_GET;
            $opId = (int)($in['id_operacion'] ?? ($in['operacion_id'] ?? ($in['id'] ?? 0)));

            if ($opId <= 0) {
                echo json_encode([
                    'ok'  => false,
                    'msg' => 'ID de operación inválido.'
                ]);
                return;
            }

            // 🔎 Debug opcional (puedes comentar después)
            // error_log("DocsPortal -> cliente: $clienteId | op: $opId");

            $rows = $this->model
                ->listarDocumentosOperacionCliente($clienteId, $opId);

            echo json_encode([
                'ok'    => true,
                'rows'  => is_array($rows) ? $rows : [],
                'total' => is_array($rows) ? count($rows) : 0
            ]);
        } catch (Throwable $e) {

            error_log("PortalClientes::listarDocsOperacion ERROR: " . $e->getMessage());

            echo json_encode([
                'ok'  => false,
                'msg' => 'Error interno al listar documentos.'
            ]);
        }
    }
}
