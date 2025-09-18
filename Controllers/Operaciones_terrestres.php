<?php

class Operaciones_terrestres extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
    }

    /** Vista principal del módulo Ferros en Operación */
    public function index()
    {
        $data['title'] = 'Ferros en Operación';

        // Catálogos para autocompletes (opcional)
        $data['ops']     = $this->model->catalogoOperacionesActivas(); // id_operacion, numero_operacion, cliente
        $data['fisicos'] = $this->model->catalogoFerros();             // id_fisico, numero_ferro

        $this->views->getView($this, "ferros_en_operacion", $data);
    }

    /**
     * GET /operaciones_terrestres/listar
     * Params: q, desde (YYYY-MM-DD), hasta (YYYY-MM-DD), page, perPage
     * Respuesta: JSON con { data, from, to, total, page, per_page, total_pages, pagination_html }
     */
    public function listar()
    {
        // --- Filtros
        $q       = isset($_GET['q'])       ? trim($_GET['q']) : '';
        $desde   = isset($_GET['desde'])   ? trim($_GET['desde']) : '';
        $hasta   = isset($_GET['hasta'])   ? trim($_GET['hasta']) : '';
        $page    = isset($_GET['page'])    ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;

        // Normaliza paginación
        if ($page < 1) $page = 1;
        $allowedPer = [10,25,50,100,200];
        if (!in_array($perPage, $allowedPer, true)) $perPage = 10;

        $filters = [
            'term'      => mb_strtolower($q, 'UTF-8'),
            'date_from' => ($desde !== '' ? $desde : null),
            'date_to'   => ($hasta !== '' ? $hasta : null),
        ];

        // --- Modelo
        $res  = $this->model->listarFerrosPaginado($filters, $page, $perPage);
        $rows = $res['data'] ?? [];
        $meta = $res['meta'] ?? ['total'=>0,'page'=>1,'per_page'=>$perPage,'total_pages'=>1];

        // --- Mapeo a las llaves esperadas por la vista
        // Campos esperados por la tabla:
        // numero_operacion | contenedor_maritimo | bultos_maritimo | cliente | ferro | bultos_asignados
        $data = array_map(function($r){
            // Acepta 'contenedor_maritimo' o 'contenedores_maritimos' del modelo
            $maritimo = '';
            if (isset($r['contenedor_maritimo'])) {
                $maritimo = (string)$r['contenedor_maritimo'];
            } elseif (isset($r['contenedores_maritimos'])) {
                $maritimo = (string)$r['contenedores_maritimos']; // GROUP_CONCAT si hay varios
            }

            return [
                'id'                 => (int)($r['id_row'] ?? 0),
                'numero_operacion'   => (string)($r['numero_operacion'] ?? ''),
                'contenedor_maritimo'=> $maritimo,
                'bultos_maritimo'    => array_key_exists('bultos_maritimo', $r) ? (int)$r['bultos_maritimo'] : null,
                'cliente'            => (string)($r['cliente'] ?? ''),
                'ferro'              => (string)($r['ferro'] ?? ''),
                'bultos_asignados'   => (int)($r['bultos_asignados'] ?? 0),
            ];
        }, $rows);

        // --- Resumen
        $total = (int)($meta['total'] ?? 0);
        $pp    = (int)($meta['per_page'] ?? $perPage);
        $pg    = (int)($meta['page'] ?? $page);
        $from  = ($total > 0) ? (($pg - 1) * $pp + 1) : 0;
        $to    = ($total > 0) ? min($total, $pg * $pp) : 0;

        // --- Paginación HTML Bootstrap
        $paginationHtml = $this->buildPaginationHtml(
            (int)($meta['total_pages'] ?? 1),
            $pg
        );

        // --- Respuesta
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data'            => $data,
            'from'            => $from,
            'to'              => $to,
            'total'           => $total,
            'page'            => $pg,
            'per_page'        => $pp,
            'total_pages'     => (int)($meta['total_pages'] ?? 1),
            'pagination_html' => $paginationHtml,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Paginación Bootstrap simple */
    private function buildPaginationHtml(int $totalPages, int $currentPage): string
    {
        if ($totalPages <= 1) return '';

        $html = '<li class="page-item'.($currentPage<=1?' disabled':'').'">
                   <a class="page-link" href="#" data-page="'.max(1, $currentPage-1).'">&laquo;</a>
                 </li>';

        // Si quieres acotar el rango visible, aquí puedes limitar i
        for ($i=1; $i <= $totalPages; $i++) {
            $active = ($i === $currentPage) ? ' active' : '';
            $html .= '<li class="page-item'.$active.'">
                        <a class="page-link" href="#" data-page="'.$i.'">'.$i.'</a>
                      </li>';
        }

        $html .= '<li class="page-item'.($currentPage>=$totalPages?' disabled':'').'">
                    <a class="page-link" href="#" data-page="'.min($totalPages, $currentPage+1).'">&raquo;</a>
                  </li>';

        return $html;
    }
    // En Operaciones_terrestres.php
public function sugerencias_operaciones()
{
    $q     = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 15;

    $data = $this->model->sugerenciasOperacionesFerroOP($q, $limit);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'success','data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}
// Controlador: Operaciones_terrestres.php
public function suma_bultos_operacion()
{
    $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
    if ($opId <= 0){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','msg'=>'operacion_id requerido','total_asignados'=>0]);
        exit;
    }
    $row = $this->model->getSumaBultosPorOperacion($opId);
    $total = (int)($row['total_asignados'] ?? 0);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'success','total_asignados'=>$total], JSON_UNESCAPED_UNICODE);
    exit;
}


}



