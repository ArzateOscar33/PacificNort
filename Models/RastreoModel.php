<?php
// Models/RastreoModel.php

class RastreoModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtiene la operación ferroviaria por número (FO-01, FO-02, etc.)
     * Útil para validar y/o mostrar más datos en el encabezado.
     */
    public function getOperacionFerroPorNumero(string $numeroOperacion): ?array
    {
        $sql = "SELECT 
                    ofe.id_operacion_ferro,
                    ofe.numero_operacion,
                    ofe.fecha,
                    ofe.bultos_total,
                    ofe.destino_id,
                    ofe.transportista_id,
                    ofe.comentarios,
                    ofe.estatus_id,
                    cli.id_cliente,
                    cli.nombre AS cliente_nombre,
                    cf.id_fisico AS contenedor_fisico_id,
                    cf.numero_ferro
                FROM operaciones_ferroviarias ofe
                LEFT JOIN clientes cli 
                       ON cli.id_cliente = ofe.cliente_id
                LEFT JOIN contenedores_fisicos cf 
                       ON cf.id_fisico = ofe.contenedor_fisico_id
                WHERE ofe.numero_operacion = ?
                LIMIT 1";

        $row = $this->select($sql, [$numeroOperacion]);
        return $row ?: null;
    }

    /**
     * Versión adaptada de getTramosPorRuta, pero filtrando directamente
     * por número de operación FO-XX en lugar de por id_ruta.
     *
     * Esta función te regresa TODO lo necesario para llenar:
     * - Encabezado: operación + contenedor
     * - Tabla de tramos: origen, destino, transportista, fecha, comentario
     */
    public function getTramosPorNumeroOperacionFerro(string $numeroOperacion): array
    {
        $sql = "SELECT 
                    t.id_tramo,
                    t.orden,
                    
                    -- Ciudades
                    t.origen_id,
                    origen.nombre_ciudad  AS origen_nombre,
                    t.destino_id,
                    destino.nombre_ciudad AS destino_nombre,
                    
                    -- Transportista
                    t.transportista_id,
                    tr.nombre             AS transportista_nombre,
                    
                    -- Datos de tramo
                    t.monto,
                    t.comentario,
                    t.created_at          AS fecha_hora,
                    
                    -- Ruta / operación / contenedor
                    r.id_ruta,
                    r.contenedor_fisico_id,
                    cf.numero_ferro,
                    ofe.id_operacion_ferro,
                    ofe.numero_operacion
                FROM operaciones_ferroviarias ofe
                INNER JOIN rutas_ferro r 
                        ON r.operacion_ferro_id = ofe.id_operacion_ferro
                INNER JOIN rutas_ferro_tramos t 
                        ON t.ruta_id = r.id_ruta
                INNER JOIN ciudades origen 
                        ON origen.id_ciudad = t.origen_id
                INNER JOIN ciudades destino 
                        ON destino.id_ciudad = t.destino_id
                INNER JOIN transportistas tr 
                        ON tr.id_transportista = t.transportista_id
                INNER JOIN contenedores_fisicos cf 
                        ON cf.id_fisico = r.contenedor_fisico_id
                WHERE ofe.numero_operacion = ?
                  AND r.estatus = 1
                  AND t.estatus = 1
                ORDER BY t.orden ASC, t.id_tramo ASC";

        return $this->selectAll($sql, [$numeroOperacion]) ?: [];
    }
}
