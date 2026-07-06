<?php
class UsuariosModel extends Query
{
    public function listar()
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.correo,
                    u.telefono,
                    u.cliente_id,
                    COALESCE(c.nombre,'') AS cliente,
                    p.nombre AS puesto,
                    d.nombre AS departamento,
                    GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles
                FROM usuarios u
                LEFT JOIN clientes c       ON c.id_cliente = u.cliente_id
                LEFT JOIN puestos p        ON p.id_puesto = u.puesto_id
                LEFT JOIN departamentos d  ON d.id_departamento = u.departamento_id
                LEFT JOIN roles_usuario ru ON ru.usuario_id = u.id_usuario
                LEFT JOIN roles r          ON r.id_rol = ru.rol_id
                WHERE u.estatus = 1
                GROUP BY 
                    u.id_usuario, u.nombre, u.apellido, u.correo, u.telefono, 
                    u.cliente_id, c.nombre, p.nombre, d.nombre
                ORDER BY u.id_usuario DESC";
        return $this->selectAll($sql) ?: [];
    }

    public function listarRoles()
    {
        $sql = "SELECT id_rol, nombre
                FROM roles
                WHERE estatus = 1
                ORDER BY id_rol DESC";
        return $this->selectAll($sql) ?: [];
    }

    public function listarPuestosPorDepartamento($deptoId)
    {
        $sql = "SELECT id_puesto, nombre
                FROM puestos
                WHERE estatus = 1 AND departamento_id = ?
                ORDER BY nombre ASC";
        return $this->selectAll($sql, [$deptoId]) ?: [];
    }

    public function obtenerDepartamentoDePuesto($puestoId)
    {
        $sql = "SELECT departamento_id
                FROM puestos
                WHERE estatus = 1 AND id_puesto = ?";
        return $this->select($sql, [$puestoId]) ?: false;
    }

    public function listarDepartamentos()
    {
        $sql = "SELECT id_departamento, nombre
                FROM departamentos
                WHERE estatus = 1
                ORDER BY id_departamento DESC";
        return $this->selectAll($sql) ?: [];
    }

    public function listarClientes(): array
    {
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                ORDER BY nombre ASC";
        return $this->selectAll($sql) ?: [];
    }

    public function existeCorreo($correo)
    {
        $sql = "SELECT id_usuario 
                FROM usuarios 
                WHERE estatus = 1 AND LOWER(correo) = LOWER(?)";
        return $this->select($sql, [$correo]);
    }

    /**
     * Registrar usuario
     * - $clienteId puede ser NULL (admin/operador)
     * - si es rol cliente, el controlador debería obligarlo (validación en Controller)
     */
    public function registrarUsuario($nombre, $apellido, $correo, $hash, $tel, $puestoId, $deptoId, $clienteId, $estatus)
    {
        $sql = "INSERT INTO usuarios 
                    (nombre, apellido, correo, clave, telefono, puesto_id, departamento_id, cliente_id, estatus)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->insertar($sql, [
            $nombre,
            $apellido,
            $correo,
            $hash,
            $tel,
            $puestoId,
            $deptoId,
            $clienteId,
            $estatus
        ]);
    }

    // por si insertar no devuelve ID: lo obtenemos por correo
    public function obtenerPorCorreo($correo)
    {
        $sql = "SELECT id_usuario 
                FROM usuarios 
                WHERE LOWER(correo) = LOWER(?) 
                LIMIT 1";
        return $this->select($sql, [$correo]);
    }

    public function asignarRol($usuarioId, $rolId)
    {
        $sql = "INSERT INTO roles_usuario (usuario_id, rol_id) VALUES (?, ?)";
        return $this->insertar($sql, [$usuarioId, $rolId]);
    }

    public function tieneRol($usuarioId, $rolId)
    {
        $sql = "SELECT 1 
                FROM roles_usuario 
                WHERE usuario_id = ? AND rol_id = ? 
                LIMIT 1";
        return $this->select($sql, [$usuarioId, $rolId]);
    }

    public function obtenerUsuario($id)
    {
        $sql = "SELECT 
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.correo,
                    u.telefono,
                    u.puesto_id,
                    u.departamento_id,
                    u.cliente_id,
                    u.estatus,
                    (SELECT ru.rol_id 
                     FROM roles_usuario ru 
                     WHERE ru.usuario_id = u.id_usuario 
                     LIMIT 1) AS rol_id
                FROM usuarios u
                WHERE u.id_usuario = ?";
        return $this->select($sql, [$id]);
    }

    /**
     * Actualizar usuario
     * - $clienteId puede ser NULL
     * - Si $hash viene, actualiza password, si no, lo respeta
     */
    public function actualizarUsuario($id, $nombre, $apellido, $correo, $tel, $puestoId, $deptoId, $clienteId, $estatus, $hash = null)
    {
        if ($hash) {
            $sql = "UPDATE usuarios
                    SET nombre = ?, apellido = ?, correo = ?, telefono = ?, 
                        puesto_id = ?, departamento_id = ?, cliente_id = ?, estatus = ?, clave = ?
                    WHERE id_usuario = ?";
            return $this->save($sql, [
                $nombre,
                $apellido,
                $correo,
                $tel,
                $puestoId,
                $deptoId,
                $clienteId,
                $estatus,
                $hash,
                $id
            ]);
        }

        $sql = "UPDATE usuarios
                SET nombre = ?, apellido = ?, correo = ?, telefono = ?, 
                    puesto_id = ?, departamento_id = ?, cliente_id = ?, estatus = ?
                WHERE id_usuario = ?";
        return $this->save($sql, [
            $nombre,
            $apellido,
            $correo,
            $tel,
            $puestoId,
            $deptoId,
            $clienteId,
            $estatus,
            $id
        ]);
    }

    // Duplicado de correo excluyendo al propio usuario
    public function existeCorreoOtro($correo, $idUsuario)
    {
        $sql = "SELECT id_usuario 
                FROM usuarios 
                WHERE estatus = 1 
                  AND LOWER(correo) = LOWER(?) 
                  AND id_usuario <> ?";
        return $this->select($sql, [$correo, $idUsuario]);
    }

    public function limpiarRolesUsuario($usuarioId)
    {
        $sql = "DELETE FROM roles_usuario WHERE usuario_id = ?";
        return $this->save($sql, [$usuarioId]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE usuarios SET estatus = 0 WHERE id_usuario = ?";
        return $this->save($sql, [$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombre,
                    u.apellido,
                    u.correo,
                    u.telefono,
                    u.cliente_id,
                    COALESCE(c.nombre,'') AS cliente,
                    p.nombre AS puesto,
                    d.nombre AS departamento,
                    GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles
                FROM usuarios u
                LEFT JOIN clientes c       ON c.id_cliente = u.cliente_id
                LEFT JOIN puestos p        ON p.id_puesto = u.puesto_id
                LEFT JOIN departamentos d  ON d.id_departamento = u.departamento_id
                LEFT JOIN roles_usuario ru ON ru.usuario_id = u.id_usuario
                LEFT JOIN roles r          ON r.id_rol = ru.rol_id
                WHERE u.estatus = 1
                  AND (
                        LOWER(u.nombre)   LIKE ?
                    OR  LOWER(u.apellido) LIKE ?
                    OR  LOWER(u.correo)   LIKE ?
                    OR  LOWER(p.nombre)   LIKE ?
                    OR  LOWER(d.nombre)   LIKE ?
                    OR  LOWER(c.nombre)   LIKE ?
                  )
                GROUP BY 
                    u.id_usuario, u.nombre, u.apellido, u.correo, u.telefono, 
                    u.cliente_id, c.nombre, p.nombre, d.nombre
                ORDER BY u.id_usuario DESC";

        $needle = "%" . mb_strtolower($termino, 'UTF-8') . "%";
        return $this->selectAll($sql, [$needle, $needle, $needle, $needle, $needle, $needle]) ?: [];
    }
}
