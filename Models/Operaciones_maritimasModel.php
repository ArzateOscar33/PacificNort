<?php
class Operaciones_maritimasModel extends Query
{
    /* =========================
       ===  CATÁLOGOS/HELPERS ===
       ========================= */

    public function getSubtipo(int $id): ?array
    {
        $sql = "SELECT id_subtipo, nombre, requiere_naviera, requiere_forwarder, puerto_arribo_default_id
                FROM subtipos_operacion
                WHERE id_subtipo = ?
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function findContenedorByNumero(string $numero): ?array
    {
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE LOWER(numero_contenedor) = LOWER(?)
                LIMIT 1";
        $row = $this->select($sql, [$numero]);
        return $row ?: null;
    }

    public function createContenedor(string $numero): int
    {
        $sql = "INSERT INTO contenedores_maritimos (numero_contenedor, estatus) VALUES (?, 1)";
        return (int)$this->insertar($sql, [$numero]);
    }

    public function linkContenedorOperacion(int $opId, int $contenedorId): int
    {
        $sql = "INSERT INTO contenedores_maritimos_operacion (operacion_id, contenedor_maritimo_id)
                VALUES (?, ?)";
        return (int)$this->insertar($sql, [$opId, $contenedorId]);
    }

    public function crearLog(int $opId, int $usuarioId, string $accion, string $descripcion = ''): int
    {
        $sql = "INSERT INTO operaciones_log (operacion_id, usuario_id, accion, descripcion, fecha)
                VALUES (?, ?, ?, ?, NOW())";
        return (int)$this->insertar($sql, [$opId, $usuarioId, $accion, $descripcion]);
    }

    /* =========================
       ===        LISTAR      ===
       ========================= */

    public function listar(array $filters = []): array
    {
        $sql = "
            SELECT
                o.id_operacion,
                o.numero_operacion,
                st.nombre  AS subtipo,
                o.numero_bl,
                p.nombre   AS puerto_arribo,        -- viene del subtipo (default) SOLO PARA MOSTRAR
                n.nombre   AS naviera,
                f.nombre   AS forwarder,
                c.nombre   AS cliente,
                o.etd, o.eta,
                e.nombre   AS estatus,
                GROUP_CONCAT(DISTINCT cm.numero_contenedor ORDER BY cm.numero_contenedor SEPARATOR ', ') AS contenedores
            FROM operaciones o
            JOIN tipos_operacion tt       ON tt.id_tipo_operacion = o.tipo_operacion_id
            LEFT JOIN subtipos_operacion st ON st.id_subtipo = o.subtipo_operacion_id
            LEFT JOIN puertos p           ON p.id_puerto = st.puerto_arribo_default_id
            LEFT JOIN navieras n          ON n.id_naviera = o.naviera_id
            LEFT JOIN forwarders f        ON f.id_forwarder = o.forwarder_id
            LEFT JOIN clientes c          ON c.id_cliente = o.cliente_id
            LEFT JOIN estatus e           ON e.id_estatus = o.estatus_id
            LEFT JOIN contenedores_maritimos_operacion cmo ON cmo.operacion_id = o.id_operacion
            LEFT JOIN contenedores_maritimos cm            ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
            WHERE UPPER(tt.nombre_operacion) LIKE 'MARIT%'
        ";
        $args = [];

        $subtipoId = isset($filters['filtroSubtipo']) ? (int)$filters['filtroSubtipo']
                   : (isset($filters['subtipo_id']) ? (int)$filters['subtipo_id'] : 0);
        if ($subtipoId > 0) {
            $sql .= " AND o.subtipo_operacion_id = ? ";
            $args[] = $subtipoId;
        }

        if (!empty($filters['term'])) {
            $needle = '%'.mb_strtolower($filters['term'],'UTF-8').'%';
            $sql .= " AND (
                        LOWER(o.numero_operacion) LIKE ?
                    OR  LOWER(o.numero_bl)        LIKE ?
                    OR  LOWER(p.nombre)           LIKE ?
                    OR  LOWER(e.nombre)           LIKE ?
                    OR  LOWER(c.nombre)           LIKE ?
                    OR  EXISTS (
                            SELECT 1
                            FROM contenedores_maritimos_operacion cmo2
                            JOIN contenedores_maritimos cm2
                              ON cm2.id_contenedor_maritimo = cmo2.contenedor_maritimo_id
                            WHERE cmo2.operacion_id = o.id_operacion
                              AND LOWER(cm2.numero_contenedor) LIKE ?
                    )
            )";
            array_push($args, $needle,$needle,$needle,$needle,$needle,$needle);
        }

        $sql .= "
            GROUP BY o.id_operacion, o.numero_operacion, st.nombre, o.numero_bl,
                     p.nombre, n.nombre, f.nombre, c.nombre, o.etd, o.eta, e.nombre
            ORDER BY o.id_operacion DESC
        ";

        return $this->selectAll($sql, $args) ?: [];
    }

