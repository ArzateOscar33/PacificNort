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
     * - FO-XX => retorna tramos (tipo=fo)
     * - LBMF/LC/etc => retorna resumen marítimo (tipo=maritimo)
     *
     * URL: base_url + "Rastreo/buscarOperacion"
     * Método: POST
     * Param: numero_operacion
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

        // Normalizar
        $numeroOperacion = strtoupper($numeroOperacion);

        try {

            // =========================
            // 1) FO (Ferro / Terrestre)
            // =========================
            if (strpos($numeroOperacion, 'FO-') === 0) {

                $tramos = $this->model->getTramosPorNumeroOperacionFerro($numeroOperacion);

                if (empty($tramos)) {
                    echo json_encode([
                        'ok'               => false,
                        'tipo'             => 'fo',
                        'msg'              => 'No se encontraron rutas para la operación indicada.',
                        'numero_operacion' => $numeroOperacion
                    ]);
                    return;
                }

                $first = $tramos[0];

                $encabezado = [
                    'numero_operacion' => $first['numero_operacion'] ?? $numeroOperacion,
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

            // =========================
            // 2) Marítimo (LBMF/LC/etc.)
            // =========================
            $rows = $this->model->getResumenOperacionMaritima($numeroOperacion);

            if (empty($rows)) {
                echo json_encode([
                    'ok'               => false,
                    'tipo'             => 'maritimo',
                    'msg'              => 'No se encontró la operación marítima indicada.',
                    'numero_operacion' => $numeroOperacion
                ]);
                return;
            }

            // Encabezado marítimo (solo operación)
            $encabezado = [
                'numero_operacion' => $numeroOperacion,
            ];

            echo json_encode([
                'ok'         => true,
                'tipo'       => 'maritimo',
                'msg'        => 'Operación marítima encontrada correctamente.',
                'encabezado' => $encabezado,
                'data'       => $rows,   // 1 fila por contenedor (o vacío si no hay contenedor ligado)
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
