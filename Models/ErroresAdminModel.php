<?php
class ErroresAdminModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getTiposError()
    {
        $sql = "SELECT id_tipo_error, nombre
                FROM cat_tipos_error
                WHERE estatus = 1
                ORDER BY nombre ASC";
        return $this->selectAll($sql);
    }

    public function getModulosError()
    {
        $sql = "SELECT id_modulo_error, nombre
                FROM cat_modulos_error
                WHERE estatus = 1
                ORDER BY nombre ASC";
        return $this->selectAll($sql);
    }

    public function listar($estatus = '', $modulo = '', $tipo = '', $busqueda = '')
    {
        $where = " WHERE 1=1 ";
        $params = [];

        if ($estatus !== '' && $estatus !== '0') {
            $where .= " AND r.estatus = ? ";
            $params[] = $estatus;
        } elseif ($estatus === '0') {
            $where .= " AND r.estatus = 0 ";
        }

        if ($modulo !== '' && $modulo !== '0') {
            $where .= " AND r.modulo_id = ? ";
            $params[] = $modulo;
        }

        if ($tipo !== '' && $tipo !== '0') {
            $where .= " AND r.tipo_error_id = ? ";
            $params[] = $tipo;
        }

        if ($busqueda !== '') {
            $where .= " AND (
                r.descripcion LIKE ? OR
                r.razon_error LIKE ? OR
                u1.nombre LIKE ?
            ) ";
            $like = '%' . $busqueda . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT 
                    r.id_reporte,
                    te.nombre AS tipo_error,
                    me.nombre AS modulo,
                    r.descripcion,
                    u1.nombre AS reportado_por,
                    r.fecha_reporte,
                    u2.nombre AS resuelto_por,
                    r.fecha_resolucion,
                    r.estatus
                FROM reportes_error r
                INNER JOIN cat_tipos_error te 
                    ON te.id_tipo_error = r.tipo_error_id
                INNER JOIN cat_modulos_error me 
                    ON me.id_modulo_error = r.modulo_id
                INNER JOIN usuarios u1 
                    ON u1.id_usuario = r.reportado_por
                LEFT JOIN usuarios u2 
                    ON u2.id_usuario = r.resuelto_por
               $where
                ORDER BY r.id_reporte DESC";

        return $this->selectAll($sql, $params);
    }

    public function getReporte(int $idReporte)
    {
        $sql = "SELECT 
                    r.id_reporte,
                    te.nombre AS tipo_error,
                    me.nombre AS modulo,
                    r.descripcion,
                    r.valor_propuesto,
                    r.razon_error,
                    u1.nombre AS reportado_por,
                    r.fecha_reporte,
                    r.estatus
                FROM reportes_error r
                INNER JOIN cat_tipos_error te 
                    ON te.id_tipo_error = r.tipo_error_id
                INNER JOIN cat_modulos_error me 
                    ON me.id_modulo_error = r.modulo_id
                INNER JOIN usuarios u1 
                    ON u1.id_usuario = r.reportado_por
                WHERE r.id_reporte = ?";

        return $this->select($sql, [$idReporte]);
    }

    public function actualizarEstatus(int $idReporte, int $estatus, int $usuarioId)
    {
        $sql = "UPDATE reportes_error
                SET estatus = ?, 
                    resuelto_por = ?, 
                    fecha_resolucion = NOW()
                WHERE id_reporte = ?";

        $datos = [$estatus, $usuarioId, $idReporte];

        return $this->save($sql, $datos);
    }
}
