<?php
class Usuarios extends Controller
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
    public function index() {
        $data['title'] = 'Usuarios';
        $data['departamentos'] = $this->model->listarDepartamentos();
        $data['roles'] = $this->model->listarRoles();
        $this->views->getView('admin/Usuarios', "index", $data);
    }

 
    public function listar(){
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

public function registrar()
{
    header('Content-Type: application/json; charset=utf-8');

    $id           = $_POST['id_usuario'] ?? '';
    $nombre       = trim($_POST['nombre'] ?? '');
    $apellido     = trim($_POST['apellido'] ?? '');
    $correo       = trim($_POST['correo'] ?? '');
    $telefono     = trim($_POST['telefono'] ?? '');
    $puestoId     = $_POST['puesto_id'] ?? '';
    $rolId        = $_POST['rol_id'] ?? '';
    $estatus      = isset($_POST['active']) ? (int)$_POST['active'] : 1;

    // Contraseñas (solo para creación o cuando el admin marca "Cambiar contraseña")
    $claveNueva   = $_POST['nueva_clave'] ?? '';
    $claveConf    = $_POST['confirmar_clave'] ?? '';

    // ---------- Validaciones generales ----------
    if ($nombre === '' || $apellido === '' || $correo === '' || $puestoId === '' || $rolId === '') {
        echo json_encode(['status' => 'warning', 'msg' => 'Campos obligatorios faltantes']); die();
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'warning', 'msg' => 'Correo no válido']); die();
    }

    // Derivar departamento desde puesto (blindaje de consistencia)
    $rowDepto = $this->model->obtenerDepartamentoDePuesto($puestoId);
    if (!$rowDepto || empty($rowDepto['departamento_id'])) {
        echo json_encode(['status' => 'warning', 'msg' => 'El puesto no tiene departamento válido']); die();
    }
    $deptoId = $rowDepto['departamento_id'];

    // ---------- ALTA ----------
    if ($id === '') {
        if ($this->model->existeCorreo($correo)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Ya existe un usuario con ese correo']); die();
        }

        // En alta, la contraseña es obligatoria y debe coincidir
        if ($claveNueva === '' || $claveConf === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'La contraseña es obligatoria']); die();
        }
        if ($claveNueva !== $claveConf) {
            echo json_encode(['status' => 'warning', 'msg' => 'Las contraseñas no coinciden']); die();
        }
        if (mb_strlen($claveNueva) < 8) {
            echo json_encode(['status' => 'warning', 'msg' => 'La contraseña debe tener al menos 8 caracteres']); die();
        }

        $hash = password_hash($claveNueva, PASSWORD_BCRYPT);

        $nuevoId = $this->model->registrarUsuario($nombre, $apellido, $correo, $hash, $telefono, $puestoId, $deptoId, $estatus);
        if (!$nuevoId) { echo json_encode(['status' => 'error', 'msg' => 'Error al registrar usuario']); die(); }

        // Si el insertar no devuelve ID, lo buscamos por correo
        if (!is_numeric($nuevoId)) {
            $row = $this->model->obtenerPorCorreo($correo);
            if (!$row || empty($row['id_usuario'])) {
                echo json_encode(['status' => 'error', 'msg' => 'Usuario creado pero sin ID']); die();
            }
            $nuevoId = $row['id_usuario'];
        }

        if (!$this->model->asignarRol($nuevoId, $rolId)) {
            echo json_encode(['status' => 'warning', 'msg' => 'Usuario creado, pero falló la asignación de rol']); die();
        }

        echo json_encode(['status' => 'success', 'msg' => 'Usuario y rol registrados correctamente']); die();
    }

    // ---------- EDICIÓN ----------
    // Correo duplicado (excluyéndome)
    if ($this->model->existeCorreoOtro($correo, $id)) {
        echo json_encode(['status' => 'warning', 'msg' => 'Otro usuario ya usa ese correo']); die();
    }

    // Si el admin decidió cambiar contraseña: validar y hashear
    $hash = null;
    if ($claveNueva !== '' || $claveConf !== '') {
        if ($claveNueva === '' || $claveConf === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Debes ingresar y confirmar la nueva contraseña']); die();
        }
        if ($claveNueva !== $claveConf) {
            echo json_encode(['status' => 'warning', 'msg' => 'Las contraseñas no coinciden']); die();
        }
        if (mb_strlen($claveNueva) < 8) {
            echo json_encode(['status' => 'warning', 'msg' => 'La contraseña debe tener al menos 8 caracteres']); die();
        }
        $hash = password_hash($claveNueva, PASSWORD_BCRYPT);
    }

    $ok = $this->model->actualizarUsuario($id, $nombre, $apellido, $correo, $telefono, $puestoId, $deptoId, $estatus, $hash);
    if (!$ok) { echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar usuario']); die(); }

    // Reasignar rol (simple: limpiamos y asignamos)
    $this->model->limpiarRolesUsuario($id);
    if (!$this->model->asignarRol($id, $rolId)) {
        echo json_encode(['status' => 'warning', 'msg' => 'Usuario actualizado, pero el rol no se pudo asignar']); die();
    }

    echo json_encode(['status' => 'success', 'msg' => 'Usuario actualizado correctamente']); die();
}




    public function puestosPorDepartamento($id)
    {
        $data = $this->model->listarPuestosPorDepartamento($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function editar($id)
    {
        $data = $this->model->obtenerUsuario($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }


    public function eliminar($id)
    {
        // impedir desactivarse a sí mismo
        if (isset($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] === (int)$id) {
            echo json_encode(['status' => 'warning', 'msg' => 'No puedes desactivar tu propio usuario']); die();
        }

        // validar id numérico
        if (!is_numeric($id) || (int)$id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'ID inválido']); die();
        }

        $ok = $this->model->eliminar($id);
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Usuario desactivado' : 'Error al desactivar'
        ]);
        die();
    }
    public function buscar()
    {
        $term = $_GET['term'] ?? '';
        $data = $this->model->buscar($term);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

}
