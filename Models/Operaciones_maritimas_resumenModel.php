<?php
class Operaciones_maritimas_resumenModel extends Query
{
public function buscarOperacionesConContenedores(string $term): array
{
    // Normaliza el término
    $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';

    $sql = "
/* SUGERENCIAS sin duplicados, prioriza prefijo en numero_operacion */
SELECT 
  o.id_operacion     AS id,
  o.numero_operacion AS numero,
  cl.nombre          AS cliente,
  CONCAT(o.numero_operacion, ' — ', cl.nombre) AS label
FROM operaciones o
JOIN clientes cl ON cl.id_cliente = o.cliente_id
WHERE 
  LOWER(o.numero_operacion) LIKE CONCAT('%', LOWER(?), '%')
  OR LOWER(cl.nombre)       LIKE CONCAT('%', LOWER(?), '%')
ORDER BY 
  CASE 
    WHEN LOWER(o.numero_operacion) LIKE CONCAT(LOWER(?), '%') THEN 1  -- prefijo primero
    ELSE 2
  END,
  o.numero_operacion
LIMIT 10;

    ";

    // Nota: tres parámetros en total: (prefijo, contains, contains)
    return $this->selectAll($sql, [$term, $needle, $needle]);
}

 public function getContenedoresPorOperacion($id){
     $sql="
    -- Marítimos de la operación
    SELECT 
    o.id_operacion,
    o.numero_operacion,
    cl.nombre AS nombre_cliente,
    'Maritimo'  AS tipo_contenedor,
    cm.id_contenedor_maritimo AS id_contenedor,
    cm.numero_contenedor      AS numero_contenedor
    FROM operaciones o
    JOIN clientes cl ON cl.id_cliente = o.cliente_id
    JOIN contenedores_maritimos_operacion cmo 
    ON cmo.operacion_id = o.id_operacion
    JOIN contenedores_maritimos cm 
    ON cm.id_contenedor_maritimo = cmo.contenedor_maritimo_id
    WHERE o.id_operacion = ?

    UNION ALL

    -- Físicos de la operación
    SELECT 
    o.id_operacion,
    o.numero_operacion,
    cl.nombre AS nombre_cliente,
    'Ferro'   AS tipo_contenedor,
    cf.id_fisico AS id_contenedor,
    cf.numero_ferro AS numero_contenedor
    FROM operaciones o
    JOIN clientes cl ON cl.id_cliente = o.cliente_id
    JOIN contenedores_operacion co 
    ON co.operacion_id = o.id_operacion
    JOIN contenedores_fisicos cf 
    ON cf.id_fisico = co.id_fisico
    WHERE o.id_operacion = ?
    ORDER BY tipo_contenedor, numero_contenedor;

     ";
     return $this->selectAll($sql, [$id,$id]);
 }
}
