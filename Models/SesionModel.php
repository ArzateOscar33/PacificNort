<?php
class SesionModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function verificarToken($userId)
    {
        $sql = "SELECT session_token FROM usuarios WHERE id_usuario = ?";
        return $this->select($sql, [$userId]);
    }

    public function guardarToken($userId, $token)
    {
        $sql = "UPDATE usuarios SET session_token = ? WHERE id_usuario = ?";
        return $this->save($sql, [$token, $userId]);
    }

    public function limpiarToken($userId)
    {
        $sql = "UPDATE usuarios SET session_token = NULL WHERE id_usuario = ?";
        return $this->save($sql, [$userId]);
    }
}
