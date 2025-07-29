-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-07-2025 a las 23:40:40
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `p_nort`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `modulo` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `entidad` varchar(50) NOT NULL,
  `entidad_id` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `detalle` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Auditoría de acciones del sistema';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bodegas`
--

CREATE TABLE `bodegas` (
  `id_bodega` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brokers`
--

CREATE TABLE `brokers` (
  `id_broker` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudades`
--

CREATE TABLE `ciudades` (
  `id_ciudad` int(11) NOT NULL,
  `nombre_ciudad` varchar(100) NOT NULL,
  `estado_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_fisicos`
--

CREATE TABLE `contenedores_fisicos` (
  `id_fisico` int(11) NOT NULL,
  `numero_ferro` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Contenedores físicos (ferrocarril)';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_maritimos`
--

CREATE TABLE `contenedores_maritimos` (
  `id_contenedor_maritimo` int(11) NOT NULL,
  `numero_contenedor` varchar(50) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `naviera` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Contenedores marítimos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_maritimos_operacion`
--

CREATE TABLE `contenedores_maritimos_operacion` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `contenedor_maritimo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_operacion`
--

CREATE TABLE `contenedores_operacion` (
  `id_contenedor` int(11) NOT NULL,
  `id_fisico` int(11) DEFAULT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `bultos` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedor_maritimo_ferro`
--

CREATE TABLE `contenedor_maritimo_ferro` (
  `id` int(11) NOT NULL,
  `contenedor_maritimo_id` int(11) DEFAULT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costos_contenedor_operacion`
--

CREATE TABLE `costos_contenedor_operacion` (
  `id_costo_contenedor` int(11) NOT NULL,
  `contenedor_operacion_id` int(11) DEFAULT NULL,
  `tipo_costo` enum('transbordo','flete_local','broker','abrecha') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` enum('DLLS','PESOS') DEFAULT 'PESOS',
  `comentario` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costos_logisticos`
--

CREATE TABLE `costos_logisticos` (
  `id_costo` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `transbordo` decimal(10,2) DEFAULT NULL,
  `flete_local` decimal(10,2) DEFAULT NULL,
  `flete_ferro` decimal(10,2) DEFAULT NULL,
  `moneda` enum('PESOS','DLLS') DEFAULT 'PESOS'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id_departamento`, `nombre`) VALUES
(1, 'Logística'),
(2, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_logisticos`
--

CREATE TABLE `detalles_logisticos` (
  `id_detalle` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `arribo_sd` date DEFAULT NULL,
  `fecha_cargado` date DEFAULT NULL,
  `fecha_cruce` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `bultos` int(11) DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `vgm` decimal(10,2) DEFAULT NULL,
  `brecha` decimal(10,2) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `bodega_id` int(11) DEFAULT NULL,
  `broker_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_operacion`
--

CREATE TABLE `documentos_operacion` (
  `id_documento` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `tipo` enum('factura','bl','guia','manifiesto','otro') NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` text NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp(),
  `subido_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE `estados` (
  `id_estado` int(11) NOT NULL,
  `nombre_estado` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estatus`
--

CREATE TABLE `estatus` (
  `id_estatus` int(11) NOT NULL,
  `descripcion` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_logisticos`
--

CREATE TABLE `eventos_logisticos` (
  `id_evento` int(11) NOT NULL,
  `mov_logistico_id` int(11) DEFAULT NULL,
  `tipo_evento_id` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `folio_operacion` varchar(50) DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_contenedor`
--

CREATE TABLE `movimientos_contenedor` (
  `id_movimiento` int(11) NOT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `tipo_movimiento_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_logisticos`
--

CREATE TABLE `movimientos_logisticos` (
  `id_mov_logistico` int(11) NOT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `origen_id` int(11) DEFAULT NULL,
  `destino_id` int(11) DEFAULT NULL,
  `estatus_id` int(11) DEFAULT NULL,
  `tipo_operacion_id` int(11) DEFAULT NULL,
  `puerto_id` int(11) DEFAULT NULL,
  `transportista_id` int(11) DEFAULT NULL,
  `comentario_etapa` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Movimientos y trazabilidad de contenedores';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `navieras`
--

CREATE TABLE `navieras` (
  `id_naviera` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operaciones`
--

CREATE TABLE `operaciones` (
  `id_operacion` int(11) NOT NULL,
  `numero_operacion` varchar(50) NOT NULL,
  `etd` date DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `numero_bl` varchar(50) DEFAULT NULL,
  `isf` tinyint(1) DEFAULT 0,
  `shipper_id` int(11) DEFAULT NULL,
  `estado_operacion` enum('abierta','en_proceso','cerrada','cancelada') DEFAULT 'abierta',
  `puerto_arribo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla principal de operaciones logísticas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operaciones_log`
--

CREATE TABLE `operaciones_log` (
  `id_log` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` enum('creacion','actualizacion','cancelacion','cerrado') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_operacion`
--

CREATE TABLE `permisos_operacion` (
  `id_permiso` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_operacion_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puertos`
--

CREATE TABLE `puertos` (
  `id_puerto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ciudad_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puestos`
--

CREATE TABLE `puestos` (
  `id_puesto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puestos`
--

INSERT INTO `puestos` (`id_puesto`, `nombre`) VALUES
(1, 'Supervisor'),
(2, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`) VALUES
(1, 'admin', 'Administrador del sistema'),
(2, 'operador', 'Usuario operador'),
(3, 'Cliente', 'Se le otorga el rol de cliente al usuario para poder visualizar datos mas no subir o modificar informacion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_usuario`
--

CREATE TABLE `roles_usuario` (
  `id_rol_usuario` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles_usuario`
--

INSERT INTO `roles_usuario` (`id_rol_usuario`, `usuario_id`, `rol_id`, `fecha_creacion`) VALUES
(1, 1, 1, '2025-07-29 20:40:33'),
(2, 3, 3, '2025-07-29 20:50:05'),
(3, 4, 3, '2025-07-29 21:05:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shippers`
--

CREATE TABLE `shippers` (
  `id_shipper` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_evento_logistico`
--

CREATE TABLE `tipos_evento_logistico` (
  `id_tipo_evento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_movimiento`
--

CREATE TABLE `tipos_movimiento` (
  `id_tipo_movimiento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('gasto','abono') NOT NULL,
  `moneda` enum('PESOS','DLLS') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_operacion`
--

CREATE TABLE `tipos_operacion` (
  `id_tipo_operacion` int(11) NOT NULL,
  `nombre_operacion` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transportistas`
--

CREATE TABLE `transportistas` (
  `id_transportista` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('terrestre','maritimo','aereo') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trazabilidad_contenedor`
--

CREATE TABLE `trazabilidad_contenedor` (
  `id_traza` int(11) NOT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `tipo_transporte` enum('buque','ferrocarril','camion') NOT NULL,
  `origen` varchar(100) DEFAULT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `fecha_llegada` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(250) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `telefono` varchar(30) NOT NULL,
  `puesto_id` int(11) DEFAULT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `session_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla de usuarios del sistema';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `correo`, `clave`, `telefono`, `puesto_id`, `departamento_id`, `active`, `session_token`) VALUES
(1, 'Oscar', 'Arzate', 'arzateoscar33@gmail.com', '$2y$10$2g7El3lblMTOatfeY09a3.1mmWZ14xuXTiO.BalFFRLuzCLSrFGFO', '6644913156', 1, 1, 1, '276875e337cae9bf296698884449102911ff1678ed289941af5b02adaa3b9730'),
(3, 'Oscar', 'Arzate', 'arzateoscar323@gmail.com', '$2y$10$iNnyuFc8U5OSKbnE/wzX3e8LErxhfza4mYJXicBY9S86HTXPDLdDW', '664989879', 2, 2, 1, '0'),
(4, 'Jose', 'Canseco', 'arzateoscar3223@gmail.com', '$2y$10$kDaGpyh0R8yUX5EuN9FzvOr4wUVzVM/IIwZbL54U2CqNkZfakr4pq', '66423530335', 2, 2, 1, '0');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_bitacora_fecha` (`fecha`);

--
-- Indices de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD PRIMARY KEY (`id_bodega`),
  ADD KEY `ciudad_id` (`ciudad_id`);

--
-- Indices de la tabla `brokers`
--
ALTER TABLE `brokers`
  ADD PRIMARY KEY (`id_broker`);

--
-- Indices de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  ADD PRIMARY KEY (`id_ciudad`),
  ADD KEY `estado_id` (`estado_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `idx_clientes_rfc` (`rfc`);

--
-- Indices de la tabla `contenedores_fisicos`
--
ALTER TABLE `contenedores_fisicos`
  ADD PRIMARY KEY (`id_fisico`),
  ADD UNIQUE KEY `numero_ferro` (`numero_ferro`),
  ADD KEY `idx_contenedores_ferro` (`numero_ferro`);

--
-- Indices de la tabla `contenedores_maritimos`
--
ALTER TABLE `contenedores_maritimos`
  ADD PRIMARY KEY (`id_contenedor_maritimo`),
  ADD UNIQUE KEY `numero_contenedor` (`numero_contenedor`),
  ADD KEY `idx_contenedores_maritimos_numero` (`numero_contenedor`);

--
-- Indices de la tabla `contenedores_maritimos_operacion`
--
ALTER TABLE `contenedores_maritimos_operacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operacion_id` (`operacion_id`),
  ADD KEY `contenedor_maritimo_id` (`contenedor_maritimo_id`);

--
-- Indices de la tabla `contenedores_operacion`
--
ALTER TABLE `contenedores_operacion`
  ADD PRIMARY KEY (`id_contenedor`),
  ADD KEY `id_fisico` (`id_fisico`),
  ADD KEY `operacion_id` (`operacion_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `contenedor_maritimo_ferro`
--
ALTER TABLE `contenedor_maritimo_ferro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contenedor_maritimo_id` (`contenedor_maritimo_id`),
  ADD KEY `contenedor_fisico_id` (`contenedor_fisico_id`),
  ADD KEY `operacion_id` (`operacion_id`);

--
-- Indices de la tabla `costos_contenedor_operacion`
--
ALTER TABLE `costos_contenedor_operacion`
  ADD PRIMARY KEY (`id_costo_contenedor`),
  ADD KEY `contenedor_operacion_id` (`contenedor_operacion_id`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `costos_logisticos`
--
ALTER TABLE `costos_logisticos`
  ADD PRIMARY KEY (`id_costo`),
  ADD KEY `operacion_id` (`operacion_id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id_departamento`);

--
-- Indices de la tabla `detalles_logisticos`
--
ALTER TABLE `detalles_logisticos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `operacion_id` (`operacion_id`),
  ADD KEY `bodega_id` (`bodega_id`),
  ADD KEY `broker_id` (`broker_id`);

--
-- Indices de la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `operacion_id` (`operacion_id`),
  ADD KEY `subido_por` (`subido_por`);

--
-- Indices de la tabla `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `estatus`
--
ALTER TABLE `estatus`
  ADD PRIMARY KEY (`id_estatus`);

--
-- Indices de la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `mov_logistico_id` (`mov_logistico_id`),
  ADD KEY `tipo_evento_id` (`tipo_evento_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_eventos_fecha` (`fecha`);

--
-- Indices de la tabla `movimientos_contenedor`
--
ALTER TABLE `movimientos_contenedor`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `contenedor_fisico_id` (`contenedor_fisico_id`),
  ADD KEY `tipo_movimiento_id` (`tipo_movimiento_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_movimientos_fecha` (`fecha`);

--
-- Indices de la tabla `movimientos_logisticos`
--
ALTER TABLE `movimientos_logisticos`
  ADD PRIMARY KEY (`id_mov_logistico`),
  ADD KEY `contenedor_fisico_id` (`contenedor_fisico_id`),
  ADD KEY `origen_id` (`origen_id`),
  ADD KEY `destino_id` (`destino_id`),
  ADD KEY `estatus_id` (`estatus_id`),
  ADD KEY `tipo_operacion_id` (`tipo_operacion_id`),
  ADD KEY `puerto_id` (`puerto_id`),
  ADD KEY `transportista_id` (`transportista_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `actualizado_por` (`actualizado_por`);

--
-- Indices de la tabla `navieras`
--
ALTER TABLE `navieras`
  ADD PRIMARY KEY (`id_naviera`);

--
-- Indices de la tabla `operaciones`
--
ALTER TABLE `operaciones`
  ADD PRIMARY KEY (`id_operacion`),
  ADD UNIQUE KEY `numero_operacion` (`numero_operacion`),
  ADD KEY `shipper_id` (`shipper_id`),
  ADD KEY `puerto_arribo_id` (`puerto_arribo_id`),
  ADD KEY `idx_operaciones_numero` (`numero_operacion`),
  ADD KEY `idx_operaciones_estado` (`estado_operacion`);

--
-- Indices de la tabla `operaciones_log`
--
ALTER TABLE `operaciones_log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `operacion_id` (`operacion_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `permisos_operacion`
--
ALTER TABLE `permisos_operacion`
  ADD PRIMARY KEY (`id_permiso`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `tipo_operacion_id` (`tipo_operacion_id`);

--
-- Indices de la tabla `puertos`
--
ALTER TABLE `puertos`
  ADD PRIMARY KEY (`id_puerto`),
  ADD KEY `ciudad_id` (`ciudad_id`);

--
-- Indices de la tabla `puestos`
--
ALTER TABLE `puestos`
  ADD PRIMARY KEY (`id_puesto`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `roles_usuario`
--
ALTER TABLE `roles_usuario`
  ADD PRIMARY KEY (`id_rol_usuario`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `shippers`
--
ALTER TABLE `shippers`
  ADD PRIMARY KEY (`id_shipper`);

--
-- Indices de la tabla `tipos_evento_logistico`
--
ALTER TABLE `tipos_evento_logistico`
  ADD PRIMARY KEY (`id_tipo_evento`);

--
-- Indices de la tabla `tipos_movimiento`
--
ALTER TABLE `tipos_movimiento`
  ADD PRIMARY KEY (`id_tipo_movimiento`);

--
-- Indices de la tabla `tipos_operacion`
--
ALTER TABLE `tipos_operacion`
  ADD PRIMARY KEY (`id_tipo_operacion`);

--
-- Indices de la tabla `transportistas`
--
ALTER TABLE `transportistas`
  ADD PRIMARY KEY (`id_transportista`);

--
-- Indices de la tabla `trazabilidad_contenedor`
--
ALTER TABLE `trazabilidad_contenedor`
  ADD PRIMARY KEY (`id_traza`),
  ADD KEY `contenedor_fisico_id` (`contenedor_fisico_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `puesto_id` (`puesto_id`),
  ADD KEY `departamento_id` (`departamento_id`),
  ADD KEY `idx_usuarios_correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bodegas`
--
ALTER TABLE `bodegas`
  MODIFY `id_bodega` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `brokers`
--
ALTER TABLE `brokers`
  MODIFY `id_broker` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  MODIFY `id_ciudad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedores_fisicos`
--
ALTER TABLE `contenedores_fisicos`
  MODIFY `id_fisico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedores_maritimos`
--
ALTER TABLE `contenedores_maritimos`
  MODIFY `id_contenedor_maritimo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedores_maritimos_operacion`
--
ALTER TABLE `contenedores_maritimos_operacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedores_operacion`
--
ALTER TABLE `contenedores_operacion`
  MODIFY `id_contenedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedor_maritimo_ferro`
--
ALTER TABLE `contenedor_maritimo_ferro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costos_contenedor_operacion`
--
ALTER TABLE `costos_contenedor_operacion`
  MODIFY `id_costo_contenedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costos_logisticos`
--
ALTER TABLE `costos_logisticos`
  MODIFY `id_costo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalles_logisticos`
--
ALTER TABLE `detalles_logisticos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estatus`
--
ALTER TABLE `estatus`
  MODIFY `id_estatus` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_contenedor`
--
ALTER TABLE `movimientos_contenedor`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_logisticos`
--
ALTER TABLE `movimientos_logisticos`
  MODIFY `id_mov_logistico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `navieras`
--
ALTER TABLE `navieras`
  MODIFY `id_naviera` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `operaciones`
--
ALTER TABLE `operaciones`
  MODIFY `id_operacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `operaciones_log`
--
ALTER TABLE `operaciones_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos_operacion`
--
ALTER TABLE `permisos_operacion`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `puertos`
--
ALTER TABLE `puertos`
  MODIFY `id_puerto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles_usuario`
--
ALTER TABLE `roles_usuario`
  MODIFY `id_rol_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `shippers`
--
ALTER TABLE `shippers`
  MODIFY `id_shipper` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_evento_logistico`
--
ALTER TABLE `tipos_evento_logistico`
  MODIFY `id_tipo_evento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_movimiento`
--
ALTER TABLE `tipos_movimiento`
  MODIFY `id_tipo_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipos_operacion`
--
ALTER TABLE `tipos_operacion`
  MODIFY `id_tipo_operacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transportistas`
--
ALTER TABLE `transportistas`
  MODIFY `id_transportista` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trazabilidad_contenedor`
--
ALTER TABLE `trazabilidad_contenedor`
  MODIFY `id_traza` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `bodegas`
--
ALTER TABLE `bodegas`
  ADD CONSTRAINT `bodegas_ibfk_1` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id_ciudad`);

--
-- Filtros para la tabla `ciudades`
--
ALTER TABLE `ciudades`
  ADD CONSTRAINT `ciudades_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id_estado`);

--
-- Filtros para la tabla `contenedores_maritimos_operacion`
--
ALTER TABLE `contenedores_maritimos_operacion`
  ADD CONSTRAINT `contenedores_maritimos_operacion_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`),
  ADD CONSTRAINT `contenedores_maritimos_operacion_ibfk_2` FOREIGN KEY (`contenedor_maritimo_id`) REFERENCES `contenedores_maritimos` (`id_contenedor_maritimo`);

--
-- Filtros para la tabla `contenedores_operacion`
--
ALTER TABLE `contenedores_operacion`
  ADD CONSTRAINT `contenedores_operacion_ibfk_1` FOREIGN KEY (`id_fisico`) REFERENCES `contenedores_fisicos` (`id_fisico`),
  ADD CONSTRAINT `contenedores_operacion_ibfk_2` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`),
  ADD CONSTRAINT `contenedores_operacion_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`);

--
-- Filtros para la tabla `contenedor_maritimo_ferro`
--
ALTER TABLE `contenedor_maritimo_ferro`
  ADD CONSTRAINT `contenedor_maritimo_ferro_ibfk_1` FOREIGN KEY (`contenedor_maritimo_id`) REFERENCES `contenedores_maritimos` (`id_contenedor_maritimo`),
  ADD CONSTRAINT `contenedor_maritimo_ferro_ibfk_2` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`),
  ADD CONSTRAINT `contenedor_maritimo_ferro_ibfk_3` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`);

--
-- Filtros para la tabla `costos_contenedor_operacion`
--
ALTER TABLE `costos_contenedor_operacion`
  ADD CONSTRAINT `costos_contenedor_operacion_ibfk_1` FOREIGN KEY (`contenedor_operacion_id`) REFERENCES `contenedores_operacion` (`id_contenedor`),
  ADD CONSTRAINT `costos_contenedor_operacion_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `costos_logisticos`
--
ALTER TABLE `costos_logisticos`
  ADD CONSTRAINT `costos_logisticos_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`);

--
-- Filtros para la tabla `detalles_logisticos`
--
ALTER TABLE `detalles_logisticos`
  ADD CONSTRAINT `detalles_logisticos_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`),
  ADD CONSTRAINT `detalles_logisticos_ibfk_2` FOREIGN KEY (`bodega_id`) REFERENCES `bodegas` (`id_bodega`),
  ADD CONSTRAINT `detalles_logisticos_ibfk_3` FOREIGN KEY (`broker_id`) REFERENCES `brokers` (`id_broker`);

--
-- Filtros para la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  ADD CONSTRAINT `documentos_operacion_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`),
  ADD CONSTRAINT `documentos_operacion_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  ADD CONSTRAINT `eventos_logisticos_ibfk_1` FOREIGN KEY (`mov_logistico_id`) REFERENCES `movimientos_logisticos` (`id_mov_logistico`),
  ADD CONSTRAINT `eventos_logisticos_ibfk_2` FOREIGN KEY (`tipo_evento_id`) REFERENCES `tipos_evento_logistico` (`id_tipo_evento`),
  ADD CONSTRAINT `eventos_logisticos_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `movimientos_contenedor`
--
ALTER TABLE `movimientos_contenedor`
  ADD CONSTRAINT `movimientos_contenedor_ibfk_1` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`),
  ADD CONSTRAINT `movimientos_contenedor_ibfk_2` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipos_movimiento` (`id_tipo_movimiento`),
  ADD CONSTRAINT `movimientos_contenedor_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `movimientos_logisticos`
--
ALTER TABLE `movimientos_logisticos`
  ADD CONSTRAINT `movimientos_logisticos_ibfk_1` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_2` FOREIGN KEY (`origen_id`) REFERENCES `ciudades` (`id_ciudad`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_3` FOREIGN KEY (`destino_id`) REFERENCES `ciudades` (`id_ciudad`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_4` FOREIGN KEY (`estatus_id`) REFERENCES `estatus` (`id_estatus`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_5` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id_tipo_operacion`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_6` FOREIGN KEY (`puerto_id`) REFERENCES `puertos` (`id_puerto`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_7` FOREIGN KEY (`transportista_id`) REFERENCES `transportistas` (`id_transportista`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_8` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `movimientos_logisticos_ibfk_9` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `operaciones`
--
ALTER TABLE `operaciones`
  ADD CONSTRAINT `operaciones_ibfk_1` FOREIGN KEY (`shipper_id`) REFERENCES `shippers` (`id_shipper`),
  ADD CONSTRAINT `operaciones_ibfk_2` FOREIGN KEY (`puerto_arribo_id`) REFERENCES `puertos` (`id_puerto`);

--
-- Filtros para la tabla `operaciones_log`
--
ALTER TABLE `operaciones_log`
  ADD CONSTRAINT `operaciones_log_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`),
  ADD CONSTRAINT `operaciones_log_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `permisos_operacion`
--
ALTER TABLE `permisos_operacion`
  ADD CONSTRAINT `permisos_operacion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `permisos_operacion_ibfk_2` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id_tipo_operacion`);

--
-- Filtros para la tabla `puertos`
--
ALTER TABLE `puertos`
  ADD CONSTRAINT `puertos_ibfk_1` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id_ciudad`);

--
-- Filtros para la tabla `roles_usuario`
--
ALTER TABLE `roles_usuario`
  ADD CONSTRAINT `roles_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `roles_usuario_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `trazabilidad_contenedor`
--
ALTER TABLE `trazabilidad_contenedor`
  ADD CONSTRAINT `trazabilidad_contenedor_ibfk_1` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`puesto_id`) REFERENCES `puestos` (`id_puesto`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id_departamento`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
