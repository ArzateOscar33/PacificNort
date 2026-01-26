<?php
class Operaciones_por_partida_rutasModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    public function existeFacturaActiva(int $factura_id): bool
    {
        $sql = "SELECT id_factura
                FROM op_partida_facturas
                WHERE id_factura = ? AND estatus = 1
                LIMIT 1";
        $row = $this->select($sql, [$factura_id]);
        return !empty($row);
    }

    // ===================== SUGERENCIAS FACTURAS =====================
    public function sugerirFacturas(string $term, int $limit = 10): array
    {
        $term  = trim((string)$term);
        $limit = (int)$limit;
        if ($limit < 1)  $limit = 10;
        if ($limit > 25) $limit = 25;

        if ($term === '') return [];

        $like = '%' . $term . '%';

        $sql = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.proveedor,
                    b.nombre AS bodega_nombre
                FROM op_partida_facturas f
                INNER JOIN bodegas b ON b.id_bodega = f.bodega_id
                WHERE f.estatus = 1
                  AND (
                    f.numero_factura LIKE ?
                    OR IFNULL(f.proveedor,'') LIKE ?
                  )
                ORDER BY f.id_factura DESC
                LIMIT $limit";

        $rows = $this->selectAll($sql, [$like, $like]);
        return ($rows === false) ? [] : $rows;
    }

    // ===================== LISTAR PRODUCTOS (RUTAS) =====================
    public function listarProductosRutas(int $facturaId, string $term = ''): array
    {
        $facturaId = (int)$facturaId;
        $term      = trim($term);

        $where  = " WHERE p.estatus = 1 AND p.factura_id = ? ";
        $params = [$facturaId];

        if ($term !== '') {
            $where .= " AND (
                p.descripcion LIKE ?
                OR IFNULL(p.upc,'') LIKE ?
                OR IFNULL(p.marca,'') LIKE ?
            ) ";
            $like = '%' . $term . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        /*
          IMPORTANTE:
          Ajusta estos nombres de columnas según tu tabla real op_partida_envios:
          - e.cajas_enviadas
          - e.estatus
          - e.producto_id
          - e.factura_id

          Si tu columna se llama diferente (ej. cajas, qty, cajas_envio, etc.),
          aquí es donde se corrige.
        */
        $sql = "SELECT
            p.id_producto,
            p.factura_id,
            p.descripcion,
            p.upc,
            p.marca,
            p.cajas AS cajas_total,
            COALESCE(SUM(CASE WHEN e.estatus IN (1,2) THEN e.cajas_enviadas ELSE 0 END), 0) AS cajas_enviadas,
            (p.cajas - COALESCE(SUM(CASE WHEN e.estatus IN (1,2) THEN e.cajas_enviadas ELSE 0 END), 0)) AS cajas_restantes
        FROM op_partida_productos p
        LEFT JOIN op_partida_envios e
          ON e.factura_id  = p.factura_id
         AND e.producto_id = p.id_producto
        $where
        GROUP BY
            p.id_producto, p.factura_id, p.descripcion, p.upc, p.marca, p.cajas
        ORDER BY p.id_producto DESC";

        $rows = $this->selectAll($sql, $params);
        return ($rows === false) ? [] : $rows;
    }

    // ===================== LISTAR ENVIOS (DETALLE) =====================
public function listarEnviosProducto(int $facturaId, int $productoId): array
{
    $facturaId  = (int)$facturaId;
    $productoId = (int)$productoId;

    $sql = "SELECT
                e.id_envio,
                e.factura_id,
                e.producto_id,
                e.ciudad_destino_id,
                c.nombre_ciudad AS destino,
                e.id_fisico,
                cf.numero_ferro AS ferro,
                e.cajas_enviadas,
                e.fecha_envio,
                e.notas,
                e.estatus,
                e.creado_en
            FROM op_partida_envios e
            INNER JOIN ciudades c ON c.id_ciudad = e.ciudad_destino_id
            INNER JOIN contenedores_fisicos cf ON cf.id_fisico = e.id_fisico
            WHERE e.factura_id = ?
              AND e.producto_id = ?
            ORDER BY e.fecha_envio DESC, e.id_envio DESC";

    $rows = $this->selectAll($sql, [$facturaId, $productoId]);
    return ($rows === false) ? [] : $rows;
}


    // ===================== CIUDADES =====================
