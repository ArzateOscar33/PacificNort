<?php
class SubTipoOperacion extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        
    }
    private function normalizarPrefijo(?string $p): ?string {
    $p = trim((string)$p);
    if ($p === '') return null;              // permitir vacío (NULL)
    $p = mb_strtoupper($p, 'UTF-8');
    // Acepta letras/números/guion; cambia el rango a tu gusto
    if (!preg_match('/^[A-Z0-9-]{1,8}$/', $p)) return null; 
    return $p;
}
    public function index()
    {
    $data['title'] = 'Subtipos De Operacion';
    $data['tipos_operacion'] = $this->model->getTipoOperacion();
    $data['puertos'] = $this->model->getPuertos();

    // 👉 IDs de tipos que requieren puerto (Marítimo y Marítimo-Ferroviario)
    $data['tipos_con_puerto_ids'] = $this->getIdsTiposMaritimos();
        $this->views->getView('admin/subtipos_operacion', "index", $data);
    }
        public function listar()
    {
        $data = $this->model->getSubTiposOperacion();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
public function registrar()
{
    $nombre            = trim($_POST['nombreSubtipoOperacion'] ?? '');
    $id_tipo_operacion = (int)($_POST['tipo_operacion_id'] ?? 0);  // 👈 int
    $clave             = trim($_POST['claveSubtipoOperacion'] ?? '');
    $puerto_id_raw     = $_POST['puerto_id'] ?? null;
    $prefijo_raw       = $_POST['prefijo_codigo'] ?? null;
    $prefijo           = $this->normalizarPrefijo($prefijo_raw);

    if (empty($nombre) || empty($clave) || empty($id_tipo_operacion)) {
        echo json_encode(['status'=>"error",'msg'=>'Nombre, clave y tipo de operación son obligatorios'], JSON_UNESCAPED_UNICODE); die();
    }

    // 👇 regla única: SOLO MARÍTIMO requiere puerto
    if ($this->tipoRequierePuerto($id_tipo_operacion)) {
        if (empty($puerto_id_raw)) {
            echo json_encode(['status'=>"error",'msg'=>'Este tipo requiere un puerto'], JSON_UNESCAPED_UNICODE); die();
        }
        $puerto_id = (int)$puerto_id_raw;
    } else {
        $puerto_id = null; // 👈 forzar NULL en no-marítimo
    }

    // Duplicados / prefijo
    if ($this->model->existeSubtipoPorClave($clave)) {
        echo json_encode(['status'=>"error",'msg'=>'Ya existe un subtipo con esa clave'], JSON_UNESCAPED_UNICODE); die();
    }
    if ($this->model->existeSubtipoPorNombre($nombre)) {
        echo json_encode(['status'=>"error",'msg'=>'Ya existe un subtipo con ese nombre'], JSON_UNESCAPED_UNICODE); die();
    }
    if (!is_null($prefijo) && $this->model->existePrefijo($prefijo)) {
        echo json_encode(['status'=>"error",'msg'=>'Ese prefijo ya está en uso'], JSON_UNESCAPED_UNICODE); die();
    }

    $ok = $this->model->registrarSubTipoOperacion($id_tipo_operacion, $clave, $nombre, $puerto_id, $prefijo);
    echo json_encode($ok ? ['status'=>"success",'msg'=>'Subtipo de operación registrado']
                         : ['status'=>"error",'msg'=>'Error al registrar'], JSON_UNESCAPED_UNICODE);
    die();
}


public function actualizar()
{
    $id                = (int)($_POST['id'] ?? 0);
    $nombre            = trim($_POST['nombreSubtipoOperacion'] ?? '');
    $id_tipo_operacion = (int)($_POST['tipo_operacion_id'] ?? 0); // 👈 int
    $clave             = trim($_POST['claveSubtipoOperacion'] ?? '');
    $puerto_id_raw     = $_POST['puerto_id'] ?? null;
    $prefijo_raw       = $_POST['prefijo_codigo'] ?? null;
    $prefijo           = $this->normalizarPrefijo($prefijo_raw);

    if (empty($id)) { echo json_encode(['status'=>false,'msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE); die(); }
    if (empty($nombre) || empty($clave) || empty($id_tipo_operacion)) {
        echo json_encode(['status'=>false,'msg'=>'Nombre, clave y tipo de operación son obligatorios'], JSON_UNESCAPED_UNICODE); die();
    }

    if ($this->tipoRequierePuerto($id_tipo_operacion)) {
        if (empty($puerto_id_raw)) {
            echo json_encode(['status'=>false,'msg'=>'Este tipo requiere un puerto'], JSON_UNESCAPED_UNICODE); die();
        }
        $puerto_id = (int)$puerto_id_raw;
    } else {
        $puerto_id = null; // 👈 forzar NULL en no-marítimo
    }

    if ($this->model->existeSubtipoPorClave($clave, $id)) {
        echo json_encode(['status'=>false,'msg'=>'Ya existe un subtipo con esa clave'], JSON_UNESCAPED_UNICODE); die();
    }
    if ($this->model->existeSubtipoPorNombre($nombre, $id)) {
        echo json_encode(['status'=>false,'msg'=>'Ya existe un subtipo con ese nombre'], JSON_UNESCAPED_UNICODE); die();
    }
    if (!is_null($prefijo) && $this->model->existePrefijo($prefijo, $id)) {
        echo json_encode(['status'=>false,'msg'=>'Ese prefijo ya está en uso'], JSON_UNESCAPED_UNICODE); die();
    }

    $ok = $this->model->actualizarTipoOperacion($id_tipo_operacion, $clave, $nombre, $puerto_id, $prefijo, $id);
    echo json_encode($ok ? ['status'=>true,'msg'=>'Subtipo de operación actualizado']
                         : ['status'=>false,'msg'=>'No se pudo actualizar'], JSON_UNESCAPED_UNICODE);
    die();
}


    public function editar($id)
    {
        $data = $this->model->getSubtipoOperacion($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
         public function eliminar($id)
    {
        $res = $this->model->eliminarSubtipoOperacion($id);
        echo json_encode([
            'status' => $res ? "success" : "error",
            'msg' => $res ? 'Tipo de operación eliminado' : 'No se pudo eliminar'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function buscar()
    {
        $termino = $_GET['term'] ?? '';
        $data = $this->model->buscarSubtipoOperacion($termino);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
private function getIdsTiposMaritimos(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    // Empata "Marítimo" y "Marítimo-Ferroviario" (ambos empiezan con "Marit")
    $cache = $this->model->getTiposOperacionIdsByNombreLike('Marit%');
    return $cache ?: [];
}

private function tipoRequierePuerto(int $tipo_operacion_id): bool {
    $idsMar = $this->getIdsTiposMaritimos();
    return in_array($tipo_operacion_id, $idsMar, true);
}


    /*







 


*/
}