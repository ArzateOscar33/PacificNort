<?php
class Operaciones_por_partida_documentos extends Controller
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
        $this->requireRoles([1, 11, 2]);
    }



    //DOCS
    public function sugerirFacturasDocs()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

            if ($limit < 1) $limit = 10;
            if ($limit > 25) $limit = 25; // tope razonable para sugerencias

            if ($term === '' || mb_strlen($term) < 2) {
                echo json_encode([
                    'ok' => true,
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Ideal: método dedicado en el MODEL para sugerencias (rápido y con LIMIT)
            // Ej: $rows = $this->model->sugerirFacturas($term, $limit);
            $rows = $this->model->sugerirFacturas($term, $limit);

            echo json_encode([
                'ok' => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_documentos/sugerirFacturasDocs ERROR: " . $e->getMessage());
            echo json_encode([
                'ok' => false,
                'msg' => 'Ocurrió un error al buscar facturas.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    public function listarDocumentosFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
            $term      = isset($_GET['term']) ? trim((string)$_GET['term']) : '';

            if ($facturaId <= 0) {
                echo json_encode([
                    'ok'   => false,
                    'msg'  => 'Factura inválida.',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Si manejas estatus, puedes fijarlo aquí (1=activo)
            $rows = $this->model->listarPorFactura($facturaId, $term, 1);

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_documentos/listarDocumentosFactura ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar documentos.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    public function listarTiposDocumentoOPP()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $rows = $this->model->listarTiposDocumentoOPP();

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_documentos/listarTiposDocumentoOPP ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar tipos de documento.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function getFacturaHeaderDocs()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;

            if ($facturaId <= 0) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Factura inválida.',
                    'factura' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $factura = $this->model->obtenerFactura($facturaId);

            if (!$factura) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Factura no encontrada.',
                    'factura' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'ok' => true,
                'factura' => $factura
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida_documentos/getFacturaHeaderDocs ERROR: " . $e->getMessage());
            echo json_encode([
                'ok' => false,
                'msg' => 'Ocurrió un error al obtener la factura.',
                'factura' => null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    private function registrarOPPDocumentos()
    {
        try {
            // ===== Inputs =====
            $factura_id  = (int)($_POST['factura_id'] ?? 0);
            $tipo_doc_id = (int)($_POST['tipo_documento_id'] ?? 0);
            $notas       = trim((string)($_POST['notas'] ?? ''));

            if ($factura_id <= 0 || $tipo_doc_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Datos inválidos (factura/tipo documento)']);
                return;
            }

            // ===== Validar factura =====
            if (!$this->model->existeFacturaActiva($factura_id)) {
                echo json_encode(['status' => 'warning', 'msg' => 'La factura no existe o está inactiva']);
                return;
            }

            // ===== Validar tipo documento (OPP) =====
            if (!$this->model->existeTipoDocumentoOPP($tipo_doc_id)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Tipo de documento no válido para Operaciones por Partida']);
                return;
            }

            // ===== Files: soporta multiple ("files") y fallback ("archivo") =====
            $filesKey = null;

            if (isset($_FILES['files']) && !empty($_FILES['files']['name'])) {
                $filesKey = 'files';
            } elseif (isset($_FILES['archivo']) && !empty($_FILES['archivo']['name'])) {
                $filesKey = 'archivo';
            }

            if ($filesKey === null) {
                echo json_encode(['status' => 'warning', 'msg' => 'Archivo(s) requerido(s)']);
                return;
            }


            // Normaliza a arreglo
            $f = $_FILES[$filesKey];

            $names = is_array($f['name']) ? $f['name'] : [$f['name']];
            $tmps  = is_array($f['tmp_name']) ? $f['tmp_name'] : [$f['tmp_name']];
            $errs  = is_array($f['error']) ? $f['error'] : [$f['error']];
            $sizes = is_array($f['size']) ? $f['size'] : [$f['size']];
            $types = is_array($f['type']) ? $f['type'] : [$f['type']];

            if (empty($names) || empty($tmps) || empty($tmps[0])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Archivo(s) requerido(s)']);
                return;
            }

            // ===== Obtener numero_factura (para carpeta) =====
            $fac = $this->model->obtenerFactura($factura_id);
            if (!$fac || empty($fac['numero_factura'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Factura no encontrada']);
                return;
            }
            $numFactura = (string)$fac['numero_factura'];

            $root = $this->getProjectRootPath();
            $baseDocs = $root . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'DocumentosPartidas';



            // slugFolder ya la usas en FO, la reutilizamos.
            $facFolder = $this->slugFolder($numFactura);

            $absPath = $baseDocs . DIRECTORY_SEPARATOR . $facFolder;

            if (!is_dir($absPath) && !@mkdir($absPath, 0775, true)) {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo crear la carpeta de destino']);
                return;
            }

            // ===== Validaciones de archivo =====
            $permit  = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'webp'];
            $maxBytes = 50 * 1024 * 1024; // 50MB (igual que tu FO)

            // ===== Insert BD por archivo =====
            $userId = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? null;
            if ($userId === null) {
                error_log('OPP_DOCS_REGISTRAR sin userId.');
            }

            $insertados = 0;
            $fallidos   = 0;
            $errores    = [];

            for ($i = 0; $i < count($names); $i++) {

                $orig = (string)$names[$i];
                $tmp  = (string)$tmps[$i];
                $err  = (int)$errs[$i];
                $size = (int)$sizes[$i];
                $type = (string)($types[$i] ?? '');

                if ($err !== UPLOAD_ERR_OK) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => "Error de upload (code $err)"];
                    continue;
                }
                if (!is_uploaded_file($tmp)) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'Archivo temporal inválido'];
                    continue;
                }

                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, $permit, true)) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'Extensión no permitida'];
                    continue;
                }
                if ($size <= 0 || $size > $maxBytes) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'Tamaño inválido o excede el límite (50MB)'];
                    continue;
                }

                // Mime real (igual que FO)
                $mime = function_exists('mime_content_type')
                    ? (mime_content_type($tmp) ?: ($type ?: 'application/octet-stream'))
                    : ($type ?: 'application/octet-stream');

                // Nombre final
                $uuid     = bin2hex(random_bytes(8));
                $sanOrig  = preg_replace('/[^A-Za-z0-9_.-]/', '_', $orig);
                $fileName = $uuid . '_' . $sanOrig;

                $destAbs = $absPath . DIRECTORY_SEPARATOR . $fileName;

                if (!move_uploaded_file($tmp, $destAbs)) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'No se pudo guardar el archivo'];
                    continue;
                }

                // Hash opcional (si tu tabla NO lo usa, no pasa nada; no lo insertamos)
                $hash = @hash_file('sha256', $destAbs) ?: null;

                // Ruta relativa para BD (y servirlo por web)
                // Importante: usa rutas con / para URL
                $rutaRel = 'Documents/DocumentosPartidas/' . $facFolder . '/' . $fileName;

                // Insert BD
                $newId = $this->model->insertarDocumentoPartida([
                    'factura_id'        => $factura_id,
                    'tipo_documento_id' => $tipo_doc_id,
                    'nombre_archivo'    => $orig,      // nombre original (recomendado)
                    'ruta_archivo'      => $rutaRel,    // ruta relativa
                    'mime_type'         => $mime,
                    'tamano_bytes'      => $size,
                    'notas'             => ($notas !== '' ? $notas : null),
                    'subido_por'        => $userId,
                    // 'hash' => $hash, // solo si tu tabla lo tiene
                ]);

                if ($newId <= 0) {
                    // Si falla BD, opcional: borrar archivo físico para no dejar basura
                    @unlink($destAbs);
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'No se pudo registrar en BD'];
                    continue;
                }

                $insertados++;
            }

            if ($insertados <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => 'No se pudo subir ningún documento',
                    'errors' => $errores
                ]);
                return;
            }

            echo json_encode([
                'status'     => 'success',
                'msg'        => 'Documento(s) subido(s) correctamente',
                'insertados' => $insertados,
                'fallidos'   => $fallidos,
                'errors'     => $errores,
                'factura'    => $numFactura,
                'folder'     => 'Documents/DocumentosPartidas/' . $facFolder . '/'
            ]);
        } catch (Throwable $e) {
            error_log("OPP_DOCS_REGISTRAR: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error inesperado']);
        }
    }
    private function slugFolder($text)
    {
        // Reemplaza espacios y caracteres no permitidos por guiones bajos
        $text = preg_replace('/[^\w]+/', '_', $text);
        // Elimina guiones bajos al inicio y final
        $text = trim($text, '_');
        // Convierte a minúsculas
        $text = strtolower($text);
        return $text;
    }

    private function getProjectRootPath(): string
    {
        if (defined('UPLOAD_ROOT')) {
            return rtrim((string) constant('UPLOAD_ROOT'), "/\\");
        }
        return rtrim(dirname(__DIR__, 2), "/\\");
    }


    public function registrarDocumentosFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Reusa tu implementación privada (la lógica pesada vive ahí)
        $this->registrarOPPDocumentos();
        exit;
    }
    public function eliminarDocumentoFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $idDocumento = (int)($_POST['id_documento'] ?? 0);
            if ($idDocumento <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'ID de documento inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 1) Traer documento de BD
            $doc = $this->model->getDocumentoPartidaById($idDocumento);
            if (!$doc) {
                echo json_encode(['status' => 'warning', 'msg' => 'Documento no encontrado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rutaRel = (string)($doc['ruta_archivo'] ?? '');
            if ($rutaRel === '') {
                echo json_encode(['status' => 'error', 'msg' => 'Ruta de documento inválida en BD.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 2) Seguridad: evitar path traversal / rutas fuera del directorio esperado
            $rutaRelNorm = str_replace('\\', '/', $rutaRel);
            $prefix = 'Documents/DocumentosPartidas/';
            if (strpos($rutaRelNorm, $prefix) !== 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Ruta no permitida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 3) Armar ruta absoluta del archivo
            $root = $this->getProjectRootPath();
            $absFile = rtrim($root, "/\\") . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelNorm);

            // 4) Borrar archivo físico (si existe)
            $fileDeleted = true;
            if (file_exists($absFile)) {
                $fileDeleted = @unlink($absFile);
            }

            // 5) Borrar registro BD
            $okDb = $this->model->eliminarDocumentoPartida($idDocumento);
            if (!$okDb) {
                // Si BD falla pero borraste archivo, te quedaría inconsistente.
                // En este punto es preferible reportar error.
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo eliminar el registro en BD.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 6) Si ya no hay archivos en la carpeta de la factura, eliminar carpeta
            //    Carpeta = dirname del archivo
            $folderAbs = dirname($absFile);

            $folderDeleted = false;
            if (is_dir($folderAbs)) {
                $items = @scandir($folderAbs);
                if (is_array($items)) {
                    // Solo . y ..
                    if (count($items) <= 2) {
                        $folderDeleted = @rmdir($folderAbs);
                    }
                }
            }

            echo json_encode([
                'status'        => 'success',
                'msg'           => 'Documento eliminado correctamente.',
                'id_documento'  => $idDocumento,
                'archivo_borrado' => (bool)$fileDeleted,
                'carpeta_borrada' => (bool)$folderDeleted
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("OPP_DOCS_ELIMINAR: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error inesperado al eliminar.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
