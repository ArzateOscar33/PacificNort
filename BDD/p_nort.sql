-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-09-2025 a las 02:07:21
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
-- Estructura de tabla para la tabla `backup_costos_contenedor_operacion`
--

CREATE TABLE `backup_costos_contenedor_operacion` (
  `id_costo_contenedor` int(11) NOT NULL DEFAULT 0,
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
-- Estructura de tabla para la tabla `backup_tipos_movimiento`
--

CREATE TABLE `backup_tipos_movimiento` (
  `id_tipo_movimiento` int(11) NOT NULL DEFAULT 0,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(15) NOT NULL,
  `moneda` varchar(30) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `backup_tipos_movimiento`
--

INSERT INTO `backup_tipos_movimiento` (`id_tipo_movimiento`, `nombre`, `tipo`, `moneda`, `estatus`) VALUES
(1, 'Carga', 'gasto', 'PESOS', 1),
(2, 'Descarga', 'gasto', 'PESOS', 1),
(6, 'dsa', 'gasto', 'PESOS', 0),
(7, 'fafa', 'gasto', 'PESOS', 0),
(8, 'Brecha Cesarea', 'abono', 'PESOS', 1),
(9, 'Transbordos', 'gasto', 'DLLS', 1);

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
  `ciudad_id` int(11) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bodegas`
--

INSERT INTO `bodegas` (`id_bodega`, `nombre`, `direccion`, `ciudad_id`, `estatus`) VALUES
(1, 'Bodega Izcalli', 'Izcalli 33255', 5, 1),
(2, 'Tijuana', 'Blv Agua Caliente', 1, 1),
(3, 'Bodega de Ensenada', 'Ensenada 332', 7, 0),
(4, 'mexicali', 'mexm', 2, 0),
(5, 'Tijuanal', 'Otay Universidad', 1, 0),
(6, 'Bodega de rosarito', 'Rosarito 225', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brokers`
--

CREATE TABLE `brokers` (
  `id_broker` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `brokers`
--

INSERT INTO `brokers` (`id_broker`, `nombre`, `contacto`, `estatus`) VALUES
(1, 'Oscar', '6644913156', 1),
(2, 'Jose', '12356542444444', 0),
(3, 'Manuel', '13298781', 1),
(4, 'Alberto', '1234567889', 1),
(5, 'Javier', '7894561223', 1),
(6, 'Ruben', '13245678989', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudades`
--

CREATE TABLE `ciudades` (
  `id_ciudad` int(11) NOT NULL,
  `nombre_ciudad` varchar(100) NOT NULL,
  `estado_id` int(11) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciudades`
--

INSERT INTO `ciudades` (`id_ciudad`, `nombre_ciudad`, `estado_id`, `estatus`) VALUES
(1, 'Tijuana', 1, 1),
(2, 'Mexicali', 1, 1),
(3, 'Rosarito', 1, 1),
(4, 'San Bartolo', 3, 1),
(5, 'Cuautitlan De Romero Rubio 2', 3, 1),
(6, 'Long Beach', 5, 1),
(7, 'Ensenada', 1, 1),
(8, 'Cuautitlan de Romero Rubio', 3, 0),
(9, 'Lázaro Cárdenas', 4, 1);

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
  `direccion` text DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `rfc`, `telefono`, `correo`, `direccion`, `estatus`) VALUES
(1, 'Juan Jesus Parra', 'AAMO990217154', '6644913156', 'juanparcam@gmail.com', 'Enrique Segoviano', 1),
(2, 'alex lora', 'FSTF19560709H', '6647898987', 'alex@gmail.com', 'Del Sol 335', 1),
(3, 'Nayo Escobar', 'LKMW202409050', '6648978587', 'nayo@gmail.com', 'Fontana I', 1),
(4, 'Carlos Arzate', 'CA98989898989', '6649135645', '3bmamog@gmail.com', 'dsa', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_fisicos`
--

CREATE TABLE `contenedores_fisicos` (
  `id_fisico` int(11) NOT NULL,
  `numero_ferro` varchar(50) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Contenedores físicos (ferrocarril)';

--
-- Volcado de datos para la tabla `contenedores_fisicos`
--

INSERT INTO `contenedores_fisicos` (`id_fisico`, `numero_ferro`, `estatus`) VALUES
(1, 'FXEU936497', 1),
(2, 'FXEU936498', 1),
(3, 'FXEU936499', 0),
(156, 'FXEU236683', 1),
(157, 'FXEU237648', 1),
(158, 'FXEU236731', 1),
(159, 'FXEU237358', 1),
(160, 'FXEU236714', 1),
(161, 'FXEU237082', 1),
(162, 'FXEU235265', 1),
(163, 'FXEU237363', 1),
(164, 'FXEU237004', 1),
(165, 'FXEU237623', 1),
(166, 'FXEU237423', 1),
(167, 'FXEU234915', 1),
(168, 'FXEU237005', 1),
(169, 'FXEU236748', 1),
(170, 'FXEU235360', 1),
(171, 'FXEU236527', 1),
(172, 'FXEU236545', 1),
(173, 'FXEU235080', 1),
(174, 'FXEU937986', 1),
(175, 'FXEU936048', 1),
(176, 'FXEU939424', 1),
(177, 'FXEU935824', 1),
(178, 'FXEU237494', 1),
(179, 'FXEU237626', 1),
(180, 'FXEU234995', 1),
(181, 'FXEU939397', 1),
(182, 'FXEU237619', 1),
(183, 'FXEU237312', 1),
(184, 'FXEU237244', 1),
(185, 'FXEU237598', 1),
(186, 'FXEU234944', 1),
(187, 'FXEU235307', 1),
(188, 'FXEU237200', 1),
(189, 'FXEU234895', 1),
(190, 'FXEU235004', 1),
(191, 'FXEU237271', 1),
(192, 'FXEU236657', 1),
(193, 'FXEU237784', 1),
(194, 'FXEU236618', 1),
(195, 'FXEU236646', 1),
(196, 'FXEU236752', 1),
(197, 'FXEU236858', 1),
(198, 'FXEU237449', 1),
(199, 'FXEU237359', 1),
(200, 'FXEU235160', 1),
(201, 'FXEU236793', 1),
(202, 'FXEU236537', 1),
(203, 'FXEU236750', 1),
(204, 'FXEU237281', 1),
(205, 'FXEU236665', 1),
(206, 'FXEU235261', 1),
(207, 'FXEU237521', 1),
(208, 'FXEU235405', 1),
(209, 'FXEU235164', 1),
(210, 'FXEU237657', 1),
(211, 'FXEU237403', 1),
(212, 'FXEU237036', 1),
(213, 'FXEU936376', 1),
(214, 'FXEU237766', 1),
(215, 'FXEU232346', 1),
(216, 'FXEU936149', 1),
(217, 'FXEU237681', 1),
(218, 'XFEU669709', 1),
(219, 'FXEU237136', 1),
(220, 'FXEU237664', 1),
(221, 'FXEU939902', 1),
(222, 'FXEU937947', 1),
(223, 'FXEU937827', 1),
(224, 'FXEU237454', 1),
(225, 'FXEU237049', 1),
(226, 'FXEU235194', 1),
(227, 'FXEU236961', 1),
(228, 'FXEU236708', 1),
(229, 'FXEU235288', 1),
(230, 'FXEU237438', 1),
(231, 'FXEU237538', 1),
(232, 'FXEU236511', 1),
(233, 'FXEU237732', 1),
(234, 'FXEU237365', 1),
(235, 'FXEU237524', 1),
(236, 'FXEU236884', 1),
(237, 'FXEU236801', 1),
(238, 'XFEU669864', 1),
(239, 'FXEU237045', 1),
(240, 'XFEU670134', 1),
(241, 'FXEU237112', 1),
(242, 'FXEU237733', 1),
(243, 'FXEU235425', 1),
(244, 'FXEU237601', 1),
(245, 'FXEU237464', 1),
(246, 'FXEU237546', 1),
(247, 'FXEU236592', 1),
(248, 'FXEU234839', 1),
(249, 'FXEU235273', 1),
(250, 'XFEU670006', 1),
(251, 'FXEU236928', 1),
(252, 'FXEU235082', 1),
(253, 'FXEU237022', 1),
(254, 'FXEU236757', 1),
(255, 'FXEU237166', 1),
(256, 'FXEU234899', 1),
(257, 'FXEU235221', 1),
(258, 'FXEU236575', 1),
(259, 'FXEU236785', 1),
(260, 'FXEU237214', 1),
(261, 'FXEU236707', 1),
(262, 'FXEU236558', 1),
(263, 'FXEU935769', 1),
(264, 'FXEU232202', 1),
(265, 'XFEU669794', 1),
(266, 'FXEU936278', 1),
(267, 'FXEU232137', 1),
(268, 'XFEU669562', 1),
(269, 'FXEU236546', 1),
(270, 'FXEU237297', 1),
(271, 'FXEU235351', 1),
(272, 'XFEU670141', 1),
(273, 'FXEU234901', 1),
(274, 'FXEU237222', 1),
(275, 'FXEU236820', 1),
(276, 'XFEU669962', 1),
(277, 'FXEU236956', 1),
(278, 'FXEU232251', 1),
(279, 'FXEU235403', 1),
(280, 'FXEU237258', 1),
(281, 'FXEU937868', 1),
(282, 'FXEU936970', 1),
(283, 'FXEU937466', 1),
(284, 'FXEU939498', 1),
(285, 'FXEU234975', 1),
(286, 'FXEU237078', 1),
(287, 'FXEU236697', 1),
(288, 'FXEU237502', 1),
(289, 'FXEU232355', 1),
(290, 'FXEU235237', 1),
(291, 'FXEU237548', 1),
(292, 'FXEU237629', 1),
(293, 'FXEU236991', 1),
(294, 'FXEU237526', 1),
(295, 'FXEU236809', 1),
(296, 'FXEU237543', 1),
(297, 'FXEU236825', 1),
(298, 'FXEU237771', 1),
(299, 'FXEU237038', 1),
(300, 'FXEU235051', 1),
(301, 'FXEU235250', 1),
(302, 'FXEU234859', 1),
(303, 'FXEU236643', 1),
(304, 'FXEU935966', 1),
(305, 'FXEU936243', 1),
(306, 'FXEU937410', 1),
(307, 'FXEU937403', 1),
(308, 'FXEU937742', 1),
(309, 'FXEU939041', 1),
(310, 'XFEU669821', 1),
(311, 'FXEU234957', 1),
(312, 'FXEU237305', 1),
(313, 'FXEU237252', 1),
(314, 'XFEU670043', 1),
(315, 'FXEU936940', 1),
(316, 'FXEU939866', 1),
(317, 'FXEU936297', 1),
(318, 'FXEU232245', 1),
(319, 'FXEU237665', 1),
(320, 'FXEU232454', 1),
(321, 'FXEU235203', 1),
(322, 'XFEU669569', 1),
(323, 'FXEU234848', 1),
(324, 'FXEU232183', 1),
(325, 'FXEU236958', 1),
(326, 'FXEU936987', 1),
(327, 'FXEU235406', 1),
(328, 'FXEU237402', 1),
(329, 'FXEU232203', 1),
(330, 'FXEU236525', 1),
(331, 'XFEU669980', 1),
(332, 'FXEU232070', 1),
(333, 'FXEU236584', 1),
(334, 'XFEU669944', 1),
(335, 'FXEU237278', 1),
(336, 'FXEU235176', 1),
(337, 'FXEU235045', 1),
(338, 'FXEU935725', 1),
(339, 'FXEU232335', 1),
(340, 'FXEU937354', 1),
(341, 'FXEU939240', 1),
(342, 'FXEU936722', 1),
(343, 'FXEU935168', 1),
(344, 'FXEU234894', 1),
(345, 'FXEU234902', 1),
(346, 'FXEU236517', 1),
(347, 'FXEU237401', 1),
(348, 'FXEU939657', 1),
(349, 'FXEU936704', 1),
(350, 'FXEU236867', 1),
(351, 'FXEU939049', 1),
(352, 'FXEU237193', 1),
(353, 'FXEU232212', 1),
(354, 'FXEU234827', 1),
(355, 'FXEU237415', 1),
(356, 'FXEU235196', 1),
(357, 'XFEU669893', 1),
(358, 'XFEU669695', 1),
(359, 'FXEU237654', 1),
(360, 'FXEU236850', 1),
(361, 'FXEU237154', 1),
(362, 'FXEU237472', 1),
(363, 'XFEU669521', 1),
(364, 'FXEU236711', 1),
(365, 'FXEU232058', 1),
(366, 'FXEU937190', 1),
(367, 'FXEU935218', 1),
(368, 'FXEU236840', 1),
(369, 'FXEU237380', 1),
(370, 'XFEU670074', 1),
(371, 'FXEU235131', 1),
(372, 'FXEU236916', 1),
(373, 'FXEU236930', 1),
(374, 'FXEU237722', 1),
(375, 'FXEU235220', 1),
(376, 'FXEU234978', 1),
(377, 'FXEU237588', 1),
(378, 'FXEU236927', 1),
(379, 'FXEU236595', 1),
(380, 'XFEU669806', 1),
(381, 'FXEU237511', 1),
(382, 'FXEU939180', 1),
(383, 'XFEU670119', 1),
(384, 'FXEU939519', 1),
(385, 'FXEU237793', 1),
(386, 'FXEU236783', 1),
(387, 'FXEU236925', 1),
(388, 'FXEU236760', 1),
(389, 'FXEU236583', 1),
(390, 'FXEU237668', 1),
(391, 'FXEU235026', 1),
(392, 'FXEU234858', 1),
(393, 'FXEU237706', 1),
(394, 'FXEU939014', 1),
(395, 'FXEU936172', 1),
(396, 'FXEU939887', 1),
(397, 'FXEU232061', 1),
(398, 'FXEU936558', 1),
(399, 'FXEU235121', 1),
(400, 'FXEU935283', 1),
(401, 'XFEU669798', 1),
(402, 'FXEU232186', 1),
(403, 'XFEU669693', 1),
(404, 'FXEU236932', 1),
(405, 'XFEU670061', 1),
(406, 'FXEU237143', 1),
(407, 'FXEU237422', 1),
(408, 'FXEU237223', 1),
(409, 'FXEU237460', 1),
(410, 'FXEU235104', 1),
(411, 'FXEU237188', 1),
(412, 'FXEU236885', 1),
(413, 'FXEU234803', 1),
(414, 'XFEU669793', 1),
(415, 'FXEU237529', 1),
(416, 'FXEU236521', 1),
(417, 'FXEU235347', 1),
(418, 'FXEU232133', 1),
(419, 'FXEU236965', 1),
(420, 'FXEU237718', 1),
(421, 'FXEU235122', 1),
(422, 'FXEU235191', 1),
(423, 'XFEU669891', 1),
(424, 'FXEU232430', 1),
(425, 'FXEU237164', 1),
(426, 'FXEU232327', 1),
(427, 'FXEU236994', 1),
(428, 'XFEU670114', 1),
(429, 'FXEU237704', 1),
(430, 'FXEU237581', 1),
(431, 'FXEU237282', 1),
(432, 'FXEU937659', 1),
(433, 'FXEU937934', 1),
(434, 'FXEU937635', 1),
(435, 'FXEU237340', 1),
(436, 'FXEU237539', 1),
(437, 'FXEU237578', 1),
(438, 'XFEU669981', 1),
(439, 'FXEU232218', 1),
(440, 'FXEU935700', 1),
(441, 'FXEU935623', 1),
(442, 'FXEU235090', 1),
(443, 'FXEU237673', 1),
(444, 'FXEU237583', 1),
(445, 'FXEU937922', 1),
(446, 'FXEU935409', 1),
(447, 'FXEU232159', 1),
(448, 'FXEU237450', 1),
(449, 'FXEU232155', 1),
(450, 'FXEU234831', 1),
(451, 'FXEU235069', 1),
(452, 'FXEU232214', 1),
(453, 'FXEU235211', 1),
(454, 'FXEU935583', 1),
(455, 'FXEU935753', 1),
(456, 'FXEU937062', 1),
(457, 'FXEU237077', 1),
(458, 'FXEU237161', 1),
(459, 'FXEU232164', 1),
(460, 'FXEU939023', 1),
(461, 'FXEU937377', 1),
(462, 'FXEU936335', 1),
(463, 'FXEU939215', 1),
(464, 'FXEU937217', 1),
(465, 'FXEU237083', 1),
(466, 'FXEU23201998', 1),
(467, 'FXEU939303', 1),
(468, 'FXEU936711', 1),
(469, 'XFEU669992', 1),
(470, 'FXEU237015', 1),
(471, 'FXEU939348', 1),
(472, 'FXEU232782', 1),
(473, 'FXEU232592', 1),
(474, 'FXEU936386', 1),
(475, 'FXEU936983', 1),
(476, 'FXEU935757', 1),
(477, 'FXEU935252', 1),
(478, 'FXEU935305', 1),
(479, 'FXEU935024', 1),
(480, 'FXEU936457', 1),
(481, 'FXEU937325', 1),
(482, 'FXEU936603', 1),
(483, 'FXEU936738', 1),
(484, 'FXEU936001', 1),
(485, 'FXEU937935', 1),
(486, 'FXEU935782', 1),
(487, 'FXEU939715', 1),
(488, 'FXEU937413', 1),
(489, 'FXEU237266', 1),
(490, 'FXEU237489', 1),
(491, 'FXEU237432', 1),
(492, 'FXEU237697', 1),
(493, 'FXEU237435', 1),
(494, 'FXEU235190', 1),
(495, 'XFEU669796', 1),
(496, 'FXEU237398', 1),
(497, 'FXEU235408', 1),
(498, 'FXEU232287', 1),
(499, 'FXEU235388', 1),
(500, 'XFEU670084', 1),
(501, 'FXEU232254', 1),
(502, 'FXEU237290', 1),
(503, 'FXEU237151', 1),
(504, 'FXEU935735', 1),
(505, 'FXEU237311', 1),
(506, 'FXEU235165', 1),
(507, 'XFEU669969', 1),
(508, 'FXEU237635', 1),
(509, 'FXEU235186', 1),
(510, 'FXEU237642', 1),
(511, 'FXEU236944', 1),
(512, 'FXEU236839', 1),
(513, 'FXEU237621', 1),
(514, 'FXEU237520', 1),
(515, 'FXEU236826', 1),
(516, 'FXEU232067', 1),
(517, 'FXEU237613', 1),
(518, 'XFEU669528', 1),
(519, 'FXEU237267', 1),
(520, 'FXEU936061', 1),
(521, 'FXEU939341', 1),
(522, 'FXEU232797', 1),
(523, 'FXEU232770', 1),
(524, 'FXEU232465', 1),
(525, 'FXEU232631', 1),
(526, 'FXEU234876', 1),
(527, 'FXEU236676', 1),
(528, 'FXEU236562', 1),
(529, 'FXEU232211', 1),
(530, 'FXEU669630', 1),
(531, 'FXEU232046', 1),
(532, 'FXEU234884', 1),
(533, 'FXEU236543', 1),
(534, 'FXEU235086', 1),
(535, 'FXEU237768', 1),
(536, 'FXEU232025', 1),
(537, 'FXEU236967', 1),
(538, 'FXEU237051', 1),
(539, 'FXEU232238', 1),
(540, 'XFEU670079', 1),
(541, 'FXEU237187', 1),
(542, 'FXEU935073', 1),
(543, 'FXEU939760', 1),
(544, 'FXEU935476', 1),
(545, 'FXEU936733', 1),
(546, 'XFEU669984', 1),
(547, 'XFEU669836', 1),
(548, 'FXEU237655', 1),
(549, 'FXEU939333', 1),
(550, 'FXEU935399', 1),
(551, 'FXEU939622', 1),
(552, 'FXEU233103', 1),
(553, 'FXEU935685', 1),
(554, 'FXEU237300', 1),
(555, 'FXEU939817', 1),
(556, 'XFEU669850', 1),
(557, 'FXEU237089', 1),
(558, 'FXEU937462', 1),
(559, 'FXEU232653', 1),
(560, 'FXEU232668', 1),
(561, 'FXEU232672', 1),
(562, 'FXEU232633', 1),
(563, 'FXEU236758', 1),
(564, 'FXEU234866', 1),
(565, 'FXEU236888', 1),
(566, 'FXEU235235', 1),
(567, 'FXEU937676', 1),
(568, '5317', 1),
(569, 'FXEU236829', 1),
(570, 'FXEU939213', 1),
(571, 'XFEU669735', 1),
(572, 'FXEU234842', 1),
(573, 'FXEU937483', 1),
(574, 'WHSU2107490', 1),
(575, 'FXEU232020', 1),
(576, 'FXEU232078', 1),
(577, 'FX456456', 1),
(578, 'FX8585464', 1),
(579, 'FX4564569889', 1),
(580, 'FX987741', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_maritimos`
--

CREATE TABLE `contenedores_maritimos` (
  `id_contenedor_maritimo` int(11) NOT NULL,
  `numero_contenedor` varchar(100) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT 'Sin Observaciones',
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Contenedores marítimos';

--
-- Volcado de datos para la tabla `contenedores_maritimos`
--

INSERT INTO `contenedores_maritimos` (`id_contenedor_maritimo`, `numero_contenedor`, `tipo`, `observaciones`, `estatus`) VALUES
(2, 'EMCU1644999', '40HQ', 'Sin observaciones', 1),
(3, 'EMCU1644998', '40HQ', 'Sin Observaciones', 1),
(4, 'EMCU1644991', '40HQ', 'Sin Observaciones', 1),
(5, 'BSIU9973901', NULL, NULL, 1),
(6, 'HPCU4819830', NULL, NULL, 1),
(7, 'WHLU5672061', NULL, NULL, 1),
(8, 'WHSU6211516', NULL, NULL, 1),
(9, 'TEMU6000240', NULL, NULL, 1),
(10, 'KOCU4081780', NULL, NULL, 1),
(11, 'CSNU6714936', NULL, NULL, 1),
(12, 'TGBU4765668', NULL, NULL, 1),
(13, 'OOCU0225055', NULL, NULL, 1),
(14, 'CSNU6261589', NULL, NULL, 1),
(15, 'WHSU5979388', NULL, NULL, 1),
(16, 'WHSU2107490', NULL, NULL, 1),
(17, 'TGBU9217667', NULL, NULL, 1),
(18, 'TXGU8189938', NULL, NULL, 1),
(19, 'TGBU9964831', NULL, NULL, 1),
(20, 'SELU4279379', NULL, NULL, 1),
(21, 'FCIU7159838', NULL, NULL, 1),
(22, 'BEAU5391862', NULL, NULL, 1),
(23, 'WHSU5726841', NULL, NULL, 1),
(24, 'TGBU4635216', NULL, NULL, 1),
(25, 'SEGU4912870', NULL, NULL, 1),
(26, 'FSCU8493216', NULL, NULL, 1),
(27, 'SEGU3162344', NULL, NULL, 1),
(28, 'TRHU5977602', NULL, NULL, 1),
(29, 'DRYU9247714', NULL, NULL, 1),
(30, 'DFSU6859580', NULL, NULL, 1),
(31, 'TGBU8686000', NULL, NULL, 1),
(32, 'CSNU6994225', NULL, NULL, 1),
(33, 'TGCU5449164', NULL, NULL, 1),
(34, 'WHLU5743421', NULL, NULL, 1),
(35, 'WHSU5060858', NULL, NULL, 1),
(36, 'TSSU5147473', NULL, NULL, 1),
(37, 'CSNU8019722', NULL, NULL, 1),
(38, 'OOCU7428970', NULL, NULL, 1),
(39, 'SMCU1096754', NULL, NULL, 1),
(40, 'SMCU1019125', NULL, NULL, 1),
(41, 'SMCU1013847', NULL, NULL, 1),
(42, 'SMCU1094237', NULL, NULL, 1),
(43, 'TRLU6716089', NULL, NULL, 1),
(44, 'SMCU1090859', NULL, NULL, 1),
(45, 'ZCSU6913943', NULL, NULL, 1),
(46, 'ZCSU6706099', NULL, NULL, 1),
(47, 'BEAU4701639', '40HQ', 'sa', 1),
(48, 'EMPA1644999', NULL, 'Sin Observaciones', 1),
(49, 'EMCU1644978', NULL, 'Sin Observaciones', 1),
(50, 'MGU8890991', '40HQ', 'Sin Observaciones', 1),
(51, 'EMCU161999', NULL, 'Sin Observaciones', 1),
(52, 'MG0000001', NULL, 'Sin Observaciones', 1),
(53, 'NG789789', NULL, 'Sin Observaciones', 1),
(54, 'MG878787', NULL, 'Sin Observaciones', 1),
(55, 'MG00000017', NULL, 'Sin Observaciones', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_maritimos_operacion`
--

CREATE TABLE `contenedores_maritimos_operacion` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `contenedor_maritimo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenedores_maritimos_operacion`
--

INSERT INTO `contenedores_maritimos_operacion` (`id`, `operacion_id`, `contenedor_maritimo_id`) VALUES
(1, 3, 2),
(2, 2, 5),
(3, 1, 14),
(6, 7, 2),
(7, 8, 48),
(8, 9, 49),
(9, 10, 50),
(10, 16, 51),
(11, 25, 51),
(12, 32, 51),
(13, 44, 51),
(14, 45, 51),
(15, 47, 48),
(16, 48, 52),
(17, 49, 53),
(18, 50, 54),
(19, 57, 52),
(20, 58, 54),
(21, 59, 52),
(22, 55, 47),
(23, 60, 54),
(24, 61, 55),
(25, 62, 55),
(26, 63, 52);

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
  `bultos` int(11) DEFAULT NULL,
  `estatus` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenedores_operacion`
--

INSERT INTO `contenedores_operacion` (`id_contenedor`, `id_fisico`, `operacion_id`, `cliente_id`, `comentarios`, `peso`, `bultos`, `estatus`) VALUES
(1, 575, 7, 1, 'sin comentarios', NULL, 155, 1),
(2, 466, 7, 1, '', NULL, 1689, 1),
(3, 576, 8, 1, 'comen', NULL, 155, 1),
(4, 466, 2, 2, NULL, NULL, 1689, 1),
(5, 576, 2, 2, NULL, NULL, 155, 1),
(6, 576, 8, 1, NULL, NULL, 155, 1),
(7, 576, 2, 2, NULL, NULL, 155, 1),
(8, 466, 47, 1, '', NULL, 1558, 1),
(9, 575, 44, 3, NULL, NULL, 155, 1),
(10, 516, 48, 1, '', NULL, 155, 1),
(11, 531, 48, 1, NULL, NULL, 95, 1),
(12, 565, 48, 1, NULL, NULL, 100, 1),
(13, 564, 48, 1, NULL, NULL, 56, 1),
(14, 562, 48, 1, NULL, NULL, 88, 1),
(15, 466, 49, 4, NULL, NULL, 12, 1),
(16, 466, 48, 1, '', NULL, 122, 1),
(17, 466, 55, 3, NULL, NULL, 30, 1),
(18, 575, 47, 1, NULL, NULL, 0, 1),
(19, 577, 61, 1, NULL, NULL, 66, 1),
(20, 578, 62, 3, NULL, NULL, 98, 1),
(21, 579, 48, 1, NULL, NULL, 36, 1),
(22, 580, 55, 3, NULL, NULL, 78, 1),
(23, 577, 59, 3, NULL, NULL, 66, 1);

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
  `tipo_movimiento_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `costos_contenedor_operacion`
--

INSERT INTO `costos_contenedor_operacion` (`id_costo_contenedor`, `contenedor_operacion_id`, `tipo_movimiento_id`, `monto`, `comentario`, `fecha_creacion`, `creado_por`) VALUES
(1, 1, 1, 150.00, 'sin asumakina', '2025-08-21 11:13:30', 1),
(2, 2, 15, 125.00, NULL, '2025-08-21 15:53:51', NULL),
(3, 2, 2, 122.00, NULL, '2025-08-21 15:54:15', NULL),
(5, 2, 1, 100.00, NULL, '2025-08-21 15:54:58', NULL),
(6, 8, 16, 100.00, NULL, '2025-08-21 15:57:51', NULL),
(7, 8, 15, 88.00, NULL, '2025-08-21 16:28:13', NULL),
(8, 8, 15, 44.00, NULL, '2025-08-21 16:28:34', NULL),
(9, 9, 16, 789.00, 'SS', '2025-08-22 08:13:44', NULL),
(10, 9, 13, 778.00, NULL, '2025-08-22 09:10:34', NULL),
(11, 9, 16, 1223.00, NULL, '2025-08-22 09:15:35', NULL),
(13, 10, 1, 200.00, NULL, '2025-08-22 15:02:33', NULL),
(15, 10, 15, 80.00, NULL, '2025-08-25 08:49:50', NULL),
(16, 12, 1, 250.00, NULL, '2025-08-25 11:15:33', NULL),
(17, 17, 1, 22.00, NULL, '2025-09-02 11:18:38', NULL),
(18, 17, 2, 213.00, NULL, '2025-09-02 15:53:11', NULL),
(19, 15, 1, 123.00, 'sa', '2025-09-02 16:52:13', NULL),
(20, 19, 15, 456.00, NULL, '2025-09-03 09:53:39', NULL),
(21, 20, 1, 789.00, 'LL', '2025-09-03 09:55:27', NULL),
(22, 21, 1, 855.00, NULL, '2025-09-05 15:20:32', NULL),
(23, 22, 14, 213.00, NULL, '2025-09-05 16:32:23', NULL),
(24, 22, 15, 444.00, NULL, '2025-09-05 16:32:49', NULL),
(25, 22, 16, 333.00, NULL, '2025-09-05 16:33:10', NULL),
(26, 22, 14, 78.00, NULL, '2025-09-05 16:42:56', NULL),
(27, 22, 2, 423.00, NULL, '2025-09-05 16:43:09', NULL),
(28, 22, 13, 78.00, NULL, '2025-09-05 16:57:06', NULL),
(29, 22, 16, 58.00, NULL, '2025-09-08 15:32:33', NULL),
(30, 17, 13, 123.00, 'dsa', '2025-09-09 14:10:34', NULL);

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
-- Estructura de tabla para la tabla `costos_operacion`
--

CREATE TABLE `costos_operacion` (
  `id_costo_operacion` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL,
  `tipo_movimiento_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `costos_operacion`
--

INSERT INTO `costos_operacion` (`id_costo_operacion`, `operacion_id`, `tipo_movimiento_id`, `monto`, `comentario`, `fecha_creacion`, `estatus`) VALUES
(1, 47, 17, 150.00, 'sin comentarios', '2025-08-22 10:47:33', 1),
(2, 48, 10, 250.00, '', '2025-08-22 15:31:21', 0),
(3, 48, 10, 12.00, '', '2025-08-22 15:45:03', 0),
(4, 48, 11, 250.00, '', '2025-08-22 15:45:20', 0),
(5, 48, 17, 25.00, '', '2025-08-22 15:47:09', 0),
(6, 48, 17, 25.00, '', '2025-08-22 15:52:05', 0),
(7, 48, 9, 12.00, '', '2025-08-22 16:14:27', 0),
(8, 48, 17, 1.00, '', '2025-08-22 16:19:06', 0),
(9, 48, 8, 1.00, '', '2025-08-22 16:19:19', 1),
(10, 48, 17, 1.00, '', '2025-08-22 16:19:24', 1),
(11, 48, 10, 25.00, '', '2025-08-22 16:19:30', 1),
(12, 48, 8, 47.00, '', '2025-08-22 16:31:04', 1),
(13, 48, 12, 85.40, '', '2025-08-22 16:31:32', 1),
(14, 48, 17, 125.00, 'cargo fecha 24/08/25', '2025-08-25 08:48:09', 1),
(15, 48, 11, 15.00, '', '2025-08-25 08:50:37', 1),
(16, 48, 10, 555.00, '', '2025-08-25 08:50:43', 1),
(17, 48, 10, 522.00, '', '2025-08-25 08:50:51', 1),
(18, 48, 10, 12.00, '', '2025-08-25 08:50:58', 0),
(19, 48, 10, 122.00, '', '2025-09-02 16:53:25', 1),
(20, 61, 17, 0.02, '', '2025-09-03 09:53:50', 1),
(21, 62, 10, 85.00, '', '2025-09-03 09:55:37', 1),
(22, 48, 10, 36.00, '', '2025-09-03 13:56:02', 1),
(23, 49, 10, 15.00, '', '2025-09-08 09:23:46', 1),
(24, 55, 17, 15.00, '', '2025-09-08 09:23:58', 1),
(25, 55, 8, 333.00, '', '2025-09-08 09:53:28', 1),
(26, 55, 11, 322.00, '', '2025-09-08 09:53:33', 1),
(27, 55, 9, 235.00, '', '2025-09-08 09:53:39', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `Estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id_departamento`, `nombre`, `Estatus`) VALUES
(1, 'Logística', 1),
(2, 'Clientes', 1),
(4, 'Recursos Humanos', 0),
(9, 'Sistemas', 1),
(10, 'Contabilidad', 1),
(12, 'Limpieza', 0),
(14, 'Ventas', 1),
(26, 'Seguridad', 0),
(29, 'Vetnas', 0);

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
  `comentarios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_operacion`
--

CREATE TABLE `documentos_operacion` (
  `id_documento` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `tipo_documento_id` int(11) DEFAULT NULL,
  `contenedor_operacion_id` int(11) DEFAULT NULL,
  `cont_maritimo_operacion_id` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamano_bytes` bigint(20) DEFAULT NULL,
  `hash_sha256` char(64) DEFAULT NULL,
  `ruta_archivo` text NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp(),
  `subido_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos_operacion`
--

INSERT INTO `documentos_operacion` (`id_documento`, `operacion_id`, `tipo_documento_id`, `contenedor_operacion_id`, `cont_maritimo_operacion_id`, `nombre_archivo`, `mime_type`, `tamano_bytes`, `hash_sha256`, `ruta_archivo`, `fecha_subida`, `subido_por`) VALUES
(34, 49, 1, 15, NULL, 'Informe_Graficos_2025-09_2025-09-09.pdf', 'application/pdf', 18634728, '68c2021b954dfc9ba85fd9ded85b18b178ee1be0362355ae0c4e2485e55d1245', 'LI-02_Documentos/FXEU23201998/cc0a9d70bd4d4e6e_Informe_Graficos_2025-09_2025-09-09.pdf', '2025-09-10 16:25:10', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE `estados` (
  `id_estado` int(11) NOT NULL,
  `nombre_estado` varchar(100) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id_estado`, `nombre_estado`, `estatus`) VALUES
(1, 'Baja California', 1),
(2, 'Baja California Sur', 1),
(3, 'Estado de Mexico', 1),
(4, 'Michoacan', 1),
(5, 'California', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estatus`
--

CREATE TABLE `estatus` (
  `id_estatus` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estatus`
--

INSERT INTO `estatus` (`id_estatus`, `nombre`, `estatus`) VALUES
(1, 'Pendiente', 1),
(5, 'En Revisión', 1),
(6, 'Cancelado', 1),
(7, 'Entregado', 1),
(9, 'Abierta', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_logisticos`
--

CREATE TABLE `eventos_logisticos` (
  `id_evento` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL,
  `contenedor_operacion_id` int(11) DEFAULT NULL,
  `cont_maritimo_operacion_id` int(11) DEFAULT NULL,
  `tipo_evento_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `comentario` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos_logisticos`
--

INSERT INTO `eventos_logisticos` (`id_evento`, `operacion_id`, `contenedor_operacion_id`, `cont_maritimo_operacion_id`, `tipo_evento_id`, `fecha`, `comentario`, `creado_por`, `fecha_creacion`, `estatus`) VALUES
(3, 48, 11, NULL, 6, '2024-07-31', 'dsa', 1, '2025-08-29 13:42:56', 1),
(6, 48, 10, NULL, 3, '2025-08-13', '', 1, '2025-08-29 13:54:12', 1),
(15, 48, 11, NULL, 9, '2025-09-24', '', 1, '2025-09-01 10:18:06', 0),
(16, 48, 11, NULL, 9, '2025-09-10', '', 1, '2025-09-01 10:20:27', 0),
(17, 48, 10, NULL, 1, '2025-09-04', 'dffda', 1, '2025-09-01 10:36:18', 1),
(18, 48, 14, NULL, 3, '2025-09-09', '', 1, '2025-09-01 10:41:52', 1),
(19, 48, 13, NULL, 4, '2025-09-11', '', 1, '2025-09-01 11:11:50', 1),
(20, 49, 15, NULL, 7, '2025-09-08', '', 1, '2025-09-01 11:21:53', 1),
(21, 49, 15, NULL, 6, '2025-09-17', '', 1, '2025-09-01 11:22:03', 1),
(22, 49, 15, NULL, 4, '2025-10-01', '', 1, '2025-09-01 11:22:50', 1),
(23, 1, 3, NULL, 8, '2025-09-10', '', 1, '2025-09-01 11:23:18', 0),
(24, 2, 4, NULL, 9, '2025-09-10', '', 1, '2025-09-01 11:23:36', 0),
(25, 1, 3, NULL, 9, '2025-09-11', '', 1, '2025-09-01 11:24:37', 0),
(26, 1, 3, NULL, 10, '2025-09-10', '', 1, '2025-09-01 11:39:49', 0),
(27, 3, 1, NULL, 8, '2025-09-11', '', 1, '2025-09-01 11:42:10', 0),
(28, 48, NULL, 16, 8, '2025-09-08', '', 1, '2025-09-01 11:47:54', 1),
(29, 48, NULL, 16, 9, '2025-09-08', 'lll', 1, '2025-09-01 11:50:28', 0),
(30, 48, NULL, 16, 10, '2025-09-08', '', 1, '2025-09-01 11:50:39', 1),
(31, 48, 13, NULL, 7, '2025-09-11', '', 1, '2025-09-01 12:00:45', 1),
(32, 1, NULL, 3, 9, '2025-09-30', '', 1, '2025-09-01 12:07:17', 0),
(33, 48, NULL, 16, 9, '2025-09-03', '', 1, '2025-09-01 12:09:14', 1),
(34, 48, 16, NULL, 8, '2025-09-03', '', 1, '2025-09-02 12:08:57', 0),
(35, 48, 13, NULL, 3, '2025-09-17', 'uiodoiusa', 1, '2025-09-02 16:56:36', 1),
(36, 61, 19, NULL, 4, '2025-09-10', '', 1, '2025-09-03 09:54:16', 1),
(37, 62, 20, NULL, 4, '2025-09-04', '', 1, '2025-09-03 09:56:01', 1),
(38, 49, NULL, 17, 8, '2025-09-17', '', 1, '2025-09-04 10:01:10', 1),
(39, 58, NULL, 20, 8, '2025-09-04', '', 1, '2025-09-04 10:02:08', 1),
(40, 59, NULL, 21, 8, '2025-09-08', '', 1, '2025-09-04 10:02:48', 1),
(41, 55, NULL, 22, 8, '2025-09-09', 'Arribo a San Diego', 1, '2025-09-08 15:57:43', 1),
(42, 55, NULL, 22, 9, '2025-09-09', 'Cargado para puerto', 1, '2025-09-08 15:58:03', 1),
(43, 55, NULL, 22, 10, '2025-09-25', 'Contenedor entregado', 1, '2025-09-08 15:58:21', 1),
(44, 48, 21, NULL, 5, '2025-09-04', '', 1, '2025-09-09 11:45:15', 1),
(45, 48, 21, NULL, 4, '2025-09-18', '', 1, '2025-09-09 11:45:25', 1),
(46, 48, NULL, 16, 11, '2025-09-17', '', 1, '2025-09-09 14:12:44', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `forwarders`
--

CREATE TABLE `forwarders` (
  `id_forwarder` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `contacto` varchar(120) DEFAULT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `forwarders`
--

INSERT INTO `forwarders` (`id_forwarder`, `nombre`, `contacto`, `estatus`) VALUES
(1, 'CM Logistics', 'Josea', 1),
(2, 'Sin Forwarder', 'Sin Forwarder', 1);

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
  `contacto` varchar(100) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `navieras`
--

INSERT INTO `navieras` (`id_naviera`, `nombre`, `contacto`, `estatus`) VALUES
(1, 'Evergreen', '66449136156', 1),
(2, 'CGEA', '6645941', 1),
(3, 'CMA', '6644913156', 0),
(4, 'Sin Naviera', 'Sin Naviera', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operaciones`
--

CREATE TABLE `operaciones` (
  `id_operacion` int(11) NOT NULL,
  `numero_operacion` varchar(50) NOT NULL,
  `tipo_operacion_id` int(11) DEFAULT NULL,
  `subtipo_operacion_id` int(11) DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `numero_bl` varchar(50) DEFAULT NULL,
  `isf` tinyint(1) DEFAULT 0,
  `shipper_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `estatus_id` int(11) NOT NULL DEFAULT 9,
  `naviera_id` int(11) DEFAULT NULL,
  `forwarder_id` int(11) DEFAULT NULL,
  `notas` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla principal de operaciones logísticas';

--
-- Volcado de datos para la tabla `operaciones`
--

INSERT INTO `operaciones` (`id_operacion`, `numero_operacion`, `tipo_operacion_id`, `subtipo_operacion_id`, `etd`, `eta`, `numero_bl`, `isf`, `shipper_id`, `cliente_id`, `estatus_id`, `naviera_id`, `forwarder_id`, `notas`) VALUES
(1, 'JL-013', 1, 1, '2025-08-01', '2025-08-07', 'TWN000001', 1, 1, 1, 9, 4, 2, NULL),
(2, 'JL-012', 1, 1, '2025-08-01', '2025-08-07', 'TWN000002', 0, 1, 2, 9, 4, 2, NULL),
(3, 'JL-011', 1, 1, '2025-08-06', '2025-08-22', '132456', 0, 2, 1, 1, 2, 1, NULL),
(7, 'JL-010', 1, 1, '2025-08-12', '2025-08-20', '132456789', 0, 2, 1, 9, 2, 1, 'SIN NOTAS'),
(8, 'JL-009', 1, 1, '2025-08-05', '2025-08-19', '1327894564', 0, 2, 1, 9, 2, 1, NULL),
(9, 'JL-008', 1, 1, '2025-08-12', '2025-08-04', '789321564', 0, 2, 3, 5, 2, 2, NULL),
(10, 'JL-002', 1, 2, '2025-08-05', '2025-08-19', '741258963', 0, 2, 1, 5, 4, 2, 'Sin Observaciones'),
(16, 'JL-007', 1, 1, '2025-08-12', '2025-08-20', '4567893332', 0, 1, 1, 9, 4, 1, 'sin observaciones'),
(25, 'JL-006', 1, 1, '2025-08-12', '2025-08-21', '741269853', 0, 2, 1, 9, 1, 1, 'sin notas'),
(32, 'JL-005', 1, 1, '2025-08-04', '2025-08-13', '852369741', 0, 2, 1, 9, 2, 1, 'sin notas'),
(44, 'JL-001', 1, 2, '2025-08-05', '2025-08-20', '8579456131', 0, 1, 3, 9, 2, 2, 'SIN NOTAS'),
(45, 'JL-004', 1, 1, '2025-08-20', '2025-08-25', '859674123', 0, 1, 1, 9, 1, 1, NULL),
(47, 'JL-003', 1, 1, '2025-08-13', '2025-08-21', '741563289', 0, 1, 1, 9, 1, 1, NULL),
(48, 'LI-01', 1, 1, '2025-08-01', '2025-08-21', '852647913', 0, 1, 1, 9, 4, 1, 'Faltan documentos'),
(49, 'LI-02', 1, 1, '2025-07-29', '2025-08-19', '8598745632', 0, 2, 4, 9, 2, 1, NULL),
(50, 'LC-014', 1, 1, '2025-09-13', '2025-09-13', '789451236', 0, 1, 1, 6, 2, 1, NULL),
(55, 'EN-01', 1, 21, '2025-09-06', '2025-09-24', '321321', 0, 1, 3, 9, 2, 1, 'sadsa'),
(57, 'EN-02', 1, 21, '2025-09-10', '2025-10-05', '1232141', 0, 2, 3, 9, 4, 2, NULL),
(58, 'EN-03', 1, 21, '2025-09-18', '2025-09-25', '87523145222', 0, 2, 3, 9, 2, 1, NULL),
(59, 'EN-04', 1, 21, '2025-09-12', '2025-10-08', '741233644', 0, 1, 3, 9, 2, 1, NULL),
(60, 'EN-05', 1, 21, '2025-09-04', '2025-09-11', '74123', 0, 2, 3, 9, 2, 1, ''),
(61, 'LC-15', 1, 1, '2025-09-18', '2025-09-23', '789451', 0, 2, 1, 5, 2, 2, NULL),
(62, 'EN-06', 1, 21, '2025-09-09', '2025-09-05', '746985213', 0, 2, 3, 5, 1, 1, 'asumakina'),
(63, 'TJ-01', 1, 20, '2025-09-14', '2025-09-19', '8579413658', 0, 2, 3, 5, 1, 1, 'dsa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operaciones_log`
--

CREATE TABLE `operaciones_log` (
  `id_log` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` enum('creacion','actualizacion','cancelacion','cerrado','costo_creado','costo_editado','costo_eliminado','contenedor_creado','contenedor_editado','contenedor_eliminado') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `operaciones_log`
--

INSERT INTO `operaciones_log` (`id_log`, `operacion_id`, `usuario_id`, `accion`, `descripcion`, `fecha`) VALUES
(1, 7, 1, 'creacion', 'Operación creada', '2025-08-19 11:24:47'),
(2, 8, 1, 'creacion', 'Operación creada', '2025-08-19 14:35:28'),
(3, 9, 1, 'creacion', 'Operación creada', '2025-08-19 14:46:06'),
(4, 10, 1, 'creacion', 'Operación creada', '2025-08-19 15:52:50'),
(5, 16, 1, 'creacion', 'Operación creada', '2025-08-20 15:25:01'),
(6, 25, 1, 'creacion', 'Operación creada', '2025-08-20 15:33:02'),
(7, 32, 1, 'creacion', 'Operación creada', '2025-08-20 15:56:58'),
(8, 44, 1, 'creacion', 'Operación creada', '2025-08-20 16:14:25'),
(9, 45, 1, 'creacion', 'Operación creada', '2025-08-21 09:02:21'),
(10, 47, 1, 'creacion', 'Operación creada', '2025-08-21 09:03:01'),
(11, 48, 1, 'creacion', 'Operación creada', '2025-08-22 14:57:28'),
(12, 49, 1, 'creacion', 'Operación creada', '2025-08-25 16:39:22'),
(13, 50, 1, 'creacion', 'Operación creada', '2025-09-01 12:55:19'),
(14, 55, 1, 'creacion', 'Operación creada', '2025-09-02 09:40:35'),
(15, 57, 1, 'creacion', 'Operación creada', '2025-09-02 09:48:12'),
(16, 58, 1, 'creacion', 'Operación creada', '2025-09-02 10:02:02'),
(17, 59, 1, 'creacion', 'Operación creada', '2025-09-02 10:19:24'),
(18, 60, 1, 'creacion', 'Operación creada', '2025-09-02 11:17:35'),
(19, 61, 1, 'creacion', 'Operación creada', '2025-09-02 16:44:42'),
(20, 62, 1, 'creacion', 'Operación creada', '2025-09-03 09:54:48'),
(21, 62, 1, 'actualizacion', 'Operación actualizada (num=\'EN-06\', estatus_id=5)', '2025-09-10 15:59:43'),
(22, 62, 1, 'actualizacion', 'Operación actualizada (num=\'EN-06\', estatus_id=5)', '2025-09-10 15:59:49'),
(23, 61, 1, 'actualizacion', 'Operación actualizada (num=\'LC-15\', estatus_id=9)', '2025-09-10 16:00:22'),
(24, 61, 1, 'actualizacion', 'Operación actualizada (num=\'LC-15\', estatus_id=5)', '2025-09-10 16:00:50'),
(25, 55, 1, 'actualizacion', 'Evento actualizado (id_evento=43, tipo_evt_id=10, fecha=2025-09-25, cont_tipo=MARITIMO, cont_ref=22)', '2025-09-10 16:19:55'),
(26, 49, 1, 'creacion', 'Documento subido (doc_tipo_id=1, cont_tipo=FISICO, cont_ref=FXEU23201998, archivo=Informe_Graficos_2025-09_2025-09-09.pdf, ruta=LI-02_Documentos/FXEU23201998/cc0a9d70bd4d4e6e_Informe_Graficos_2025-09_2025-09-09.pdf, size=18634728)', '2025-09-10 16:25:10'),
(27, 48, 1, 'actualizacion', 'Costo de operación actualizado (costo_id=22, tipo_id=10, monto=36, moneda=PESOS, coment=)', '2025-09-10 16:31:06'),
(28, 55, 1, 'actualizacion', 'Costo de contenedor actualizado (costo_id=30, cont_op_id=17, tipo_id=13, monto=123, coment=dsa…)', '2025-09-10 16:36:45'),
(29, 59, 1, 'actualizacion', 'Contenedor FISICO actualizado (cont_op_id=23, numero=FX456456, bultos=66, coment=)', '2025-09-10 16:53:18'),
(30, 63, 1, 'creacion', 'Operación creada', '2025-09-10 16:55:14'),
(31, 63, 1, 'creacion', 'Operación creada (num=\'\', subtipo_id=20, contenedores=1)', '2025-09-10 16:55:14'),
(32, 63, 1, 'actualizacion', 'Operación actualizada (num=\'TJ-01\', estatus_id=5)', '2025-09-10 16:56:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_operacion`
--

CREATE TABLE `permisos_operacion` (
  `id_permiso` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_operacion_id` int(11) DEFAULT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos_operacion`
--

INSERT INTO `permisos_operacion` (`id_permiso`, `usuario_id`, `tipo_operacion_id`, `estatus`, `creado_en`, `actualizado_en`) VALUES
(1, 7, 1, 1, '2025-08-12 18:05:20', '2025-08-12 18:05:41'),
(2, 1, 8, 1, '2025-08-12 18:05:55', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puertos`
--

CREATE TABLE `puertos` (
  `id_puerto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ciudad_id` int(11) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puertos`
--

INSERT INTO `puertos` (`id_puerto`, `nombre`, `ciudad_id`, `estatus`) VALUES
(1, 'Long Beach', 6, 1),
(4, 'Marina Coral', 7, 1),
(5, 'Puerto de Tijuana', 1, 0),
(6, 'Lázaro Cárdenas', 9, 1),
(7, 'Ensenada', 7, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puestos`
--

CREATE TABLE `puestos` (
  `id_puesto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puestos`
--

INSERT INTO `puestos` (`id_puesto`, `nombre`, `departamento_id`, `estatus`) VALUES
(1, 'Coordinador', 14, 1),
(2, 'Cliente', 2, 1),
(8, 'Desarrollo', 9, 1),
(17, 'Redes', 9, 1),
(18, 'dsadsa', 9, 0),
(19, 'dsa', 9, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`, `estatus`) VALUES
(1, 'Admin', 'Administrador del sistema', 1),
(2, 'Operador', 'Usuario operador', 1),
(3, 'Cliente', 'Clientes', 1),
(10, 'Invitado', 'Usuario Invitado', 1),
(11, 'Supervisor', 'Aprueba y revisa operaciones', 0),
(12, 'Logística', 'Encargado de trazabilidad y contenedores', 0);

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
(2, 3, 3, '2025-07-29 20:50:05'),
(3, 4, 3, '2025-07-29 21:05:38'),
(4, 5, 3, '2025-08-11 22:49:51'),
(5, 6, 10, '2025-08-11 22:53:02'),
(7, 8, 2, '2025-08-11 23:00:55'),
(8, 9, 2, '2025-08-11 23:02:48'),
(9, 10, 10, '2025-08-11 23:03:18'),
(10, 11, 10, '2025-08-11 23:04:56'),
(11, 12, 10, '2025-08-11 23:06:25'),
(12, 13, 10, '2025-08-11 23:08:32'),
(13, 14, 10, '2025-08-11 23:10:45'),
(16, 15, 3, '2025-08-11 23:25:33'),
(20, 1, 1, '2025-08-12 20:43:55'),
(21, 7, 2, '2025-08-27 18:38:15'),
(23, 16, 2, '2025-08-27 18:45:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shippers`
--

CREATE TABLE `shippers` (
  `id_shipper` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shippers`
--

INSERT INTO `shippers` (`id_shipper`, `nombre`, `contacto`, `direccion`, `estatus`) VALUES
(1, 'Jose Garcia', '6642353235', 'Enrique Segoviano', 1),
(2, 'Sin Shipper', 'Sin Shipper', 'Sin Shipper', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subtipos_operacion`
--

CREATE TABLE `subtipos_operacion` (
  `id_subtipo` int(11) NOT NULL,
  `tipo_operacion_id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `prefijo_codigo` varchar(10) NOT NULL DEFAULT '',
  `requiere_naviera` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_forwarder` tinyint(1) NOT NULL DEFAULT 0,
  `puerto_arribo_default_id` int(11) DEFAULT NULL,
  `schema_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_json`)),
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `subtipos_operacion`
--

INSERT INTO `subtipos_operacion` (`id_subtipo`, `tipo_operacion_id`, `clave`, `nombre`, `prefijo_codigo`, `requiere_naviera`, `requiere_forwarder`, `puerto_arribo_default_id`, `schema_json`, `estatus`) VALUES
(1, 1, 'LAZARO_CARDENAS', 'Lázaro Cárdenas', 'LC', 1, 1, 6, '{\"campos_visibles\": [\"numero_bl\", \"puerto_arribo_id\", \"naviera_id\", \"forwarder_id\"]}', 1),
(2, 1, 'LONG_BEACH', 'Long Beach', 'LB', 0, 0, 1, '{\"campos_visibles\": [\"numero_bl\", \"puerto_arribo_id\"]}', 1),
(3, 1, 'NUEVO', 'Nuevo Tipo', 'NT', 1, 1, 1, '{\"campos_visibles\": [\"numero_bl\", \"puerto_arribo_id\"]}', 1),
(20, 1, 'TIJUANA', 'Tijuana', 'TJ', 0, 0, 4, NULL, 1),
(21, 1, 'ENSENADA', 'Ensenada', 'EN', 0, 0, 7, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_documento`
--

CREATE TABLE `tipos_documento` (
  `id_tipo_documento` int(11) NOT NULL,
  `clave` varchar(64) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `aplica_sobre` enum('operacion','contenedor_fisico','contenedor_maritimo','cualquiera') NOT NULL DEFAULT 'cualquiera',
  `obligatorio_por_defecto` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_documento`
--

INSERT INTO `tipos_documento` (`id_tipo_documento`, `clave`, `nombre`, `descripcion`, `aplica_sobre`, `obligatorio_por_defecto`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'ENCOMIENDA', 'Carta Encomienda', '', 'contenedor_fisico', 1, 1, '2025-08-25 18:31:40', '2025-09-02 19:52:57'),
(2, 'GARANTIA', 'Garantía', '', 'contenedor_fisico', 1, 1, '2025-08-25 18:31:40', '2025-09-02 19:52:53'),
(3, 'EIR', 'EIR', NULL, 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', NULL),
(4, 'ARRIBO', 'Notificación de Arribo', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-02 19:52:47'),
(5, 'REVALIDACION', 'Pago de Revalidación', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-02 19:52:38'),
(6, 'CARGOS', 'Pago de Cargos Locales', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-08 23:45:49'),
(7, 'Pruebasasas', 'prueba', '123', 'cualquiera', 1, 0, '2025-08-26 16:18:16', '2025-08-26 16:57:25'),
(8, 'Pruebas', 'prueba', 'Esto es una prueba', 'cualquiera', 1, 1, '2025-08-26 16:54:26', '2025-08-26 17:05:27'),
(9, 'Prueba', 'prueba', '123', 'operacion', 1, 0, '2025-08-26 16:54:36', '2025-08-26 17:01:56'),
(10, 'dsa', 'dsa', 'dsa', 'operacion', 1, 0, '2025-08-26 16:58:15', '2025-08-26 17:01:53'),
(11, 'fda', 'fda', 'fda', 'operacion', 1, 0, '2025-08-26 17:07:04', '2025-08-26 17:07:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_evento_logistico`
--

CREATE TABLE `tipos_evento_logistico` (
  `id_tipo_evento` int(11) NOT NULL,
  `id_tipo_operacion` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_evento_logistico`
--

INSERT INTO `tipos_evento_logistico` (`id_tipo_evento`, `id_tipo_operacion`, `nombre`, `estatus`) VALUES
(1, 2, 'Ingresa Cargado', 1),
(2, 2, 'Sale Vacio', 1),
(3, 2, 'Salida a Ferro', 1),
(4, 2, 'Arribo a Destino', 1),
(5, 2, 'Disponibilidad Contenedor', 1),
(6, 2, 'Entrega Cargado', 1),
(7, 2, 'Vacio Retornado', 1),
(8, 1, 'Arribo A Puerto', 1),
(9, 1, 'Cargado', 1),
(10, 1, 'Entrega', 1),
(11, 1, 'Cita en puerto', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_movimiento`
--

CREATE TABLE `tipos_movimiento` (
  `id_tipo_movimiento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(15) NOT NULL,
  `moneda` varchar(30) NOT NULL,
  `tipo_operacion_id` int(11) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_movimiento`
--

INSERT INTO `tipos_movimiento` (`id_tipo_movimiento`, `nombre`, `tipo`, `moneda`, `tipo_operacion_id`, `estatus`) VALUES
(1, 'Carga', 'gasto', 'PESOS', 2, 1),
(2, 'Descarga', 'gasto', 'PESOS', 2, 1),
(8, 'Brecha', 'gasto', 'PESOS', 1, 1),
(9, 'Trasbordo', 'gasto', 'PESOS', 1, 1),
(10, 'Bodega', 'gasto', 'PESOS', 1, 1),
(11, 'Broker', 'gasto', 'PESOS', 1, 1),
(12, 'Flete Local', 'gasto', 'PESOS', 1, 1),
(13, 'Flete Ferro', 'gasto', 'PESOS', 2, 1),
(14, 'Estadias', 'gasto', 'PESOS', 2, 1),
(15, 'Comisiones', 'gasto', 'PESOS', 2, 1),
(16, 'Gastos Extra', 'gasto', 'DLLS', 2, 1),
(17, 'Bodega Maritima', 'gasto', 'DLLS', 1, 1),
(18, 'Descargasa', 'gasto', 'PESOS', 2, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_operacion`
--

CREATE TABLE `tipos_operacion` (
  `id_tipo_operacion` int(11) NOT NULL,
  `nombre_operacion` varchar(100) NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_operacion`
--

INSERT INTO `tipos_operacion` (`id_tipo_operacion`, `nombre_operacion`, `estatus`) VALUES
(1, 'Maritimo', 1),
(2, 'Terrestre', 1),
(3, 'Aereo', 0),
(6, 'Global', 0),
(7, 'Aereos', 0),
(8, 'Aereo', 0),
(9, 'Ferroviario', 0),
(10, 'LAZARO/LOGINCO', 0),
(11, 'Ferroviario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transportistas`
--

CREATE TABLE `transportistas` (
  `id_transportista` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('terrestre','maritimo','aereo') NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transportistas`
--

INSERT INTO `transportistas` (`id_transportista`, `nombre`, `tipo`, `estatus`) VALUES
(1, 'Jose Camacho', 'terrestre', 1);

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
  `estatus` int(11) NOT NULL DEFAULT 1,
  `session_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla de usuarios del sistema';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `correo`, `clave`, `telefono`, `puesto_id`, `departamento_id`, `estatus`, `session_token`) VALUES
(1, 'Oscar', 'Arzate', 'arzateoscar33@gmail.com', '$2y$10$95pFqn08R/rcjrm1DQvu2.IeiqYIathuUN9qIhvycPizJydwsX05C', '6644913156', 17, 9, 1, 'bc712ca1520707ff3f196ad3c419734e73dfd4d944e81a6a8e374d95b9fecb8e'),
(3, 'Oscar', 'Arzate', 'arzateoscar323@gmail.com', '$2y$10$iNnyuFc8U5OSKbnE/wzX3e8LErxhfza4mYJXicBY9S86HTXPDLdDW', '664989879', 2, 2, 1, NULL),
(4, 'Jose', 'Canseco', 'arzateoscar3223@gmail.com', '$2y$10$kDaGpyh0R8yUX5EuN9FzvOr4wUVzVM/IIwZbL54U2CqNkZfakr4pq', '66423530335', 2, 2, 0, '0'),
(5, 'Oscar', 'Arzate', 'arzateoscar3233@gmail.com', '$2y$10$YWCFCvHIoLIJsAindDxrlOkJ0QQYIuVgZW69nZiGXadm24WkufEAu', '6644913156', 1, 14, 0, NULL),
(6, 'Gabriel', 'Garcia', 'gabrielg@gmail.com', '$2y$10$W.bI07HmkOhaXF19E0ocnuyTliPe0oROkczFCHHsYJ80m1vYR5Kgq', '66478979832', 1, 14, 0, NULL),
(7, 'Gabriela', 'Garcia', 'ggarcia@gmail.com', '$2y$10$2EUNBsyePeSzPVIPfhqkkuPAuREZLzRw1Lm5XaSpJt1eWu4wV2V.K', '6645648971', 8, 9, 1, NULL),
(8, 'Oscar', 'Arzate', 'a@gmail.com', '$2y$10$qv3u6vbvxRSNJcU2zhlRy.TydQWWdO/w3nhCvs.PMneTTIQ4sfrVK', '6644594848', 8, 9, 0, NULL),
(9, 'Oscar', 'Arzate', 'da@gmail.com', '$2y$10$VbDXQimOGMhs70ttx2OMwOKRRdWWDrWmy9MzOgokowrnt5TvcavSu', '6644594848', 8, 9, 0, NULL),
(10, 'Jose', 'Canseco', 'r@gmail.com', '$2y$10$mYeJt3J4aIkECOgl3lDt7eB7lcsrD4VyszZxyIJapCWrSbJKzOTSO', '6643235654', 1, 14, 0, NULL),
(11, 'Alberto', 'del Rio', 'a3@gmail.com', '$2y$10$6uZhKarrggV1WobcI0K6v.Hj2ONDh50adULvagZjp0OwMaPCsh236', '6645984845', 1, 14, 0, NULL),
(12, 'Javier', 'Lopez', 'lj@gmail.com', '$2y$10$cl4NLfP1uHjyWX0ghcQZc.HhAX9Fyo5qDeggwpuBTw5yq7sAAm1gG', '56645864856', 1, 14, 0, NULL),
(13, 'dsa', 'dsa', 'ssss@gmail.com', '$2y$10$W74Ak6GNTD2Xp/CNgpOuh.F2QP5FxQN5y2mMT82dMPIZs0MLToFoe', '6645984846', 1, 14, 0, NULL),
(14, 'sad', 'das', 'dsad@gmail.com', '$2y$10$kOWF9nbVvacyQPov4BJAcOxlEq1zRIG2c5BtYwZWfmDUYRQ/KL3RC', '66459448456', 1, 14, 0, NULL),
(15, 'Manuela', 'Mendoza', 'mendozar33@gmail.com', '$2y$10$JnlXaS3c4p9zn3JGvoUwP.8ItD3TA5Vgaixp2e5eYLOW/3VQvxIS2', '6648888585', 2, 2, 0, NULL),
(16, 'Luis Alberto', 'Navarrete', 'luis@gmail.com', '$2y$10$hg8/i2jaebzLeqmPiMPNtuEsjr1M6UoR4jSYNg6D4CgNUYXUCrZru', '6648789898', 1, 14, 0, NULL);

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
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `fk_costo_tipo_movimiento` (`tipo_movimiento_id`);

--
-- Indices de la tabla `costos_logisticos`
--
ALTER TABLE `costos_logisticos`
  ADD PRIMARY KEY (`id_costo`),
  ADD KEY `operacion_id` (`operacion_id`);

--
-- Indices de la tabla `costos_operacion`
--
ALTER TABLE `costos_operacion`
  ADD PRIMARY KEY (`id_costo_operacion`),
  ADD KEY `idx_co_operacion` (`operacion_id`),
  ADD KEY `idx_co_tipo_mov` (`tipo_movimiento_id`),
  ADD KEY `idx_co_estatus` (`estatus`);

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
  ADD KEY `operacion_id` (`operacion_id`);

--
-- Indices de la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `subido_por` (`subido_por`),
  ADD KEY `idx_doc_op` (`operacion_id`),
  ADD KEY `idx_doc_cont_fis` (`contenedor_operacion_id`),
  ADD KEY `idx_doc_cont_mar` (`cont_maritimo_operacion_id`),
  ADD KEY `idx_doc_tipo` (`tipo_documento_id`);

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
  ADD KEY `idx_ev_operacion` (`operacion_id`),
  ADD KEY `idx_ev_cont_op` (`contenedor_operacion_id`),
  ADD KEY `idx_ev_cont_maritimo` (`cont_maritimo_operacion_id`),
  ADD KEY `idx_ev_tipo_evento` (`tipo_evento_id`),
  ADD KEY `idx_ev_fecha` (`fecha`),
  ADD KEY `idx_ev_creado_por` (`creado_por`),
  ADD KEY `idx_ev_estatus` (`estatus`);

--
-- Indices de la tabla `forwarders`
--
ALTER TABLE `forwarders`
  ADD PRIMARY KEY (`id_forwarder`),
  ADD UNIQUE KEY `ux_forwarders_nombre` (`nombre`);

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
  ADD KEY `idx_operaciones_numero` (`numero_operacion`),
  ADD KEY `idx_operaciones_estado` (`estatus_id`),
  ADD KEY `fk_oper_tipo` (`tipo_operacion_id`),
  ADD KEY `fk_oper_subtipo` (`subtipo_operacion_id`),
  ADD KEY `fk_oper_naviera` (`naviera_id`),
  ADD KEY `fk_oper_forwarder` (`forwarder_id`),
  ADD KEY `fk_operaciones_cliente` (`cliente_id`);

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
  ADD UNIQUE KEY `ux_permiso_operacion` (`usuario_id`,`tipo_operacion_id`),
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
  ADD PRIMARY KEY (`id_puesto`),
  ADD KEY `fk_puestos_departamento` (`departamento_id`);

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
-- Indices de la tabla `subtipos_operacion`
--
ALTER TABLE `subtipos_operacion`
  ADD PRIMARY KEY (`id_subtipo`),
  ADD UNIQUE KEY `ux_subtipo_clave` (`clave`),
  ADD KEY `fk_subtipo_tipo` (`tipo_operacion_id`),
  ADD KEY `fk_subtipo_puerto_default` (`puerto_arribo_default_id`);

--
-- Indices de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  ADD PRIMARY KEY (`id_tipo_documento`),
  ADD UNIQUE KEY `uk_tipos_documento_clave` (`clave`);

--
-- Indices de la tabla `tipos_evento_logistico`
--
ALTER TABLE `tipos_evento_logistico`
  ADD PRIMARY KEY (`id_tipo_evento`),
  ADD KEY `idx_tel_tipo_operacion` (`id_tipo_operacion`),
  ADD KEY `idx_tel1_tipo_operacion` (`id_tipo_operacion`);

--
-- Indices de la tabla `tipos_movimiento`
--
ALTER TABLE `tipos_movimiento`
  ADD PRIMARY KEY (`id_tipo_movimiento`),
  ADD KEY `fk_tipos_movimiento_operacion` (`tipo_operacion_id`);

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
  MODIFY `id_bodega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `brokers`
--
ALTER TABLE `brokers`
  MODIFY `id_broker` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  MODIFY `id_ciudad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `contenedores_fisicos`
--
ALTER TABLE `contenedores_fisicos`
  MODIFY `id_fisico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=581;

--
-- AUTO_INCREMENT de la tabla `contenedores_maritimos`
--
ALTER TABLE `contenedores_maritimos`
  MODIFY `id_contenedor_maritimo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `contenedores_maritimos_operacion`
--
ALTER TABLE `contenedores_maritimos_operacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `contenedores_operacion`
--
ALTER TABLE `contenedores_operacion`
  MODIFY `id_contenedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `contenedor_maritimo_ferro`
--
ALTER TABLE `contenedor_maritimo_ferro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costos_contenedor_operacion`
--
ALTER TABLE `costos_contenedor_operacion`
  MODIFY `id_costo_contenedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `costos_logisticos`
--
ALTER TABLE `costos_logisticos`
  MODIFY `id_costo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costos_operacion`
--
ALTER TABLE `costos_operacion`
  MODIFY `id_costo_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `detalles_logisticos`
--
ALTER TABLE `detalles_logisticos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estatus`
--
ALTER TABLE `estatus`
  MODIFY `id_estatus` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `forwarders`
--
ALTER TABLE `forwarders`
  MODIFY `id_forwarder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id_naviera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `operaciones`
--
ALTER TABLE `operaciones`
  MODIFY `id_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de la tabla `operaciones_log`
--
ALTER TABLE `operaciones_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `permisos_operacion`
--
ALTER TABLE `permisos_operacion`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `puertos`
--
ALTER TABLE `puertos`
  MODIFY `id_puerto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `roles_usuario`
--
ALTER TABLE `roles_usuario`
  MODIFY `id_rol_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `shippers`
--
ALTER TABLE `shippers`
  MODIFY `id_shipper` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `subtipos_operacion`
--
ALTER TABLE `subtipos_operacion`
  MODIFY `id_subtipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  MODIFY `id_tipo_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tipos_evento_logistico`
--
ALTER TABLE `tipos_evento_logistico`
  MODIFY `id_tipo_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tipos_movimiento`
--
ALTER TABLE `tipos_movimiento`
  MODIFY `id_tipo_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `tipos_operacion`
--
ALTER TABLE `tipos_operacion`
  MODIFY `id_tipo_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `transportistas`
--
ALTER TABLE `transportistas`
  MODIFY `id_transportista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `trazabilidad_contenedor`
--
ALTER TABLE `trazabilidad_contenedor`
  MODIFY `id_traza` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
  ADD CONSTRAINT `costos_contenedor_operacion_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_costo_tipo_movimiento` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipos_movimiento` (`id_tipo_movimiento`);

--
-- Filtros para la tabla `costos_logisticos`
--
ALTER TABLE `costos_logisticos`
  ADD CONSTRAINT `costos_logisticos_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`);

--
-- Filtros para la tabla `costos_operacion`
--
ALTER TABLE `costos_operacion`
  ADD CONSTRAINT `fk_co_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_co_tipo_mov` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipos_movimiento` (`id_tipo_movimiento`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_logisticos`
--
ALTER TABLE `detalles_logisticos`
  ADD CONSTRAINT `detalles_logisticos_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`);

--
-- Filtros para la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  ADD CONSTRAINT `documentos_operacion_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`),
  ADD CONSTRAINT `documentos_operacion_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_doc_cont_fis` FOREIGN KEY (`contenedor_operacion_id`) REFERENCES `contenedores_operacion` (`id_contenedor`),
  ADD CONSTRAINT `fk_doc_cont_mar` FOREIGN KEY (`cont_maritimo_operacion_id`) REFERENCES `contenedores_maritimos_operacion` (`id`),
  ADD CONSTRAINT `fk_doc_tipo` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id_tipo_documento`);

--
-- Filtros para la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  ADD CONSTRAINT `fk_ev_cont_maritimo` FOREIGN KEY (`cont_maritimo_operacion_id`) REFERENCES `contenedores_maritimos_operacion` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ev_cont_op` FOREIGN KEY (`contenedor_operacion_id`) REFERENCES `contenedores_operacion` (`id_contenedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ev_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ev_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ev_tipo_evento` FOREIGN KEY (`tipo_evento_id`) REFERENCES `tipos_evento_logistico` (`id_tipo_evento`) ON UPDATE CASCADE;

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
  ADD CONSTRAINT `estatus_id_id_estatus` FOREIGN KEY (`estatus_id`) REFERENCES `estatus` (`id_estatus`),
  ADD CONSTRAINT `fk_oper_forwarder` FOREIGN KEY (`forwarder_id`) REFERENCES `forwarders` (`id_forwarder`),
  ADD CONSTRAINT `fk_oper_naviera` FOREIGN KEY (`naviera_id`) REFERENCES `navieras` (`id_naviera`),
  ADD CONSTRAINT `fk_oper_subtipo` FOREIGN KEY (`subtipo_operacion_id`) REFERENCES `subtipos_operacion` (`id_subtipo`),
  ADD CONSTRAINT `fk_oper_tipo` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id_tipo_operacion`),
  ADD CONSTRAINT `fk_operaciones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `operaciones_ibfk_1` FOREIGN KEY (`shipper_id`) REFERENCES `shippers` (`id_shipper`);

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
-- Filtros para la tabla `puestos`
--
ALTER TABLE `puestos`
  ADD CONSTRAINT `fk_puestos_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id_departamento`);

--
-- Filtros para la tabla `roles_usuario`
--
ALTER TABLE `roles_usuario`
  ADD CONSTRAINT `roles_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `roles_usuario_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `subtipos_operacion`
--
ALTER TABLE `subtipos_operacion`
  ADD CONSTRAINT `fk_subtipo_puerto_default` FOREIGN KEY (`puerto_arribo_default_id`) REFERENCES `puertos` (`id_puerto`),
  ADD CONSTRAINT `fk_subtipo_tipo` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id_tipo_operacion`);

--
-- Filtros para la tabla `tipos_evento_logistico`
--
ALTER TABLE `tipos_evento_logistico`
  ADD CONSTRAINT `fk_tel_tipo_operacion` FOREIGN KEY (`id_tipo_operacion`) REFERENCES `tipos_operacion` (`id_tipo_operacion`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tipos_movimiento`
--
ALTER TABLE `tipos_movimiento`
  ADD CONSTRAINT `fk_tipos_movimiento_operacion` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id_tipo_operacion`) ON DELETE SET NULL ON UPDATE CASCADE;

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