public function listarCiudadesActivas(): array
{
    $sql = "SELECT
                c.id_ciudad,
                c.nombre_ciudad
            FROM ciudades c
            WHERE c.estatus = 1
            ORDER BY c.nombre_ciudad ASC";
    $rows = $this->selectAll($sql);
    return ($rows === false || empty($rows)) ? [] : $rows;
}

    // =====================
    // SUGERIR CAJA/FERRO
    // =====================

 

// =====================
// SUGERIR FERROS (FÍSICOS)
// Tabla real: contenedores_fisicos (id_fisico, numero_ferro, estatus)
// =====================

// Wrapper para que el Controller/JS llamen sugerirFisicos()
public function sugerirFisicos(string $term, int $limit = 10): array
{
    return $this->sugerirFerros($term, $limit);
}

public function sugerirFerros(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;

    if ($limit < 1)  $limit = 10;
    if ($limit > 25) $limit = 25;

    // para sugerencias: no saturar si escriben 1 caracter
    if ($term === '' || mb_strlen($term) < 2) return [];

    $like = '%' . $term . '%';

    // Opcional: prioriza los que empiezan con lo escrito
    $likeStart = $term . '%';

    $sql = "SELECT
                cf.id_fisico AS id,
                'FERRO'      AS tipo,
                cf.numero_ferro AS texto
            FROM contenedores_fisicos cf
            WHERE cf.estatus = 1
              AND cf.numero_ferro LIKE ?
            ORDER BY
              (cf.numero_ferro LIKE ?) DESC,
              cf.id_fisico DESC
            LIMIT $limit";

    $rows = $this->selectAll($sql, [$like, $likeStart]);
    return ($rows === false) ? [] : $rows;
}

// =====================
// SUGERENCIAS CIUDADES (DESTINOS)
// =====================
public function sugerirCiudades(string $term, int $limit = 10): array
{
    $term  = trim((string)$term);
    $limit = (int)$limit;

    if ($limit < 1)  $limit = 10;
    if ($limit > 25) $limit = 25;

    // Evita consultas con 1 caracter
    if ($term === '' || mb_strlen($term) < 2) return [];

    $like      = '%' . $term . '%';
    $likeStart = $term . '%';

    // AJUSTE DE CAMPO:
    // Si tu columna se llama "nombre" en vez de "nombre_ciudad", cámbialo aquí.
    $sql = "SELECT
                c.id_ciudad AS id,
                c.nombre_ciudad AS texto
            FROM ciudades c
            WHERE c.estatus = 1
              AND c.nombre_ciudad LIKE ?
            ORDER BY
              (c.nombre_ciudad LIKE ?) DESC,
              c.nombre_ciudad ASC
            LIMIT $limit";

    $rows = $this->selectAll($sql, [$like, $likeStart]);
    return ($rows === false) ? [] : $rows;
}



//alta

// =====================
// HELPERS: VALIDACIONES BÁSICAS
// =====================

public function existeProductoEnFactura(int $facturaId, int $productoId): bool
{
    $sql = "SELECT id_producto
            FROM op_partida_productos
            WHERE id_producto = ? AND factura_id = ? AND estatus = 1
            LIMIT 1";
    $row = $this->select($sql, [$productoId, $facturaId]);
    return !empty($row);
}

public function getCajasTotalesProducto(int $facturaId, int $productoId): int
{
    $sql = "SELECT COALESCE(cajas,0) AS cajas
            FROM op_partida_productos
            WHERE id_producto = ? AND factura_id = ? AND estatus = 1
            LIMIT 1";
    $row = $this->select($sql, [$productoId, $facturaId]);
    return (int)($row['cajas'] ?? 0);
}

