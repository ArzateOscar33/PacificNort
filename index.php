<?php

require_once __DIR__ . '/Config/Config.php';

$ruta = trim($_GET['url'] ?? 'home/index', '/');

$segmentos = array_values(
    array_filter(
        explode('/', $ruta),
        static fn($valor) => $valor !== ''
    )
);

$controladorSolicitado = $segmentos[0] ?? 'home';
$metodo = $segmentos[1] ?? 'index';
$parametro = implode(',', array_slice($segmentos, 2));

// Evita intentar cargar rutas arbitrarias.
if (
    !preg_match('/^[A-Za-z0-9_]+$/', $controladorSolicitado)
    || !preg_match('/^[A-Za-z0-9_]+$/', $metodo)
) {
    header('Location: ' . BASE_URL . 'errors');
    exit;
}

require_once __DIR__ . '/Config/App/Autoload.php';
require_once __DIR__ . '/Config/Helpers.php';

$archivoControlador = null;
$nombreControlador = null;
$directorioControladores = __DIR__ . '/Controllers';

// Busca el controlador sin depender de mayúsculas o minúsculas.
foreach (glob($directorioControladores . '/*.php') ?: [] as $archivo) {
    $nombreArchivo = pathinfo($archivo, PATHINFO_FILENAME);

    if (strcasecmp($nombreArchivo, $controladorSolicitado) === 0) {
        $archivoControlador = $archivo;
        $nombreControlador = $nombreArchivo;
        break;
    }
}

if ($archivoControlador === null) {
    header('Location: ' . BASE_URL . 'errors');
    exit;
}

require_once $archivoControlador;

if (!class_exists($nombreControlador, false)) {
    error_log(
        "La clase {$nombreControlador} no existe en {$archivoControlador}"
    );

    header('Location: ' . BASE_URL . 'errors');
    exit;
}

$controlador = new $nombreControlador();

if (!is_callable([$controlador, $metodo])) {
    header('Location: ' . BASE_URL . 'errors');
    exit;
}

$controlador->$metodo($parametro);