    /* =========================
       ===  INSERT PRINCIPAL  ===
       ========================= */

    /**
     * Inserta operación marítima y relaciones usando SOLO métodos de Query (transacción con SAVE).
     * @param array      $op            Campos para tabla operaciones
     * @param array      $contenedores  [['id'=>?, 'numero'=>?], ...]
     * @param int        $usuarioId
     * @return array     ['status'=>'success','id_operacion'=>X] | ['status'=>'error','msg'=>...]
     */
    public function insertarOperacion(array $op, array $contenedores, int $usuarioId): array
    {
        try {
            // Iniciar transacción
            $this->save("START TRANSACTION", []);

            // 1) Validar subtipo y flags
            $st = $this->getSubtipo((int)$op['subtipo_operacion_id']);
            if (!$st) {
                $this->save("ROLLBACK", []);
                return ['status'=>'error','msg'=>'Subtipo inválido'];
            }
            if ((int)$st['requiere_naviera'] === 1 && empty($op['naviera_id'])) {
                $this->save("ROLLBACK", []);
                return ['status'=>'warning','msg'=>'Selecciona una naviera'];
            }
            if ((int)$st['requiere_forwarder'] === 1 && empty($op['forwarder_id'])) {
                $this->save("ROLLBACK", []);
                return ['status'=>'warning','msg'=>'Selecciona un forwarder'];
            }

            // 2) Insert en operaciones
            $sqlOp = "INSERT INTO operaciones
                        (numero_operacion, tipo_operacion_id, subtipo_operacion_id, etd, eta, numero_bl,
                         cliente_id, estatus_id, naviera_id, forwarder_id)
                      VALUES (?,?,?,?,?,?,?,?,?,?)";
            $paramsOp = [
                $op['numero_operacion'] ?? null,
                (int)$op['tipo_operacion_id'],      // 1 = Marítimo
                (int)$op['subtipo_operacion_id'],
                $op['etd'] ?? null,
                $op['eta'] ?? null,
                $op['numero_bl'] ?? null,
                (int)$op['cliente_id'],
                (int)($op['estatus_id'] ?? 9),
                !empty($op['naviera_id']) ? (int)$op['naviera_id'] : null,
                !empty($op['forwarder_id']) ? (int)$op['forwarder_id'] : null,
            ];
            $opId = (int)$this->insertar($sqlOp, $paramsOp);
            if ($opId <= 0) {
                $this->save("ROLLBACK", []);
                return ['status'=>'error','msg'=>'No se pudo guardar la operación'];
            }

            // 3) Contenedores (alta en catálogo si hace falta + enlace)
            if (!empty($contenedores)) {
                $vistos = [];
                foreach ($contenedores as $c) {
                    $cid  = isset($c['id']) ? (int)$c['id'] : 0;
                    $cnum = trim($c['numero'] ?? '');

                    if ($cid <= 0 && $cnum === '') continue;

                    $key = $cid > 0 ? 'id:'.$cid : 'num:'.mb_strtolower($cnum,'UTF-8');
                    if (isset($vistos[$key])) continue;
                    $vistos[$key] = true;

                    if ($cid <= 0) {
                        $found = $this->findContenedorByNumero($cnum);
                        if ($found) $cid = (int)$found['id_contenedor_maritimo'];
                        else        $cid = (int)$this->createContenedor($cnum);
                        if ($cid <= 0) {
                            $this->save("ROLLBACK", []);
                            return ['status'=>'error','msg'=>"No se pudo crear el contenedor: {$cnum}"];
                        }
                    }

                    $linkId = $this->linkContenedorOperacion($opId, $cid);
                    if ($linkId <= 0) {
                        $this->save("ROLLBACK", []);
                        return ['status'=>'error','msg'=>'No se pudo relacionar contenedor con la operación'];
                    }
                }
            }

            // (Se eliminó la lógica de movimientos_logisticos)

            // 4) Log de creación
            $logId = $this->crearLog($opId, $usuarioId, 'creacion', 'Operación creada');
            if ($logId <= 0) {
                $this->save("ROLLBACK", []);
                return ['status'=>'error','msg'=>'No se pudo registrar la bitácora de creación'];
            }

            // Commit
            $this->save("COMMIT", []);
            return ['status'=>'success','msg'=>'Operación creada','id_operacion'=>$opId];

        } catch (\Throwable $ex) {
            $this->save("ROLLBACK", []);
            error_log("insertarOperacion error: ".$ex->getMessage());
            return ['status'=>'error','msg'=>'Error inesperado al guardar'];
        }
    }