public function getCajasEnviadasProducto(int $facturaId, int $productoId): int
{
    // En tu UI: 1=En camino, 2=Entregado => ambos cuentan como enviadas
    $sql = "SELECT COALESCE(SUM(cajas_enviadas),0) AS enviadas
            FROM op_partida_envios
            WHERE factura_id = ?
              AND producto_id = ?
              AND estatus IN (1,2)";
    $row = $this->select($sql, [$facturaId, $productoId]);
    return (int)($row['enviadas'] ?? 0);
}

public function existeCiudadActiva(int $ciudadId): bool
{
    $sql = "SELECT id_ciudad
            FROM ciudades
            WHERE id_ciudad = ? AND estatus = 1
            LIMIT 1";
    $row = $this->select($sql, [$ciudadId]);
    return !empty($row);
}

// =====================
// FÍSICOS: OBTENER / CREAR (contenedores_fisicos)
// =====================

public function getFisicoPorNumero(string $numeroFerro): ?array
{
    $numeroFerro = trim($numeroFerro);
    if ($numeroFerro === '') return null;

    $sql = "SELECT id_fisico, numero_ferro, estatus
            FROM contenedores_fisicos
            WHERE numero_ferro = ?
            LIMIT 1";
    $row = $this->select($sql, [$numeroFerro]);
    return $row ?: null;
}

public function crearFisico(string $numeroFerro): int
{
    $numeroFerro = trim($numeroFerro);
    if ($numeroFerro === '') return 0;

    // estatus por defecto: 1
    $sql = "INSERT INTO contenedores_fisicos (numero_ferro, estatus)
            VALUES (?, 1)";

    // OJO: en tu Query normalmente existe insertar() o insertar/insertarId.
    // Ajusta si tu método se llama distinto.
    $id = $this->insertar($sql, [$numeroFerro]); // ideal: retorna id insertado
    return (int)$id;
}

public function obtenerOCrearFisicoPorNumero(string $numeroFerro): int
{
    $numeroFerro = trim($numeroFerro);
    if ($numeroFerro === '') return 0;

    $existe = $this->getFisicoPorNumero($numeroFerro);
    if (!empty($existe['id_fisico'])) {
        // Si existe pero está inactivo, lo puedes reactivar (opcional)
        if ((int)$existe['estatus'] !== 1) {
            $sql = "UPDATE contenedores_fisicos SET estatus = 1 WHERE id_fisico = ?";
            $this->save($sql, [(int)$existe['id_fisico']]); // ajusta si tu Query usa otro método
        }
        return (int)$existe['id_fisico'];
    }

    return $this->crearFisico($numeroFerro);
}

// =====================
// GUARDAR ENVÍOS (MÚLTIPLES RENGLONES)
// =====================

/**
 * Guarda múltiples envíos de un producto (desde el modal).
 *
 * @param int $facturaId
 * @param int $productoId
 * @param array $envios  Cada item:
 *  [
 *    'ciudad_id' => int,
 *    'fecha_envio' => 'YYYY-MM-DD',
 *    'fisico_id' => int (opcional si ya existe),
 *    'fisico_texto' => string (opcional si quieres crear por texto),
 *    'cajas' => int,
 *    'estatus' => 1|2,
 *    'nota' => string|null
 *  ]
 *
 * @return array ['ok'=>bool,'msg'=>string,'inserted'=>int,'ids'=>array]
 */
