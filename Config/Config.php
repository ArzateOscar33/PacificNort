<?php
// ======================================================
// CONFIGURACIÓN DINÁMICA DE BASE_URL
// ======================================================
$esHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);

$protocolo = $esHttps ? 'https://' : 'http://';

// HTTP_HOST suele traer: 192.168.1.200, 100.x.x.x, localhost, dominio, etc.
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Carpeta del proyecto
$carpetaProyecto = '/PacificNort/';

// Definir BASE_URL dinámica
define('BASE_URL', $protocolo . $host . $carpetaProyecto);

// ======================================================
// BASE DE DATOS
// ======================================================
const HOST = "localhost";
const USER = "root";
const PASS = "";
const DB = "p_nort";
const CHARSET = "charset=utf8";

// ======================================================
// GENERALES
// ======================================================
const TITLE = "PacificNort Agencia Aduanal";
const MONEDA = "USD";

// ======================================================
// SMTP
// ======================================================
const USER_SMTP = "sistemas@pacificnort.com";
const PASS_SMTP = "Pacific2025.";
const PUERTO_SMTP = 465;
const HOST_SMTP = "mailc75.carrierzone.com";

// ======================================================
// RUTA FÍSICA DEL PROYECTO
// ======================================================
define('UPLOAD_ROOT', rtrim(dirname(__DIR__), "/\\"));
