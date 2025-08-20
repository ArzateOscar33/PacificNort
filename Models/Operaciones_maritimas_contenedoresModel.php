<?php
class Operaciones_maritimas_contenedoresModel extends Query
{

       // Operaciones “abiertas” o activas (ajusta criterio si usas otro estatus)
public function catalogoOperaciones(): array
{
    $sql = "SELECT 
                o.id_operacion,
                o.numero_operacion,
                o.numero_bl,
                o.etd, o.eta,
                c.id_cliente AS cliente_id,
                COALESCE(c.nombre,'') AS cliente
            FROM operaciones o
            LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
            WHERE o.estatus_id <> 6
            ORDER BY o.id_operacion DESC
            LIMIT 1000";
    return $this->selectAll($sql) ?: [];
}


    // Contenedores físicos (terrestres/ferros)
    public function catalogoContenedoresFisicos(): array
    {
        $sql = "SELECT id_fisico, numero_ferro
                FROM contenedores_fisicos
                WHERE estatus = 1
                ORDER BY numero_ferro";
        return $this->selectAll($sql) ?: [];
    }

    // Shippers (si tu tabla se llama distinto, ajusta)
    public function catalogoShippers(): array
    {
        $sql = "SELECT id_shipper, nombre
                FROM shippers
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }
    /** Retorna [cliente_id, cliente_nombre] para una operación */
public function getClienteDeOperacion(int $operacion_id): ?array
{
    $sql = "SELECT c.id_cliente AS cliente_id, COALESCE(c.nombre,'') AS cliente
            FROM operaciones o
            LEFT JOIN clientes c ON c.id_cliente = o.cliente_id
            WHERE o.id_operacion = ?
            LIMIT 1";
    $row = $this->select($sql, [$operacion_id]);
    return $row ?: null;
}

