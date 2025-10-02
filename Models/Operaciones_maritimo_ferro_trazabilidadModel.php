<?php
class Operaciones_maritimo_ferro_trazabilidadModel extends Query
{
    /**
     * Sugerencias para el input "Operación Ferroviaria"
     * Busca por número de operación FO, número de ferro/caja o nombre de cliente.
     * Usa la vista para evitar múltiples JOINs en el autocomplete.
     */
    public function buscarOperacionesFerroSugerencias(string $term, int $limit = 10): array
    {
        $like = '%' . trim($term) . '%';
        $limit = max(1, min(50, (int)$limit)); // bound seguro

        // NOTA: Algunos drivers no aceptan bind para LIMIT.
        // Si tu PDO no emula prepares, interpolamos $limit ya saneado.
        $sql = "
            SELECT id_operacion_ferro, numero_operacion, numero_ferro, cliente_nombre
            FROM vista_operaciones_ferroviarias_completa
            WHERE numero_operacion LIKE ?
               OR numero_ferro LIKE ?
               OR cliente_nombre LIKE ?
            ORDER BY id_operacion_ferro DESC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$like, $like, $like]) ?: [];
    }

    /**
     * Encabezado/básicos de la operación ferroviaria seleccionada:
     * - FO: id, numero_operacion, fecha, comentarios, estatus_id
     * - Cliente principal: id/nombre
     * - Ferro/caja vinculado: id_fisico/numero_ferro
     */
    public function getOperacionFerroBasicos(int $operacionFerroId): ?array
    {
        $sql = "
            SELECT 
                of.id_operacion_ferro,
                of.numero_operacion,
                of.fecha,
                of.comentarios,
                of.estatus_id,
                of.cliente_id,
                c.nombre AS cliente_nombre,
                of.contenedor_fisico_id,
                cf.numero_ferro
            FROM operaciones_ferroviarias AS of
            LEFT JOIN clientes AS c           ON c.id_cliente = of.cliente_id
            LEFT JOIN contenedores_fisicos cf ON cf.id_fisico  = of.contenedor_fisico_id
            WHERE of.id_operacion_ferro = ?
            LIMIT 1
        ";
        $row = $this->select($sql, [$operacionFerroId]);
        return $row ?: null;
    }

    /**
     * Ferro/caja vinculado a la operación (por si lo necesitas por separado).
     */
    public function getFerroDeOperacionFerro(int $operacionFerroId): ?array
    {
        $sql = "
            SELECT 
                cf.id_fisico,
                cf.numero_ferro
            FROM operaciones_ferroviarias of
            INNER JOIN contenedores_fisicos cf ON cf.id_fisico = of.contenedor_fisico_id
            WHERE of.id_operacion_ferro = ?
            LIMIT 1
        ";
        $row = $this->select($sql, [$operacionFerroId]);
        return $row ?: null;
    }

    /**
     * Clientes involucrados en la operación FO:
     *  - Cliente principal de la FO (of.cliente_id)
     *  - Clientes de las operaciones marítimas enlazadas vía contenedor_maritimo_ferro -> cmo -> operaciones.cliente_id
     */
    public function getClientesDeOperacionFerro(int $operacionFerroId): array
    {
        $sql = "
            SELECT DISTINCT c.id_cliente, c.nombre
            FROM (
                SELECT of.cliente_id AS cliente_id
                FROM operaciones_ferroviarias of
                WHERE of.id_operacion_ferro = ?

                UNION

                SELECT o.cliente_id
                FROM contenedor_maritimo_ferro cmf
                INNER JOIN contenedores_maritimos_operacion cmo 
                        ON cmo.id = cmf.cont_maritimo_operacion_id
                INNER JOIN operaciones o 
                        ON o.id_operacion = cmo.operacion_id
                WHERE cmf.operacion_ferro_id = ?
                  AND o.cliente_id IS NOT NULL
            ) x
            INNER JOIN clientes c ON c.id_cliente = x.cliente_id
            ORDER BY c.nombre
        ";
        return $this->selectAll($sql, [$operacionFerroId, $operacionFerroId]) ?: [];
    }

    /**
     * Paquete listo para llenar tu modal:
     *  - operacion: básicos FO
     *  - ferro: id/numero_ferro
     *  - clientes: chips con id/nombre
     */
    public function getDatosModalTrazabilidad(int $operacionFerroId): array
    {
        $hdr      = $this->getOperacionFerroBasicos($operacionFerroId);
        $ferro    = $hdr 
                    ? ['id_fisico' => $hdr['contenedor_fisico_id'], 'numero_ferro' => $hdr['numero_ferro']] 
                    : $this->getFerroDeOperacionFerro($operacionFerroId);
        $clientes = $this->getClientesDeOperacionFerro($operacionFerroId);

        return [
            'operacion' => $hdr,
            'ferro'     => $ferro,
            'clientes'  => $clientes,
        ];
    }


     /* ============================
     * LUGARES (CIUDADES / PUERTOS)
     * ============================ */

    /** Sugerencias de ciudades activas por nombre */
    public function buscarCiudades(string $term, int $limit = 10): array
    {
        $like  = '%' . trim($term) . '%';
        $limit = max(1, min(50, (int)$limit));
        $sql = "
            SELECT 
                c.id_ciudad      AS id,
                c.nombre_ciudad  AS nombre,
                'ciudad'         AS tipo
            FROM ciudades c
            WHERE c.estatus = 1
              AND c.nombre_ciudad LIKE ?
            ORDER BY c.nombre_ciudad ASC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    /** Sugerencias de puertos activos por nombre (incluye ciudad si quieres mostrarla) */
    public function buscarPuertos(string $term, int $limit = 10): array
    {
        $like  = '%' . trim($term) . '%';
        $limit = max(1, min(50, (int)$limit));
        $sql = "
            SELECT 
                p.id_puerto AS id,
                p.nombre    AS nombre,
                'puerto'    AS tipo
            FROM puertos p
            WHERE p.estatus = 1
              AND p.nombre LIKE ?
            ORDER BY p.nombre ASC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    /**
     * Sugerencias unificadas de lugares (ciudades + puertos).
     * Útil para el autosuggest de Origen/Destino en un solo input.
     */
    public function buscarLugares(string $term, int $limit = 10): array
    {
        $limit = max(1, min(50, (int)$limit));

        // Para repartir el límite aproximado entre ambos (50/50)
        $limC  = max(1, (int)floor($limit / 2));
        $limP  = $limit - $limC;

        $ciudades = $this->buscarCiudades($term, $limC);
        $puertos  = $this->buscarPuertos($term, $limP);

        // Mezcla simple (puedes mejorar priorizando coincidencias exactas)
        return array_values(array_merge($ciudades, $puertos));
    }

    /* =================
     * TRANSPORTISTAS
     * ================= */

    /**
     * Transportistas activos filtrados por término y tipo.
     * Para trazabilidad ferroviaria es común usar tipo='ferroviario'.
     * Acepta: 'terrestre' | 'maritimo' | 'ferroviario'
     */
    public function buscarTransportistas(string $term, string $tipo = 'ferroviario', int $limit = 10): array
    {
        $like  = '%' . trim($term) . '%';
        $tipo  = in_array($tipo, ['terrestre','maritimo','ferroviario'], true) ? $tipo : 'ferroviario';
        $limit = max(1, min(50, (int)$limit));

        $sql = "
            SELECT 
                t.id_transportista AS id,
                t.nombre           AS nombre,
                t.tipo             AS tipo
            FROM transportistas t
            WHERE t.estatus = 1
              AND t.tipo = ?
              AND t.nombre LIKE ?
            ORDER BY t.nombre ASC
            LIMIT $limit
        ";
        return $this->selectAll($sql, [$tipo, $like]) ?: [];
    }

    /** Azúcar sintáctica para ferroviarios (default del módulo) */
    public function buscarTransportistasFerro(string $term, int $limit = 10): array
    {
        return $this->buscarTransportistas($term, 'ferroviario', $limit);
    }
    
}
