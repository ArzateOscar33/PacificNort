<?php
class Operaciones_maritimas_costos_Contenedor extends Controller
{
    

    public function __construct()
    {
        parent::__construct();
    }

    public function listarPaginado()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Query params
        $page    = (int)($_GET['page']    ?? 1);
        $perPage = (int)($_GET['perPage'] ?? 10);
        $buscar  = trim($_GET['buscar']   ?? '');
        $moneda  = trim($_GET['moneda']   ?? '');            // 'PESOS' | 'DLLS' | ''
        $tipoId  = (int)($_GET['tipo']    ?? 0);             // id_tipo_movimiento

        // Filtros
        $filtros = [
            'buscar'            => $buscar,
            'moneda'            => $moneda,
            'tipo_movimiento_id'=> $tipoId,
        ];

        try {
            $total = $this->model->contarCostos($filtros);
            $rows  = $this->model->listarCostosPaginado($page, $perPage, $filtros);

            $totalPages = (int)ceil($total / max(1, $perPage));
            echo json_encode([
                'status' => 'success',
                'meta' => [
                    'page'       => $page,
                    'perPage'    => $perPage,
                    'total'      => $total,
                    'totalPages' => $totalPages
                ],
                'data' => $rows
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar costos: ' . $e->getMessage()
            ]);
        }
    }
    public function catalogoTiposMovimiento()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $soloGastos = isset($_GET['solo_gastos']) ? (int)$_GET['solo_gastos'] : 1; // default: 1
        try {
            if (method_exists($this->model, 'catalogoTiposMovimiento')) {
                if ($soloGastos) {
                    $rows = $this->model->catalogoTiposMovimiento(); // ya filtra a GASTO
                } else {
                    // versión sin filtro (si la implementas)
                    $rows = $this->model->catalogoTiposMovimiento(false);
                }
                echo json_encode($rows, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }


    /**
     * GET /Operaciones_maritimas_costos_Contenedor/buscarOperaciones?term=JL
     * Respuesta: JSON [{id_operacion, numero_operacion, cliente_id, cliente}, ...]
     */
    public function buscarOperaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $term  = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

        try {
            $rows = $this->model->buscarOperaciones($term, $limit);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /Operaciones_maritimas_costos_Contenedor/buscarContenedoresPorOperacion?operacion_id=7&term=FXE
     * Respuesta: JSON [{contenedor_operacion_id, id_fisico, numero_ferro}, ...]
     */
    public function buscarContenedoresPorOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        $term        = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit       = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if ($operacionId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'warning', 'message' => 'operacion_id es requerido']);
            return;
        }

        try {
            $rows = $this->model->buscarContenedoresPorOperacion($operacionId, $term, $limit);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    
}


