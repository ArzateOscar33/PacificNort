<?php
class Operaciones_maritimo_ferro_trazabilidad extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

 
 
}
