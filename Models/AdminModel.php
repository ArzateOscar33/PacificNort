<?php
class AdminModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUsuario($correo)
    {
        $sql = "SELECT * FROM users WHERE correo = ?";
        return $this->select($sql, [$correo]);
    }

    public function getRolUsuario($userId)
    {
        $sql = "SELECT role_id FROM user_roles WHERE user_id = ?";
        $data = $this->select($sql, [$userId]);
        return $data ? $data['role_id'] : null;
    }

     
    public function registrar( $nombre, $apellido, $correo, $hash, $phone, $role_id)
    {
        // Insertar en tabla users
        $sql = "INSERT INTO users (first_name, last_name, correo, clave, phone)
                VALUES (?, ?, ?, ?, ?)";
        $array = [$nombre, $apellido, $correo, $hash, $phone];
        $userId = $this->insertar($sql, $array);

        // Si se insertó correctamente
        if ($userId > 0) {
            $sql_rol = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
            $array_rol = [$userId, $role_id];
            $this->insertar($sql_rol, $array_rol);
            return $userId;
        } else {
            return 0;
        }
    }

    public function verificarCorreo($correo)
    {
        $sql = "SELECT correo FROM users WHERE correo = ? AND active = 1";
        return $this->select($sql, [$correo]);
    }
        public function modificar($nombre, $apellido, $correo, $phone, $role_id, $id)   
    {
        // Actualizar datos del usuario
        $sql = "UPDATE users SET first_name = ?, last_name = ?, correo = ?, phone = ?,
                WHERE id = ?";
        $result = $this->save($sql, [$nombre, $apellido, $correo, $phone,  $id]);

        // Actualizar rol
        $sql_rol = "UPDATE user_roles SET role_id = ? WHERE user_id = ?";
        $this->save($sql_rol, [$role_id, $id]);

        return $result;
    }
 
}
