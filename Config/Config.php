<?php

// ======================================================
// CONFIGURACIÓN PRIVADA DEL ENTORNO
// ======================================================

$configLocal = __DIR__ . '/Config.local.php';

if (!is_file($configLocal)) {
    throw new RuntimeException(
        'Falta el archivo privado Config/Config.local.php'
    );
}

require_once $configLocal;

// ======================================================
// CONFIGURACIÓN DINÁMICA DE BASE_URL
// ======================================================

$esHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (
        isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
    )
    || (
        isset($_SERVER['SERVER_PORT'])
        && (int) $_SERVER['SERVER_PORT'] === 443
    )
);

$protocolo = $esHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$rutaBase = defined('APP_PATH') ? APP_PATH : '/';

// Garantiza una diagonal inicial y final.
$rutaBase = '/' . trim($rutaBase, '/') . '/';

// Para la raíz evita generar "//".
if ($rutaBase === '//') {
    $rutaBase = '/';
}

define('BASE_URL', $protocolo . $host . $rutaBase);

// ======================================================
// CONFIGURACIÓN GENERAL
// ======================================================

const TITLE = 'PacificNort Agencia Aduanal';
const MONEDA = 'USD';

define(
    'UPLOAD_ROOT',
    rtrim(dirname(__DIR__), '/\\')
);
