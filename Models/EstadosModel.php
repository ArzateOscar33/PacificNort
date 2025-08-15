<?php
class EstadosModel extends Query
{
    /**
     * Lista paginada con filtro opcional por nombre_estado.
     * Devuelve: ['rows' => [...], 'total' => int]
     */
    public function listarPaginado(int $offset, int $limit, ?string $q = null): array
    {
        $offset = max(0, (int)$offset);
        $limit  = in_array((int)$limit, [25, 50], true) ? (int)$limit : 25;

        $where  = "estatus = 1";
        $params = [];

        if ($q !== null && $q !== '') {
            $where   .= " AND LOWER(nombre_estado) LIKE ?";
            $params[] = "%" . mb_strtolower($q, 'UTF-8') . "%";
        }

        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE $where
                ORDER BY id_estado DESC
                LIMIT $limit OFFSET $offset";
        $rows = $this->selectAll($sql, $params);

        $sqlCount = "SELECT COUNT(*) AS total
                     FROM estados
                     WHERE $where";
        $count = $this->select($sqlCount, $params);

        return [
            'rows'  => $rows ?: [],
            'total' => (int)($count['total'] ?? 0),
        ];
    }

     
    public function buscar($termino)
    {
        $needle = "%" . mb_strtolower((string)$termino, 'UTF-8') . "%";
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE estatus = 1
                  AND LOWER(nombre_estado) LIKE ?
                ORDER BY id_estado DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle]);
    }

 

    public function listar()
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE estatus = 1
                ORDER BY id_estado DESC";
        return $this->selectAll($sql);
    }

    public function registrar($nombre)
    {
        // Si tu columna estatus tiene default 1, puedes omitir 'estatus'.
        $sql = "INSERT INTO estados (nombre_estado, estatus) VALUES (?, 1)";
        return $this->insertar($sql, [$nombre]);
    }

    public function existe($nombre)
    {
        $sql = "SELECT id_estado
                FROM estados
                WHERE estatus = 1
                  AND LOWER(nombre_estado) = LOWER(?)
                LIMIT 1";
        return $this->select($sql, [$nombre]);
    }
 
    public function existeEnOtro($nombre, $id)
    {
        $sql = "SELECT id_estado
                FROM estados
                WHERE estatus = 1
                  AND LOWER(nombre_estado) = LOWER(?)
                  AND id_estado <> ?
                LIMIT 1";
        return $this->select($sql, [$nombre, $id]);
    }

    public function actualizar($id, $nombre)
    {
        $sql = "UPDATE estados
                SET nombre_estado = ?
                WHERE id_estado = ?";
        return $this->save($sql, [$nombre, $id]);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE estados SET estatus = 0 WHERE id_estado = ?";
        return $this->save($sql, [$id]);
    }

    public function obtener($id)
    {
        $sql = "SELECT id_estado, nombre_estado
                FROM estados
                WHERE id_estado = ? AND estatus = 1
                LIMIT 1";
        return $this->select($sql, [$id]);
    }
}