    /* =========================
       === LECTURAS PARA EDIT ===
       ========================= */

    public function getOperacionById(int $id): ?array
    {
        $sql = "SELECT *
                FROM operaciones
                WHERE id_operacion = ?
                LIMIT 1";
        $row = $this->select($sql, [$id]);
        return $row ?: null;
    }

    public function getContenedoresByOperacion(int $opId): array
    {
        $sql = "SELECT cm.id_contenedor_maritimo, cm.numero_contenedor
                FROM contenedores_maritimos_operacion cmo
                INNER JOIN contenedores_maritimos cm
                    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
                WHERE cmo.operacion_id = ?";
        return $this->selectAll($sql, [$opId]) ?: [];
    }

    /* =========================
       ===  BAJA LÓGICA      ===
       ========================= */

    public function desactivarOperacion(int $id, int $usuarioId): array
    {
        try {
            $this->save("START TRANSACTION", []);

            // Ajusta el estatus_id según tu flujo (6 = cancelada, por ejemplo)
            $res = $this->save("UPDATE operaciones SET estatus_id = 6 WHERE id_operacion = ?", [$id]);
            if (!$res) {
                $this->save("ROLLBACK", []);
                return ['status'=>'error','msg'=>'No se pudo desactivar la operación'];
            }

            $this->crearLog($id, $usuarioId, 'eliminar', 'Operación desactivada');

            $this->save("COMMIT", []);
            return ['status'=>'success','msg'=>'Operación desactivada'];
        } catch (\Throwable $e) {
            $this->save("ROLLBACK", []);
            return ['status'=>'error','msg'=>'Error inesperado al desactivar'];
        }
    }

    /* =========================
       ===  CATÁLOGOS VISTA  ===
       ========================= */

    public function subtiposMaritimos(): array
    {
        $sql = "SELECT st.id_subtipo, st.nombre, st.requiere_naviera, st.requiere_forwarder, st.puerto_arribo_default_id
                FROM subtipos_operacion st
                INNER JOIN tipos_operacion tt ON tt.id_tipo_operacion = st.tipo_operacion_id
                WHERE UPPER(tt.nombre_operacion) LIKE 'MARIT%'
                ORDER BY st.nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoEstatus(): array
    {
        $sql = "SELECT id_estatus, nombre
                FROM estatus
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoPuertos(): array
    {
        $sql = "SELECT id_puerto, nombre
                FROM puertos
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoNavieras(): array
    {
        $sql = "SELECT id_naviera, nombre
                FROM navieras
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    public function catalogoForwarders(): array
    {
        $sql = "SELECT id_forwarder, nombre
                FROM forwarders
                WHERE estatus = 1
                ORDER BY nombre";
        return $this->selectAll($sql) ?: [];
    }

    /* =========================
       ===   AUTOCOMPLETES   ===
       ========================= */

    public function buscarClientes(string $term): array
    {
        $like = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE estatus = 1
                  AND LOWER(nombre) LIKE ?
                ORDER BY nombre
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }

    public function buscarContenedores(string $term): array
    {
        $like = '%'.mb_strtolower($term, 'UTF-8').'%';
        $sql = "SELECT id_contenedor_maritimo, numero_contenedor
                FROM contenedores_maritimos
                WHERE estatus = 1
                  AND LOWER(numero_contenedor) LIKE ?
                ORDER BY numero_contenedor
                LIMIT 10";
        return $this->selectAll($sql, [$like]) ?: [];
    }
}
