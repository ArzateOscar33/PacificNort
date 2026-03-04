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

 

// =========================
// MARÍTIMO
// =========================

    /**
     * Regresa un resumen “para portal” de una operación marítima buscando por:
     * - número de operación (LBMF-XX, LC-XX, etc.)
     * - o BL (operaciones.numero_bl)
     *
     * Devuelve 1 fila por contenedor (si hay contenedores ligados).
     */
    public function getResumenOperacionMaritima(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];

        $sql = "SELECT
                o.numero_operacion,
                o.numero_bl,

                -- Contenedor marítimo (pueden ser varios)
                cm.numero_contenedor AS contenedor,

                -- Estatus actual (desde operaciones)
                e.nombre AS estatus_nombre,

                -- Último comentario preferido: evento -> notas -> vacío
                COALESCE(ult.comentario, o.notas, '') AS comentario,

                -- Fecha/hora de la última actualización (si hay evento)
                ult.fecha_creacion AS actualizacion
            FROM operaciones o
            LEFT JOIN estatus e
                   ON e.id_estatus = o.estatus_id

            -- Relación operación -> contenedor marítimo(s)
            LEFT JOIN contenedores_maritimos_operacion cmo
                   ON cmo.operacion_id = o.id_operacion
            LEFT JOIN contenedores_maritimos cm
                   ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id

            -- Último evento por operación y por contenedor (si aplica)
            LEFT JOIN (
                SELECT
                    el.operacion_id,
                    el.cont_maritimo_operacion_id,
                    el.comentario,
                    el.fecha_creacion
                FROM eventos_logisticos el
                INNER JOIN (
                    SELECT
                        operacion_id,
                        cont_maritimo_operacion_id,
                        MAX(fecha_creacion) AS max_fc
                    FROM eventos_logisticos
                    WHERE estatus = 1
                    GROUP BY operacion_id, cont_maritimo_operacion_id
                ) x
                  ON x.operacion_id = el.operacion_id
                 AND (x.cont_maritimo_operacion_id <=> el.cont_maritimo_operacion_id)
                 AND x.max_fc = el.fecha_creacion
                WHERE el.estatus = 1
            ) ult
              ON ult.operacion_id = o.id_operacion
             AND (ult.cont_maritimo_operacion_id <=> cmo.id)

            WHERE o.tipo_operacion_id = 11
              AND (
                   REPLACE(UPPER(o.numero_operacion), ' ', '') = REPLACE(UPPER(?), ' ', '')
                OR REPLACE(UPPER(o.numero_bl),        ' ', '') = REPLACE(UPPER(?), ' ', '')
              )";

        return $this->selectAll($sql, [$term, $term]) ?: [];
    }

    /**
     * (Opcional) Si aún usas esta validación en algún lado,
     * hazla compatible también con BL.
     */
    public function getOperacionMaritimaPorNumero(string $term): ?array
    {
        $term = trim($term);
        if ($term === '') return null;

        $sql = "SELECT
                o.id_operacion,
                o.numero_operacion,
                o.numero_bl,
                o.cliente_id,
                o.estatus_id,
                e.nombre AS estatus_nombre,
                o.notas
            FROM operaciones o
            LEFT JOIN estatus e
                   ON e.id_estatus = o.estatus_id
            WHERE o.tipo_operacion_id = 11
              AND (
                   REPLACE(UPPER(o.numero_operacion), ' ', '') = REPLACE(UPPER(?), ' ', '')
                OR REPLACE(UPPER(o.numero_bl),        ' ', '') = REPLACE(UPPER(?), ' ', '')
              )
            LIMIT 1";

        $row = $this->select($sql, [$term, $term]);
        return $row ?: null;
    }
}
