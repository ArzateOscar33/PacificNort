<?php
class Contenedores_fisicosModel extends Query
{
    // Consulta paginada + total (con búsqueda opcional por numero_ferro)
    public function listarPaginado(int $offset, int $limit, ?string $q = null): array
    {
        $offset = max(0, (int)$offset);
        $limit  = in_array((int)$limit, [25, 50], true) ? (int)$limit : 25;

        $where  = "estatus = 1";
        $params = [];

        if ($q !== null && $q !== '') {
            $where  .= " AND numero_ferro LIKE ?";
            $params[] = "%{$q}%";
        }

        // Datos
        $sql = "SELECT id_fisico, numero_ferro, estatus
                FROM contenedores_fisicos
                WHERE $where
                ORDER BY id_fisico DESC
                LIMIT $limit OFFSET $offset";
        $rows = $this->selectAll($sql, $params);

        // Total
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM contenedores_fisicos
                     WHERE $where";
        $count = $this->select($sqlCount, $params);

        return [
            'rows'  => $rows ?: [],
            'total' => (int)($count['total'] ?? 0),
        ];
    }
 
 
    public function existeNombre($nombre)
    {
        $sql = "SELECT id_fisico, estatus
                FROM contenedores_fisicos
                WHERE LOWER(numero_ferro) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }

    public function existeNombreEnOtro($nombre, $id_fisico)
    {
        $sql = "SELECT id_fisico
                FROM contenedores_fisicos
                WHERE LOWER(numero_ferro) = LOWER(?)
                  AND id_fisico <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $id_fisico]);
    }

    public function registrar($nombre)
    {
        $sql = "INSERT INTO contenedores_fisicos (numero_ferro, estatus)
                VALUES (?, 1)";
        return $this->insertar($sql, [$nombre]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE contenedores_fisicos SET estatus = 0 WHERE id_fisico = ?";
        return $this->save($sql, [$id]);
    }

    public function obtenerContenedorFisico($id)
    { 
        $sql = "SELECT id_fisico AS id, numero_ferro, estatus
                FROM contenedores_fisicos
                WHERE id_fisico = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function actualizar($id_fisico, $numero_ferro)
    { 
        $sql = "UPDATE contenedores_fisicos
                SET numero_ferro = ?
                WHERE id_fisico = ?";
        return $this->save($sql, [$numero_ferro, $id_fisico]);
    }

      // NUEVO: para sugerencias y resultados rápidos (sin paginación)
    public function buscar(string $termino)
    {
        $needle = "%".mb_strtolower($termino, 'UTF-8')."%";
        $sql = "SELECT id_fisico, numero_ferro, estatus
                FROM contenedores_fisicos
                WHERE estatus = 1
                  AND LOWER(numero_ferro) LIKE ?
                ORDER BY id_fisico DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle]);
    }

}
