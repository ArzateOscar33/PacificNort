<?php
class OperacionesLogModel extends Query
{
    // Opciones actuales en tu ENUM: 'creacion','actualizacion','cancelacion','cerrado'
    public const CREACION      = 'creacion';
    public const ACTUALIZACION = 'actualizacion';
    public const CANCELACION   = 'cancelacion';
    public const CERRADO       = 'cerrado';

    // Inserta un evento
    public function crear(int $operacionId, ?int $usuarioId, string $accion, ?string $descripcion = null)
    {
        // Bound params ya protegen; esto solo limpia extremos y longitudes raras
        $accion      = trim($accion);
        $descripcion = $descripcion !== null ? trim($descripcion) : null;

        $sql = "INSERT INTO operaciones_log (operacion_id, usuario_id, accion, descripcion)
                VALUES (?, ?, ?, ?)";
        return $this->insertar($sql, [$operacionId, $usuarioId, $accion, $descripcion]);
    }

    // Actualiza (poco usado en auditoría, pero lo dejas por si hay correcciones)
    public function actualizar(int $idLog, int $operacionId, ?int $usuarioId, string $accion, ?string $descripcion = null)
    {
        $accion      = trim($accion);
        $descripcion = $descripcion !== null ? trim($descripcion) : null;

        $sql = "UPDATE operaciones_log
                   SET operacion_id = ?, usuario_id = ?, accion = ?, descripcion = ?
                 WHERE id_log = ?
                 LIMIT 1";
        return $this->save($sql, [$operacionId, $usuarioId, $accion, $descripcion, $idLog]);
    }

    // Helper: arma un texto estándar de costo/cont/operación
    public function desc(string $entidad, string $evento, array $info = []): string
    {
        // $entidad: 'contenedor_fisico','contenedor_maritimo','costo','operacion'
        // $evento: 'creado','actualizado','eliminado'
        $base = strtoupper($entidad) . " {$evento}";
        if (!empty($info)) {
            $kv = [];
            foreach ($info as $k => $v) { $kv[] = "$k=$v"; }
            $base .= " (" . implode(", ", $kv) . ")";
        }
        return $base;
    }
}
