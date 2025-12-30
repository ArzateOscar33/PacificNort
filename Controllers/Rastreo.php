<?php
class Rastreo extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
    }

    /**
     * Muestra la página de rastreo (la vista pública con el input y la tabla).
     * Views/Principal/rastreo.php
     */
    public function index()
    {
        $data['title'] = 'Rastreo de Carga';
        // Si tu vista está en Views/Principal/rastreo.php, el segundo parámetro es "rastreo"
        // y el router ya se encarga de usar la carpeta correcta (según tu estructura).
        $this->views->getView($this, "rastreo", $data);
    }

    /**
     * Endpoint AJAX para buscar una operación ferroviaria por número (FO-03, FO-10, etc.)
     * y devolver los tramos de la ruta en formato JSON.
     *
     * URL esperada desde JS:
     *   base_url + "Rastreo/buscarOperacion"
     *
     * Método: POST
     * Parámetro: numero_operacion
     */
    public function buscarOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Solo permitimos POST para este endpoint
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            echo json_encode([
                'ok'  => false,
                'msg' => 'Método no permitido.'
            ]);
            return;
        }

        $numeroOperacion = isset($_POST['numero_operacion'])
            ? trim($_POST['numero_operacion'])
            : '';

        if ($numeroOperacion === '') {
            echo json_encode([
                'ok'  => false,
                'msg' => 'Debes ingresar un número de operación.'
            ]);
            return;
        }

        // Normalizamos a mayúsculas (por si el usuario escribe fo-03)
        $numeroOperacion = strtoupper($numeroOperacion);

        try {
            // Obtenemos los tramos completos para esa operación (FO-XX)
            $tramos = $this->model->getTramosPorNumeroOperacionFerro($numeroOperacion);

            if (empty($tramos)) {
                echo json_encode([
                    'ok'               => false,
                    'msg'              => 'No se encontraron rutas para la operación indicada.',
                    'numero_operacion' => $numeroOperacion
                ]);
                return;
            }

            // Usamos el primer tramo para armar el encabezado (operación + contenedor/caja)
            $first = $tramos[0];

            $encabezado = [
                'numero_operacion' => $first['numero_operacion'] ?? $numeroOperacion,
                'contenedor'       => $first['numero_ferro'] ?? '',   // Contenedor/caja

            ];

            echo json_encode([
                'ok'         => true,
                'msg'        => 'Rutas encontradas correctamente.',
                'encabezado' => $encabezado,
                'tramos'     => $tramos,
            ]);
        } catch (Exception $e) {
            // Manejo básico de error interno
            http_response_code(500);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Ocurrió un error al consultar la operación.',

            ]);
        }
    }
}
