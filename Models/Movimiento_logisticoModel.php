<?php
class Movimiento_logisticoModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function listar()
    {
        $sql = "SELECT
                    tm.id_tipo_movimiento,
                    tm.nombre,
                    tm.tipo,
                    tm.moneda,
                    tm.categoria_id,
                    tmc.nombre AS categoria
                FROM tipos_movimiento tm
                LEFT JOIN tipos_movimiento_categorias tmc
                       ON tmc.id_categoria = tm.categoria_id
                LEFT JOIN tipos_operacion t
                       ON t.id_tipo_operacion = tm.tipo_operacion_id
                WHERE tm.estatus = 1
               ORDER BY tmc.nombre ASC, tm.nombre ASC";
        return $this->selectAll($sql);
    }

    // ✅ Ahora recibe categoria_id (nullable)
    public function registrar($nombre, $tipo, $moneda, $categoriaId = null)
    {
        $sql = "INSERT INTO tipos_movimiento (nombre, tipo, moneda, categoria_id, tipo_operacion_id, estatus)
                VALUES (?, ?, ?, ?, 1, 1)";
        $datos = [
            $nombre,
            $tipo,
            ($moneda !== '' ? $moneda : null),
            ((int)$categoriaId > 0 ? (int)$categoriaId : null),
        ];
        return $this->insertar($sql, $datos);
    }

    public function existeMovimiento($nombre, $excludeId = 0)
    {
        // opcional: excluir un id en edición para evitar falso duplicado
        $sql = "SELECT id_tipo_movimiento
                FROM tipos_movimiento
                WHERE estatus = 1
                  AND LOWER(nombre) = LOWER(?)
                  " . ((int)$excludeId > 0 ? " AND id_tipo_movimiento <> ?" : "") . "
                LIMIT 1";
        $params = [(string)$nombre];
        if ((int)$excludeId > 0) $params[] = (int)$excludeId;

        return $this->select($sql, $params);
    }

    public function obtener($id)
    {
        $sql = "SELECT
                    tm.id_tipo_movimiento,
                    tm.nombre,
                    tm.tipo,
                    tm.moneda,
                    tm.categoria_id,
                    tmc.nombre AS categoria
                FROM tipos_movimiento tm
                LEFT JOIN tipos_movimiento_categorias tmc
                       ON tmc.id_categoria = tm.categoria_id
                WHERE tm.id_tipo_movimiento = ?
                  AND tm.estatus = 1
                LIMIT 1";
        return $this->select($sql, [(int)$id]);
    }

    // ✅ Ahora actualiza categoria_id
    public function actualizar($id, $nombre, $tipo, $moneda, $categoriaId = null)
    {
        $sql = "UPDATE tipos_movimiento
                SET nombre = ?,
                    tipo = ?,
                    moneda = ?,
                    categoria_id = ?,
                    tipo_operacion_id = 1
                WHERE id_tipo_movimiento = ?";
        $datos = [
            $nombre,
            $tipo,
            ($moneda !== '' ? $moneda : null),
            ((int)$categoriaId > 0 ? (int)$categoriaId : null),
            (int)$id
        ];
        return $this->save($sql, $datos);
    }

    public function eliminar($id)
    {
        $sql = "UPDATE tipos_movimiento SET estatus = 0 WHERE id_tipo_movimiento = ?";
        return $this->save($sql, [(int)$id]);
    }

    public function buscar($termino)
    {
        $sql = "SELECT
                    tm.id_tipo_movimiento,
                    tm.nombre,
                    tm.tipo,
                    tm.moneda,
                    tm.categoria_id,
                    tmc.nombre AS categoria
                FROM tipos_movimiento tm
                LEFT JOIN tipos_movimiento_categorias tmc
                       ON tmc.id_categoria = tm.categoria_id
                WHERE tm.estatus = 1
                  AND LOWER(tm.nombre) LIKE ?
                ORDER BY tm.id_tipo_movimiento DESC";
        $param = ["%" . mb_strtolower(trim((string)$termino), 'UTF-8') . "%"];
        return $this->selectAll($sql, $param);
    }

    // ✅ Agrego filtro por categoria_id (si lo ocupas)
    public function filtrar($term, $tipo, $moneda, $categoriaId = '')
    {
        $sql = "SELECT
                    tm.id_tipo_movimiento,
                    tm.nombre,
                    tm.tipo,
                    tm.moneda,
                    tm.categoria_id,
                    tmc.nombre AS categoria
                FROM tipos_movimiento tm
                LEFT JOIN tipos_movimiento_categorias tmc
                       ON tmc.id_categoria = tm.categoria_id
                WHERE tm.estatus = 1";
        $params = [];

        if ($term !== '') {
            $sql .= " AND LOWER(tm.nombre) LIKE ?";
            $params[] = "%" . mb_strtolower($term, 'UTF-8') . "%";
        }
        if ($tipo !== '') {
            $sql .= " AND tm.tipo = ?";
            $params[] = $tipo;
        }
        if ($moneda !== '') {
            $sql .= " AND tm.moneda = ?";
            $params[] = $moneda;
        }
        if ($categoriaId !== '' && (int)$categoriaId > 0) {
            $sql .= " AND tm.categoria_id = ?";
            $params[] = (int)$categoriaId;
        }

        $sql .= " ORDER BY tmc.nombre ASC, tm.nombre ASC";
        return $this->selectAll($sql, $params);
    }

    // (Opcional) Métodos previos de filtro individual (los dejo, pero sin categoría)
    public function buscarFiltroTipo($tipo)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda, categoria_id
                FROM tipos_movimiento
                WHERE tipo = ? AND estatus = 1";
        return $this->selectAll($sql, [$tipo]);
    }

    public function buscarFiltroMoneda($moneda)
    {
        $sql = "SELECT id_tipo_movimiento, nombre, tipo, moneda, categoria_id
                FROM tipos_movimiento
                WHERE moneda = ? AND estatus = 1";
        return $this->selectAll($sql, [$moneda]);
    }

    public function catalogoTiposOperacion()
    {
        $sql = "SELECT id_tipo_operacion, nombre_operacion
                FROM tipos_operacion
                WHERE estatus = 1
                ORDER BY id_tipo_operacion ASC";
        return $this->selectAll($sql);
    }

    // ✅ Categorías
    public function getCategorias($soloActivas = true)
    {
        $sql = "SELECT id_categoria, nombre, estatus
                FROM tipos_movimiento_categorias";
        $params = [];

        if ($soloActivas) {
            $sql .= " WHERE estatus = 1";
        }

        $sql .= " ORDER BY nombre ASC";
        return $this->selectAll($sql, $params);
    }

    //categorias
    // =========================
    // CATEGORÍAS
    // =========================

    public function listarCategorias(bool $soloActivas = true): array
    {
        $sql = "SELECT id_categoria, nombre, estatus
            FROM tipos_movimiento_categorias";
        $params = [];

        if ($soloActivas) {
            $sql .= " WHERE estatus = 1";
        }

        $sql .= " ORDER BY nombre ASC";
        return $this->selectAll($sql, $params) ?: [];
    }

    public function existeCategoria(string $nombre, int $excludeId = 0)
    {
        $sql = "SELECT id_categoria
            FROM tipos_movimiento_categorias
            WHERE estatus = 1
              AND LOWER(nombre) = LOWER(?)
              " . ($excludeId > 0 ? " AND id_categoria <> ?" : "") . "
            LIMIT 1";

        $params = [$nombre];
        if ($excludeId > 0) $params[] = $excludeId;

        return $this->select($sql, $params);
    }

    public function registrarCategoria(string $nombre)
    {
        $sql = "INSERT INTO tipos_movimiento_categorias (nombre, estatus)
            VALUES (?, 1)";
        return $this->insertar($sql, [$nombre]);
    }

    // por si queremos desactivar despues
    public function desactivarCategoria(int $id)
    {
        $sql = "UPDATE tipos_movimiento_categorias SET estatus = 0 WHERE id_categoria = ?";
        return $this->save($sql, [$id]);
    }
}
