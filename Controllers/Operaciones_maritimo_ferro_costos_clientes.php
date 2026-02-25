<?php

class Operaciones_maritimo_ferro_costos_clientes extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        require_once "Models/OperacionesLogModel.php";
        $this->opLog = new OperacionesLogModel();
    }
}
