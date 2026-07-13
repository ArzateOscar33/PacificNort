<?php

class Views
{
    public function getView(
        string $ruta,
        string $vista,
        $data = ''
    ): void {
        $ruta = trim($ruta, '/');

        // Conserva compatibilidad con el comportamiento original.
        if (strcasecmp($ruta, 'home') === 0) {
            $archivo = __DIR__
                . '/../../Views/'
                . $vista
                . '.php';
        } else {
            $segmentos = array_values(
                array_filter(explode('/', $ruta))
            );

            $directoriosPrincipales = [
                'admin' => 'Admin',
                'principal' => 'Principal',
                'portalclientes' => 'PortalClientes',
                'errors' => 'Errors',
                'template' => 'Template',
            ];

            if (!empty($segmentos)) {
                $clave = strtolower($segmentos[0]);

                if (isset($directoriosPrincipales[$clave])) {
                    $segmentos[0] = $directoriosPrincipales[$clave];
                }
            }

            $archivo = __DIR__
                . '/../../Views/'
                . implode('/', $segmentos)
                . '/'
                . $vista
                . '.php';
        }

        if (!is_file($archivo)) {
            error_log("Vista no encontrada: {$archivo}");

            throw new RuntimeException(
                'No fue posible cargar la vista solicitada.'
            );
        }

        require $archivo;
    }
}