    /**
     * Lista contenedores en operación (marítimos + terrestres) con filtros opcionales.
     * $filters = [
     *   'tipo' => '' | 'maritimo' | 'terrestre',
     *   'term' => 'texto a buscar en contenedor o cliente'
     * ]
     */
public function listar(array $filters = []): array
{
    $args = [];

    // Búsqueda libre
    $term   = isset($filters['term']) ? trim(mb_strtolower($filters['term'],'UTF-8')) : '';
    $buscar = ($term !== '');
    if ($buscar) {
        $needle = "%{$term}%";
    }

    // Filtro por tipo
    $filtroTipo = isset($filters['tipo']) ? strtolower(trim($filters['tipo'])) : '';

    $sql = "
    SELECT * FROM (
        /* ===== MARÍTIMO ===== */
        SELECT   
            'maritimo'                            AS tipo, 
            cmo.id                                 AS row_id, 
            cm.numero_contenedor                  AS contenedor,
            COALESCE(cli.nombre,'')               AS cliente,
            NULL                                  AS bultos,
            NULL                                  AS peso,
            o.eta,
            o.etd,
            dl.arribo_sd,
            NULL                                  AS shipper,
            o.id_operacion,
            o.numero_operacion                    AS operacion,   -- << NUEVO
            o.numero_bl                           AS bl,          -- << OPCIONAL
            cm.id_contenedor_maritimo             AS contenedor_id
        FROM contenedores_maritimos_operacion cmo
        INNER JOIN contenedores_maritimos cm
            ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
        INNER JOIN operaciones o
            ON o.id_operacion = cmo.operacion_id
        LEFT JOIN clientes cli
            ON cli.id_cliente = o.cliente_id
        LEFT JOIN detalles_logisticos dl
            ON dl.operacion_id = o.id_operacion
        WHERE 1=1
        " . ($buscar ? " AND (LOWER(cm.numero_contenedor) LIKE ? OR LOWER(cli.nombre) LIKE ? OR LOWER(o.numero_operacion) LIKE ?)" : "") . "

        UNION ALL

        /* ===== TERRESTRE (FÍSICO) ===== */
        SELECT
        
            'terrestre'                           AS tipo,
            co.id_contenedor                      AS row_id,
            cf.numero_ferro                       AS contenedor,
            COALESCE(cli2.nombre, cli.nombre, '') AS cliente,
            co.bultos                             AS bultos,
            co.peso                               AS peso,
            o.eta,
            o.etd,
            dl.arribo_sd,
            NULL                                  AS shipper,
            o.id_operacion,
            o.numero_operacion                    AS operacion,   -- << NUEVO
            o.numero_bl                           AS bl,          -- << OPCIONAL
            cf.id_fisico                          AS contenedor_id
        FROM contenedores_operacion co
        INNER JOIN contenedores_fisicos cf
            ON cf.id_fisico = co.id_fisico
        INNER JOIN operaciones o
            ON o.id_operacion = co.operacion_id
        LEFT JOIN clientes cli
            ON cli.id_cliente = o.cliente_id
        LEFT JOIN clientes cli2
            ON cli2.id_cliente = co.cliente_id
        LEFT JOIN detalles_logisticos dl
            ON dl.operacion_id = o.id_operacion
        WHERE 1=1
        " . ($buscar ? " AND (LOWER(cf.numero_ferro) LIKE ? OR LOWER(COALESCE(cli2.nombre, cli.nombre, '')) LIKE ? OR LOWER(o.numero_operacion) LIKE ?)" : "") . "
    ) AS x
    WHERE 1=1
    " . ($filtroTipo === 'maritimo' ? " AND x.tipo = 'maritimo' " : "") . "
    " . ($filtroTipo === 'terrestre' ? " AND x.tipo = 'terrestre' " : "") . "
    ORDER BY x.eta DESC, x.contenedor ASC
    ";

    if ($buscar) {
        // tramo marítimo
        $args[] = $needle; // numero_contenedor
        $args[] = $needle; // cliente
        $args[] = $needle; // numero_operacion
        // tramo terrestre
        $args[] = $needle; // numero_ferro
        $args[] = $needle; // cliente (cli2/cli)
        $args[] = $needle; // numero_operacion
    }

    return $this->selectAll($sql, $args) ?: [];
}
/** Busca un contenedor físico por su número (case-insensitive). */
    public function findContenedorFisicoByNumero(string $numero_ferro)
    {
        $sql = "SELECT id_fisico, numero_ferro, estatus
                FROM contenedores_fisicos
                WHERE UPPER(TRIM(numero_ferro)) = UPPER(TRIM(?))
                LIMIT 1";
        return $this->select($sql, [$numero_ferro]);
    }

    /** Inserta un contenedor físico (estatus=1). Retorna el ID insertado. */
    public function insertContenedorFisico(string $numero_ferro)
    {
        $sql   = "INSERT INTO contenedores_fisicos (numero_ferro, estatus) VALUES (?, 1)";
        $datos = [trim($numero_ferro)];
        return $this->insertar($sql, $datos);
    }

    /**
     * Asegura que exista el contenedor físico y retorna su id_fisico.
     * Si no existe, lo crea.
     */
    public function ensureContenedorFisico(string $numero_ferro): int
    {
        $row = $this->findContenedorFisicoByNumero($numero_ferro);
        if (!empty($row) && isset($row['id_fisico'])) {
            return (int)$row['id_fisico'];
        }
        $id = $this->insertContenedorFisico($numero_ferro);
        return (int)$id;
    }

    /** Verifica si ya existe la relación contenedor físico ↔ operación. */
    public function existsContenedorFisicoOperacion(int $operacion_id, int $id_fisico)
    {
        $sql = "SELECT id_contenedor_operacion
                FROM contenedores_operacion
                WHERE operacion_id = ? AND id_fisico = ?
                LIMIT 1";
        return $this->select($sql, [$operacion_id, $id_fisico]);
    }

