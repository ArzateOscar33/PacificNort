<?php
class Rastreo extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
    }

    public function index()
    {
        $data['title'] = 'Rastreo de Carga';
        $this->views->getView($this, "rastreo", $data);
    }

    /**
     * Endpoint AJAX unificado:
     * - FO-XX => retorna tramos (tipo=fo)  (si lo reactivas)
     * - LBMF/LC/etc o BL => retorna resumen marítimo (tipo=maritimo)
     *
     * URL: base_url + "Rastreo/buscarOperacion"
     * Método: POST
     * Param: numero_operacion  (aquí lo tratamos como "termino")
     */
    public function buscarOperacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Método no permitido.'
            ]);
            return;
        }

        // Este campo puede venir con número de operación o con BL
        $term = isset($_POST['numero_operacion']) ? trim($_POST['numero_operacion']) : '';

        if ($term === '') {
            echo json_encode([
                'ok'  => false,
                'msg' => 'Debes ingresar un número de operación o BL.'
            ]);
            return;
        }

        // Normalizar: mayúsculas y sin espacios (para BL pegado con espacios)
        $termRaw = $term; // por si quieres loggear / mostrar
        $term = strtoupper($term);
        $termNoSpaces = str_replace(' ', '', $term);

        try {

            // =========================
            // 1) FO (Ferro / Terrestre)  (si lo reactivas)
            // =========================
            /*
            if (strpos($termNoSpaces, 'FO-') === 0) {

                $tramos = $this->model->getTramosPorNumeroOperacionFerro($termNoSpaces);

                if (empty($tramos)) {
                    echo json_encode([
                        'ok'               => false,
                        'tipo'             => 'fo',
                        'msg'              => 'No se encontraron rutas para la operación indicada.',
                        'numero_operacion' => $termNoSpaces
                    ]);
                    return;
                }

                $first = $tramos[0];

                $encabezado = [
                    'numero_operacion' => $first['numero_operacion'] ?? $termNoSpaces,
                    'contenedor'       => $first['numero_ferro'] ?? '',
                ];

                echo json_encode([
                    'ok'         => true,
                    'tipo'       => 'fo',
                    'msg'        => 'Rutas encontradas correctamente.',
                    'encabezado' => $encabezado,
                    'tramos'     => $tramos,
                ]);
                return;
            }
            */

            // =========================
            // 2) Marítimo (por Operación o por BL)
            // =========================
            // OJO: el modelo ya debe soportar buscar por numero_operacion OR numero_bl
            $rows = $this->model->getResumenOperacionMaritima($termNoSpaces);

            if (empty($rows)) {
                echo json_encode([
                    'ok'               => false,
                    'tipo'             => 'maritimo',
                    'msg'              => 'No se encontró la operación marítima indicada.',
                    'numero_operacion' => $termNoSpaces
                ]);
                return;
            }

            // Si quieres, detectamos si el usuario metió BL o número de operación
            // - si empieza con prefijos típicos, asumimos operación
            // - si no, asumimos BL (solo para texto del encabezado)
            $isOperacion = (preg_match('/^(LBMF|LC|FO|LBC|LB|MF|OP|PN)/', $termNoSpaces) === 1);

            $encabezado = [
                // tu JS solo usa numero_operacion, pero aquí puedes mostrar el término buscado
                'numero_operacion' => $termNoSpaces,
                'busqueda_tipo'    => $isOperacion ? 'operacion' : 'bl',
            ];

            echo json_encode([
                'ok'         => true,
                'tipo'       => 'maritimo',
                'msg'        => 'Consulta realizada correctamente.',
                'encabezado' => $encabezado,
                'data'       => $rows, // 1 fila por contenedor
            ]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Ocurrió un error al consultar la operación.',
            ]);
            return;
        }
    }
}
