<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimas_documentos extends Controller
{
    // Puedes cambiar la carpeta si quieres separar por módulo
    private const UPLOAD_ROOT = 'C:/xampp/htdocs/PacificNort/Documents/DocumentosOperacion';

    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        $this->opLog = new OperacionesLogModel();
    }

    /* ===== Helpers de auditoría ===== */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        if ($operacionId <= 0) return; // seguridad
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) { error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}"); }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: ".$e->getMessage());
        }
    }
    private function makeDesc(string $base, array $info = []): string
    {
        if (empty($info)) return $base;
        $kv = [];
        foreach ($info as $k => $v) { $kv[] = "$k=$v"; }
        return $base.' ('.implode(', ', $kv).')';
    }

    /* =========================
     *  AUTOCOMPLETE OPERACIONES
     * ========================= */
    public function buscarOperaciones()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if ($term === '') { echo json_encode([]); die(); }

        // Nuevo: ya no contamos contenedores
        $rows = $this->model->buscarOperaciones($term);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* =====================
     *  LISTADO POR OPERACIÓN
     * ===================== */
public function listar()
{
  header('Content-Type: application/json; charset=UTF-8');
  $operacion_id           = (int)($_GET['operacion_id'] ?? 0);
  $contenedor_maritimo_id = (int)($_GET['contenedor_maritimo_id'] ?? 0);

  if ($operacion_id <= 0) { echo json_encode(['error'=>'operacion_id es requerido']); return; }

  try {
    if ($contenedor_maritimo_id > 0) {
        $cmo_id = $this->model->getCMOId($operacion_id, $contenedor_maritimo_id);
        $rows   = $cmo_id ? $this->model->listarDocumentosPorCMO($cmo_id) : [];
    } else {
        $rows = $this->model->listarDocumentosOperacion($operacion_id); // compat
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  } catch (\Throwable $e) {
    error_log("DOCS_LISTAR: ".$e->getMessage());
    echo json_encode([]);
  }
}



    /* ==================================
     *  TIPOS DE DOCUMENTO (OPERACIÓN)
     * ================================== */
    public function tipos()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Soporta compatibilidad: si viene contenedor_tipo=O o no viene, usamos operación
        $solo_activos = isset($_GET['solo_activos']) ? (int)$_GET['solo_activos'] : 1;
        $q            = isset($_GET['q']) ? trim($_GET['q']) : null;

        try {
            $rows = $this->model->tiposDocumentoOperacion($solo_activos === 1, $q);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("DOCS_TIPOS: " . $e->getMessage());
            echo json_encode([]);
        }
        die();
    }

    /* =========================
     *  REGISTRO (UPLOAD) POR OPERACIÓN
     * ========================= */
 public function registrar()
{
  header('Content-Type: application/json; charset=UTF-8');
  try {
    $operacion_id           = (int)($_POST['operacion_id'] ?? 0);
    $tipo_doc_id            = (int)($_POST['tipo_documento_id'] ?? 0);
    $contenedor_maritimo_id = (int)($_POST['contenedor_maritimo_id'] ?? 0);

    if ($operacion_id <= 0 || $tipo_doc_id <= 0 || $contenedor_maritimo_id <= 0) {
        echo json_encode(['status'=>'warning','msg'=>'Selecciona operación y contenedor marítimo']); return;
    }
    if (empty($_FILES['archivo']['tmp_name'])) {
        echo json_encode(['status'=>'warning','msg'=>'Archivo requerido']); return;
    }

    // === Construcción de ruta: EN-01 / MG000002
    $numOp = $this->model->getNumeroOperacion($operacion_id);
    if (!$numOp) { echo json_encode(['status'=>'warning','msg'=>'Operación no encontrada']); return; }

    $numCont = $this->model->getNumeroContenedorMaritimo($contenedor_maritimo_id) ?: 'CMO_'.$contenedor_maritimo_id;

    // Hallar CMO (vínculo op↔contenedor) para guardar en BD
    $cmo_id = $this->model->getCMOId($operacion_id, $contenedor_maritimo_id);
    if (!$cmo_id) {
        echo json_encode(['status'=>'warning','msg'=>'El contenedor no pertenece a la operación seleccionada']); return;
    }

    $root     = rtrim(self::UPLOAD_ROOT, '/\\');
    $opFolder = $this->slugFolder(strtoupper($numOp));           // EN-01
    $ctFolder = $this->slugFolder(strtoupper($numCont));         // MG000002
    $absPath  = $root . DIRECTORY_SEPARATOR . $opFolder . DIRECTORY_SEPARATOR . $ctFolder;

    if (!is_dir($absPath) && !@mkdir($absPath, 0775, true)) {
        echo json_encode(['status'=>'error','msg'=>'No se pudo crear la carpeta de destino']); return;
    }

    // === Archivo
    $orig = $_FILES['archivo']['name'];
    $tmp  = $_FILES['archivo']['tmp_name'];
    $size = (int)$_FILES['archivo']['size'];
    $mime = mime_content_type($tmp) ?: ($_FILES['archivo']['type'] ?? null);

    $ext    = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $permit = ['pdf','jpg','jpeg','png','gif','webp','doc','docx','xls','xlsx','txt','csv'];
    if (!in_array($ext, $permit, true)) {
        echo json_encode(['status'=>'warning','msg'=>'Extensión no permitida']); return;
    }

    $uuid     = bin2hex(random_bytes(6));
    $sanOrig  = preg_replace('/[^A-Za-z0-9_.-]/', '_', $orig);

    // 👇 Nombre amigable: EN-01_MG000002_<uuid>_archivo.ext
    $fileName = strtoupper($numOp).'_'.strtoupper($numCont).'_'.$uuid.'_'.$sanOrig;
    $destAbs  = $absPath . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmp, $destAbs)) {
        echo json_encode(['status'=>'error','msg'=>'No se pudo guardar el archivo']); return;
    }

    $hash    = @hash_file('sha256', $destAbs) ?: null;
    $rutaRel = $opFolder . '/' . $ctFolder . '/' . $fileName;

    $userId = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? null;

    // Guardar en BD con CMO
    $ok = $this->model->insertarDocumentoOperacion([
        'operacion_id' => $operacion_id,
        'tipo_doc_id'  => $tipo_doc_id,
        'cmo_id'       => $cmo_id,            // ← CLAVE
        'nombre_orig'  => $orig,
        'ruta'         => $rutaRel,
        'mime'         => $mime,
        'size'         => $size,
        'hash'         => $hash,
        'subido_por'   => $userId,
    ]);
    if (!$ok) { echo json_encode(['status'=>'error','msg'=>'No se pudo registrar en BD']); return; }

    $this->logOp($operacion_id, 'creacion', $this->makeDesc('Documento subido', [
        'doc_tipo_id' => $tipo_doc_id,
        'archivo'     => $orig,
        'ruta'        => $rutaRel,
        'cmo_id'      => $cmo_id,
    ]));

    echo json_encode(['status'=>'success','msg'=>'Documento subido correctamente']);
  } catch (\Throwable $e) {
    error_log("DOCS_REGISTRAR: ".$e->getMessage());
    echo json_encode(['status'=>'error','msg'=>'Error inesperado']);
  }
}



    /* =========================
     *  DESCARGA / PREVIEW
     * ========================= */
    public function ver($id = 0)
    {
        $id = (int)$id;
        if ($id <= 0) { http_response_code(400); echo "Solicitud inválida"; return; }

        $doc = $this->model->getDocumentoPorId($id);
        if (!$doc) { http_response_code(404); echo "Documento no encontrado"; return; }

        $root     = rtrim(self::UPLOAD_ROOT, '/\\');
        $rel      = str_replace(['\\'], '/', $doc['ruta_archivo'] ?? '');
        $abs      = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        $realRoot = realpath($root);
        $realFile = realpath($abs);

        if (!$realFile || strpos($realFile, $realRoot) !== 0 || !is_file($realFile)) {
            http_response_code(404); echo "Archivo no disponible"; return;
        }

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

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: '.$mime);
        header('Content-Length: '.$filesize);
        header('Last-Modified: '.$lastMod);
        if ($etag) header('ETag: '.$etag);
        header('Cache-Control: private, max-age=86400');

        $disposition = $forceDl ? 'attachment' : 'inline';
        header('Content-Disposition: '.$disposition.'; filename="'.basename($filename).'"');

        readfile($realFile);
        exit;
    }

    /* =========================
     *  ELIMINAR
     * ========================= */
    public function eliminar($id = 0)
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status'=>'error','msg'=>'Método no permitido']);
                return;
            }

            $id = (int)$id;
            if ($id <= 0) { echo json_encode(['status'=>'warning','msg'=>'ID inválido']); return; }

            $doc = $this->model->getDocumentoPorId($id);
            if (!$doc) { echo json_encode(['status'=>'warning','msg'=>'Documento no encontrado']); return; }

            $root     = rtrim(self::UPLOAD_ROOT, '/\\');
            $rel      = str_replace(['\\'], '/', $doc['ruta_archivo'] ?? '');
            $abs      = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
            $realRoot = realpath($root);
            $realFile = realpath($abs);

            if ($realFile && strpos($realFile, $realRoot) === 0 && is_file($realFile)) {
                @unlink($realFile);
                $this->rmEmptyDirs(dirname($realFile), $realRoot);
            }

            if (!$this->model->eliminarDocumento($id)) {
                echo json_encode(['status'=>'error','msg'=>'No se pudo eliminar en BD']); return;
            }

            // Log (si tu método getDocumentoPorId incluyera operacion_id, se loguea; si no, se omite)
            $operacionId = (int)($doc['operacion_id'] ?? 0);
            $this->logOp($operacionId, 'cancelacion', $this->makeDesc('Documento eliminado', [
                'doc_id'  => $id,
                'archivo' => ($doc['nombre_archivo'] ?? basename($rel)),
            ]));

            echo json_encode(['status'=>'success','msg'=>'Documento eliminado']);
        } catch (Throwable $e) {
            error_log("DOCS_ELIMINAR: ".$e->getMessage());
            echo json_encode(['status'=>'error','msg'=>'Error inesperado']);
        }
    }

    private function rmEmptyDirs(string $path, string $stopAt): void
    {
        $stopAtReal = realpath($stopAt);
        $pathReal   = realpath($path);
        while ($pathReal && $stopAtReal && strpos($pathReal, $stopAtReal) === 0) {
            $scan = @scandir($pathReal);
            if ($scan === false || count(array_diff($scan, ['.','..'])) > 0) break;
            @rmdir($pathReal);
            $parent  = dirname($pathReal);
            if ($parent === $pathReal) break;
            $pathReal = $parent;
        }
    }

    /* =========================
     *  FALTANTES (OPERACIÓN)
     * ========================= */
    public function faltantes()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $operacion_id = (int)($_GET['operacion_id'] ?? 0);
        if ($operacion_id <= 0) { echo json_encode([]); return; }

        try {
            $rows = $this->model->faltantesPorOperacion($operacion_id);
            echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("DOCS_FALTANTES: ".$e->getMessage());
            echo json_encode([]);
        }
    }

