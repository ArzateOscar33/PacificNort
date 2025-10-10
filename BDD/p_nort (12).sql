-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-10-2025 a las 23:34:46
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

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerarNumeroOperacionFerro` (IN `p_subtipo_id` INT, OUT `p_numero_operacion` VARCHAR(50))   BEGIN
    DECLARE v_anio INT DEFAULT YEAR(CURDATE());
    DECLARE v_siguiente_num INT DEFAULT 1;
    DECLARE v_prefijo VARCHAR(10);
    
    -- Obtener el prefijo del subtipo
    SELECT prefijo_codigo INTO v_prefijo 
    FROM subtipos_operacion 
    WHERE id_subtipo = p_subtipo_id;
    
    -- Obtener y actualizar el siguiente número
    INSERT INTO secuencias_operacion (subtipo_id, anio, valor) 
    VALUES (p_subtipo_id, v_anio, 1)
    ON DUPLICATE KEY UPDATE valor = valor + 1;
    
    SELECT valor INTO v_siguiente_num 
    FROM secuencias_operacion 
    WHERE subtipo_id = p_subtipo_id AND anio = v_anio;
    
    -- Generar el número
    SET p_numero_operacion = CONCAT(v_prefijo, '-', LPAD(v_siguiente_num, 2, '0'));
END$$

DELIMITER ;

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

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`id_bitacora`, `usuario_id`, `modulo`, `accion`, `entidad`, `entidad_id`, `fecha`, `detalle`) VALUES
(1, 1, 'trazabilidad_ferro', 'baja_logica', 'rutas_ferro', 1, '2025-10-03 16:35:28', 'Baja lógica: ruta_ferro + tramos + costos transporte (FO=1)'),
(2, 1, 'trazabilidad_ferro', 'baja_logica', 'rutas_ferro', 4, '2025-10-03 16:47:53', 'Baja lógica: ruta_ferro + tramos + costos transporte (FO=2)'),
(3, 1, 'trazabilidad_ferro', 'baja_logica', 'rutas_ferro', 2, '2025-10-03 16:53:11', 'Baja lógica: ruta_ferro + tramos + costos transporte (FO=18)'),
(4, 1, 'trazabilidad_ferro', 'baja_logica', 'rutas_ferro', 8, '2025-10-03 16:54:53', 'Baja lógica: ruta_ferro + tramos + costos transporte (FO=1)'),
(5, 1, 'trazabilidad_ferro', 'baja_logica', 'rutas_ferro', 9, '2025-10-08 17:31:43', 'Baja lógica: ruta_ferro + tramos + costos transporte (FO=15)');

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
(9, 'Lázaro Cárdenas', 4, 1),
(10, 'Pantaco', 6, 1),
(11, 'Veracruz', 7, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `telefono`, `correo`, `estatus`) VALUES
(1, 'Juan Jesus Parra', '6644913156', 'juanparcam@gmail.com', 1),
(2, 'alex lora', '6647898987', 'alex@gmail.com', 0),
(3, 'Nayo Escobar', '6648978587', 'arzateoscar33@gmail.com', 1),
(4, 'Carlos Arzate', '6649135645', '3bmamog@gmail.com', 1),
(5, 'Jose', '321321214', 'mendozar33@gmail.com', 1),
(6, 'CP Dany', 'Sin Telefono', 'cpdany_78@hotmail.com', 1),
(7, 'Lic. Sandra/Sr. Ricky', 'Sin Telefono', 'dorantesand@gmail.com', 1),
(8, 'David', 'Sin Telefono', 'cxc.logistica@gmail.com', 1),
(9, 'Gerardo Schek', 'Sin Telefono', 'sincorreo3@gmail.com', 1),
(10, 'Sr Haim/ Banana Limon', 'Sin Telefono', 'haim@bananalimon.com', 1),
(11, 'Benjamin TGC', 'Sin Telefono', 'notificacioneszonflo@gmail.com', 1),
(12, 'Ricardo GDL', 'Sin Telefono', 'sincorreo6@gmail.com', 1),
(13, 'Andrea/ TOMMER', 'Sin Telefono', 'silvermx.admon@gmail.com', 1);

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
(580, 'FX987741', 1),
(581, 'FX555434', 1),
(582, 'FXEU2320787', 1),
(583, 'FXEU2320784', 1),
(584, 'FX010101', 1),
(585, 'C-12345', 1),
(586, 'C-12341', 1),
(587, 'C-78954', 1),
(588, 'C-12365', 1),
(589, 'FX789789', 1),
(590, 'OOLU9419905', 1),
(591, 'C-78964', 1),
(592, 'FXEU8595489', 1);

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
(55, 'MG00000017', NULL, 'Sin Observaciones', 1),
(56, 'EMCU164499931A', NULL, NULL, 1),
(57, 'MGU8890992', NULL, 'Sin Observaciones', 1),
(58, 'PCI5833036', NULL, 'Sin Observaciones', 1),
(59, 'MG123132', NULL, 'Sin Observaciones', 1),
(60, 'MG90901', NULL, 'Sin Observaciones', 1),
(61, 'MG90902', NULL, 'Sin Observaciones', 1),
(62, 'MPH9090', NULL, 'Sin Observaciones', 1),
(63, 'MG90123', NULL, 'Sin Observaciones', 1),
(64, 'MG901232', NULL, 'Sin Observaciones', 1),
(65, 'MG789789', NULL, 'Sin Observaciones', 1),
(66, 'MG909012', NULL, 'Sin Observaciones', 1),
(67, 'MG0000012', NULL, 'Sin Observaciones', 1),
(68, 'MG10101010', NULL, 'Sin Observaciones', 1),
(69, 'MB9090', NULL, 'Sin Observaciones', 1),
(70, 'MG9090122', NULL, 'Sin Observaciones', 1),
(71, 'MG902133', NULL, 'Sin Observaciones', 1),
(72, 'MG00000002', NULL, 'Sin Observaciones', 1),
(73, 'MG7897891', NULL, 'Sin Observaciones', 1),
(74, 'FSU90902121', NULL, 'Sin Observaciones', 1),
(75, 'MG789258', NULL, 'Sin Observaciones', 1),
(76, 'MG00132564', NULL, 'Sin Observaciones', 1),
(77, 'OOLU9419905', NULL, 'Sin Observaciones', 1),
(78, 'OOLU9940567', NULL, 'Sin Observaciones', 1),
(79, 'OOLU9940569', NULL, 'Sin Observaciones', 1),
(80, 'CLO90381', NULL, 'Sin Observaciones', 1),
(81, 'MG89082191', NULL, 'Sin Observaciones', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores_maritimos_operacion`
--

