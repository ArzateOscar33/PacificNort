<?php
class Home extends Controller
{
    public function __construct() {
        parent::__construct();
         
    }
    public function index()
    {
        if (!empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin/home');
            exit;
        }
        $data['title'] = 'Acceso al sistema';
        $this->views->getView('principal', "index", $data);
    }

 

    
}