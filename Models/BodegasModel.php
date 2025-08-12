<?php
class BodegasModel extends Query
{
  public function listar(){
    $sql="SELECT b.id_bodega,b.nombre,b.direccion,c.nombre_ciudad,b.estatus
            FROM bodegas b
            INNER JOIN ciudades c ON b.ciudad_id=c.id_ciudad
            WHERE b.estatus=1";
    return $this->selectAll($sql);
  }

  public function listarCiudades(){
    $sql="SELECT id_ciudad,nombre_ciudad
            FROM ciudades
            WHERE estatus=1";
    return $this->selectAll($sql);
  }

  public function existeNombreEnCiudad($nombre, $ciudad_id)
    {
        $sql = "SELECT id_bodega, estatus
                FROM bodegas
                WHERE LOWER(nombre) = LOWER(?) AND ciudad_id = ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $ciudad_id]);
    }
 
    public function registrar($nombre, $direccion, $ciudad_id)
    {
        $sql = "INSERT INTO bodegas (nombre, direccion, ciudad_id, estatus)
                VALUES (?,?,?,1)";
        return $this->insertar($sql, [$nombre, $direccion, $ciudad_id]);
    }
 
    public function obtener($id_bodega)
    {
        $sql = "SELECT 
                    b.id_bodega,
                    b.nombre,
                    b.direccion,
                    b.ciudad_id,
                    c.nombre_ciudad,
                    b.estatus
                FROM bodegas b
                INNER JOIN ciudades c ON c.id_ciudad = b.ciudad_id
                WHERE b.id_bodega = ?
                LIMIT 1";
        return $this->select($sql, [$id_bodega]);
    }
 
    public function existeNombreEnCiudadOtro($nombre, $ciudad_id, $id_bodega)
    {
        $sql = "SELECT id_bodega, estatus
                FROM bodegas
                WHERE LOWER(nombre) = LOWER(?) 
                  AND ciudad_id = ? 
                  AND id_bodega <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $ciudad_id, $id_bodega]);
    }

    public function actualizar($id_bodega, $nombre, $direccion, $ciudad_id)
    {
        $sql = "UPDATE bodegas
                SET nombre = ?, direccion = ?, ciudad_id = ?
                WHERE id_bodega = ?";
        return $this->save($sql, [$nombre, $direccion, $ciudad_id, $id_bodega]);
    }
        public function eliminar($id)
    {
        $sql = "UPDATE bodegas SET estatus = 0 WHERE id_bodega = ?";
        return $this->save($sql, [$id]);
    }
    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                    b.id_bodega,
                    b.nombre,
                    b.direccion,
                    c.nombre_ciudad
                FROM bodegas b
                INNER JOIN ciudades c ON c.id_ciudad = b.ciudad_id
                WHERE b.estatus = 1 AND c.estatus = 1
                AND (
                        LOWER(b.nombre)        LIKE ?
                    OR LOWER(b.direccion)     LIKE ?
                    OR LOWER(c.nombre_ciudad) LIKE ?
                )
                ORDER BY b.id_bodega DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle, $needle]);
    }

}