public function guardarEnviosProducto(int $facturaId, int $productoId, array $envios): array
{
    $facturaId  = (int)$facturaId;
    $productoId = (int)$productoId;

    if ($facturaId <= 0 || $productoId <= 0) {
        return ['ok'=>false,'msg'=>'Factura/Producto inválidos.','inserted'=>0,'ids'=>[]];
    }

    // Validaciones: factura activa y producto en factura
    if (!$this->existeFacturaActiva($facturaId)) {
        return ['ok'=>false,'msg'=>'La factura no existe o está inactiva.','inserted'=>0,'ids'=>[]];
    }
    if (!$this->existeProductoEnFactura($facturaId, $productoId)) {
        return ['ok'=>false,'msg'=>'El producto no pertenece a la factura o está inactivo.','inserted'=>0,'ids'=>[]];
    }

    if (!is_array($envios) || count($envios) === 0) {
        return ['ok'=>false,'msg'=>'No hay renglones de envíos para guardar.','inserted'=>0,'ids'=>[]];
    }

    // Checar disponibilidad (restantes)
    $totales  = $this->getCajasTotalesProducto($facturaId, $productoId);
    $enviadas = $this->getCajasEnviadasProducto($facturaId, $productoId);
    $restantes = max(0, $totales - $enviadas);

    $totalNuevo = 0;
    foreach ($envios as $r) {
        $c = (int)($r['cajas'] ?? 0);
        if ($c > 0) $totalNuevo += $c;
    }

    if ($totalNuevo <= 0) {
        return ['ok'=>false,'msg'=>'Las cajas a enviar deben ser mayor a 0.','inserted'=>0,'ids'=>[]];
    }

    if ($totalNuevo > $restantes) {
        return ['ok'=>false,'msg'=>"No puedes enviar $totalNuevo cajas. Restantes en bodega: $restantes.",'inserted'=>0,'ids'=>[]];
    }

    // Insertar renglones
    $inserted = 0;
    $ids = [];

$sqlIns = "INSERT INTO op_partida_envios
            (factura_id, producto_id, ciudad_destino_id, fecha_envio, id_fisico, cajas_enviadas, notas, estatus, creado_por)
           VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)";


    foreach ($envios as $r) {
        $ciudadId = (int)($r['ciudad_id'] ?? 0);
        $fecha    = trim((string)($r['fecha_envio'] ?? ''));
        $fisicoId = (int)($r['fisico_id'] ?? 0);
        $fisicoTxt= trim((string)($r['fisico_texto'] ?? ''));
        $cajas    = (int)($r['cajas'] ?? 0);
        $estatus  = (int)($r['estatus'] ?? 1);
        $nota     = trim((string)($r['nota'] ?? ''));
        // fecha_envio en BD es DATETIME
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = $fecha . ' 00:00:00';
}


        if ($ciudadId <= 0 || !$this->existeCiudadActiva($ciudadId)) {
            return ['ok'=>false,'msg'=>'Destino (ciudad) inválido o inactivo.','inserted'=>$inserted,'ids'=>$ids];
        }
        if ($fecha === '') {
            return ['ok'=>false,'msg'=>'Fecha de envío requerida.','inserted'=>$inserted,'ids'=>$ids];
        }
        if ($cajas <= 0) {
            return ['ok'=>false,'msg'=>'Cajas a enviar inválidas.','inserted'=>$inserted,'ids'=>$ids];
        }
        if ($estatus !== 1 && $estatus !== 2) {
            $estatus = 1;
        }

        // Resolver físico:
        // - si viene fisico_id, úsalo
        // - si no viene, pero viene texto, intenta crear/obtener
        if ($fisicoId <= 0) {
            if ($fisicoTxt !== '') {
                $fisicoId = $this->obtenerOCrearFisicoPorNumero($fisicoTxt);
            }
        }

        if ($fisicoId <= 0) {
            return ['ok'=>false,'msg'=>'Caja/Ferro inválido.','inserted'=>$inserted,'ids'=>$ids];
        }

        // Nota (varchar 255)
        if (mb_strlen($nota) > 255) {
            $nota = mb_substr($nota, 0, 255);
        }
        $creadoPor = (int)($_SESSION['id_usuario'] ?? 0);

        $newId = $this->insertar($sqlIns, [
            $facturaId,
            $productoId,
            $ciudadId,
            $fecha,
            $fisicoId,
            $cajas,
            $nota,
            $estatus,
            $creadoPor ?: null
            
        ]);

        if (!$newId) {
            return ['ok'=>false,'msg'=>'No se pudo guardar uno de los envíos.','inserted'=>$inserted,'ids'=>$ids];
        }

        $inserted++;
        $ids[] = (int)$newId;
    }

    return ['ok'=>true,'msg'=>'Envíos guardados correctamente.','inserted'=>$inserted,'ids'=>$ids];
}

    
}
