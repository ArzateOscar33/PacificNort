<?php
class Contenedores_maritimosModel extends Query
{
    /**
     * Lista con paginación y búsqueda opcional (por numero_contenedor, tipo u observaciones)
     * @return array ['rows' => [...], 'total' => int]
     */
    public function listarPaginado(int $offset, int $limit, ?string $q = null): array
    {
        $offset = max(0, (int)$offset);
        $limit  = in_array((int)$limit, [25, 50], true) ? (int)$limit : 25;

        $where  = "estatus = 1";
        $params = [];

        if ($q !== null && $q !== '') {
            $needle = "%" . mb_strtolower($q, 'UTF-8') . "%";
            $where .= " AND (LOWER(numero_contenedor) LIKE ?
                           OR LOWER(tipo)              LIKE ?
                           OR LOWER(observaciones)     LIKE ?)";
            $params = [$needle, $needle, $needle];
        }

        $sql = "SELECT 
                    id_contenedor_maritimo AS id_contenedor,
                    numero_contenedor,
                    tipo,
                    observaciones,
                    estatus
                FROM contenedores_maritimos
                WHERE $where
                ORDER BY id_contenedor_maritimo DESC
                LIMIT $limit OFFSET $offset";
        $rows = $this->selectAll($sql, $params);

        $sqlCount = "SELECT COUNT(*) AS total
                     FROM contenedores_maritimos
                     WHERE $where";
        $count = $this->select($sqlCount, $params);

        return [
            'rows'  => $rows ?: [],
            'total' => (int)($count['total'] ?? 0),
        ];
    }

 
    public function buscar(string $termino)
    {
        $needle = "%" . mb_strtolower($termino, 'UTF-8') . "%";
        $sql = "SELECT
                    id_contenedor_maritimo AS id_contenedor,
                    numero_contenedor,
                    tipo,
                    observaciones,
                    estatus
                FROM contenedores_maritimos
                WHERE estatus = 1
                  AND (LOWER(numero_contenedor) LIKE ?
                       OR LOWER(tipo)          LIKE ?
                       OR LOWER(observaciones) LIKE ?)
                ORDER BY id_contenedor_maritimo DESC
                LIMIT 100";
        return $this->selectAll($sql, [$needle, $needle, $needle]);
    }


    public function obtenerContenedorMaritimo($id)
    {
        $sql = "SELECT
                    id_contenedor_maritimo AS id_contenedor,
                    numero_contenedor,
                    tipo,
                    observaciones,
                    estatus
                FROM contenedores_maritimos
                WHERE id_contenedor_maritimo = ? AND estatus = 1
                LIMIT 1";
        return $this->select($sql, [$id]);
    }


    public function existeNumero(string $numero)
    {
        $sql = "SELECT id_contenedor_maritimo
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                  AND estatus = 1
                LIMIT 1";
        return $this->select($sql, [$numero]);
    }

    public function existeNumeroEnOtro(string $numero, int $id)
    {
        $sql = "SELECT id_contenedor_maritimo
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                  AND id_contenedor_maritimo <> ?
                  AND estatus = 1
                LIMIT 1";
        return $this->select($sql, [$numero, $id]);
    }

    /**
     * Crear / Update / Delete (lógico)
     */
    public function registrar(string $numero, ?string $tipo, ?string $obs)
    {
        $tipo = ($tipo === '' ? null : $tipo);
        $obs  = ($obs  === '' ? null : $obs);

        $sql = "INSERT INTO contenedores_maritimos
                    (numero_contenedor, tipo, observaciones, estatus)
                VALUES (?, ?, ?, 1)";
        return $this->insertar($sql, [$numero, $tipo, $obs]);
    }

    public function actualizar(int $id, string $numero, ?string $tipo, ?string $obs)
    {
        $tipo = ($tipo === '' ? null : $tipo);
        $obs  = ($obs  === '' ? null : $obs);

        $sql = "UPDATE contenedores_maritimos
                   SET numero_contenedor = ?,
                       tipo              = ?,
                       observaciones     = ?
                 WHERE id_contenedor_maritimo = ?";
        return $this->save($sql, [$numero, $tipo, $obs, $id]);
    }

    public function eliminar(int $id)
    {
        $sql = "UPDATE contenedores_maritimos
                   SET estatus = 0
                 WHERE id_contenedor_maritimo = ?";
        return $this->save($sql, [$id]);
    }

    public function listar()
    {
        $sql = "SELECT 
                    id_contenedor_maritimo AS id_contenedor,
                    numero_contenedor, tipo, observaciones, estatus
                FROM contenedores_maritimos
                WHERE estatus = 1
                ORDER BY id_contenedor_maritimo DESC";
        return $this->selectAll($sql);
    }
}
