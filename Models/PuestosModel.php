<?php
class PuestosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

     public function getPuestos()
    {
        $sql = "SELECT * FROM puestos ORDER BY id_puesto DESC;";
        return $this->selectAll($sql);
    }

    public function registrarPuesto($nombre)
    {
        $sql = "INSERT INTO puestos (nombre) VALUES (?)";
        return $this->insertar($sql, [$nombre]);
    }
    public function existePuesto($nombre)
    {
        $sql = "SELECT id_puesto FROM puestos WHERE nombre = ?";
        return $this->select($sql, [$nombre]);
    }

    public function eliminarPuesto($id)
    {
        $sql = "DELETE FROM puestos WHERE id_puesto = ?";
        $datos = [$id];
        return $this->save($sql, $datos);
    }

    public function getPuesto($id)
    {
        $sql = "SELECT * FROM puestos WHERE id_puesto = ?";
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
    $sql = "SELECT * FROM puestos WHERE nombre LIKE ?";
    $param = ["%$termino%"];
    return $this->selectAll($sql, $param);
}

}
