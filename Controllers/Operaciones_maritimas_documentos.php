<?php
class Operaciones_maritimas_documentos extends Controller
{
    private const UPLOAD_ROOT = 'C:/xampp/htdocs/PacificNort/Documents/DocumentosContenedor';
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    }

    // === BUSCAR OPERACIONES (solo las que tengan contenedores) ===
    public function buscarOperaciones()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if ($term === '') {
            echo json_encode([]);
            die();
        }
        $rows = $this->model->buscarOperacionesConContenedores($term);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }

    // === CONTENEDORES POR OPERACIÓN (sugerencias) ===
    public function contenedoresPorOperacion($operacion_id = 0)
    {
        $opId = (int)$operacion_id;
        if ($opId <= 0) {
            echo json_encode([]);
            die();
        }
        $rows = $this->model->contenedoresDeOperacionMixto($opId); // NUEVO
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function listar()
    {
        $operacion_id  = (int)($_GET['operacion_id'] ?? 0);
        $contenedor_id = isset($_GET['contenedor_id']) ? (int)$_GET['contenedor_id'] : null;
        $tipo          = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;

        if ($operacion_id <= 0) {
            echo json_encode(['error' => 'operacion_id es requerido']);
            die();
        }

        $rows = $this->model->listarDocumentosMixto($operacion_id, $contenedor_id, $tipo); // NUEVO
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }


    public function tipos()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Prioridad: contenedor_tipo (F|M|O) > aplica (lista separada por coma)
        $contenedor_tipo = isset($_GET['contenedor_tipo']) ? strtoupper(trim($_GET['contenedor_tipo'])) : null; // 'F'|'M'|'O'
        $aplica_raw      = isset($_GET['aplica']) ? trim($_GET['aplica']) : null; // ej: "operacion,contenedor_fisico"
        $solo_activos    = isset($_GET['solo_activos']) ? (int)$_GET['solo_activos'] : 1;
        $q               = isset($_GET['q']) ? trim($_GET['q']) : null;

        // Mapear contenedor_tipo -> aplica_sobre
        $aplica = null;
        if ($contenedor_tipo === 'F') {
            $aplica = ['contenedor_fisico', 'cualquiera'];
        } elseif ($contenedor_tipo === 'M') {
            $aplica = ['contenedor_maritimo', 'cualquiera'];
        } elseif ($contenedor_tipo === 'O') {
            $aplica = ['operacion', 'cualquiera'];
        } elseif ($aplica_raw) {
            $aplica = array_filter(array_map('trim', explode(',', strtolower($aplica_raw))));
        }

        try {
            $rows = $this->model->tiposDocumentoFiltrados($aplica, $solo_activos === 1, $q);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("DOCS_TIPOS: " . $e->getMessage());
            echo json_encode([]);
        }
        die();
    }
    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $operacion_id    = (int)($_POST['operacion_id'] ?? 0);
            $contenedor_id   = (int)($_POST['contenedor_id'] ?? 0);
            $contenedor_tipo = trim($_POST['contenedor_tipo'] ?? ''); // 'F'|'M'
            $tipo_doc_id     = (int)($_POST['tipo_documento_id'] ?? 0);

            if ($operacion_id <= 0 || $contenedor_id <= 0 || !in_array($contenedor_tipo, ['F', 'M'], true) || $tipo_doc_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Datos inválidos']);
                return;
            }
            if (empty($_FILES['archivo']['tmp_name'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Archivo requerido']);
                return;
            }

            // Validar tipo/documento
            if (!$this->model->validarTipoDocumento($tipo_doc_id, $contenedor_tipo)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Tipo de documento no válido para el destino']);
                return;
            }

            // Obtener nombres para armar ruta
            $numOp = $this->model->getNumeroOperacion($operacion_id);
            if (!$numOp) {
                echo json_encode(['status' => 'warning', 'msg' => 'Operación no encontrada']);
                return;
            }

            $etqCont = $this->model->getEtiquetaContenedor($contenedor_tipo, $contenedor_id);
            if (!$etqCont) {
                echo json_encode(['status' => 'warning', 'msg' => 'Contenedor no encontrado']);
                return;
            }

            // Sanitizar nombres de carpetas
            $opFolder   = $this->slugFolder(strtoupper($numOp) . '_Documentos');
            $contFolder = $this->slugFolder(strtoupper($etqCont));

            // Rutas: absoluta (para guardar) y relativa (para BD)
            $root    = rtrim(self::UPLOAD_ROOT, '/\\');
            $absPath = $root . DIRECTORY_SEPARATOR . $opFolder . DIRECTORY_SEPARATOR . $contFolder;

            // Crear directorios
            if (!is_dir($absPath) && !@mkdir($absPath, 0775, true)) {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo crear la carpeta de destino']);
                return;
            }

            // Validar archivo
            $orig    = $_FILES['archivo']['name'];
            $tmp     = $_FILES['archivo']['tmp_name'];
            $size    = (int)$_FILES['archivo']['size'];
            $mime    = mime_content_type($tmp) ?: ($_FILES['archivo']['type'] ?? null);

            // (Opcional) Valida tamaño/extension
            $ext     = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $permit  = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            if (!in_array($ext, $permit, true)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Extensión no permitida']);
                return;
            }

            // Nombre único + sanitizado
            $uuid    = bin2hex(random_bytes(8));
            $sanOrig = preg_replace('/[^A-Za-z0-9_.-]/', '_', $orig);
            $fileName = $uuid . '_' . $sanOrig;                // ej: 4fd1a2bc_archivo.pdf
            $destAbs = $absPath . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($tmp, $destAbs)) {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo guardar el archivo']);
                return;
            }

            // Hash y ruta relativa para BD
            $hash       = @hash_file('sha256', $destAbs) ?: null;
            $rutaRel    = $opFolder . '/' . $contFolder . '/' . $fileName;

            // Insert BD
            //$subido_por = $_SESSION['id_usuario'] ?? null;
            $userId =
                    $_SESSION['id_usuario']      ??
                    $_SESSION['usuario_id']      ??
                    $_SESSION['id']              ??
                    $_SESSION['admin_id']        ??
                    null;
                    if ($userId === null) {
                    // log opcional para depurar qué llaves tienes en la sesión
                    error_log('DOCS_REGISTRAR sin userId. Session: ' . json_encode(array_keys($_SESSION ?? [])));
}
            $ok = $this->model->insertarDocumento([
                'operacion_id' => $operacion_id,
                'co_id'        => ($contenedor_tipo === 'F' ? $contenedor_id : null),
                'cmo_id'       => ($contenedor_tipo === 'M' ? $contenedor_id : null),
                'tipo_doc_id'  => $tipo_doc_id,
                'nombre_orig'  => $orig,
                'ruta'         => $rutaRel,   // <— guardamos RELATIVA (ej: LI_Documentos/FXEU0101/...)
                'mime'         => $mime,
                'size'         => $size,
                'hash'         => $hash,
                'subido_por'   => $userId,
            ]);

            if (!$ok) {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo registrar en BD']);
                return;
            }

            echo json_encode(['status' => 'success', 'msg' => 'Documento subido correctamente']);
            return;
        } catch (Throwable $e) {
            error_log("DOCS_REGISTRAR: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error inesperado']);
            return;
        }
    }

    // Helper para carpetas seguras
    private function slugFolder(string $s): string
    {
        $s = trim($s);
        $s = str_replace([' ', "\t"], '_', $s);
        $s = preg_replace('/[^A-Za-z0-9_.-]/', '_', $s);
        return preg_replace('/_+/', '_', $s);
    }
    // controllers/Operaciones_maritimas_documentos.php