/* ============================================
 *  NOTIFICAR FALTANTES (por CMO u OPERACIÓN)
 * ============================================ */
public function notificarFaltantes()
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $operacion_id           = (int)($_POST['operacion_id'] ?? 0);
        $contenedor_maritimo_id = (int)($_POST['contenedor_maritimo_id'] ?? 0); // ← opcional: si viene, será por contenedor
        $emailOverride          = trim($_POST['email'] ?? '');

        if ($operacion_id <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'Parámetros inválidos: operacion_id requerido','data'=>null]);
            return;
        }

        // ===== Datos base
        $numOp = $this->model->getNumeroOperacion($operacion_id) ?: ('OP '.$operacion_id);
        $cli   = $this->model->getClienteInfoOperacion($operacion_id);
        $clienteNombre = $cli['cliente_nombre'] ?? 'Cliente';
        $clienteEmail  = $cli['cliente_email']  ?? '';

        if ($clienteEmail === '' && $emailOverride === '') {
            echo json_encode(['status'=>'need_email','msg'=>'No se encontró correo del cliente. Proporciona uno.','data'=>null]);
            return;
        }
        $destino = ($emailOverride !== '') ? $emailOverride : $clienteEmail;

        $numContStr = '';
        $faltantes  = [];

        if ($contenedor_maritimo_id > 0) {
            // ======= MODO POR CONTENEDOR =======
            $cmo_id = $this->model->getCMOId($operacion_id, $contenedor_maritimo_id);
            if (!$cmo_id) {
                echo json_encode(['status'=>'warning','msg'=>'El contenedor no pertenece a la operación','data'=>null]);
                return;
            }

            $faltantes = $this->model->faltantesPorCMO($cmo_id);
            if (!is_array($faltantes) || count($faltantes) === 0) {
                echo json_encode(['status'=>'info','msg'=>'No hay documentos faltantes para este contenedor','data'=>[
                    'operacion'=>$numOp,
                    'contenedor'=>$this->model->getNumeroContenedorMaritimo($contenedor_maritimo_id) ?: ('CMO '.$contenedor_maritimo_id),
                    'count'=>0
                ]]);
                return;
            }

            $numContStr = $this->model->getNumeroContenedorMaritimo($contenedor_maritimo_id) ?: ('CMO '.$contenedor_maritimo_id);

            // Asunto y cuerpo
            $numOpH   = htmlspecialchars($numOp, ENT_QUOTES, 'UTF-8');
            $numContH = htmlspecialchars($numContStr, ENT_QUOTES, 'UTF-8');
            $clienteNombreH = htmlspecialchars($clienteNombre, ENT_QUOTES, 'UTF-8');

            $itemsHtml = '';
            foreach ($faltantes as $t) {
                $nombre = htmlspecialchars($t['nombre'] ?? $t['clave'] ?? 'Documento', ENT_QUOTES, 'UTF-8');
                $clave  = htmlspecialchars($t['clave'] ?? '', ENT_QUOTES, 'UTF-8');
                $itemsHtml .= "<li style=\"padding:6px 0;\">{$nombre} ".($clave ? "<small style=\"color:#666;\">({$clave})</small>" : "")."</li>";
            }

            $subject = "Faltantes Op {$numOp} - Contenedor {$numContStr}";
            $body = '
            <div style="max-width:680px;margin:0 auto;font-family:\'Segoe UI\',sans-serif;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #eee;">
              <div style="background:#1c1e74;color:#fff;padding:18px 24px;">
                <h2 style="margin:0;font-size:18px;">Pendientes de documentación</h2>
                <div style="font-size:13px;opacity:.9;">
                  Operación <strong>'.$numOpH.'</strong><br>
                  Contenedor <strong>'.$numContH.'</strong>
                </div>
              </div>
              <div style="padding:22px;color:#333;font-size:15px;line-height:1.5;">
                <p>Hola '.$clienteNombreH.',</p>
                <p>Te compartimos el listado de <strong>documentos faltantes del contenedor '.$numContH.'</strong> para continuar con el proceso:</p>
                <ul style="margin:10px 0 18px 18px;padding:0;">'.$itemsHtml.'</ul>
                <p>Puedes responder a este correo adjuntando los documentos o compartir un enlace de descarga.</p>
                <p>Gracias y saludos,</p>
                <p><strong>'.(defined('TITLE') ? TITLE : 'Equipo').'</strong></p>
              </div>
              <div style="background:#f7f7f7;color:#666;padding:12px 18px;font-size:12px;text-align:center;">
                Este mensaje fue generado por el sistema de gestión de documentos.
              </div>
            </div>';

            $alt = "Documentos faltantes (Op: {$numOp} / Cont: {$numContStr}):\n" .
                   implode("\n", array_map(fn($t)=>"- ".($t['nombre'] ?? $t['clave'] ?? 'Documento'), $faltantes));

            // Enviar
            $this->enviarCorreoFaltantes($destino, $clienteNombre, $subject, $body, $alt);

            // Log
            $this->logOp($operacion_id, 'actualizacion', $this->makeDesc('Notificación faltantes por contenedor enviada', [
                'faltantes'  => count($faltantes),
                'destino'    => $destino,
                'contenedor' => $numContStr
            ]));

            echo json_encode(['status'=>'success','msg'=>"Correo enviado a {$destino}", 'data'=>[
                'scope'     =>'contenedor',
                'operacion' =>$numOp,
                'contenedor'=>$numContStr,
                'count'     =>count($faltantes)
            ]]);
            return;

        } else {
            // ======= MODO POR OPERACIÓN (compatibilidad) =======
            $faltantes = $this->model->faltantesPorOperacion($operacion_id);
            if (!is_array($faltantes) || count($faltantes) === 0) {
                echo json_encode(['status'=>'info','msg'=>'No hay documentos faltantes para la operación','data'=>[
                    'operacion'=>$numOp,'count'=>0
                ]]);
                return;
            }

            // (Opcional) si quieres listar también los contenedores de la op en el correo
            $contListado = $this->model->getContenedoresMaritimosOperacion($operacion_id) ?: [];
            // intenta tomar los labels amigables
            $contStr = implode(', ', array_map(function($it){
                // soporta distintos formatos de retorno
                return htmlspecialchars($it['numero_contenedor'] ?? $it['label'] ?? (string)$it, ENT_QUOTES, 'UTF-8');
            }, $contListado));
            if ($contStr === '') $contStr = 'N/D';

            $numOpH         = htmlspecialchars($numOp, ENT_QUOTES, 'UTF-8');
            $clienteNombreH = htmlspecialchars($clienteNombre, ENT_QUOTES, 'UTF-8');

            $itemsHtml = '';
            foreach ($faltantes as $t) {
                $nombre = htmlspecialchars($t['nombre'] ?? $t['clave'] ?? 'Documento', ENT_QUOTES, 'UTF-8');
                $clave  = htmlspecialchars($t['clave'] ?? '', ENT_QUOTES, 'UTF-8');
                $itemsHtml .= "<li style=\"padding:6px 0;\">{$nombre} ".($clave ? "<small style=\"color:#666;\">({$clave})</small>" : "")."</li>";
            }

            $subject = "Faltantes Operación {$numOp}";
            $body = '
            <div style="max-width:680px;margin:0 auto;font-family:\'Segoe UI\',sans-serif;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #eee;">
              <div style="background:#1c1e74;color:#fff;padding:18px 24px;">
                <h2 style="margin:0;font-size:18px;">Pendientes de documentación</h2>
                <div style="font-size:13px;opacity:.9;">
                  Operación <strong>'.$numOpH.'</strong><br>
                  Contenedores <strong>'.$contStr.'</strong>
                </div>
              </div>
              <div style="padding:22px;color:#333;font-size:15px;line-height:1.5;">
                <p>Hola '.$clienteNombreH.',</p>
                <p>Te compartimos el listado de <strong>documentos faltantes de la operación '.$numOpH.'</strong>:</p>
                <ul style="margin:10px 0 18px 18px;padding:0;">'.$itemsHtml.'</ul>
                <p>Puedes responder a este correo adjuntando los documentos o compartir un enlace de descarga.</p>
                <p>Gracias y saludos,</p>
                <p><strong>'.(defined('TITLE') ? TITLE : 'Equipo').'</strong></p>
              </div>
              <div style="background:#f7f7f7;color:#666;padding:12px 18px;font-size:12px;text-align:center;">
                Este mensaje fue generado por el sistema de gestión de documentos.
              </div>
            </div>';

            $alt = "Documentos faltantes (Op: {$numOp}):\n" .
                   implode("\n", array_map(fn($t)=>"- ".($t['nombre'] ?? $t['clave'] ?? 'Documento'), $faltantes));

            // Enviar
            $this->enviarCorreoFaltantes($destino, $clienteNombre, $subject, $body, $alt);

            // Log
            $this->logOp($operacion_id, 'actualizacion', $this->makeDesc('Notificación faltantes por operación enviada', [
                'faltantes'  => count($faltantes),
                'destino'    => $destino
            ]));

            echo json_encode(['status'=>'success','msg'=>"Correo enviado a {$destino}", 'data'=>[
                'scope'     =>'operacion',
                'operacion' =>$numOp,
                'count'     =>count($faltantes)
            ]]);
            return;
        }

    } catch (Exception $e) {
        error_log('DOCS_NOTIF: '.$e->getMessage());
        echo json_encode(['status'=>'error','msg'=>'No se pudo enviar el correo. '.$e->getMessage(), 'data'=>null]);
    } catch (\Throwable $e) {
        error_log('DOCS_NOTIF_T: '.$e->getMessage());
        echo json_encode(['status'=>'error','msg'=>'Error inesperado.', 'data'=>null]);
    }
}

