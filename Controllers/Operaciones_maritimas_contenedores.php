<?php
class Operaciones_maritimas_contenedores extends Controller
{
    

    public function __construct()
    {
        parent::__construct();
        $this->model = new Operaciones_maritimas_contenedoresModel();
    }

    /* ===========================================================
     *  REGISTRAR CONTENEDOR FÍSICO (terrestre)
     *  - Crea el contenedor físico si no existe
     *  - Vincula el contenedor con la operación
     *  POST: operacion_id, numero_ferro, [cliente_id], [bultos], [peso]
     * =========================================================== */
public function registrarFisico()
{
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $operacion_id = (int)($_POST['operacion_id'] ?? 0);
        $numero_ferro = isset($_POST['numero_ferro']) ? trim($_POST['numero_ferro']) : '';
        $bultos       = isset($_POST['bultos']) ? (int)$_POST['bultos'] : null;

        // viene desde la vista pero lo validaremos contra la operación
        $cliente_id_in = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;

        if ($operacion_id <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'Falta operación']); return;
        }
        if ($numero_ferro === '') {
            echo json_encode(['status'=>'warning','msg'=>'Captura o selecciona el contenedor físico']); return;
        }
        if ($bultos !== null && $bultos < 0) {
            echo json_encode(['status'=>'warning','msg'=>'Bultos inválidos']); return;
        }

        // ✅ Forzar cliente desde la operación (fuente de la verdad)
        $cliRow = $this->model->getClienteDeOperacion($operacion_id);
        if (empty($cliRow) || (int)$cliRow['cliente_id'] <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'La operación no tiene cliente asociado']); return;
        }
        $cliente_id = (int)$cliRow['cliente_id'];

        // (Opcional) si vino cliente distinto desde el form, lo ignoramos y usamos el de la operación
        if ($cliente_id_in > 0 && $cliente_id_in !== $cliente_id) {
            // puedes loguear o advertir si lo prefieres
        }

        // Asegurar contenedor
        $rowFisico = $this->model->findContenedorFisicoByNumero($numero_ferro);
        if (!empty($rowFisico) && isset($rowFisico['estatus']) && (int)$rowFisico['estatus'] === 0) {
            echo json_encode(['status'=>'warning','msg'=>'El contenedor existe pero está INACTIVO. Reactívalo primero.']); return;
        }
        $id_fisico = !empty($rowFisico) ? (int)$rowFisico['id_fisico'] : (int)$this->model->insertContenedorFisico($numero_ferro);
        if ($id_fisico <= 0) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo crear/obtener el contenedor físico']); return;
        }

        // Duplicado vínculo
        $dupe = $this->model->existsContenedorFisicoOperacion($operacion_id, $id_fisico);
        if (!empty($dupe)) {
            echo json_encode(['status'=>'warning','msg'=>'Este contenedor ya está vinculado a la operación']); return;
        }

        // Insertar vínculo con cliente de la operación
        $id_vinculo = $this->model->insertContenedorFisicoOperacion($operacion_id, $id_fisico, $cliente_id, $bultos, null);
        if ($id_vinculo <= 0) {
            echo json_encode(['status'=>'error','msg'=>'Error al vincular el contenedor a la operación']); return;
        }

        echo json_encode([
            'status'=>'success',
            'msg'=>'Contenedor físico registrado y vinculado',
            'data'=>[
                'id_contenedor_operacion'=>$id_vinculo,
                'id_fisico'=>$id_fisico,
                'numero_ferro'=>$numero_ferro,
                'cliente_id'=>$cliente_id
            ]
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['status'=>'error','msg'=>'Excepción: '.$e->getMessage()]);
    }
}


    /* ===========================================================
     *  REGISTRAR CONTENEDOR MARÍTIMO (solo vínculo)
     *  - NO crea contenedor marítimo automáticamente (por política)
     *  POST: operacion_id, contenedor_maritimo_id
     *  (Opcional: permitir numero_contenedor si luego decides auto-alta)
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
     *  (Opcional) Listar para refrescar la tabla después del registro
     *  GET: [tipo], [term]
     * =========================================================== */
    public function listar()
    {
        $filters = [
            'tipo' => isset($_GET['tipo']) ? trim(strtolower($_GET['tipo'])) : '',
            'term' => isset($_GET['term']) ? trim($_GET['term']) : ''
        ];
        $data = $this->model->listar($filters);
        echo json_encode($data);
    }


    /* ===========================================================
 *  DETALLE PARA EDITAR
 *  GET: tipo, row_id
 *  - Si tipo = maritimo → editable=false y msg para alerta
 *  - Si tipo = terrestre → retorna payload completo para el modal
 * =========================================================== */
public function detalle()
{
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $tipo   = isset($_GET['tipo']) ? trim(strtolower($_GET['tipo'])) : '';
        $row_id = (int)($_GET['row_id'] ?? 0);

        if ($tipo === '' || $row_id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Parámetros incompletos']); return;
        }

        $row = $this->model->getDetalleParaEditar($tipo, $row_id);
        if (!$row) {
            echo json_encode(['status' => 'error', 'msg' => 'Registro no encontrado']); return;
        }

        // Si es marítimo, avisar que no se edita aquí
        if (isset($row['editable']) && $row['editable'] === false) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => $row['motivo_no_editable'] ?? 'Este contenedor se edita en el módulo de Operaciones',
                'data'   => $row
            ]);
            return;
        }

        // Terrestre OK
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
 *  POST: row_id, operacion_id, numero_ferro, [bultos], [comentarios]
 *  - Cambia el contenedor físico (crea si no existe/activo)
 *  - Valida duplicado en la misma operación
 *  - Actualiza bultos/comentarios
 * =========================================================== */
public function actualizarFisico()
{
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $row_id       = (int)($_POST['row_id'] ?? 0);           // id de contenedores_operacion
        $operacion_id = (int)($_POST['operacion_id'] ?? 0);     // para validar duplicados
        $numero_ferro = isset($_POST['numero_ferro']) ? trim($_POST['numero_ferro']) : '';
        $bultos       = (isset($_POST['bultos']) && $_POST['bultos'] !== '') ? (int)$_POST['bultos'] : null;
        $comentarios  = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : null;

        if ($row_id <= 0 || $operacion_id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Faltan identificadores']); return;
        }
        if ($numero_ferro === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Captura el número de contenedor físico']); return;
        }
        if ($bultos !== null && $bultos < 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Bultos inválidos']); return;
        }

        // Reutilizamos el helper de modelo que valida/asegura físico y evita duplicados
        $res = $this->model->actualizarTerrestreByNumero(
            $row_id,
            $operacion_id,
            $numero_ferro,
            $bultos,
            $comentarios
        );

        // respuesta directa del modelo
        echo json_encode($res);
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
    }
}

}