    /**
     * Inserta la relación contenedor físico ↔ operación.
     * Campos opcionales: cliente_id, bultos, peso (ajusta si tu tabla tiene más columnas).
     * Retorna el ID insertado en contenedores_operacion.
     */
    public function insertContenedorFisicoOperacion(
        int $operacion_id,
        int $id_fisico,
        ?int $cliente_id = null,
        ?int $bultos = null,
        ?float $peso = null
    ) {
        $sql = "INSERT INTO contenedores_operacion (operacion_id, id_fisico, cliente_id, bultos, peso)
                VALUES (?, ?, ?, ?, ?)";
        $datos = [$operacion_id, $id_fisico, $cliente_id, $bultos, $peso];
        return $this->insertar($sql, $datos);
    }

    /* ===========================
     *  CONTENEDOR MARÍTIMO (opcional)
     * =========================== */

    /** Busca contenedor marítimo por su número. */
    public function findContenedorMaritimoByNumero(string $numero_contenedor)
    {
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor, estatus
                FROM contenedores_maritimos
                WHERE UPPER(TRIM(numero_contenedor)) = UPPER(TRIM(?))
                LIMIT 1";
        return $this->select($sql, [$numero_contenedor]);
    }

    /** (Opcional) Crea contenedor marítimo. */
    public function insertContenedorMaritimo(string $numero_contenedor)
    {
        $sql   = "INSERT INTO contenedores_maritimos (numero_contenedor, estatus) VALUES (?, 1)";
        $datos = [trim($numero_contenedor)];
        return $this->insertar($sql, $datos);
    }

    /** Verifica si ya existe la relación contenedor marítimo ↔ operación. */
    public function existsContenedorMaritimoOperacion(int $operacion_id, int $contenedor_maritimo_id)
    {
        $sql = "SELECT id
                FROM contenedores_maritimos_operacion
                WHERE operacion_id = ? AND contenedor_maritimo_id = ?
                LIMIT 1";
        return $this->select($sql, [$operacion_id, $contenedor_maritimo_id]);
    }

    /** Inserta la relación contenedor marítimo ↔ operación. */
    public function insertContenedorMaritimoOperacion(int $operacion_id, int $contenedor_maritimo_id)
    {
        $sql   = "INSERT INTO contenedores_maritimos_operacion (operacion_id, contenedor_maritimo_id)
                  VALUES (?, ?)";
        $datos = [$operacion_id, $contenedor_maritimo_id];
        return $this->insertar($sql, $datos);
    }


