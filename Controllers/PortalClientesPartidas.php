<?php

class PortalClientesPartidas extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Solo rol cliente
        $this->requireRoles([3]);

        if (empty($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        $accion = trim($_GET['url'] ?? '', '/');

        $permitidasSinCliente = [
            'PortalClientesPartida/pendiente',
            'PortalClientesPartida/salir',
        ];

        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);

        if ($clienteId <= 0 && !in_array($accion, $permitidasSinCliente, true)) {
            header('Location: ' . BASE_URL . 'PortalClientes/pendiente');
            exit;
        }
    }

    public function salir()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

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

        session_destroy();

        header('Location: ' . BASE_URL . 'admin');
        exit;
    }

    public function pendiente()
    {
        $data = [];
        $data['title'] = 'Cuenta pendiente de vinculación';
        $data['nombre_usuario'] = $this->model->getNombreUsuario();

        $this->views->getView('PortalClientes', 'pendiente', $data);
    }

    /**
     * Entrada principal del módulo
     */
    public function index()
    {
        $this->op_partidas();
    }

    /**
     * Vista principal del módulo Operaciones por Partida
     */
    public function op_partidas()
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);

        if ($clienteId <= 0) {
            header('Location: ' . BASE_URL . 'PortalClientesPartida/pendiente');
            exit;
        }

        $data = [];
        $data['title'] = 'Operaciones por Partida';
        $data['nombre_cliente'] = $this->model->getNombreCliente();
        $data['nombre_usuario'] = $this->model->getNombreUsuario();

        // Catálogos para filtros
        $data['ciudades'] = $this->model->getCiudadesActivas();
        $data['transportistas'] = $this->model->getTransportistasActivos();

        // KPIs del módulo
        $data['kpis'] = $this->model->kpisPortalClientePartida($clienteId);

        $this->views->getView('PortalClientes', 'op_partidas', $data);
    }

    /**
     * Listado principal de facturas + envío
     * Respuesta JSON para la tabla
     */
    public function listar()
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);

        if ($clienteId <= 0) {
            return $this->jsonResponse([
                'ok' => false,
                'msg' => 'Cliente no vinculado.',
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 15,
            ], 403);
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 15;

        if ($page <= 0) $page = 1;
        if ($perPage <= 0) $perPage = 15;

        $filters = [
            'cliente_id'       => $clienteId,
            'buscar'           => trim((string)($_GET['buscar'] ?? '')),
            'estatus_envio'    => trim((string)($_GET['estatus_envio'] ?? '')),
            'destino_id'       => (int)($_GET['destino_id'] ?? 0),
            'fecha_inicio'     => trim((string)($_GET['fecha_inicio'] ?? '')),
            'fecha_fin'        => trim((string)($_GET['fecha_fin'] ?? '')),
            'transportista_id' => (int)($_GET['transportista_id'] ?? 0),
        ];

        $result = $this->model->listarOperacionesPartidaPortal($filters, $page, $perPage);

        $rows = $result['rows'] ?? [];

        // Opcional: decorar estatus de revisión para la vista
        foreach ($rows as &$row) {
            $row['revision_estatus_texto'] = $this->mapRevisionEstatus((int)($row['revision_estatus'] ?? 0));
            $row['estatus_envio_texto'] = $this->mapEstatusEnvio((string)($row['estatus_envio'] ?? ''));
        }
        unset($row);

        return $this->jsonResponse([
            'ok'       => true,
            'rows'     => $rows,
            'total'    => (int)($result['total'] ?? 0),
            'page'     => (int)($result['page'] ?? $page),
            'per_page' => (int)($result['per_page'] ?? $perPage),
        ]);
    }

    /**
     * Modal de factura:
     * encabezado + productos + fotos de mercancía
     */
    public function verFactura()
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
        $facturaId = (int)($_GET['factura_id'] ?? 0);

        if ($clienteId <= 0 || $facturaId <= 0) {
            return $this->jsonResponse([
                'ok' => false,
                'msg' => 'Parámetros inválidos.'
            ], 400);
        }

        $factura = $this->model->getFacturaDetallePortal($facturaId, $clienteId);

        if (empty($factura)) {
            return $this->jsonResponse([
                'ok' => false,
                'msg' => 'Factura no encontrada.'
            ], 404);
        }

        $productos = $this->model->getFacturaProductosPortal($facturaId, $clienteId);
        $fotos = $this->model->getFacturaFotosMercanciaPortal($facturaId, $clienteId);

        $factura['revision_estatus_texto'] = $this->mapRevisionEstatus((int)($factura['revision_estatus'] ?? 0));

        return $this->jsonResponse([
            'ok' => true,
            'factura' => $factura,
            'productos' => $productos,
            'fotos' => $fotos,
        ]);
    }

    /**
     * Modal de envío:
     * encabezado + facturas + productos + imágenes
     */
    public function verEnvio()
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
        $envioId = (int)($_GET['envio_id'] ?? 0);

        if ($clienteId <= 0 || $envioId <= 0) {
            return $this->jsonResponse([
                'ok' => false,
                'msg' => 'Parámetros inválidos.'
            ], 400);
        }

        $envio = $this->model->getEnvioDetallePortal($envioId, $clienteId);

        if (empty($envio)) {
            return $this->jsonResponse([
                'ok' => false,
                'msg' => 'Envío no encontrado.'
            ], 404);
        }

        $facturas = $this->model->getEnvioFacturasPortal($envioId, $clienteId);
        $productos = $this->model->getEnvioProductosPortal($envioId, $clienteId);
        $imagenes = $this->model->getEnvioImagenesPortal($envioId, $clienteId);

        $envio['estatus_envio_texto'] = $this->mapEstatusEnvio((string)($envio['estatus_envio'] ?? ''));

        return $this->jsonResponse([
            'ok' => true,
            'envio' => $envio,
            'facturas' => $facturas,
            'productos' => $productos,
            'imagenes' => $imagenes,
        ]);
    }

    /**
     * Modal de imágenes de mercancía por producto
     */
    public function verImagenesProducto()
    {
        $clienteId = (int)($_SESSION['cliente_id'] ?? 0);
        $productoId = (int)($_GET['producto_id'] ?? 0);
        $facturaId = (int)($_GET['factura_id'] ?? 0);

        if ($clienteId <= 0 || $productoId <= 0 || $facturaId <= 0) {
            return $this->jsonResponse([
                'ok' => false,
                'msg' => 'Parámetros inválidos.'
            ], 400);
        }

        $imagenes = $this->model->getProductoImagenesPortal($productoId, $facturaId, $clienteId);

        return $this->jsonResponse([
            'ok' => true,
            'imagenes' => $imagenes,
        ]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function mapRevisionEstatus(int $estatus): string
    {
        switch ($estatus) {
            case 1:
                return 'Factura Revisada';
            case 2:
                return 'Envío sin Revisión';
            case 3:
                return 'Factura No Cuadrada';
            case 0:
            default:
                return 'Factura No Revisada';
        }
    }

    private function mapEstatusEnvio(string $estatus): string
    {
        $estatus = trim($estatus);

        if ($estatus === '') {
            return 'Sin envío';
        }

        return $estatus;
    }
}
