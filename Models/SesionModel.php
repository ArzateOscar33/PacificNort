<?php
class SesionModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function verificarToken($userId)
    {
        $sql = "SELECT session_token FROM users WHERE id = ?";
        return $this->select($sql, [$userId]);
    }

    public function guardarToken($userId, $token)
    {
        $sql = "UPDATE users SET session_token = ? WHERE id = ?";
        return $this->save($sql, [$token, $userId]);
    }

    public function limpiarToken($userId)
    {
        $sql = "UPDATE users SET session_token = NULL WHERE id = ?";
        return $this->save($sql, [$userId]);
    }
}