/*
    // Vista principal con tabs
    public function ver($id)
    {
        $data['id_operacion'] = 1;
        $data['title'] = 'Detalles Operacion';
        $this->views->getView('admin/operaciones_terrestres', "ver", $data);
    }
    // TAB : Crear Operación
    public function crear_operacion($id)
    {
        $data['title'] = 'Crear Operación';
        $this->views->getView('admin/operaciones_terrestres/tabs/operaciones_terrestres', "crear_operacion", $data);
    }
    // TAB: Detalles Generales (operaciones + detalles_logisticos)
    public function detalles($id)
    {
        //$data = $this->model->getDetallesOperacion($id);
        $data['title'] = 'Detalles Operacion';
        $this->views->getView('admin/operaciones_terrestres/tabs/detalles_generales', "detalles", $data);
         
    }

    // TAB: Contenedores
    public function contenedores($id)
    {
        //$data = $this->model->getContenedoresPorOperacion($id);
        $data['title'] = 'Contenedores';
        $this->views->getView('admin/operaciones_terrestres/tabs/contenedores', "contenedores", $data);
        
    }

    // TAB: Costos por Contenedor
    public function costos($id)
    {   
        //$data = $this->model->getCostosPorOperacion($id);
        $data['title'] = 'Costos por Contenedor';
        $this->views->getView('admin/operaciones_terrestres/tabs/costos', "costos", $data);
    }

    // TAB: Trazabilidad / Movimientos
    public function trazabilidad($id)
    {
        //$data = $this->model->getTrazabilidadOperacion($id);
        $data['title'] = 'Trazabilidad';
        $this->views->getView('admin/operaciones_terrestres/tabs/trazabilidad', "trazabilidad", $data);
    }

    // TAB: Documentos
    public function documentos($id)
    {
        //$data = $this->model->getDocumentosOperacion($id);
        $data['title'] = 'Documentos';
        $this->views->getView('admin/operaciones_terrestres/tabs/documentos', "documentos", $data);
 
    }
    public function costos_operacion($id)
    {
        $data['title'] = 'Costos por Operación';
        $this->views->getView('admin/operaciones_terrestres/tabs/costos_operacion', "costos", $data);
    }
 
    // TAB: Bitácora / Log
    public function log($id)
    {
        //$data = $this->model->getBitacoraOperacion($id);
        $data['title'] = 'Bitácora';
        $this->views->getView('admin/operaciones_terrestres/tabs/log', "log", $data);
    }
    // TAB: Detalles Logísticos
    public function detalles_logisticos($id)
    {
        //$data = $this->model->getBitacoraOperacion($id);
        $data['title'] = 'Detalles Logísticos';
        $this->views->getView('admin/operaciones_terrestres/tabs/detalles_logisticos', "detalles_logisticos", $data);
    }
        */
 
    
?>
