<?php
class BitacoraModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Construye dinámicamente los filtros para la consulta de bitácora.
     *
     * @param string $sql    Referencia al SQL base (se le van agregando condiciones).
     * @param array  $params Referencia al arreglo de parámetros para bind.
     * @param array  $filtros  [
     *   'usuario'        => string|null,
     *   'operacion'      => string|null,
     *   'accion'         => string|null,
     *   'fecha_desde'    => string|null (YYYY-MM-DD),
     *   'fecha_hasta'    => string|null (YYYY-MM-DD)
     * ]
     */
    private function aplicarFiltrosBitacora(string &$sql, array &$params, array $filtros): void
    {
        // =========================
        // Filtro: Usuario
        // =========================
        // Busca por:
        //  - nombre
        //  - apellido
        //  - nombre completo
        //  - correo
        //  - id de usuario (ej: "1")
        if (!empty($filtros['usuario'])) {
            $bus = '%' . $filtros['usuario'] . '%';
            $sql .= " AND (
                        u.nombre LIKE ?
                        OR u.apellido LIKE ?
                        OR CONCAT(u.nombre, ' ', u.apellido) LIKE ?
                        OR u.correo LIKE ?
                        OR CAST(l.usuario_id AS CHAR) LIKE ?
                      )";
            $params[] = $bus;
            $params[] = $bus;
            $params[] = $bus;
            $params[] = $bus;
            $params[] = $bus;
        }

        // =========================
        // Filtro: Operación (numero_operacion)
        // =========================
        if (!empty($filtros['operacion'])) {
            $sql     .= " AND o.numero_operacion LIKE ?";
            $params[] = '%' . $filtros['operacion'] . '%';
        }

        // =========================
        // Filtro: Acción (ENUM de operaciones_log)
        // =========================
        if (!empty($filtros['accion'])) {
            $sql     .= " AND l.accion = ?";
            $params[] = $filtros['accion'];
        }

        // =========================
        // Filtro: Fecha desde
        // =========================
        if (!empty($filtros['fecha_desde'])) {
            $sql     .= " AND DATE(l.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        // =========================
        // Filtro: Fecha hasta
        // =========================
        if (!empty($filtros['fecha_hasta'])) {
            $sql     .= " AND DATE(l.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
    }

    /**
     * Obtiene los registros de operaciones_log con joins a usuarios y operaciones.
     */
    public function obtenerLogs(array $filtros = []): array
    {
        $sql = "SELECT
                    l.id_log,
                    l.operacion_id,
                    o.numero_operacion,
                    l.usuario_id,
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario_nombre,
                    l.accion,
                    l.descripcion,
                    l.fecha
                FROM operaciones_log AS l
                LEFT JOIN usuarios AS u   ON u.id_usuario    = l.usuario_id
                LEFT JOIN operaciones AS o ON o.id_operacion = l.operacion_id
                WHERE 1 = 1";

        $params = [];

        // Aplica filtros dinámicos
        $this->aplicarFiltrosBitacora($sql, $params, $filtros);

        // Orden por fecha (últimos primero)
        $sql .= " ORDER BY l.fecha DESC, l.id_log DESC";

        // Paginación opcional
        if (!empty($filtros['limit'])) {
            $limit  = (int) $filtros['limit'];
            $offset = isset($filtros['offset']) ? (int) $filtros['offset'] : 0;

            $limit  = max($limit, 1);
            $offset = max($offset, 0);

            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $rows = $this->selectAll($sql, $params);

        return $rows ?: [];
    }

    /**
     * Cuenta cuántos registros hay en la bitácora con los filtros aplicados.
     */
    public function contarLogs(array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM operaciones_log AS l
                LEFT JOIN usuarios AS u    ON u.id_usuario    = l.usuario_id
                LEFT JOIN operaciones AS o ON o.id_operacion  = l.operacion_id
                WHERE 1 = 1";

        $params = [];

        $this->aplicarFiltrosBitacora($sql, $params, $filtros);

        $row = $this->select($sql, $params);

        return isset($row['total']) ? (int) $row['total'] : 0;
    }
}
