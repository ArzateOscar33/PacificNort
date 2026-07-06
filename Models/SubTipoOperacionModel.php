<?php
class SubTipoOperacionModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getTipoOperacion()
    {
        $sql = "SELECT id_tipo_operacion,nombre_operacion FROM tipos_operacion WHERE estatus = 1 ORDER BY id_tipo_operacion DESC";
        return $this->selectAll($sql);
    }
    public function getPuertos()
    {
        $sql = "SELECT id_puerto,nombre FROM puertos WHERE estatus = 1 ORDER BY id_puerto DESC";
        return $this->selectAll($sql);
    }
    public function getSubTiposOperacion()
    {
        $sql = "SELECT st.id_subtipo,st.clave,st.nombre,tp.nombre_operacion,tp.id_tipo_operacion,tp.nombre_operacion,pu.nombre as puerto,st.prefijo_codigo as prefijo
                FROM subtipos_operacion st
                LEFT JOIN tipos_operacion tp ON tp.id_tipo_operacion = st.tipo_operacion_id
                LEFT JOIN puertos pu ON pu.id_puerto = st.puerto_arribo_default_id
                WHERE st.estatus = 1
                ORDER BY st.id_subtipo DESC";
        return $this->selectAll($sql);
    }

    public function existeSubTipoOperacion($nombre)
    {
        $sql = "SELECT id_subtipo FROM subtipos_operacion WHERE LOWER(clave) = LOWER(?) AND estatus = 1";
        return $this->select($sql, [$nombre]);
    }
    public function existeSubtipoPorClave($clave, $excluirId = null)
    {
        $sql = "SELECT id_subtipo FROM subtipos_operacion 
            WHERE LOWER(clave) = LOWER(?) AND estatus = 1";
        $params = [$clave];
        if (!empty($excluirId)) {
            $sql .= " AND id_subtipo <> ?";
            $params[] = $excluirId;
        }
        return $this->select($sql, $params);
    }

    public function existeSubtipoPorNombre($nombre, $excluirId = null)
    {
        $sql = "SELECT id_subtipo FROM subtipos_operacion 
            WHERE LOWER(nombre) = LOWER(?) AND estatus = 1";
        $params = [$nombre];
        if (!empty($excluirId)) {
            $sql .= " AND id_subtipo <> ?";
            $params[] = $excluirId;
        }
        return $this->select($sql, $params);
    }


    public function registrarSubTipoOperacion($id_tipo_operacion, $clave, $nombre, $puerto_id, $prefijo = null)
    {
        try {
            // Inicia transacción (usa tu método de Query)
            $this->save("START TRANSACTION", []);

            // Inserta subtipo
            $sql = "INSERT INTO subtipos_operacion 
                (tipo_operacion_id, clave, nombre, puerto_arribo_default_id, prefijo_codigo, estatus) 
                VALUES (?,?,?,?,?,1)";
            $newId = (int)$this->insertar($sql, [$id_tipo_operacion, $clave, $nombre, $puerto_id, $prefijo]);

            if ($newId <= 0) {
                $this->save("ROLLBACK", []);
                return 0;
            }


            $ok = $this->save(
                "INSERT INTO secuencias_operacion (subtipo_id, valor)
             VALUES (?, 0)
             ON DUPLICATE KEY UPDATE valor = valor",
                [$newId]
            );
            if ($ok === false) {
                $this->save("ROLLBACK", []);
                return 0;
            }

            $this->save("COMMIT", []);
            return $newId;
        } catch (\Throwable $e) {
            $this->save("ROLLBACK", []);
            return 0;
        }
    }

    public function getSubtipoOperacion($id)
    {
        $sql = "SELECT id_subtipo,tipo_operacion_id,clave,nombre,puerto_arribo_default_id,prefijo_codigo
            FROM subtipos_operacion
            WHERE id_subtipo = ? AND estatus = 1";
        return $this->select($sql, [$id]);
    }

    public function actualizarTipoOperacion($id_tipo_operacion, $clave, $nombre, $puerto_id, $prefijo, $id)
    {
        $sql = "UPDATE subtipos_operacion 
            SET tipo_operacion_id = ?, clave = ?, nombre = ?, puerto_arribo_default_id = ?, prefijo_codigo = ?
            WHERE id_subtipo = ?";
        return $this->save($sql, [$id_tipo_operacion, $clave, $nombre, $puerto_id, $prefijo, $id]);
    }

    public function eliminarSubtipoOperacion($id)
    {
        $sql = "UPDATE subtipos_operacion SET estatus = 0 WHERE id_subtipo = ?";
        return $this->save($sql, [$id]);
    }

    public function buscarSubtipoOperacion($termino)
    {
        $termino = trim($termino ?? '');
        $like = "%{$termino}%";

        $sql = "SELECT 
                    st.id_subtipo,
                    st.clave,
                    st.nombre,
                    tp.id_tipo_operacion,
                    tp.nombre_operacion,
                    pu.id_puerto,
                    pu.nombre AS puerto
                FROM subtipos_operacion st
                LEFT JOIN tipos_operacion tp 
                    ON tp.id_tipo_operacion = st.tipo_operacion_id
                LEFT JOIN puertos pu 
                    ON pu.id_puerto = st.puerto_arribo_default_id
                WHERE st.estatus = 1
                AND (
                        st.clave LIKE ?
                    OR st.nombre LIKE ?
                    OR pu.nombre LIKE ?
                    OR tp.nombre_operacion LIKE ?
                )
                ORDER BY st.id_subtipo DESC";

        return $this->selectAll($sql, [$like, $like, $like, $like]);
    }

    public function existePrefijo($prefijo, $excluirId = null)
    {
        $sql = "SELECT id_subtipo FROM subtipos_operacion 
            WHERE estatus = 1 AND LOWER(prefijo_codigo) = LOWER(?)";
        $params = [$prefijo];
        if (!empty($excluirId)) {
            $sql .= " AND id_subtipo <> ?";
            $params[] = $excluirId;
        }
        return $this->select($sql, $params);
    }

    public function getTiposOperacionIdsByNombreLike(string $like): array
    {
        $sql = "SELECT id_tipo_operacion
            FROM tipos_operacion
            WHERE estatus = 1 AND nombre_operacion LIKE ?
            ORDER BY id_tipo_operacion ASC";
        $rows = $this->selectAll($sql, [$like]) ?: [];
        return array_map('intval', array_column($rows, 'id_tipo_operacion'));
    }
}
