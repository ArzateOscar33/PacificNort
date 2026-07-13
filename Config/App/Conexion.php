<?php

class Conexion
{
    private PDO $conect;

    public function __construct()
    {
        $dsn = 'mysql:host=' . HOST
            . ';dbname=' . DB
            . ';' . CHARSET;

        try {
            $this->conect = new PDO(
                $dsn,
                USER,
                PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log(
                'Error de conexión a MySQL: ' . $e->getMessage()
            );

            throw new RuntimeException(
                'No fue posible establecer conexión con la base de datos.'
            );
        }
    }

    public function conect(): PDO
    {
        return $this->conect;
    }
}