        /** Detalle para Editar por tipo + row_id (vínculo) */
    public function getDetalleParaEditar(string $tipo, int $row_id): ?array
    {
        $tipo = strtolower(trim($tipo));
        if ($tipo === 'maritimo') {
            $sql = "SELECT 
                        'maritimo' AS tipo,
                        cmo.id     AS row_id,
                        o.id_operacion,
                        o.numero_operacion,
                        cm.id_contenedor_maritimo,
                        cm.numero_contenedor,
                        o.cliente_id,
                        cli.nombre AS cliente,
                        o.shipper_id,
                        sp.nombre  AS shipper
                    FROM contenedores_maritimos_operacion cmo
                    INNER JOIN contenedores_maritimos cm
                        ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                    INNER JOIN operaciones o
                        ON o.id_operacion = cmo.operacion_id
                    LEFT JOIN clientes cli ON cli.id_cliente = o.cliente_id
                    LEFT JOIN shippers sp  ON sp.id_shipper  = o.shipper_id
                    WHERE cmo.id = ?
                    LIMIT 1";
            $row = $this->select($sql, [$row_id]);
            if (!$row) return null;
            // bandera de no editable en este módulo
            $row['editable'] = false;
            $row['motivo_no_editable'] = 'Contenedor Marítimo se edita en Módulo de Operaciones';
            return $row;
        }

        // TERRESTRE
        $sql = "SELECT 
                    'terrestre' AS tipo,
                    co.id_contenedor           AS row_id,
                    o.id_operacion,
                    o.numero_operacion,
                    /* cliente: preferimos el de la operación como fuente de la verdad */
                    o.cliente_id               AS cliente_id,
                    COALESCE(cli.nombre,'')    AS cliente,
                    cf.id_fisico,
                    cf.numero_ferro,
                    co.bultos,
                    co.peso,
                    co.comentarios,
                    o.shipper_id,
                    sp.nombre                  AS shipper
                FROM contenedores_operacion co
                INNER JOIN contenedores_fisicos cf
                    ON cf.id_fisico = co.id_fisico
                INNER JOIN operaciones o
                    ON o.id_operacion = co.operacion_id
                LEFT JOIN clientes cli
                    ON cli.id_cliente = o.cliente_id
                LEFT JOIN shippers sp
                    ON sp.id_shipper = o.shipper_id
                WHERE co.id_contenedor = ?
                LIMIT 1";
        $row = $this->select($sql, [$row_id]);
        if (!$row) return null;
        $row['editable'] = true;
        return $row;
    }
        /** ¿Ya existe el mismo id_fisico en esta operación, excluyendo este row_id? */
        public function existsContenedorFisicoOperacionExcept(int $operacion_id, int $id_fisico, int $row_id)
        {
            $sql = "SELECT id_contenedor
                    FROM contenedores_operacion
                    WHERE operacion_id = ? AND id_fisico = ? AND id_contenedor <> ?
                    LIMIT 1";
            return $this->select($sql, [$operacion_id, $id_fisico, $row_id]);
        }

        /** Actualiza el vínculo terrestre (cambia físico, bultos, comentarios) */
        public function updateContenedorFisicoOperacion(int $row_id, int $id_fisico, ?int $bultos, ?string $comentarios): bool
        {
            $sql = "UPDATE contenedores_operacion
                    SET id_fisico = ?, bultos = ?, comentarios = ?
                    WHERE id_contenedor = ?";
            $ok = $this->save($sql, [$id_fisico, $bultos, $comentarios, $row_id]);
            return $ok > 0;
        }
        public function actualizarTerrestreByNumero(
            int $row_id,
            int $operacion_id,
            string $numero_ferro,
            ?int $bultos,
            ?string $comentarios
        ): array {
            // Asegurar contenedor físico activo
            $rowFis = $this->findContenedorFisicoByNumero($numero_ferro);
            if (!empty($rowFis) && isset($rowFis['estatus']) && (int)$rowFis['estatus'] === 0) {
                return ['status'=>'warning','msg'=>'El contenedor existe pero está INACTIVO. Reactívalo primero.'];
            }
            $id_fisico = !empty($rowFis) ? (int)$rowFis['id_fisico'] : (int)$this->insertContenedorFisico($numero_ferro);
            if ($id_fisico <= 0) {
                return ['status'=>'error','msg'=>'No se pudo crear/obtener el contenedor físico'];
            }

            // Duplicado (otra fila en la misma operación con el mismo físico)
            $dupe = $this->existsContenedorFisicoOperacionExcept($operacion_id, $id_fisico, $row_id);
            if (!empty($dupe)) {
                return ['status'=>'warning','msg'=>'Ya existe este contenedor físico en la operación'];
            }

            $ok = $this->updateContenedorFisicoOperacion($row_id, $id_fisico, $bultos, $comentarios);
            if (!$ok) return ['status'=>'error','msg'=>'No se pudo actualizar el contenedor'];

            return [
                'status'=>'success',
                'msg'=>'Contenedor actualizado',
                'data'=>[
                    'row_id'      => $row_id,
                    'id_fisico'   => $id_fisico,
                    'numero_ferro'=> $numero_ferro,
                    'bultos'      => $bultos,
                    'comentarios' => $comentarios
                ]
            ];
        }
}
