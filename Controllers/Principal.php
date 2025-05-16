<?php
class Principal extends Controller
{
    public function __construct() {
        parent::__construct();
        session_start();
    }
    //vista about
    public function servicios()
    {
         
        $data['title'] = 'Servicios';
        $this->views->getView('principal', "servicios", $data);
    }
 
    //Plantilla para vistas 
// public function nombreVista()
// {
//     $data['perfil'] = 'no';
//     $data['title'] = 'Titulo';
//     $this->views->getView('principal', "nombreVista", $data);
// }    
    //vista lista deseos
 
 
 
}