<?php
class ErroresUsuarioModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtener catálogo de tipos de error activos
     */
    public function getTiposError()
    {
        $sql = "SELECT id_tipo_error, nombre
                FROM cat_tipos_error
                WHERE estatus = 1
                ORDER BY nombre ASC";
        return $this->selectAll($sql);
    }

    /**
     * Obtener catálogo de módulos activos
     */
    public function getModulosError()
    {
        $sql = "SELECT id_modulo_error, nombre
                FROM cat_modulos_error
                WHERE estatus = 1
                ORDER BY nombre ASC";
        return $this->selectAll($sql);
    }

    /**
     * Registrar nuevo reporte de error
     */
    public function registrarReporte(
        int $tipoErrorId,
        int $moduloId,
        string $descripcion,
        ?string $valorPropuesto,
        ?string $razonError,
        int $reportadoPor
    ) {
        $sql = "INSERT INTO reportes_error
                    (
                        tipo_error_id,
                        modulo_id,
                        descripcion,
                        valor_propuesto,
                        razon_error,
                        reportado_por,
                        estatus
                    )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $datos = [
            $tipoErrorId,
            $moduloId,
            $descripcion,
            $valorPropuesto,
            $razonError,
            $reportadoPor,
            0 // 0 = sin resolver
        ];

        return $this->insertar($sql, $datos);
    }

    /**
     * Obtener un reporte por ID
     */
    public function getReporteById(int $idReporte)
    {
        $sql = "SELECT 
                    r.id_reporte,
                    r.tipo_error_id,
                    te.nombre AS tipo_error,
                    r.modulo_id,
                    me.nombre AS modulo,
                    r.descripcion,
                    r.valor_propuesto,
                    r.razon_error,
                    r.reportado_por,
                    u1.nombre AS reportado_por_nombre,
                    r.resuelto_por,
                    u2.nombre AS resuelto_por_nombre,
                    r.fecha_reporte,
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
                WHERE r.id_reporte = ?";
        return $this->select($sql, [$idReporte]);
    }

    /**
     * Listar reportes
     */
    public function listarReportes()
    {
        $sql = "SELECT 
                    r.id_reporte,
                    te.nombre AS tipo_error,
                    me.nombre AS modulo,
                    r.descripcion,
                    r.valor_propuesto,
                    r.razon_error,
                    u1.nombre AS reportado_por,
                    u2.nombre AS resuelto_por,
                    r.fecha_reporte,
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
                ORDER BY r.id_reporte DESC";
        return $this->selectAll($sql);
    }

    /**
     * Marcar reporte como resuelto
     */
    public function resolverReporte(int $idReporte, int $resueltoPor)
    {
        $sql = "UPDATE reportes_error
                SET estatus = ?, 
                    resuelto_por = ?, 
                    fecha_resolucion = NOW()
                WHERE id_reporte = ?";
        $datos = [1, $resueltoPor, $idReporte];
        return $this->save($sql, $datos);
    }

    /**
     * Marcar reporte como rechazado
     */
    public function rechazarReporte(int $idReporte, int $resueltoPor)
    {
        $sql = "UPDATE reportes_error
                SET estatus = ?, 
                    resuelto_por = ?, 
                    fecha_resolucion = NOW()
                WHERE id_reporte = ?";
        $datos = [2, $resueltoPor, $idReporte];
        return $this->save($sql, $datos);
    }
}
