<?php
class DepartamentosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getDepartamentos()
    {
        $sql = "SELECT * FROM departamentos ORDER BY id_departamento DESC;";
        return $this->selectAll($sql);
    }

    public function registrarDepartamento($nombre)
    {
        $sql = "INSERT INTO departamentos (nombre) VALUES (?)";
        return $this->insertar($sql, [$nombre]);
    }
    public function existeDepartamento($nombre)
    {
        $sql = "SELECT id_departamento FROM departamentos WHERE nombre = ?";
        return $this->select($sql, [$nombre]);
    }

    public function eliminarDepartamento($id)
    {
        $sql = "DELETE FROM departamentos WHERE id_departamento = ?";
        $datos = [$id];
        return $this->save($sql, $datos);
    }

    public function getDepartamento($id)
    {
        $sql = "SELECT * FROM departamentos WHERE id_departamento = ?";
        return $this->select($sql, [$id]);
    }

    public function modificarDepartamento($id, $nombre)
    {
        $sql = "UPDATE departamentos SET nombre = ? WHERE id_departamento = ?";
        $datos = [$nombre, $id];
        return $this->save($sql, $datos);
    }
    public function buscarDepartamento($termino)
{
    $sql = "SELECT * FROM departamentos WHERE nombre LIKE ?";
    $param = ["%$termino%"];
    return $this->selectAll($sql, $param);
}

}
