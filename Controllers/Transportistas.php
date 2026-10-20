
<?php
class Transportistas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2, 15]);
    }

    // Valida nomenclatura de cuatro letras.
    private function validarCodigo($codigo)
    {
        return preg_match('/^[A-Z]{4}$/', $codigo) === 1;
    }

    public function index()
    {
        $data['title'] = 'Transportistas';
        $this->views->getView('admin/transportistas', "index", $data);
    }

    /* ===== LISTAR ===== */
    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== REGISTRAR ===== */
    public function registrar()
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo   = trim($_POST['tipo'] ?? '');
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));

        if ($nombre === '' || $tipo === '' || $codigo === '') {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Completa nombre, tipo y código'
            ]);
            die();
        }

        if (!$this->validarCodigo($codigo)) {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Código inválido. Utiliza cuatro letras (ej. TSAN)'
            ]);
            die();
        }

        // Duplicado por (nombre, tipo)
        $existe = $this->model->existeNombreTipo($nombre, $tipo);
        if ($existe) {
            $msg = ((int)$existe['estatus'] === 1)
                ? 'Ya existe un transportista con ese nombre y tipo'
                : 'Existe un transportista con ese nombre y tipo desactivado. Cambia el nombre/tipo o reactívalo.';

            echo json_encode([
                'status' => 'warning',
                'msg' => $msg
            ]);
            die();
        }

        // Verificar que el código no exista.
        if ($this->model->existeCodigo($codigo)) {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Ese código ya pertenece a otro transportista'
            ]);
            die();
        }

        $nuevoId = $this->model->registrar($nombre, $tipo, $codigo);

        if (!$nuevoId) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'No se pudo registrar el transportista'
            ]);
            die();
        }

        echo json_encode([
            'status' => 'success',
            'msg' => 'Transportista registrado correctamente'
        ]);
        die();
    }

    /* ===== OBTENER UNO (para editar) ===== */
    public function editar($id)
    {
        $data = $this->model->obtenerTransportista($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ===== ACTUALIZAR ===== */
    public function actualizar()
    {
        $id_transportista = (int)($_POST['id_transportista'] ?? 0);
        $nombre           = trim($_POST['nombre'] ?? '');
        $tipo             = trim($_POST['tipo'] ?? '');
        $codigo           = strtoupper(trim($_POST['codigo'] ?? ''));

        if (
            $id_transportista <= 0 ||
            $nombre === '' ||
            $tipo === '' ||
            $codigo === ''
        ) {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Completa nombre, tipo y código'
            ]);
            die();
        }

        if (!$this->validarCodigo($codigo)) {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Código inválido. Utiliza cuatro letras (ej. TSAN)'
            ]);
            die();
        }

        // Colisión con OTRO registro (nombre, tipo)
        $dup = $this->model->existeNombreTipoOtro(
            $nombre,
            $tipo,
            $id_transportista
        );

        if ($dup) {
            $msg = ((int)$dup['estatus'] === 1)
                ? 'Ya existe otro transportista con ese nombre y tipo'
                : 'Existe otro transportista con ese nombre y tipo desactivado.';

            echo json_encode([
                'status' => 'warning',
                'msg' => $msg
            ]);
            die();
        }

        // Validar código, excluyendo el registro actual.
        if ($this->model->existeCodigo($codigo, $id_transportista)) {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Ese código ya pertenece a otro transportista'
            ]);
            die();
        }

        $ok = $this->model->actualizar(
            $id_transportista,
            $nombre,
            $tipo,
            $codigo
        );

        if (!$ok) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'No se pudo actualizar el transportista'
            ]);
            die();
        }

        echo json_encode([
            'status' => 'success',
            'msg' => 'Transportista actualizado correctamente'
        ]);
        die();
    }

    /* ===== ELIMINAR (lógico) ===== */
    public function eliminar($id)
    {
        $ok = $this->model->eliminar($id);

        if ($ok) {
            echo json_encode([
                'status' => 'success',
                'msg' => 'Transportista desactivado correctamente'
            ]);
            die();
        }

        echo json_encode([
            'status' => 'error',
            'msg' => 'No se pudo desactivar el transportista'
        ]);
        die();
    }

    /* ===== BÚSQUEDA ===== */
    public function buscar()
    {
        $term = $_GET['term'] ?? '';

        if ($term === '') {
            echo json_encode([]);
            die();
        }

        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
}