/** Helper para unificar el envío con PHPMailer */
private function enviarCorreoFaltantes(string $destino, string $nombre, string $subject, string $html, string $alt): void
{
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = HOST_SMTP;
    $mail->SMTPAuth   = true;
    $mail->Username   = USER_SMTP;
    $mail->Password   = PASS_SMTP;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = PUERTO_SMTP;

    $mail->setFrom(USER_SMTP, (defined('TITLE') ? TITLE : 'Sistema'));
    $mail->addAddress($destino, $nombre);

    $userMail = $_SESSION['email'] ?? $_SESSION['correo'] ?? null;
    if ($userMail) { $mail->addCC($userMail); }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = $alt;

    $mail->send();
}


    /* =========================
     *  Utils
     * ========================= */
    private function slugFolder(string $s): string
    {
        $s = trim($s);
        $s = str_replace([' ', "\t"], '_', $s);
        $s = preg_replace('/[^A-Za-z0-9_.-]/', '_', $s);
        return preg_replace('/_+/', '_', $s);
    }
    public function contenedores_por_operacion()
{
    header('Content-Type: application/json; charset=UTF-8');

    $operacion_id = (int)($_GET['operacion_id'] ?? 0);
    $term         = isset($_GET['term']) ? trim($_GET['term']) : null;

    if ($operacion_id <= 0) {
        echo json_encode([]); 
        return;
    }

    try {
        // Usa el método de tu modelo que ya ajustaste:
        // getContenedoresMaritimosOperacion(int $operacion_id, ?string $q = null, int $limit = 30)
        $rows = $this->model->getContenedoresMaritimosOperacion($operacion_id, $term, 30);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        error_log("DOCS_CONT_POR_OP: " . $e->getMessage());
        echo json_encode([]);
    }
}

}
