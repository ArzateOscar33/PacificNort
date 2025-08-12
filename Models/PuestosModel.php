<?php
class PuestosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getPuestos()
    {
        $sql = "SELECT 
                    p.id_puesto,
                    p.nombre AS nombre_puesto,
                    d.nombre AS nombre_departamento
                FROM puestos p
                INNER JOIN departamentos d ON p.departamento_id = d.id_departamento
                WHERE p.estatus = 1
                ORDER BY p.id_puesto DESC;";
        return $this->selectAll($sql);
    }

    public function getDepartamentos()
    {
        $sql = "SELECT * FROM departamentos WHERE estatus = 1";
        return $this->selectAll($sql);
    }
    public function registrarPuesto($nombre, $departamento_id)
    {
        $sql = "INSERT INTO puestos (nombre, departamento_id, estatus) VALUES (?, ?, 1)";
        return $this->insertar($sql, [$nombre, $departamento_id]);
    }

    public function existePuesto($nombre)
    {
        $sql = "SELECT id_puesto FROM puestos WHERE nombre = ?";
        return $this->select($sql, [$nombre]);
    }

    public function eliminarPuesto($id)
    {
        $sql = "UPDATE puestos SET estatus = 0 WHERE id_puesto = ?";
        $datos = [$id];
        return $this->save($sql, $datos);
    }

    public function getPuesto($id)
    {
        $sql = "SELECT id_puesto, nombre, departamento_id FROM puestos WHERE id_puesto = ? AND estatus=1";
        return $this->select($sql, [$id]);
    }

    public function modificarPuesto($id, $nombre)
    {
        $sql = "UPDATE puestos SET nombre = ? WHERE id_puesto = ?";
        $datos = [$nombre, $id];
        return $this->save($sql, $datos);
    }
    public function buscarPuesto($termino)
    {
        $sql = "SELECT p.id_puesto, p.nombre AS nombre_puesto, d.nombre AS nombre_departamento
                FROM puestos p
                INNER JOIN departamentos d ON p.departamento_id = d.id_departamento
                WHERE p.nombre LIKE ? AND p.estatus=1";
        $param = ["%$termino%"];
        return $this->selectAll($sql, $param);
    }

}
