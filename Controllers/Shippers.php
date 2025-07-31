<?php
class Shippers extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    public function index()
    {
        $data['title'] = 'Shippers';

        $this->views->getView('admin/Shippers', "index", $data);
    }
 

}