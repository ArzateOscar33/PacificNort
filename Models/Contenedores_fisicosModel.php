<?php
class Contenedores_fisicosModel extends Query
{
    public function listar(){
    $sql="SELECT * from contenedores_fisicos
            WHERE estatus=1";
    return $this->selectAll($sql);
  }
   public function existeNombre($nombre)
    {
        $sql = "SELECT id_fisico, estatus
                FROM contenedores_fisicos
                WHERE LOWER(numero_ferro) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }
 
    public function registrar($nombre)
    {
        $sql = "INSERT INTO contenedores_fisicos (numero_ferro,  estatus)
                VALUES (?,1)";
        return $this->insertar($sql, [$nombre]);
    }

        public function eliminar($id)
    {
        $sql = "UPDATE contenedores_fisicos SET estatus = 0 WHERE id_fisico = ?";
        return $this->save($sql, [$id]);
    }

        public function obtenerContenedorFisico($id)
    {
        $sql = "SELECT id_fisico,numero_ferro,estatus FROM contenedores_fisicos WHERE id_fisico = ? AND estatus=1"; 
        return $this->select($sql, [$id]);
    }
        public function actualizar($id_fisico, $numero_ferro)
    {
        $sql = "UPDATE contenedores_fisicos
                SET numero_ferro = ?,  
                WHERE id_fisico = ?";
        return $this->save($sql, [$numero_ferro,  $id_fisico]);
    }
    /*



    public function buscar($termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT 
                   nombre,contacto
                FROM  brokers
                WHERE estatus = 1
                AND (
                        LOWER(nombre)        LIKE ?
                    OR LOWER(contacto)     LIKE ?
                     
                )
                ORDER BY id_broker DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle]);
    }



*/
}
