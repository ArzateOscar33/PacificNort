<?php
class AdminModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUsuario($correo)
    {
        $sql = "SELECT id_usuario, nombre, apellido, correo, clave, telefono, cliente_id
            FROM usuarios
            WHERE correo = ?";
        return $this->select($sql, [$correo]);
    }


    public function getRolUsuario($userId)
    {
        $sql = "SELECT rol_id FROM roles_usuario WHERE usuario_id = ?";
        $data = $this->select($sql, [$userId]);
        return $data ? $data['rol_id'] : null;
    }

    public function registrar($nombre, $apellido, $correo, $hash, $telefono, $puesto_id, $departamento_id, $rol_id)
    {
        // Insertar en la tabla usuarios
        $sql = "INSERT INTO usuarios (nombre,apellido, correo, clave,telefono, puesto_id, departamento_id)
                VALUES (?, ?, ?,?, ?, ?,?)";
        $array = [$nombre, $apellido, $correo, $hash, $telefono, $puesto_id, $departamento_id];
        $userId = $this->insertar($sql, $array);

        if ($userId > 0) {
            // Insertar rol en tabla roles_usuario
            $sql_rol = "INSERT INTO roles_usuario (usuario_id, rol_id) VALUES (?, ?)";
            $array_rol = [$userId, $rol_id];
            $this->insertar($sql_rol, $array_rol);
            return $userId;
        } else {
            return 0;
        }
    }

    public function verificarCorreo($correo)
    {
        $sql = "SELECT correo FROM usuarios WHERE correo = ?";
        return $this->select($sql, [$correo]);
    }

    public function modificar($nombre, $correo, $puesto_id, $departamento_id, $rol_id, $id)
    {
        // Actualizar datos del usuario
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, puesto_id = ?, departamento_id = ? WHERE id_usuario = ?";
        $result = $this->save($sql, [$nombre, $correo, $puesto_id, $departamento_id, $id]);

        // Actualizar rol
        $sql_rol = "UPDATE roles_usuario SET rol_id = ? WHERE usuario_id = ?";
        $this->save($sql_rol, [$rol_id, $id]);

        return $result;
    }
}