CREATE TABLE `contenedores_maritimos_operacion` (
  `id` int(11) NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `contenedor_maritimo_id` int(11) DEFAULT NULL,
  `bultos` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenedores_maritimos_operacion`
--

INSERT INTO `contenedores_maritimos_operacion` (`id`, `operacion_id`, `contenedor_maritimo_id`, `bultos`) VALUES
(1, 1, 52, 500),
(2, 2, 55, 650),
(3, 3, 72, 255),
(4, 4, 76, 1100),
(5, 5, 77, 357),
(6, 6, 78, 357),
(7, 7, 79, 255),
(8, 8, 80, 355),
(9, 9, 81, 1255);

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
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `bultos` int(11) DEFAULT NULL,
  `estatus` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedor_maritimo_ferro`
--

CREATE TABLE `contenedor_maritimo_ferro` (
  `id` int(11) NOT NULL,
  `operacion_ferro_id` int(11) NOT NULL,
  `contenedor_maritimo_id` int(11) DEFAULT NULL,
  `cont_maritimo_operacion_id` int(11) DEFAULT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `bultos_asignados` int(11) DEFAULT 0,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `estatus` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenedor_maritimo_ferro`
--

INSERT INTO `contenedor_maritimo_ferro` (`id`, `operacion_ferro_id`, `contenedor_maritimo_id`, `cont_maritimo_operacion_id`, `contenedor_fisico_id`, `comentario`, `bultos_asignados`, `fecha_asignacion`, `estatus`) VALUES
(4, 3, 55, 2, 575, NULL, 10, '2025-09-26 12:38:32', 1),
(8, 6, 55, 2, 365, NULL, 10, '2025-09-26 12:49:39', 1),
(9, 7, 55, 2, 589, NULL, 25, '2025-09-26 13:08:50', 1),
(10, 7, 52, 1, 589, NULL, 10, '2025-09-26 13:08:50', 1),
(12, 9, 55, 2, 397, NULL, 1, '2025-09-26 13:58:21', 1),
(13, 9, 52, 1, 397, NULL, 36, '2025-09-26 13:58:21', 1),
(14, 10, 55, 2, 581, NULL, 120, '2025-09-29 09:51:25', 1),
(15, 11, 55, 2, 580, NULL, 25, '2025-09-29 09:56:52', 1),
(16, 11, 52, 1, 580, NULL, 30, '2025-09-29 09:56:52', 1),
(19, 13, 55, 2, 576, NULL, 10, '2025-09-29 10:27:04', 1),
(20, 13, 52, 1, 576, NULL, 12, '2025-09-29 10:27:04', 1),
(23, 12, 52, 1, 397, NULL, 25, '2025-09-29 11:32:21', 1),
(24, 5, 55, 2, 365, NULL, 10, '2025-09-29 11:36:03', 1),
(25, 14, 55, 2, 397, NULL, 25, '2025-09-29 11:59:38', 1),
(26, 4, 55, 2, 586, NULL, 10, '2025-09-29 11:59:45', 1),
(27, 2, 55, 2, 588, NULL, 50, '2025-09-29 12:45:48', 1),
(28, 15, 55, 2, 516, NULL, 100, '2025-09-29 14:44:42', 1),
(29, 15, 52, 1, 516, NULL, 30, '2025-09-29 14:44:42', 1),
(30, 8, 55, 2, 580, NULL, 120, '2025-09-29 15:14:10', 1),
(34, 16, 52, 1, 418, NULL, 120, '2025-09-29 17:05:37', 1),
(35, 16, 55, 2, 418, NULL, 100, '2025-09-29 17:05:37', 1),
(36, 16, 76, 4, 418, NULL, 260, '2025-09-29 17:05:37', 1),
(37, 17, 76, 4, 449, NULL, 100, '2025-10-01 16:19:26', 1),
(40, 18, 76, 4, 447, NULL, 100, '2025-10-02 11:28:11', 1),
(41, 18, 55, 2, 447, NULL, 9, '2025-10-02 11:28:11', 1),
(42, 18, 52, 1, 447, NULL, 100, '2025-10-02 11:28:11', 1),
(43, 1, 52, 1, 584, NULL, 33, '2025-10-02 13:33:18', 1),
(44, 1, 55, 2, 584, NULL, 25, '2025-10-02 13:33:18', 1),
(45, 19, 76, 4, 587, NULL, 640, '2025-10-03 16:58:49', 1),
(46, 19, 52, 1, 587, NULL, 10, '2025-10-03 16:58:49', 1),
(47, 20, 77, 5, 590, NULL, 357, '2025-10-08 17:39:12', 1),
(48, 21, 78, 6, 591, NULL, 300, '2025-10-08 17:52:49', 1),
(50, 22, 78, 6, 592, NULL, 57, '2025-10-09 15:46:18', 1),
(51, 22, 52, 1, 592, NULL, 94, '2025-10-09 15:46:18', 1),
(52, 23, 80, 8, 583, NULL, 255, '2025-10-10 12:45:00', 1);

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
(1, 3, 17, 10.00, '', '2025-09-29 12:53:54', 1),
(2, 3, 20, 300.00, '', '2025-09-29 12:54:03', 1),
(3, 3, 17, 3.00, '', '2025-09-29 14:54:45', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costos_operacion_ferro`
--

CREATE TABLE `costos_operacion_ferro` (
  `id_costo_ferro` int(11) NOT NULL,
  `operacion_ferro_id` int(11) NOT NULL,
  `tipo_movimiento_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Costos específicos de operaciones ferroviarias';

--
-- Volcado de datos para la tabla `costos_operacion_ferro`
--

INSERT INTO `costos_operacion_ferro` (`id_costo_ferro`, `operacion_ferro_id`, `tipo_movimiento_id`, `monto`, `comentario`, `fecha_creacion`, `creado_por`, `estatus`) VALUES
(1, 1, 23, 125.00, 'bajo a tiempo', '2025-10-02 13:24:28', 1, 0),
(2, 1, 23, 365.00, NULL, '2025-10-02 13:24:28', 1, 0),
(5, 18, 23, 1358.00, NULL, '2025-10-02 15:40:16', 1, 0),
(6, 18, 23, 230.00, NULL, '2025-10-02 15:40:16', 1, 0),
(7, 18, 23, 350.00, NULL, '2025-10-02 15:40:16', 1, 0),
(8, 18, 23, 300.00, NULL, '2025-10-02 15:40:16', 1, 0),
(9, 18, 23, 1358.00, NULL, '2025-10-02 15:43:08', 1, 0),
(10, 18, 23, 230.00, NULL, '2025-10-02 15:43:08', 1, 0),
(11, 18, 23, 350.00, NULL, '2025-10-02 15:43:08', 1, 0),
(12, 18, 23, 300.00, NULL, '2025-10-02 15:43:08', 1, 0),
(13, 18, 23, 200.00, NULL, '2025-10-02 15:43:08', 1, 0),
(14, 1, 23, 125.00, 'bajo a tiempo', '2025-10-02 15:43:35', 1, 0),
(15, 1, 23, 365.00, NULL, '2025-10-02 15:43:35', 1, 0),
(16, 1, 23, 125.00, 'bajo a tiempo', '2025-10-02 15:43:42', 1, 0),
(17, 1, 23, 365.00, NULL, '2025-10-02 15:43:42', 1, 0),
(18, 1, 23, 123.00, NULL, '2025-10-02 15:43:42', 1, 0),
(19, 1, 23, 12.00, NULL, '2025-10-02 16:03:43', 1, 0),
(20, 13, 23, 258.00, NULL, '2025-10-02 16:22:57', 1, 1),
(21, 13, 23, 1.00, NULL, '2025-10-02 16:26:48', 1, 1),
(22, 13, 23, 2.00, NULL, '2025-10-02 16:27:23', 1, 1),
(23, 2, 23, 125.00, NULL, '2025-10-02 16:28:40', 1, 0),
(24, 2, 23, 3.00, 'fsa', '2025-10-02 16:29:14', 1, 0),
(25, 2, 23, 13.00, NULL, '2025-10-02 16:29:53', 1, 0),
(26, 3, 23, 123.00, NULL, '2025-10-02 16:50:24', 1, 1),
(27, 4, 23, 129.00, NULL, '2025-10-02 16:50:58', 1, 1),
(28, 10, 23, 125.00, NULL, '2025-10-03 15:22:18', 1, 1),
(29, 10, 23, 133.00, NULL, '2025-10-03 15:22:18', 1, 1),
(30, 13, 23, 147.00, NULL, '2025-10-03 15:32:58', 1, 1),
(31, 1, 23, 132.00, NULL, '2025-10-03 16:43:36', 1, 0),
(32, 1, 23, 123.00, NULL, '2025-10-03 16:51:29', 1, 0),
(33, 4, 23, 236.00, NULL, '2025-10-03 16:56:06', 1, 1),
(34, 15, 23, 2500.00, NULL, '2025-10-08 17:30:20', 1, 0),
(35, 15, 23, 3000.00, NULL, '2025-10-08 17:30:20', 1, 0),
(36, 15, 23, 5000.00, NULL, '2025-10-08 17:30:37', 1, 0),
(37, 13, 23, 2851.00, 'retorno', '2025-10-09 15:48:50', 1, 1),
(38, 13, 23, 1322.00, NULL, '2025-10-10 12:48:03', 1, 1);

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
(14, 'Ventas', 0),
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
(4, 3, 5, NULL, 3, 'Informe_Graficos_2025-09_2025-09-27 (1).pdf', 'application/pdf', 19533852, 'e13166246396b89a3b0314194a802d0a7ba1ac43767f5547ff2e068dcc651d18', 'EN-02/MG00000002/EN-02_MG00000002_b813f775e67f_Informe_Graficos_2025-09_2025-09-27__1_.pdf', '2025-09-29 14:34:04', 1),
(8, 4, 6, NULL, 4, 'Informe_Graficos_2025-10_2025-10-06.pdf', 'application/pdf', 18625277, 'b6d2fd534d8f68bdff4263a6cea822892eb4a58c78ff1013088bcacc47d3c509', 'LBMF-06_Documentos/MG00132564/02da2b28dd388fd3_Informe_Graficos_2025-10_2025-10-06.pdf', '2025-10-07 17:37:09', 1),
(9, 2, 4, NULL, 2, 'Informe_Graficos_2025-10_2025-10-06.pdf', 'application/pdf', 18625277, 'b6d2fd534d8f68bdff4263a6cea822892eb4a58c78ff1013088bcacc47d3c509', 'LBMF-05_Documentos/MG00000017/32f0a7e7fae0e39b_Informe_Graficos_2025-10_2025-10-06.pdf', '2025-10-07 18:06:27', 1),
(14, 4, 4, NULL, 4, 'Informe_Graficos_2025-10_2025-10-06.pdf', 'application/pdf', 18625277, 'b6d2fd534d8f68bdff4263a6cea822892eb4a58c78ff1013088bcacc47d3c509', 'LBMF-06_Documentos/MG00132564/b176febdfd39ebe1_Informe_Graficos_2025-10_2025-10-06.pdf', '2025-10-07 18:52:29', 1),
(21, 4, 6, NULL, 4, 'SAT (1).pdf', 'application/pdf', 140355, 'b2f993b2cf19d1f69b2547c4a98abb8f863570589c7bd0fa1f4dd46585dcd4e4', 'LBMF-06_Documentos/MG00132564/84c836125704cbb5_SAT__1_.pdf', '2025-10-07 18:59:45', 1),
(22, 19, 2, 587, NULL, 'Informe_Graficos_2025-10_2025-10-06.pdf', 'application/pdf', 18625277, 'b6d2fd534d8f68bdff4263a6cea822892eb4a58c78ff1013088bcacc47d3c509', 'FO-19_Documentos/C-78954/ee3efc77ae156587_Informe_Graficos_2025-10_2025-10-06.pdf', '2025-10-07 19:07:37', 1),
(23, 4, 4, NULL, 4, '01_merged.pdf', 'application/pdf', 905736, 'dce08161301d911c5443294a0e2ec43e72655b58a052f09cc9c0bca7021f6adb', 'LBMF-06_Documentos/MG00132564/9b7f8beb709739c9_01_merged.pdf', '2025-10-07 19:07:48', 1),
(24, 19, 1, 587, NULL, 'SAT (1).pdf', 'application/pdf', 140355, 'b2f993b2cf19d1f69b2547c4a98abb8f863570589c7bd0fa1f4dd46585dcd4e4', 'FO-19_Documentos/C-78954/11873092c5681db3_SAT__1_.pdf', '2025-10-07 19:08:12', 1),
(26, 4, 4, NULL, 4, 'SAT (1).pdf', 'application/pdf', 140355, 'b2f993b2cf19d1f69b2547c4a98abb8f863570589c7bd0fa1f4dd46585dcd4e4', 'LBMF-06_Documentos/MG00132564/ac6da51a49b73cbf_SAT__1_.pdf', '2025-10-07 19:13:36', 1);

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
(5, 'California', 1),
(6, 'Ciudad de Mexico', 1),
(7, 'Veracruz', 1);

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
(7, 'Finalizada', 1),
(9, 'Abierta', 1),
(10, 'Modulado a Ferro', 1),
(11, 'En Proceso', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_ferroviarios`
--

CREATE TABLE `eventos_ferroviarios` (
  `id_evento` int(11) NOT NULL,
  `operacion_ferro_id` int(11) NOT NULL,
  `contenedor_fisico_id` int(11) NOT NULL,
  `tipo_evento_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos_ferroviarios`
--

INSERT INTO `eventos_ferroviarios` (`id_evento`, `operacion_ferro_id`, `contenedor_fisico_id`, `tipo_evento_id`, `fecha`, `comentario`, `creado_por`, `creado_en`, `actualizado_en`, `estatus`) VALUES
(1, 1, 584, 3, '2025-10-08', NULL, 1, '2025-10-10 12:12:57', '2025-10-10 12:12:57', 1),
(2, 1, 584, 4, '2025-10-10', '', 1, '2025-10-10 12:40:36', '2025-10-10 12:40:40', 0),
(3, 2, 588, 3, '2025-10-09', '', 1, '2025-10-10 12:41:00', '2025-10-10 12:41:00', 1),
(4, 7, 589, 7, '2025-10-29', '', 1, '2025-10-10 12:41:24', '2025-10-10 12:41:24', 1),
(5, 6, 365, 12, '2025-10-08', '', 1, '2025-10-10 12:46:29', '2025-10-10 12:46:41', 0),
(6, 10, 581, 4, '2025-10-01', '', 1, '2025-10-10 13:49:51', '2025-10-10 13:49:51', 1),
(7, 10, 581, 12, '2025-10-17', '', 1, '2025-10-10 13:49:58', '2025-10-10 13:50:03', 0),
(11, 10, 581, 5, '2025-10-23', '', 1, '2025-10-10 13:52:17', '2025-10-10 13:52:21', 0),
(13, 10, 581, 12, '2025-10-09', '', 1, '2025-10-10 14:32:45', '2025-10-10 14:32:49', 0),
(14, 10, 581, 12, '2025-10-17', '', 1, '2025-10-10 14:32:53', '2025-10-10 14:32:53', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_logisticos`
--

CREATE TABLE `eventos_logisticos` (
  `id_evento` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL,
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

INSERT INTO `eventos_logisticos` (`id_evento`, `operacion_id`, `cont_maritimo_operacion_id`, `tipo_evento_id`, `fecha`, `comentario`, `creado_por`, `fecha_creacion`, `estatus`) VALUES
(1, 3, 3, 8, '2025-09-23', 'fdsa', 1, '2025-09-29 15:12:52', 1),
(2, 3, 3, 9, '2025-09-10', 'CAMBIO DE TRANSPORTISTA', 1, '2025-09-29 17:06:49', 1),
(3, 1, 1, 8, '2025-12-18', '', 1, '2025-10-09 10:31:11', 0),
(4, 1, 1, 9, '2025-10-11', '', 1, '2025-10-09 10:53:46', 1),
(5, 1, 1, 10, '2025-10-09', '', 1, '2025-10-09 10:54:00', 1),
(6, 1, 1, 11, '2025-10-19', '', 1, '2025-10-09 10:54:11', 1),
(7, 1, 1, 14, '2025-10-21', '', 1, '2025-10-09 10:54:22', 1),
(8, 2, 2, 8, '2025-10-01', '', 1, '2025-10-09 10:54:39', 0),
(9, 4, 4, 8, '2025-10-06', '', 1, '2025-10-09 10:55:13', 1),
(10, 5, 5, 8, '2025-10-21', '', 1, '2025-10-09 10:55:29', 1),
(11, 6, 6, 8, '2025-10-07', '', 1, '2025-10-09 11:02:05', 1),
(12, 6, 6, 9, '2025-10-15', '', 1, '2025-10-09 11:02:26', 1),
(13, 2, 2, 9, '2025-10-10', '', 1, '2025-10-09 11:33:57', 0),
(14, 5, 5, 9, '2025-10-11', 'kdsa', 1, '2025-10-09 11:34:04', 1),
(15, 4, 4, 9, '2025-10-11', 'dsa', 1, '2025-10-09 11:34:14', 1),
(16, 5, 5, 10, '2025-10-11', '', 1, '2025-10-09 11:34:47', 1),
(17, 2, 2, 10, '2025-10-11', '', 1, '2025-10-09 11:35:02', 0),
(18, 2, 2, 11, '2025-10-11', '', 1, '2025-10-09 11:35:08', 0),
(19, 4, 4, 14, '2025-10-11', '', 1, '2025-10-09 11:35:14', 1),
(20, 4, 4, 10, '2025-10-18', '', 1, '2025-10-09 11:35:37', 1),
(21, 4, 4, 11, '2025-12-18', '', 1, '2025-10-09 11:35:50', 1),
(22, 6, 6, 14, '2025-10-31', '', 1, '2025-10-09 11:40:20', 1),
(23, 5, 5, 14, '2025-10-11', '', 1, '2025-10-09 11:44:29', 1),
(24, 2, 2, 14, '2025-10-04', '', 1, '2025-10-09 11:44:34', 0),
(25, 6, 6, 11, '2025-10-15', '', 1, '2025-10-09 11:48:23', 1),
(26, 1, 1, 8, '2025-10-18', '', 1, '2025-10-09 13:20:49', 1),
(27, 6, 6, 10, '2026-01-01', '', 1, '2025-10-09 13:21:28', 1),
(28, 5, 5, 11, '2025-10-31', '', 1, '2025-10-09 13:21:33', 1),
(29, 7, 7, 8, '2025-10-01', '', 1, '2025-10-09 13:26:30', 1),
(30, 7, 7, 9, '2025-10-11', '', 1, '2025-10-09 13:38:34', 1),
(31, 2, 2, 8, '2025-10-09', '', 1, '2025-10-09 13:50:03', 1),
(32, 2, 2, 9, '2025-10-10', '', 1, '2025-10-09 15:43:46', 0),
(33, 2, 2, 9, '2025-10-11', '', 1, '2025-10-10 13:49:17', 1),
(34, 9, 9, 8, '2025-10-02', '', 1, '2025-10-10 13:49:33', 0),
(35, 9, 9, 8, '2025-10-18', '', 1, '2025-10-10 13:50:31', 1);

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
(1, 'CM LOGISTICS', 'Sin Contacto', 1),
(2, 'Sin Forwarder', 'Sin Forwarder', 1),
(3, 'NEXA', 'Sin Contacto', 1),
(4, 'SOLEX', 'Sin Contacto', 1),
(5, 'OSEAPEX', 'Sin Contacto', 1),
(6, 'EZ HOLDING', 'Sin Contacto', 1),
(7, 'YIRI UNIQUE', 'Sin Contacto', 1),
(8, 'RAINBOW', 'Sin Contacto', 1),
(9, 'HLS HONOUR LINE', 'Sin Contacto', 1);

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
(1, 'EVERGREEN', '66449136156', 1),
(2, 'CGEA', '6645941', 1),
(3, 'CMA', '6644913156', 0),
(4, 'Sin Naviera', 'Sin Naviera', 1),
(5, 'PIL', 'Sin Contacto', 1),
(6, 'MSC', 'Sin Contacto', 1),
(7, 'CMA CGM', 'Sin Contacto', 1),
(8, 'COSCO SHIPPING', 'Sin Contacto', 1),
(9, 'WAN HAI', 'Sin Contacto', 1),
(10, 'APL', 'Sin Contacto', 1),
(11, 'MAERSK', 'Sin Contacto', 1),
(12, 'HMM', 'Sin Contacto', 1),
(13, 'YANG MING', 'Sin Contacto', 1),
(14, 'ONE LINE', 'Sin Contacto', 1),
(15, 'HAPAG LLOYD', 'Sin Contacto', 1),
(16, 'ZIM', 'Sin Contacto', 1);

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
(1, 'LBMF-04', 11, 24, '2025-09-08', '2025-09-19', '132456789', 0, 1, 3, 5, 10, 1, NULL),
(2, 'LBMF-05', 11, 24, '2025-09-25', '2025-09-30', '789456132', 0, 2, 1, 5, 15, 6, 'dsa'),
(3, 'EN-02', 1, 21, '2025-09-24', '2025-09-25', '132456', 0, 2, 3, 9, 10, 1, NULL),
(4, 'LBMF-06', 11, 24, '2025-09-16', '2025-09-24', '132456789', 0, 1, 3, 9, 7, 9, 'KJHGFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'),
(5, 'LBMF-07', 11, 24, '2025-08-19', '2025-09-08', 'OOLU2310497925', 0, 2, 12, 9, 4, 2, '357 / 357'),
(6, 'LBMF-08', 11, 24, '2025-10-01', '2025-10-16', '1328987457897', 0, 2, 3, 9, 4, 2, '357/357'),
(7, 'NT-01', 11, 3, '2025-09-29', '2025-10-14', '8547984125', 0, 2, 3, 9, 4, 2, NULL),
(8, 'LBMF-09', 11, 24, '2025-10-08', '2025-10-17', '879789456', 0, 1, 3, 9, 10, 1, NULL),
(9, 'NT-02', 11, 3, '2025-09-30', '2025-10-07', '854235467', 0, 1, 3, 9, 10, 1, '1255/1255');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operaciones_ferroviarias`
--

CREATE TABLE `operaciones_ferroviarias` (
  `id_operacion_ferro` int(11) NOT NULL,
  `numero_operacion` varchar(50) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `destino_id` int(11) DEFAULT NULL,
  `transportista_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `estatus_id` int(11) NOT NULL DEFAULT 9,
  `comentarios` text DEFAULT NULL,
  `bultos_total` int(11) DEFAULT 0,
  `tipo_operacion_id` int(11) DEFAULT 2,
  `subtipo_operacion_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `operaciones_ferroviarias`
--

INSERT INTO `operaciones_ferroviarias` (`id_operacion_ferro`, `numero_operacion`, `cliente_id`, `contenedor_fisico_id`, `destino_id`, `transportista_id`, `fecha`, `estatus_id`, `comentarios`, `bultos_total`, `tipo_operacion_id`, `subtipo_operacion_id`, `created_at`, `creado_por`, `updated_at`) VALUES
(1, 'FO-01', 1, 584, 10, 4, '2025-09-25', 7, NULL, 58, 2, 26, '2025-09-26 12:09:02', 1, '2025-10-02 13:33:18'),
(2, 'FO-02', 1, 588, 5, 4, '2025-09-26', 9, 'jj', 50, 2, 26, '2025-09-26 12:30:55', 1, '2025-09-29 12:45:48'),
(3, 'FO-03', 1, 575, 10, 4, '2025-09-29', 9, NULL, 10, 2, 26, '2025-09-26 12:38:32', 1, '2025-09-26 12:38:32'),
(4, 'FO-04', 1, 586, 10, 4, '2025-09-30', 1, NULL, 10, 2, 26, '2025-09-26 12:39:54', 1, '2025-09-29 11:59:45'),
(5, 'FO-05', 1, 365, 10, 4, '2025-09-28', 9, NULL, 10, 2, 26, '2025-09-26 12:48:44', 1, '2025-09-29 11:36:03'),
(6, 'FO-06', 1, 365, 10, 4, '2025-09-25', 9, NULL, 10, 2, 26, '2025-09-26 12:49:39', 1, '2025-09-26 12:49:39'),
(7, 'FO-07', 1, 589, 10, 4, '2025-09-30', 9, NULL, 35, 2, 26, '2025-09-26 13:08:50', 1, '2025-09-26 13:08:50'),
(8, 'FO-08', 1, 580, 10, 4, '2025-09-30', 10, NULL, 120, 2, 26, '2025-09-26 13:18:49', 1, '2025-09-29 15:14:10'),
(9, 'FO-09', 1, 397, 10, 4, '2025-09-25', 9, NULL, 37, 2, 26, '2025-09-26 13:58:21', 1, '2025-09-26 13:58:21'),
(10, 'FO-10', 1, 581, 10, 4, '2025-09-29', 9, NULL, 120, 2, 26, '2025-09-29 09:51:25', 1, '2025-09-29 09:51:25'),
(11, 'FO-11', 1, 580, 10, 4, '2025-09-25', 9, NULL, 55, 2, 26, '2025-09-29 09:56:52', 1, '2025-09-29 09:56:52'),
(12, 'FO-12', 1, 397, 10, 4, '2025-09-30', 5, NULL, 25, 2, 26, '2025-09-29 10:17:13', 1, '2025-09-29 11:32:21'),
(13, 'FO-13', 1, 576, 10, 4, '2025-09-23', 9, NULL, 22, 2, 26, '2025-09-29 10:27:04', 1, '2025-09-29 10:27:04'),
(14, 'FO-14', 1, 397, 10, 4, '2025-09-30', 1, NULL, 25, 2, 26, '2025-09-29 10:39:04', 1, '2025-09-29 11:59:38'),
(15, 'FO-15', 1, 516, 10, 4, '2025-09-30', 9, NULL, 130, 2, 26, '2025-09-29 14:44:42', 1, '2025-09-29 14:44:42'),
(16, 'FO-16', 3, 418, 4, 4, '2025-09-30', 7, NULL, 480, 2, 26, '2025-09-29 17:05:05', 1, '2025-09-29 17:05:37'),
(17, 'FO-17', 3, 449, 1, 4, '2025-10-13', 9, NULL, 100, 2, 26, '2025-10-01 16:19:26', 1, '2025-10-01 16:19:26'),
(18, 'FO-18', 3, 447, 10, 4, '2025-10-10', 9, NULL, 209, 2, 26, '2025-10-02 11:28:11', 1, '2025-10-02 11:28:11'),
(19, 'FO-19', 3, 587, 10, 4, '2025-10-15', 9, NULL, 650, 2, 26, '2025-10-03 16:58:49', 1, '2025-10-03 16:58:49'),
(20, 'FO-20', 12, 590, 10, 4, '2025-09-19', 9, NULL, 357, 2, 26, '2025-10-08 17:39:12', 1, '2025-10-08 17:39:12'),
(21, 'FO-21', 3, 591, 4, 4, '2025-10-09', 9, NULL, 300, 2, 26, '2025-10-08 17:52:49', 1, '2025-10-08 17:52:49'),
(22, 'FO-22', 3, 592, 10, 4, '2025-10-09', 9, NULL, 151, 2, 26, '2025-10-09 15:45:15', 1, '2025-10-09 15:46:18'),
(23, 'FO-23', 3, 583, 10, 4, '2025-09-30', 9, NULL, 255, 2, 26, '2025-10-10 12:45:00', 1, '2025-10-10 12:45:00');

--
-- Disparadores `operaciones_ferroviarias`
--
DELIMITER $$
CREATE TRIGGER `trg_operacion_ferro_numero` BEFORE INSERT ON `operaciones_ferroviarias` FOR EACH ROW BEGIN
    IF NEW.numero_operacion IS NULL OR NEW.numero_operacion = '' THEN
        CALL GenerarNumeroOperacionFerro(NEW.subtipo_operacion_id, @nuevo_numero);
        SET NEW.numero_operacion = @nuevo_numero;
    END IF;
END
$$
DELIMITER ;

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
(1, 1, 1, 'creacion', 'Operación creada', '2025-09-26 11:27:40'),
(2, 2, 1, 'creacion', 'Operación creada', '2025-09-26 11:28:15'),
(3, 1, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-26 12:49:52'),
(4, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-26 12:49:57'),
(5, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 09:54:25'),
(6, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 09:54:31'),
(7, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 10:27:43'),
(8, 3, 1, 'creacion', 'Operación creada', '2025-09-29 12:14:16'),
(9, 3, 1, 'creacion', 'Operación creada (num=\'\', subtipo_id=21, contenedores=1)', '2025-09-29 12:14:16'),
(10, 3, 1, 'actualizacion', 'Operación actualizada (num=\'EN-02\', estatus_id=5)', '2025-09-29 12:14:25'),
(11, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 12:39:38'),
(12, 1, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 12:39:45'),
(13, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 12:44:57'),
(14, 2, 1, '', 'Operación actualizada (incluye bultos)', '2025-09-29 12:45:21'),
(15, 3, 1, 'creacion', 'Movimiento de operación creado (costo_id=1, tipo_id=17, tipo=gasto, monto=250, moneda=DLLS, coment=)', '2025-09-29 12:53:54'),
(16, 3, 1, 'creacion', 'Movimiento de operación creado (costo_id=2, tipo_id=20, tipo=abono, monto=300, moneda=PESOS, coment=)', '2025-09-29 12:54:03'),
(17, 3, 1, 'actualizacion', 'Movimiento de operación actualizado (costo_id=1, tipo_id=17, tipo=gasto, monto=20, moneda=DLLS, coment=)', '2025-09-29 12:54:08'),
(18, 3, 1, 'actualizacion', 'Movimiento de operación actualizado (costo_id=1, tipo_id=17, tipo=gasto, monto=10, moneda=DLLS, coment=)', '2025-09-29 12:54:16'),
(19, 3, 1, 'actualizacion', 'Operación actualizada (num=\'EN-02\', estatus_id=9)', '2025-09-29 13:48:50'),
(20, 3, 1, 'creacion', 'Documento subido (doc_tipo_id=3, archivo=Certificados_Semanal_2025-09-21_a_2025-09-27.pdf, ruta=EN-02/MG00000002/EN-02_MG00000002_db3a6126aa80_Certificados_Semanal_2025-09-21_a_2025-09-27.pdf, cmo_id=3)', '2025-09-29 14:21:51'),
(21, 3, 1, 'creacion', 'Documento subido (doc_tipo_id=4, archivo=Informe_Graficos_2025-09_2025-09-27 (1).pdf, ruta=EN-02/MG00000002/EN-02_MG00000002_50a57f6f1d5c_Informe_Graficos_2025-09_2025-09-27__1_.pdf, cmo_id=3)', '2025-09-29 14:23:05'),
(22, 3, 1, 'actualizacion', 'Notificación de faltantes enviada (faltantes=5, destino=arzateoscar33@gmail.com)', '2025-09-29 14:29:31'),
(23, 3, 1, 'creacion', 'Documento subido (doc_tipo_id=4, archivo=Certificados_Semanal_2025-09-21_a_2025-09-27.pdf, ruta=EN-02/MG00000002/EN-02_MG00000002_3c40b41d0ef6_Certificados_Semanal_2025-09-21_a_2025-09-27.pdf, cmo_id=3)', '2025-09-29 14:33:54'),
(24, 3, 1, 'creacion', 'Documento subido (doc_tipo_id=5, archivo=Informe_Graficos_2025-09_2025-09-27 (1).pdf, ruta=EN-02/MG00000002/EN-02_MG00000002_b813f775e67f_Informe_Graficos_2025-09_2025-09-27__1_.pdf, cmo_id=3)', '2025-09-29 14:34:04'),
(25, 3, 1, 'actualizacion', 'Notificación de faltantes enviada (faltantes=4, destino=arzateoscar33@gmail.com)', '2025-09-29 14:35:13'),
(26, 3, 1, 'actualizacion', 'Notificación faltantes por contenedor enviada (faltantes=4, destino=arzateoscar33@gmail.com, contenedor=MG00000002)', '2025-09-29 14:39:27'),
(27, 3, 1, 'actualizacion', 'Notificación faltantes por contenedor enviada (faltantes=4, destino=arzateoscar33@gmail.com, contenedor=MG00000002)', '2025-09-29 14:41:18'),
(28, 3, 1, 'creacion', 'Movimiento de operación creado (costo_id=3, tipo_id=17, tipo=gasto, monto=3, moneda=DLLS, coment=)', '2025-09-29 14:54:45'),
(29, 3, 1, 'creacion', 'Evento creado (id_evento=1, tipo_evt_id=8, fecha=2025-09-23, cont_tipo=MARITIMO, cont_ref=3)', '2025-09-29 15:12:52'),
(30, 3, 1, 'actualizacion', 'Evento actualizado (id_evento=1, tipo_evt_id=8, fecha=2025-09-23, cont_tipo=MARITIMO, cont_ref=3)', '2025-09-29 15:12:57'),
(31, 3, 1, 'actualizacion', 'Evento actualizado (id_evento=1, tipo_evt_id=9, fecha=2025-09-23, cont_tipo=MARITIMO, cont_ref=3)', '2025-09-29 15:13:02'),
(32, 3, 1, 'actualizacion', 'Evento actualizado (id_evento=1, tipo_evt_id=11, fecha=2025-09-23, cont_tipo=MARITIMO, cont_ref=3)', '2025-09-29 15:13:14'),
(33, 3, 1, 'actualizacion', 'Evento actualizado (id_evento=1, tipo_evt_id=8, fecha=2025-09-23, cont_tipo=MARITIMO, cont_ref=3)', '2025-09-29 15:14:22'),
(34, 4, 1, 'creacion', 'Operación creada', '2025-09-29 16:45:29'),
(35, 3, 1, 'creacion', 'Evento creado (id_evento=2, tipo_evt_id=9, fecha=2025-09-10, cont_tipo=MARITIMO, cont_ref=3)', '2025-09-29 17:06:49'),
(36, 4, 1, '', 'Operación actualizada (incluye bultos)', '2025-10-02 11:28:46'),
(37, 4, 1, '', 'Operación actualizada (incluye bultos)', '2025-10-02 11:29:07'),
(38, 1, 1, 'cancelacion', 'Costo de operación FERRO desactivado (costo_id=14, tipo_id=23, monto=125.00, moneda=PESOS)', '2025-10-02 16:07:13'),
(39, 1, 1, 'cancelacion', 'Costo de operación FERRO desactivado (costo_id=16, tipo_id=23, monto=125.00, moneda=PESOS)', '2025-10-02 16:07:17'),
(40, 1, 1, 'cancelacion', 'Costo de operación FERRO desactivado (costo_id=17, tipo_id=23, monto=365.00, moneda=PESOS)', '2025-10-02 16:07:23'),
(41, 1, 1, 'cancelacion', 'Costo de operación FERRO desactivado (costo_id=15, tipo_id=23, monto=365.00, moneda=PESOS)', '2025-10-02 16:07:27'),
(46, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=3, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=SAT (1).pdf, ruta=LBMF-06_Documentos/MG00132564/9ca4740125ec278f_SAT__1_.pdf, size=140355)', '2025-10-07 17:34:21'),
(47, 4, 1, 'cancelacion', 'Documento eliminado (doc_id=5, doc_tipo_id=3, cont_tipo=MARITIMO, cont_ref=4, archivo=SAT (1).pdf)', '2025-10-07 17:34:31'),
(48, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=SAT (1).pdf, ruta=LBMF-06_Documentos/MG00132564/e6cf8e8155f1721e_SAT__1_.pdf, size=140355)', '2025-10-07 17:34:42'),
(49, 4, 1, 'cancelacion', 'Documento eliminado (doc_id=6, doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=4, archivo=SAT (1).pdf)', '2025-10-07 17:34:57'),
(50, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=8, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=SAT (1).pdf, ruta=LBMF-06_Documentos/MG00132564/151a414f0648060f_SAT__1_.pdf, size=140355)', '2025-10-07 17:36:39'),
(51, 4, 1, 'cancelacion', 'Documento eliminado (doc_id=7, doc_tipo_id=8, cont_tipo=MARITIMO, cont_ref=4, archivo=SAT (1).pdf)', '2025-10-07 17:36:54'),
(52, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=6, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=Informe_Graficos_2025-10_2025-10-06.pdf, ruta=LBMF-06_Documentos/MG00132564/02da2b28dd388fd3_Informe_Graficos_2025-10_2025-10-06.pdf, size=18625277)', '2025-10-07 17:37:09'),
(53, 2, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00000017, archivo=Informe_Graficos_2025-10_2025-10-06.pdf, ruta=LBMF-05_Documentos/MG00000017/32f0a7e7fae0e39b_Informe_Graficos_2025-10_2025-10-06.pdf, size=18625277)', '2025-10-07 18:06:27'),
(54, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=SAT (1).pdf, ruta=LBMF-06_Documentos/MG00132564/a72fcf564177180e_SAT__1_.pdf, size=140355)', '2025-10-07 18:32:00'),
(55, 4, 1, 'cancelacion', 'Documento eliminado (doc_id=10, doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=4, archivo=SAT (1).pdf)', '2025-10-07 18:32:12'),
(56, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=01_merged.pdf, ruta=LBMF-06_Documentos/MG00132564/2aaf373594e29f72_01_merged.pdf, size=905736)', '2025-10-07 18:37:54'),
(57, 4, 1, 'cancelacion', 'Documento eliminado (doc_id=11, doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=4, archivo=01_merged.pdf)', '2025-10-07 18:52:23'),
(58, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=Informe_Graficos_2025-10_2025-10-06.pdf, ruta=LBMF-06_Documentos/MG00132564/b176febdfd39ebe1_Informe_Graficos_2025-10_2025-10-06.pdf, size=18625277)', '2025-10-07 18:52:29'),
(59, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=6, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=SAT (1).pdf, ruta=LBMF-06_Documentos/MG00132564/84c836125704cbb5_SAT__1_.pdf, size=140355)', '2025-10-07 18:59:45'),
(61, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=01_merged.pdf, ruta=LBMF-06_Documentos/MG00132564/9b7f8beb709739c9_01_merged.pdf, size=905736)', '2025-10-07 19:07:48'),
(64, 4, 1, 'creacion', 'Documento subido (doc_tipo_id=4, cont_tipo=MARITIMO, cont_ref=MG00132564, archivo=SAT (1).pdf, ruta=LBMF-06_Documentos/MG00132564/ac6da51a49b73cbf_SAT__1_.pdf, size=140355)', '2025-10-07 19:13:36'),
(67, 5, 1, 'creacion', 'Operación creada', '2025-10-08 17:22:37'),
(68, 5, 1, '', 'Operación actualizada (incluye bultos)', '2025-10-08 17:23:58'),
(69, 5, 1, '', 'Operación actualizada (incluye bultos)', '2025-10-08 17:36:54'),
(70, 6, 1, 'creacion', 'Operación creada', '2025-10-08 17:51:38'),
(71, 1, 1, 'creacion', 'Evento creado (id_evento=3, operacion=1, cmo=1, tipo_evt_id=8, fecha=2025-10-01)', '2025-10-09 10:31:11'),
(72, 1, 1, 'creacion', 'Evento creado (id_evento=4, operacion=1, cmo=1, tipo_evt_id=9, fecha=2025-10-16)', '2025-10-09 10:53:46'),
(73, 1, 1, 'creacion', 'Evento creado (id_evento=5, operacion=1, cmo=1, tipo_evt_id=10, fecha=2025-10-09)', '2025-10-09 10:54:00'),
(74, 1, 1, 'creacion', 'Evento creado (id_evento=6, operacion=1, cmo=1, tipo_evt_id=11, fecha=2025-10-19)', '2025-10-09 10:54:11'),
(75, 1, 1, 'creacion', 'Evento creado (id_evento=7, operacion=1, cmo=1, tipo_evt_id=14, fecha=2025-10-21)', '2025-10-09 10:54:22'),
(76, 2, 1, 'creacion', 'Evento creado (id_evento=8, operacion=2, cmo=2, tipo_evt_id=8, fecha=2025-10-01)', '2025-10-09 10:54:39'),
(77, 4, 1, 'creacion', 'Evento creado (id_evento=9, operacion=4, cmo=4, tipo_evt_id=8, fecha=2025-10-06)', '2025-10-09 10:55:13'),
(78, 5, 1, 'creacion', 'Evento creado (id_evento=10, operacion=5, cmo=5, tipo_evt_id=8, fecha=2025-10-05)', '2025-10-09 10:55:29'),
(79, 6, 1, 'creacion', 'Evento creado (id_evento=11, operacion=6, cmo=6, tipo_evt_id=8, fecha=2025-10-07)', '2025-10-09 11:02:05'),
(80, 6, 1, 'creacion', 'Evento creado (id_evento=12, operacion=6, cmo=6, tipo_evt_id=9, fecha=2025-10-15)', '2025-10-09 11:02:26'),
(81, 2, 1, 'creacion', 'Evento creado (id_evento=13, operacion=2, cmo=2, tipo_evt_id=9, fecha=2025-10-10)', '2025-10-09 11:33:57'),
(82, 5, 1, 'creacion', 'Evento creado (id_evento=14, operacion=5, cmo=5, tipo_evt_id=9, fecha=2025-10-11)', '2025-10-09 11:34:04'),
(83, 4, 1, 'creacion', 'Evento creado (id_evento=15, operacion=4, cmo=4, tipo_evt_id=9, fecha=2025-10-11)', '2025-10-09 11:34:14'),
(84, 5, 1, 'creacion', 'Evento creado (id_evento=16, operacion=5, cmo=5, tipo_evt_id=10, fecha=2025-10-11)', '2025-10-09 11:34:47'),
(85, 2, 1, 'creacion', 'Evento creado (id_evento=17, operacion=2, cmo=2, tipo_evt_id=10, fecha=2025-10-11)', '2025-10-09 11:35:02'),
(86, 2, 1, 'creacion', 'Evento creado (id_evento=18, operacion=2, cmo=2, tipo_evt_id=11, fecha=2025-10-11)', '2025-10-09 11:35:08'),
(87, 4, 1, 'creacion', 'Evento creado (id_evento=19, operacion=4, cmo=4, tipo_evt_id=14, fecha=2025-10-11)', '2025-10-09 11:35:14'),
(88, 4, 1, 'creacion', 'Evento creado (id_evento=20, operacion=4, cmo=4, tipo_evt_id=10, fecha=2025-10-18)', '2025-10-09 11:35:37'),
(89, 4, 1, 'creacion', 'Evento creado (id_evento=21, operacion=4, cmo=4, tipo_evt_id=11, fecha=2025-10-11)', '2025-10-09 11:35:50'),
(90, 6, 1, 'creacion', 'Evento creado (id_evento=22, operacion=6, cmo=6, tipo_evt_id=14, fecha=2025-10-07)', '2025-10-09 11:40:20'),
(91, 5, 1, 'creacion', 'Evento creado (id_evento=23, operacion=5, cmo=5, tipo_evt_id=14, fecha=2025-10-11)', '2025-10-09 11:44:29'),
(92, 2, 1, 'creacion', 'Evento creado (id_evento=24, operacion=2, cmo=2, tipo_evt_id=14, fecha=2025-10-04)', '2025-10-09 11:44:34'),
(93, 6, 1, 'creacion', 'Evento creado (id_evento=25, operacion=6, cmo=6, tipo_evt_id=11, fecha=2025-10-15)', '2025-10-09 11:48:23'),
(94, 4, 1, 'actualizacion', 'Evento actualizado (id_evento=21, operacion=4, cmo=4, tipo_evt_id=11, fecha=2025-12-18)', '2025-10-09 11:49:16'),
(95, 6, 1, 'actualizacion', 'Evento actualizado (id_evento=22, operacion=6, cmo=6, tipo_evt_id=14, fecha=2025-10-31)', '2025-10-09 11:49:22'),
(96, 5, 1, 'actualizacion', 'Evento actualizado (id_evento=10, operacion=5, cmo=5, tipo_evt_id=8, fecha=2025-10-02)', '2025-10-09 12:10:04'),
(97, 5, 1, 'actualizacion', 'Evento actualizado (id_evento=10, operacion=5, cmo=5, tipo_evt_id=8, fecha=2025-10-21)', '2025-10-09 12:10:09'),
(98, 1, 1, 'actualizacion', 'Evento actualizado (id_evento=3, operacion=1, cmo=1, tipo_evt_id=8, fecha=2025-12-01)', '2025-10-09 12:38:53'),
(99, 1, 1, 'actualizacion', 'Evento actualizado (id_evento=4, operacion=1, cmo=1, tipo_evt_id=9, fecha=2025-10-11)', '2025-10-09 13:20:34'),
(100, 1, 1, 'actualizacion', 'Evento actualizado (id_evento=3, operacion=1, cmo=1, tipo_evt_id=8, fecha=2025-12-18)', '2025-10-09 13:20:41'),
(101, 1, 1, 'creacion', 'Evento creado (id_evento=26, operacion=1, cmo=1, tipo_evt_id=8, fecha=2025-10-18)', '2025-10-09 13:20:49'),
(102, 6, 1, 'creacion', 'Evento creado (id_evento=27, operacion=6, cmo=6, tipo_evt_id=10, fecha=2026-01-01)', '2025-10-09 13:21:28'),
(103, 5, 1, 'creacion', 'Evento creado (id_evento=28, operacion=5, cmo=5, tipo_evt_id=11, fecha=2025-10-31)', '2025-10-09 13:21:33'),
(104, 7, 1, 'creacion', 'Operación creada', '2025-10-09 13:26:17'),
(105, 7, 1, 'creacion', 'Evento creado (id_evento=29, operacion=7, cmo=7, tipo_evt_id=8, fecha=2025-10-01)', '2025-10-09 13:26:30'),
(106, 7, 1, 'creacion', 'Evento creado (id_evento=30, operacion=7, cmo=7, tipo_evt_id=9, fecha=2025-10-11)', '2025-10-09 13:38:34'),
(108, 2, 1, 'creacion', 'Evento creado (id_evento=31, operacion=2, cmo=2, tipo_evt_id=8, fecha=2025-10-09)', '2025-10-09 13:50:03'),
(109, 2, 1, 'creacion', 'Evento creado (id_evento=32, operacion=2, cmo=2, tipo_evt_id=9, fecha=2025-10-10)', '2025-10-09 15:43:46'),
(110, 1, 1, 'creacion', 'Evento FER creado (id_evento=2, op_ferro=1, ferro_id=584, tipo_evt=4, fecha=2025-10-10)', '2025-10-10 12:40:36'),
(111, 2, 1, 'creacion', 'Evento FER creado (id_evento=3, op_ferro=2, ferro_id=588, tipo_evt=3, fecha=2025-10-09)', '2025-10-10 12:41:00'),
(112, 7, 1, 'creacion', 'Evento FER creado (id_evento=4, op_ferro=7, ferro_id=589, tipo_evt=7, fecha=2025-10-29)', '2025-10-10 12:41:24'),
(113, 8, 1, 'creacion', 'Operación creada', '2025-10-10 12:42:07'),
(114, 6, 1, 'creacion', 'Evento FER creado (id_evento=5, op_ferro=6, ferro_id=365, tipo_evt=12, fecha=2025-10-08)', '2025-10-10 12:46:29'),
(115, 9, 1, 'creacion', 'Operación creada', '2025-10-10 13:48:42'),
(116, 2, 1, 'creacion', 'Evento creado (id_evento=33, operacion=2, cmo=2, tipo_evt_id=9, fecha=2025-10-11)', '2025-10-10 13:49:17'),
(117, 9, 1, 'creacion', 'Evento creado (id_evento=34, operacion=9, cmo=9, tipo_evt_id=8, fecha=2025-10-02)', '2025-10-10 13:49:33'),
(120, 9, 1, 'creacion', 'Evento creado (id_evento=35, operacion=9, cmo=9, tipo_evt_id=8, fecha=2025-10-18)', '2025-10-10 13:50:31');

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
(23, 16, 2, '2025-08-27 18:45:35'),
(25, 3, 1, '2025-09-15 20:46:55'),
(27, 7, 2, '2025-09-17 17:49:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutas_ferro`
--

CREATE TABLE `rutas_ferro` (
  `id_ruta` int(11) NOT NULL,
  `operacion_ferro_id` int(11) NOT NULL,
  `contenedor_fisico_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estatus` int(5) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rutas_ferro`
--

INSERT INTO `rutas_ferro` (`id_ruta`, `operacion_ferro_id`, `contenedor_fisico_id`, `cliente_id`, `comentario`, `created_at`, `updated_at`, `estatus`) VALUES
(1, 1, 584, NULL, NULL, '2025-10-02 13:24:28', '2025-10-03 16:35:28', 0),
(2, 18, 447, NULL, NULL, '2025-10-02 15:40:16', '2025-10-03 16:53:11', 0),
(3, 13, 576, NULL, NULL, '2025-10-02 16:22:57', NULL, 1),
(4, 2, 588, NULL, NULL, '2025-10-02 16:28:40', '2025-10-03 16:47:53', 0),
(5, 3, 575, NULL, NULL, '2025-10-02 16:50:24', NULL, 1),
(6, 4, 586, NULL, NULL, '2025-10-02 16:50:58', NULL, 1),
(7, 10, 581, NULL, NULL, '2025-10-03 15:22:18', NULL, 1),
(8, 1, 584, NULL, NULL, '2025-10-03 16:43:36', '2025-10-03 16:54:53', 0),
(9, 15, 516, NULL, NULL, '2025-10-08 17:30:20', '2025-10-08 17:31:43', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutas_ferro_tramos`
--

CREATE TABLE `rutas_ferro_tramos` (
  `id_tramo` int(11) NOT NULL,
  `ruta_id` int(11) NOT NULL,
  `orden` int(11) NOT NULL,
  `origen_id` int(11) NOT NULL,
  `destino_id` int(11) NOT NULL,
  `transportista_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comentario` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `estatus` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rutas_ferro_tramos`
--

INSERT INTO `rutas_ferro_tramos` (`id_tramo`, `ruta_id`, `orden`, `origen_id`, `destino_id`, `transportista_id`, `monto`, `comentario`, `created_at`, `estatus`) VALUES
(1, 1, 0, 2, 1, 4, 125.00, 'bajo a tiempo', '2025-10-02 13:24:28', 0),
(2, 1, 2, 1, 10, 4, 365.00, NULL, '2025-10-02 13:24:28', 0),
(5, 2, 0, 6, 2, 4, 1358.00, NULL, '2025-10-02 15:40:16', 0),
(6, 2, 2, 2, 1, 2, 230.00, NULL, '2025-10-02 15:40:16', 0),
(7, 2, 3, 1, 10, 4, 350.00, NULL, '2025-10-02 15:40:16', 0),
(8, 2, 4, 10, 5, 4, 300.00, NULL, '2025-10-02 15:40:16', 0),
(9, 2, 1, 5, 11, 4, 200.00, NULL, '2025-10-02 15:43:08', 0),
(10, 1, 1, 10, 5, 2, 123.00, NULL, '2025-10-02 15:43:42', 0),
(13, 1, 3, 10, 11, 2, 12.00, NULL, '2025-10-02 16:03:43', 0),
(14, 3, 0, 6, 2, 4, 258.00, NULL, '2025-10-02 16:22:57', 1),
(15, 3, 1, 2, 1, 4, 1.00, NULL, '2025-10-02 16:26:48', 1),
(16, 3, 2, 1, 10, 4, 2.00, NULL, '2025-10-02 16:27:23', 1),
(17, 4, 0, 6, 2, 4, 125.00, NULL, '2025-10-02 16:28:40', 0),
(18, 4, 2, 2, 1, 4, 3.00, 'fsa', '2025-10-02 16:29:14', 0),
(19, 4, 3, 1, 5, 4, 13.00, NULL, '2025-10-02 16:29:53', 0),
(20, 5, 1, 2, 1, 4, 123.00, NULL, '2025-10-02 16:50:24', 1),
(21, 6, 0, 6, 2, 4, 129.00, NULL, '2025-10-02 16:50:58', 1),
(22, 7, 1, 6, 2, 4, 125.00, NULL, '2025-10-03 15:22:18', 1),
(23, 7, 2, 2, 1, 4, 133.00, NULL, '2025-10-03 15:22:18', 1),
(24, 3, 3, 10, 11, 4, 147.00, NULL, '2025-10-03 15:32:58', 1),
(25, 8, 0, 6, 2, 4, 132.00, NULL, '2025-10-03 16:43:36', 0),
(26, 8, 2, 2, 1, 2, 123.00, NULL, '2025-10-03 16:51:29', 0),
(27, 6, 2, 2, 1, 2, 236.00, NULL, '2025-10-03 16:56:06', 1),
(28, 9, 0, 6, 2, 2, 2500.00, NULL, '2025-10-08 17:30:20', 0),
(29, 9, 2, 2, 1, 4, 3000.00, NULL, '2025-10-08 17:30:20', 0),
(30, 9, 3, 1, 10, 4, 5000.00, NULL, '2025-10-08 17:30:37', 0),
(31, 3, 4, 11, 1, 2, 2851.00, 'retorno', '2025-10-09 15:48:50', 1),
(32, 3, 5, 1, 2, 4, 1322.00, NULL, '2025-10-10 12:48:03', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secuencias_operacion`
--

CREATE TABLE `secuencias_operacion` (
  `subtipo_id` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `valor` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `secuencias_operacion`
--

INSERT INTO `secuencias_operacion` (`subtipo_id`, `anio`, `valor`) VALUES
(1, 2025, 3),
(2, 2025, 1),
(3, 2025, 2),
(20, 2025, 0),
(21, 2025, 2),
(22, 2025, 0),
(23, 2025, 0),
(24, 2025, 9),
(26, 2025, 23);

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
(2, 1, 'LONG_BEACH', 'Long Beach Maritimo', 'LBM', 0, 0, 1, '{\"campos_visibles\": [\"numero_bl\", \"puerto_arribo_id\"]}', 1),
(3, 11, 'NUEVO', 'Nuevo Tipo', 'NT', 1, 1, 4, '{\"campos_visibles\": [\"numero_bl\", \"puerto_arribo_id\"]}', 1),
(20, 2, 'TIJUANA', 'Tijuana', 'TJ', 0, 0, NULL, NULL, 1),
(21, 1, 'ENSENADA', 'Ensenada', 'EN', 0, 0, 7, NULL, 1),
(22, 2, 'LONG_BEACH_FERRO', 'Long Beach Ferro', 'LBF', 0, 0, 1, NULL, 0),
(23, 2, 'TIJUANA_TERRESTRE', 'Tijuana Terrestre', 'TJT', 0, 0, NULL, NULL, 1),
(24, 11, 'LONG_BEACH_MARITIMO_FERRO', 'Long Beach', 'LBMF', 0, 0, 1, NULL, 1),
(26, 2, 'FERRO_GENERAL', 'Operación Ferroviaria General', 'FO', 0, 0, NULL, NULL, 1);

--
-- Disparadores `subtipos_operacion`
--
DELIMITER $$
CREATE TRIGGER `trg_subtipo_ai` AFTER INSERT ON `subtipos_operacion` FOR EACH ROW BEGIN
  INSERT INTO secuencias_operacion (subtipo_id, anio, valor)
  VALUES (NEW.id_subtipo, YEAR(CURDATE()), 0)
  ON DUPLICATE KEY UPDATE valor = valor; -- no cambia nada si ya existía
END
$$
DELIMITER ;

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
(3, 'EIR', 'EIR', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-24 21:27:16'),
(4, 'ARRIBO', 'Notificación de Arribo', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-24 21:27:11'),
(5, 'REVALIDACION', 'Pago de Revalidación', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-24 21:27:07'),
(6, 'CARGOS', 'Pago de Cargos Locales', '', 'contenedor_maritimo', 1, 1, '2025-08-25 18:31:40', '2025-09-24 21:25:56'),
(7, 'Pruebasasas', 'prueba', '123', 'cualquiera', 1, 0, '2025-08-26 16:18:16', '2025-08-26 16:57:25'),
(8, 'Pruebas', 'prueba', 'Esto es una prueba', 'contenedor_fisico', 1, 1, '2025-08-26 16:54:26', '2025-10-08 01:30:20'),
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
(11, 1, 'Fecha Cruce', 1),
(12, 2, 'Arribo a Puerto', 1),
(13, 2, 'Ingresa a Navojoa', 0),
(14, 1, 'Retorno Vacio', 1);

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
(10, 'Bodega', 'gasto', 'PESOS', 11, 1),
(11, 'Broker', 'gasto', 'PESOS', 1, 1),
(12, 'Flete Local', 'gasto', 'PESOS', 1, 1),
(13, 'Flete Ferro', 'gasto', 'PESOS', 2, 1),
(14, 'Estadias', 'gasto', 'PESOS', 2, 1),
(15, 'Comisiones', 'gasto', 'PESOS', 2, 1),
(16, 'Gastos Extra', 'gasto', 'DLLS', 2, 1),
(17, 'Bodega Maritima', 'gasto', 'DLLS', 1, 1),
(18, 'Descargasa', 'gasto', 'PESOS', 2, 0),
(19, 'Ganancia Pesos Terrestre', 'abono', 'PESOS', 2, 1),
(20, 'Ganancia Pesos Maritimo', 'abono', 'PESOS', 1, 1),
(22, 'Demoras', 'gasto', 'PESOS', 1, 1),
(23, 'Transporte', 'gasto', 'PESOS', 2, 1);

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
(11, 'Maritimo-Ferroviario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transportistas`
--

CREATE TABLE `transportistas` (
  `id_transportista` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('terrestre','maritimo','ferroviario') NOT NULL,
  `estatus` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transportistas`
--

INSERT INTO `transportistas` (`id_transportista`, `nombre`, `tipo`, `estatus`) VALUES
(1, 'Jose Camacho', 'terrestre', 1),
(2, 'GCIS', 'ferroviario', 1),
(3, 'AGOE', 'ferroviario', 1),
(4, 'MEAK', 'ferroviario', 1);

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
(1, 'Oscar', 'Arzate', 'arzateoscar33@gmail.com', '$2y$10$95pFqn08R/rcjrm1DQvu2.IeiqYIathuUN9qIhvycPizJydwsX05C', '6644913156', 17, 9, 1, NULL),
(3, 'Giovanny', 'Arzate', 'arzateoscar323@gmail.com', '$2y$10$fI2lN8cytvTW8aUI.thgb.eX0KKAXtzwO.bohFa6ndYE/1NzPncBG', '664989879', 8, 9, 1, '65a683c1275779cf5a339f6632ee845ae1f92b4881ab28928bf8faa808dc838a'),
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

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_operaciones_ferroviarias_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_operaciones_ferroviarias_completa` (
`id_operacion_ferro` int(11)
,`numero_operacion` varchar(50)
,`fecha` date
,`bultos_total` int(11)
,`comentarios` text
,`numero_ferro` varchar(50)
,`cliente_nombre` varchar(150)
,`destino_nombre` varchar(100)
,`transportista_nombre` varchar(100)
,`estatus_nombre` varchar(100)
,`contenedores_maritimos` mediumtext
,`bultos_maritimos_total` decimal(32,0)
,`total_contenedores_maritimos` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_operaciones_ferroviarias_completa`
--
DROP TABLE IF EXISTS `vista_operaciones_ferroviarias_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_operaciones_ferroviarias_completa`  AS SELECT `of`.`id_operacion_ferro` AS `id_operacion_ferro`, `of`.`numero_operacion` AS `numero_operacion`, `of`.`fecha` AS `fecha`, `of`.`bultos_total` AS `bultos_total`, `of`.`comentarios` AS `comentarios`, `cf`.`numero_ferro` AS `numero_ferro`, `c`.`nombre` AS `cliente_nombre`, `ci`.`nombre_ciudad` AS `destino_nombre`, `t`.`nombre` AS `transportista_nombre`, `e`.`nombre` AS `estatus_nombre`, group_concat(distinct `cm`.`numero_contenedor` order by `cm`.`numero_contenedor` ASC separator ', ') AS `contenedores_maritimos`, sum(distinct `cmf`.`bultos_asignados`) AS `bultos_maritimos_total`, count(distinct `cmf`.`contenedor_maritimo_id`) AS `total_contenedores_maritimos` FROM (((((((`operaciones_ferroviarias` `of` left join `contenedores_fisicos` `cf` on(`of`.`contenedor_fisico_id` = `cf`.`id_fisico`)) left join `clientes` `c` on(`of`.`cliente_id` = `c`.`id_cliente`)) left join `ciudades` `ci` on(`of`.`destino_id` = `ci`.`id_ciudad`)) left join `transportistas` `t` on(`of`.`transportista_id` = `t`.`id_transportista`)) left join `estatus` `e` on(`of`.`estatus_id` = `e`.`id_estatus`)) left join `contenedor_maritimo_ferro` `cmf` on(`of`.`id_operacion_ferro` = `cmf`.`operacion_ferro_id`)) left join `contenedores_maritimos` `cm` on(`cmf`.`contenedor_maritimo_id` = `cm`.`id_contenedor_maritimo`)) WHERE `of`.`estatus_id` <> 6 GROUP BY `of`.`id_operacion_ferro` ;

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
  ADD PRIMARY KEY (`id_cliente`);

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
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `idx_contenedores_operacion_fecha_creacion` (`fecha_creacion`);

--
-- Indices de la tabla `contenedor_maritimo_ferro`
--
ALTER TABLE `contenedor_maritimo_ferro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contenedor_maritimo_id` (`contenedor_maritimo_id`),
  ADD KEY `contenedor_fisico_id` (`contenedor_fisico_id`),
  ADD KEY `fk_cmf_cmo` (`cont_maritimo_operacion_id`),
  ADD KEY `idx_contenedor_maritimo_ferro_operacion` (`operacion_ferro_id`);

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
-- Indices de la tabla `costos_operacion_ferro`
--
ALTER TABLE `costos_operacion_ferro`
  ADD PRIMARY KEY (`id_costo_ferro`),
  ADD KEY `fk_costo_ferro_operacion` (`operacion_ferro_id`),
  ADD KEY `fk_costo_ferro_tipo_mov` (`tipo_movimiento_id`),
  ADD KEY `fk_costo_ferro_creado_por` (`creado_por`),
  ADD KEY `idx_costos_ferro_operacion` (`operacion_ferro_id`);

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
  ADD KEY `idx_doc_cont_mar` (`cont_maritimo_operacion_id`),
  ADD KEY `idx_doc_tipo` (`tipo_documento_id`),
  ADD KEY `idx_doc_cont_fisico` (`contenedor_operacion_id`);

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
-- Indices de la tabla `eventos_ferroviarios`
--
ALTER TABLE `eventos_ferroviarios`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `idx_evfer_op` (`operacion_ferro_id`),
  ADD KEY `idx_evfer_ctn` (`contenedor_fisico_id`),
  ADD KEY `idx_evfer_tipo` (`tipo_evento_id`),
  ADD KEY `idx_evfer_fecha` (`fecha`),
  ADD KEY `idx_evfer_status` (`estatus`),
  ADD KEY `idx_evfer_user` (`creado_por`);

--
-- Indices de la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `idx_ev_operacion` (`operacion_id`),
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
  ADD UNIQUE KEY `uq_operaciones_numero` (`numero_operacion`),
  ADD KEY `shipper_id` (`shipper_id`),
  ADD KEY `idx_operaciones_numero` (`numero_operacion`),
  ADD KEY `idx_operaciones_estado` (`estatus_id`),
  ADD KEY `fk_oper_tipo` (`tipo_operacion_id`),
  ADD KEY `fk_oper_subtipo` (`subtipo_operacion_id`),
  ADD KEY `fk_oper_naviera` (`naviera_id`),
  ADD KEY `fk_oper_forwarder` (`forwarder_id`),
  ADD KEY `fk_operaciones_cliente` (`cliente_id`);

--
-- Indices de la tabla `operaciones_ferroviarias`
--
ALTER TABLE `operaciones_ferroviarias`
  ADD PRIMARY KEY (`id_operacion_ferro`),
  ADD KEY `destino_id` (`destino_id`),
  ADD KEY `estatus_id` (`estatus_id`),
  ADD KEY `fk_opferro_fisico` (`contenedor_fisico_id`),
  ADD KEY `fk_opferro_transportista` (`transportista_id`),
  ADD KEY `fk_opferro_tipo` (`tipo_operacion_id`),
  ADD KEY `fk_opferro_subtipo` (`subtipo_operacion_id`),
  ADD KEY `fk_opferro_creado_por` (`creado_por`),
  ADD KEY `idx_operaciones_ferro_cliente` (`cliente_id`),
  ADD KEY `idx_operaciones_ferro_fecha` (`fecha`);

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
-- Indices de la tabla `rutas_ferro`
--
ALTER TABLE `rutas_ferro`
  ADD PRIMARY KEY (`id_ruta`),
  ADD KEY `idx_rf_opferro` (`operacion_ferro_id`),
  ADD KEY `idx_rf_conten` (`contenedor_fisico_id`),
  ADD KEY `idx_rf_created` (`created_at`);

--
-- Indices de la tabla `rutas_ferro_tramos`
--
ALTER TABLE `rutas_ferro_tramos`
  ADD PRIMARY KEY (`id_tramo`),
  ADD UNIQUE KEY `uq_rft_ruta_orden` (`ruta_id`,`orden`),
  ADD KEY `fk_rft_origen` (`origen_id`),
  ADD KEY `fk_rft_destino` (`destino_id`),
  ADD KEY `fk_rft_transp` (`transportista_id`);

--
-- Indices de la tabla `secuencias_operacion`
--
ALTER TABLE `secuencias_operacion`
  ADD PRIMARY KEY (`subtipo_id`,`anio`),
  ADD UNIQUE KEY `uq_secuencia` (`subtipo_id`,`anio`);

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
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id_ciudad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `contenedores_fisicos`
--
ALTER TABLE `contenedores_fisicos`
  MODIFY `id_fisico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=593;

--
-- AUTO_INCREMENT de la tabla `contenedores_maritimos`
--
ALTER TABLE `contenedores_maritimos`
  MODIFY `id_contenedor_maritimo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT de la tabla `contenedores_maritimos_operacion`
--
ALTER TABLE `contenedores_maritimos_operacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `contenedores_operacion`
--
ALTER TABLE `contenedores_operacion`
  MODIFY `id_contenedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedor_maritimo_ferro`
--
ALTER TABLE `contenedor_maritimo_ferro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
-- AUTO_INCREMENT de la tabla `costos_operacion`
--
ALTER TABLE `costos_operacion`
  MODIFY `id_costo_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `costos_operacion_ferro`
--
ALTER TABLE `costos_operacion_ferro`
  MODIFY `id_costo_ferro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `estatus`
--
ALTER TABLE `estatus`
  MODIFY `id_estatus` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `eventos_ferroviarios`
--
ALTER TABLE `eventos_ferroviarios`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `forwarders`
--
ALTER TABLE `forwarders`
  MODIFY `id_forwarder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id_naviera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `operaciones`
--
ALTER TABLE `operaciones`
  MODIFY `id_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `operaciones_ferroviarias`
--
ALTER TABLE `operaciones_ferroviarias`
  MODIFY `id_operacion_ferro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `operaciones_log`
--
ALTER TABLE `operaciones_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

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
  MODIFY `id_rol_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `rutas_ferro`
--
ALTER TABLE `rutas_ferro`
  MODIFY `id_ruta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `rutas_ferro_tramos`
--
ALTER TABLE `rutas_ferro_tramos`
  MODIFY `id_tramo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `shippers`
--
ALTER TABLE `shippers`
  MODIFY `id_shipper` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `subtipos_operacion`
--
ALTER TABLE `subtipos_operacion`
  MODIFY `id_subtipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  MODIFY `id_tipo_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tipos_evento_logistico`
--
ALTER TABLE `tipos_evento_logistico`
  MODIFY `id_tipo_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tipos_movimiento`
--
ALTER TABLE `tipos_movimiento`
  MODIFY `id_tipo_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `tipos_operacion`
--
ALTER TABLE `tipos_operacion`
  MODIFY `id_tipo_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `transportistas`
--
ALTER TABLE `transportistas`
  MODIFY `id_transportista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `fk_cmf_cmo` FOREIGN KEY (`cont_maritimo_operacion_id`) REFERENCES `contenedores_maritimos_operacion` (`id`),
  ADD CONSTRAINT `fk_op_ferro` FOREIGN KEY (`operacion_ferro_id`) REFERENCES `operaciones_ferroviarias` (`id_operacion_ferro`);

--
-- Filtros para la tabla `costos_contenedor_operacion`
--
ALTER TABLE `costos_contenedor_operacion`
  ADD CONSTRAINT `costos_contenedor_operacion_ibfk_1` FOREIGN KEY (`contenedor_operacion_id`) REFERENCES `contenedor_maritimo_ferro` (`contenedor_fisico_id`),
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
-- Filtros para la tabla `costos_operacion_ferro`
--
ALTER TABLE `costos_operacion_ferro`
  ADD CONSTRAINT `fk_costo_ferro_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_costo_ferro_operacion` FOREIGN KEY (`operacion_ferro_id`) REFERENCES `operaciones_ferroviarias` (`id_operacion_ferro`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_costo_ferro_tipo_mov` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipos_movimiento` (`id_tipo_movimiento`);

--
-- Filtros para la tabla `detalles_logisticos`
--
ALTER TABLE `detalles_logisticos`
  ADD CONSTRAINT `detalles_logisticos_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id_operacion`);

--
-- Filtros para la tabla `documentos_operacion`
--
ALTER TABLE `documentos_operacion`
  ADD CONSTRAINT `documentos_operacion_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_doc_cont_mar` FOREIGN KEY (`cont_maritimo_operacion_id`) REFERENCES `contenedores_maritimos_operacion` (`id`),
  ADD CONSTRAINT `fk_doc_tipo` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id_tipo_documento`);

--
-- Filtros para la tabla `eventos_ferroviarios`
--
ALTER TABLE `eventos_ferroviarios`
  ADD CONSTRAINT `fk_evfer_contenedor_fisico` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evfer_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evfer_operacion` FOREIGN KEY (`operacion_ferro_id`) REFERENCES `operaciones_ferroviarias` (`id_operacion_ferro`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evfer_tipo_evento` FOREIGN KEY (`tipo_evento_id`) REFERENCES `tipos_evento_logistico` (`id_tipo_evento`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `eventos_logisticos`
--
ALTER TABLE `eventos_logisticos`
  ADD CONSTRAINT `fk_ev_cont_maritimo` FOREIGN KEY (`cont_maritimo_operacion_id`) REFERENCES `contenedores_maritimos_operacion` (`id`) ON UPDATE CASCADE,
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
-- Filtros para la tabla `operaciones_ferroviarias`
--
ALTER TABLE `operaciones_ferroviarias`
  ADD CONSTRAINT `fk_opferro_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `fk_opferro_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_opferro_fisico` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`),
  ADD CONSTRAINT `fk_opferro_subtipo` FOREIGN KEY (`subtipo_operacion_id`) REFERENCES `subtipos_operacion` (`id_subtipo`),
  ADD CONSTRAINT `fk_opferro_tipo` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id_tipo_operacion`),
  ADD CONSTRAINT `fk_opferro_transportista` FOREIGN KEY (`transportista_id`) REFERENCES `transportistas` (`id_transportista`),
  ADD CONSTRAINT `operaciones_ferroviarias_ibfk_3` FOREIGN KEY (`destino_id`) REFERENCES `ciudades` (`id_ciudad`),
  ADD CONSTRAINT `operaciones_ferroviarias_ibfk_4` FOREIGN KEY (`estatus_id`) REFERENCES `estatus` (`id_estatus`);

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
-- Filtros para la tabla `rutas_ferro`
--
ALTER TABLE `rutas_ferro`
  ADD CONSTRAINT `fk_rf_conten` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id_fisico`),
  ADD CONSTRAINT `fk_rf_opferro` FOREIGN KEY (`operacion_ferro_id`) REFERENCES `operaciones_ferroviarias` (`id_operacion_ferro`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutas_ferro_tramos`
--
ALTER TABLE `rutas_ferro_tramos`
  ADD CONSTRAINT `fk_rft_destino` FOREIGN KEY (`destino_id`) REFERENCES `ciudades` (`id_ciudad`),
  ADD CONSTRAINT `fk_rft_origen` FOREIGN KEY (`origen_id`) REFERENCES `ciudades` (`id_ciudad`),
  ADD CONSTRAINT `fk_rft_ruta` FOREIGN KEY (`ruta_id`) REFERENCES `rutas_ferro` (`id_ruta`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rft_transp` FOREIGN KEY (`transportista_id`) REFERENCES `transportistas` (`id_transportista`);

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
