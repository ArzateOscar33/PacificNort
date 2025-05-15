<?php
class Query extends Conexion
{
    private $pdo, $con, $sql, $datos;

    public function __construct()
    {
        $this->pdo = new Conexion();
        $this->con = $this->pdo->conect();
    }

    // SELECT que acepta parámetros opcionales
    public function select(string $sql, array $params = [])
    {
        try {
            $resul = $this->con->prepare($sql);
            $resul->execute($params);
            return $resul->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en select: " . $e->getMessage());
            return false;
        }
    }

    // SELECT ALL que acepta parámetros opcionales
    public function selectAll(string $sql, array $params = [])
    {
        try {
            $resul = $this->con->prepare($sql);
            $resul->execute($params);
            return $resul->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en selectAll: " . $e->getMessage());
            return false;
        }
    }

    public function save(string $sql, array $datos)
    {
        try {
            $this->sql = $sql;
            $this->datos = $datos;
            $insert = $this->con->prepare($this->sql);
            $data = $insert->execute($this->datos);
            return $data ? 1 : 0;
        } catch (PDOException $e) {
            error_log("Error en save: " . $e->getMessage());
            return 0;
        }
    }

    public function insertar(string $sql, array $datos)
    {
        try {
            $this->sql = $sql;
            $this->datos = $datos;
            $insert = $this->con->prepare($this->sql);
            $data = $insert->execute($this->datos);
            return $data ? $this->con->lastInsertId() : 0;
        } catch (PDOException $e) {
            error_log("Error en insertar: " . $e->getMessage());
            return 0;
        }
    }
}
