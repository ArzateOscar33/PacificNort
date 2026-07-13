<?php

spl_autoload_register(function (string $class): void {
    $archivo = __DIR__ . '/' . $class . '.php';

    if (is_file($archivo)) {
        require_once $archivo;
    }
});
