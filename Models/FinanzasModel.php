<?php
class FinanzasModel extends Query
{




    public function catalogoClientes(): array
    {
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }
    public function getBrokers(): array
    {
        $sql = "SELECT id_broker, nombre
                FROM brokers
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function getTransportistas(): array
    {
        $sql = "SELECT id_transportista, nombre
                FROM transportistas
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }
    public function listarDestinos(): array
    {
        $sql = "SELECT id_ciudad,nombre_ciudad 
                FROM ciudades   
                WHERE estatus = 1 
                ORDER BY nombre_ciudad";
        return $this->selectAll($sql) ?: [];
    }
    public function listarCategoriasCostos(): array
    {
        $sql = "SELECT id_categoria, nombre
                FROM tipos_movimiento_categorias
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }
}
