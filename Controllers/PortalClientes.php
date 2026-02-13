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
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);

        $data['title'] = 'Portal Cliente';
        $data['nombre_cliente'] = $this->model->getNombreCliente();
        $data['nombre_usuario'] = $this->model->getNombreUsuario();
        $data['estatus_op'] = $this->model->getEstatusOp();
        // ✅ KPIs iniciales (para pintar server-side si quieres)
        $data['kpis'] = ($clienteId > 0) ? $this->model->kpisPortalCliente($clienteId) : [
            'mar_agua'   => 0,
            'mar_puerto' => 0,
            'fo_camino'  => 0,
            'entregadas' => 0,
            'bodegas'    => 0,
            'yardas'     => 0,
        ];

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
            // ✅ sesión
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Sesión sin cliente válido.']);
                return;
            }

            // ✅ input (POST preferente, fallback GET)
            $in = $_POST ?: $_GET;

            $opId = (int)($in['id_operacion'] ?? ($in['operacion_id'] ?? ($in['id'] ?? 0)));
            if ($opId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID de operación inválido.']);
                return;
            }

            // ✅ tipo operación (MAR | LBMF | FO)
            $tipoOp = strtoupper(trim((string)($in['tipo_operacion'] ?? ($in['tipo'] ?? 'MAR'))));
            if (!in_array($tipoOp, ['MAR', 'LBMF', 'FO'], true)) {
                // fallback seguro
                $tipoOp = 'MAR';
            }

            // ✅ contenedor opcional (si luego lo ocupas)
            $contenedorId = isset($in['contenedor_id']) ? (int)$in['contenedor_id'] : null;
            if ($contenedorId !== null && $contenedorId <= 0) $contenedorId = null;

            // 🔒 El MODEL decide el SQL correcto según tipoOp (FO vs MAR/LBMF)
            $rows = $this->model->listarDocumentosOperacionPortal(
                $clienteId,
                $opId,
                $tipoOp,
                $contenedorId
            );

            echo json_encode([
                'ok'    => true,
                'rows'  => is_array($rows) ? $rows : [],
                'total' => is_array($rows) ? count($rows) : 0,
                // útil para debug UI (opcional)
                // 'tipo_operacion' => $tipoOp
            ]);
        } catch (Throwable $e) {
            error_log("PortalClientes::listarDocsOperacion ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error interno al listar documentos.']);
        }
    }

    public function listarTiposDocumentoOperacion()
    {
        // Solo AJAX
        if (
            empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
        ) {
            header("HTTP/1.1 403 Forbidden");
            exit;
        }

        // Sesión cliente
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Sesión no válida.']);
            exit;
        }

        // Params
        $tipo = strtoupper(trim((string)($_GET['tipo'] ?? $_POST['tipo'] ?? '')));
        $operacionId = (int)($_GET['operacion_id'] ?? $_POST['operacion_id'] ?? 0);

        if (!in_array($tipo, ['MAR', 'LBMF', 'FO'], true) || $operacionId <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos.']);
            exit;
        }

        // (Opcional recomendado) Validación de pertenencia al cliente antes de listar:
        // - MAR/LBMF: operaciones.cliente_id
        // - FO: operaciones_ferroviarias.cliente_id
        // Si quieres, lo metemos ya en la siguiente iteración.

        $rows = $this->model->listarTiposDocumentoParaOperacion($tipo, $operacionId);

        echo json_encode([
            'ok' => true,
            'rows' => $rows
        ]);
        exit;
    }
    /* =========================
 *  Path helpers (PORTABLE)
 * ========================= */
    private function getProjectRootPath(): string
    {
        if (defined('UPLOAD_ROOT')) {
            return rtrim((string) constant('UPLOAD_ROOT'), "/\\");
        }
        return rtrim(dirname(__DIR__, 2), "/\\");
    }

    private function getPortalDocsBaseDirAbs(string $tipoOperacion): string
    {
        $root = $this->getProjectRootPath();
        $baseDocuments = $root . DIRECTORY_SEPARATOR . 'Documents';

        $tipoOperacion = strtoupper(trim($tipoOperacion));

        if ($tipoOperacion === 'MAR') {
            return $baseDocuments . DIRECTORY_SEPARATOR . 'DocumentosOperacion';
        }
        return $baseDocuments . DIRECTORY_SEPARATOR . 'DocumentosContenedor';
    }

    private function slugFolder(string $s): string
    {
        $s = trim($s);
        $s = str_replace([" ", "\t"], "_", $s);
        $s = preg_replace('/[^A-Za-z0-9_.-]/', "_", $s);
        return preg_replace('/_+/', "_", $s);
    }

    private function jsonOut(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireAjax(): void
    {
        if (
            empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
        ) {
            $this->jsonOut(['ok' => false, 'msg' => 'Forbidden'], 403);
        }
    }

    public function subirDocOperacion()
    {
        $this->requireAjax();

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            $userId    = (int)($_SESSION['id_usuario'] ?? 0);

            if ($clienteId <= 0) {
                $this->jsonOut(['ok' => false, 'msg' => 'Sesión no válida.'], 401);
            }

            // ===== Params =====
            $opId      = (int)($_POST['id_operacion'] ?? 0);
            $tipoDocId = (int)($_POST['tipo_documento'] ?? 0);

            $contenedorId   = (int)($_POST['contenedor_id'] ?? 0);
            $contenedorTipo = strtoupper(trim((string)($_POST['contenedor_tipo'] ?? ''))); // F|M

            if ($opId <= 0 || $tipoDocId <= 0) {
                $this->jsonOut(['ok' => false, 'msg' => 'Parámetros inválidos.'], 400);
            }

            // ✅ Tipo real por BD (FO/MAR/LBMF) - sin depender del subtipo/texto
            $tipoOp = $this->resolverTipoOperacionReal($clienteId, $opId);
            if ($tipoOp === '') {
                $this->jsonOut(['ok' => false, 'msg' => 'Operación no encontrada o sin acceso.'], 403);
            }

            // ===== Contenedor (OBLIGATORIO por tu lógica de carpetas) =====
            if ($contenedorId <= 0) {
                $this->jsonOut(['ok' => false, 'msg' => 'Debes seleccionar un contenedor.'], 400);
            }

            // Reglas contenedor_tipo por tipo normalizado
            if ($tipoOp === 'MAR') {
                if ($contenedorTipo === '') $contenedorTipo = 'M';
                if ($contenedorTipo !== 'M') {
                    $this->jsonOut(['ok' => false, 'msg' => 'MAR requiere contenedor_tipo=M.'], 400);
                }
            } elseif ($tipoOp === 'FO') {
                if ($contenedorTipo !== 'F') {
                    $this->jsonOut(['ok' => false, 'msg' => 'FO requiere contenedor_tipo=F.'], 400);
                }
            } else { // LBMF
                if (!in_array($contenedorTipo, ['F', 'M'], true)) {
                    $this->jsonOut(['ok' => false, 'msg' => 'LBMF requiere contenedor_tipo=F o M.'], 400);
                }
            }

            // ===== Validar tipo documento =====
            $tipoDoc = $this->model->getTipoDocumentoById($tipoDocId);
            if (empty($tipoDoc)) {
                $this->jsonOut(['ok' => false, 'msg' => 'Tipo de documento inválido.'], 400);
            }

            // ===== Archivo =====
            if (empty($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
                $this->jsonOut(['ok' => false, 'msg' => 'Archivo no recibido.'], 400);
            }

            $f = $_FILES['archivo'];

            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $this->jsonOut(['ok' => false, 'msg' => 'Error de carga (code ' . ($f['error'] ?? -1) . ').'], 400);
            }

            $origName = (string)($f['name'] ?? '');
            $tmpPath  = (string)($f['tmp_name'] ?? '');
            $size     = (int)($f['size'] ?? 0);

            if ($origName === '' || $tmpPath === '' || $size <= 0) {
                $this->jsonOut(['ok' => false, 'msg' => 'Archivo inválido.'], 400);
            }

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $permit = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($ext, $permit, true)) {
                $this->jsonOut(['ok' => false, 'msg' => 'Extensión no permitida. Usa PDF/JPG/PNG.'], 400);
            }

            $maxBytes = 20 * 1024 * 1024; // 20MB
            if ($size > $maxBytes) {
                $this->jsonOut(['ok' => false, 'msg' => 'El archivo excede el límite (20MB).'], 400);
            }

            // ===== Operación (MODELO) =====
            $numOp = (string)$this->model->getNumeroOperacion($tipoOp, $opId);
            if (trim($numOp) === '') {
                $this->jsonOut(['ok' => false, 'msg' => 'Operación no encontrada.'], 404);
            }

            // ===== Contenedor etiqueta (MODELO) =====
            $etqCont = (string)$this->model->getEtiquetaContenedor($tipoOp, $contenedorTipo, $contenedorId);
            if (trim($etqCont) === '') {
                $this->jsonOut(['ok' => false, 'msg' => 'Contenedor no encontrado.'], 404);
            }

            // ===== Rutas =====
            $baseAbs = $this->getPortalDocsBaseDirAbs($tipoOp);

            if ($tipoOp === 'MAR') {
                // Documents/DocumentosOperacion/NombreOperacion/ContenedorMaritimo
                $opFolder   = $this->slugFolder(strtoupper($numOp));
                $contFolder = $this->slugFolder(strtoupper($etqCont));
                $destDirAbs = $baseAbs . DIRECTORY_SEPARATOR . $opFolder . DIRECTORY_SEPARATOR . $contFolder;
            } else {
                // Documents/DocumentosContenedor/NombreOperacion_Documentos/Contenedor
                $opFolder   = $this->slugFolder(strtoupper($numOp) . '_Documentos');
                $contFolder = $this->slugFolder(strtoupper($etqCont));
                $destDirAbs = $baseAbs . DIRECTORY_SEPARATOR . $opFolder . DIRECTORY_SEPARATOR . $contFolder;
            }

            if (!is_dir($destDirAbs) && !@mkdir($destDirAbs, 0775, true)) {
                $this->jsonOut(['ok' => false, 'msg' => 'No se pudo crear carpeta destino.'], 500);
            }

            // ===== Guardar archivo =====
            $uuid     = bin2hex(random_bytes(8));
            $sanOrig  = preg_replace('/[^A-Za-z0-9_.-]/', '_', $origName);
            $fileName = $uuid . '_' . $sanOrig;

            $destAbs = $destDirAbs . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($tmpPath, $destAbs)) {
                $this->jsonOut(['ok' => false, 'msg' => 'No se pudo guardar el archivo.'], 500);
            }

            $mime = @mime_content_type($destAbs) ?: ($f['type'] ?? null);
            $hash = @hash_file('sha256', $destAbs) ?: null;

            // ===== Ruta relativa BD =====
            $carpetaBase = ($tipoOp === 'MAR') ? 'DocumentosOperacion' : 'DocumentosContenedor';
            $rutaRel = 'Documents/' . $carpetaBase . '/' . $opFolder . '/' . $contFolder . '/' . $fileName;

            // ===== IDs contenedor para BD =====
            $coId  = null;
            $cmoId = null;

            if ($tipoOp === 'FO') {
                $coId = $contenedorId;
            } elseif ($tipoOp === 'LBMF') {
                if ($contenedorTipo === 'F') $coId = $contenedorId;
                if ($contenedorTipo === 'M') $cmoId = $contenedorId;
            } else { // MAR
                $cmoId = $contenedorId; // MAR siempre M
            }

            // ===== Insert BD =====
            $docId = (int)$this->model->insertarDocumentoOperacion([
                'operacion_id' => $opId,
                'tipo_documento_id' => $tipoDocId,
                'contenedor_operacion_id' => $coId,
                'cont_maritimo_operacion_id' => $cmoId,
                'nombre_archivo' => $origName,
                'mime_type' => $mime,
                'tamano_bytes' => $size,
                'hash_sha256' => $hash,
                'ruta_archivo' => $rutaRel,
                'subido_por' => ($userId > 0 ? $userId : null),
            ]);

            if ($docId <= 0) {
                @unlink($destAbs);
                $this->jsonOut(['ok' => false, 'msg' => 'No se pudo registrar el documento en BD.'], 500);
            }

            $this->jsonOut([
                'ok' => true,
                'msg' => 'Documento subido correctamente.',
                'id_documento' => $docId,
                'ruta_archivo' => $rutaRel,
            ]);
        } catch (Throwable $e) {
            error_log("PortalClientes::subirDocOperacion ERROR: " . $e->getMessage());
            $this->jsonOut(['ok' => false, 'msg' => 'Error interno al subir documento.'], 500);
        }
    }


    private function resolverTipoOperacionReal(int $clienteId, int $opId): string
    {
        if ($clienteId <= 0 || $opId <= 0) return '';

        // 1) ¿Es FO (directa o vinculada)?
        if (method_exists($this->model, 'foPerteneceAClientePublic')) {
            if ($this->model->foPerteneceAClientePublic($clienteId, $opId)) {
                return 'FO';
            }
        } else {
            // fallback: tu validación vieja (menos robusta)
            if (
                method_exists($this->model, 'operacionPerteneceACliente') &&
                $this->model->operacionPerteneceACliente('FO', $opId, $clienteId)
            ) {
                return 'FO';
            }
        }

        // 2) Si no es FO, debe estar en operaciones
        if (method_exists($this->model, 'operacionPerteneceACliente')) {
            if (!$this->model->operacionPerteneceACliente('MAR', $opId, $clienteId)) {
                return '';
            }
        }

        // 3) Tipo real desde operaciones.tipo_operacion_id
        if (!method_exists($this->model, 'getTipoOperacionIdOperacion')) {
            return ''; // sin este método no podemos resolver “por tipo”
        }

        $tipoId = (int)$this->model->getTipoOperacionIdOperacion($opId);

        return ($tipoId === 1) ? 'MAR' : 'LBMF';
    }


    // ✅ NUEVO: KPIs del Portal Cliente (JSON)
    public function kpis()
    {
        $this->requireAjax();

        try {
            $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
            if ($clienteId <= 0) {
                $this->jsonOut(['ok' => false, 'msg' => 'Sesión sin cliente válido.'], 401);
            }

            // Llama al model (kpisPortalCliente + counts)
            $kpis = $this->model->kpisPortalCliente($clienteId);

            $this->jsonOut([
                'ok' => true,
                'kpis' => $kpis
            ]);
        } catch (Throwable $e) {
            error_log("PortalClientes::kpis ERROR: " . $e->getMessage());
            $this->jsonOut(['ok' => false, 'msg' => 'Error interno al cargar KPIs.'], 500);
        }
    }
}