public function ver($id = 0)
{
    $id = (int)$id;
    if ($id <= 0) { http_response_code(400); echo "Solicitud inválida"; return; }

    $doc = $this->model->getDocumentoPorId($id);
    if (!$doc) { http_response_code(404); echo "Documento no encontrado"; return; }

    // Construir ruta absoluta segura
    $root     = rtrim(self::UPLOAD_ROOT, '/\\');
    $rel      = str_replace(['\\'], '/', $doc['ruta_archivo'] ?? ''); // normaliza separadores
    $abs      = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
    $realRoot = realpath($root);
    $realFile = realpath($abs);

    if (!$realFile || strpos($realFile, $realRoot) !== 0 || !is_file($realFile)) {
        http_response_code(404); echo "Archivo no disponible"; return;
    }

    // Determinar MIME
    $mime = $doc['mime_type'] ?: null;
    if (!$mime) {
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $map = [
            'pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg',
            'png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp',
            'txt'=>'text/plain','csv'=>'text/csv','doc'=>'application/msword',
            'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'=>'application/vnd.ms-excel',
            'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        $mime = $map[$ext] ?? 'application/octet-stream';
    }

    $filename  = $doc['nombre_archivo'] ?: basename($realFile);
    $filesize  = filesize($realFile);
    $etag      = !empty($doc['hash_sha256']) ? '"'.$doc['hash_sha256'].'"' : null;
    $lastMod   = gmdate('D, d M Y H:i:s', filemtime($realFile)) . ' GMT';
    $forceDl   = isset($_GET['dl']) && $_GET['dl'] == '1';

    // Cache básica + validación condicional (opcional)
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: '.$mime);
    header('Content-Length: '.$filesize);
    header('Last-Modified: '.$lastMod);
    if ($etag) header('ETag: '.$etag);
    header('Cache-Control: private, max-age=86400'); // 1 día

    // inline para previsualizar; si ?dl=1 => attachment
    $disposition = $forceDl ? 'attachment' : 'inline';
    header('Content-Disposition: '.$disposition.'; filename="'.basename($filename).'"');

    // Sirve el archivo
    readfile($realFile);
    exit;
}
public function eliminar($id = 0)
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        // Solo por POST para operaciones destructivas
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status'=>'error','msg'=>'Método no permitido']);
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'ID inválido']);
            return;
        }

        // 1) Buscar registro
        $doc = $this->model->getDocumentoPorId($id);
        if (!$doc) {
            echo json_encode(['status'=>'warning','msg'=>'Documento no encontrado']);
            return;
        }

        // 2) Construir ruta absoluta segura
        $root     = rtrim(self::UPLOAD_ROOT, '/\\');
        $rel      = str_replace(['\\'], '/', $doc['ruta_archivo'] ?? '');
        $abs      = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        $realRoot = realpath($root);
        $realFile = realpath($abs);

        // 3) Borrar archivo físico (si existe y está bajo el root)
        if ($realFile && strpos($realFile, $realRoot) === 0 && is_file($realFile)) {
            @unlink($realFile);

            // Limpia directorios vacíos (contenedor y/o operación) — opcional
            $this->rmEmptyDirs(dirname($realFile), $realRoot);
        }

        // 4) Borrar registro en BD
        if (!$this->model->eliminarDocumento($id)) {
            echo json_encode(['status'=>'error','msg'=>'No se pudo eliminar en BD']);
            return;
        }

        echo json_encode(['status'=>'success','msg'=>'Documento eliminado']);
    } catch (Throwable $e) {
        error_log("DOCS_ELIMINAR: ".$e->getMessage());
        echo json_encode(['status'=>'error','msg'=>'Error inesperado']);
    }
}

// Elimina recursivamente carpetas vacías hasta root
private function rmEmptyDirs(string $path, string $stopAt): void
{
    $stopAtReal = realpath($stopAt);
    $pathReal   = realpath($path);
    while ($pathReal && $stopAtReal && strpos($pathReal, $stopAtReal) === 0) {
        // si hay algo dentro, paramos
        $scan = @scandir($pathReal);
        if ($scan === false || count(array_diff($scan, ['.','..'])) > 0) break;
        @rmdir($pathReal);
        $parent  = dirname($pathReal);
        if ($parent === $pathReal) break;
        $pathReal = $parent;
    }
}

}
