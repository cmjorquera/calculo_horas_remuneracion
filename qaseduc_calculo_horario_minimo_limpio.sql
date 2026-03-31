SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `usuario_rol_colegio`;
DROP TABLE IF EXISTS `usuario_menu_v`;
DROP TABLE IF EXISTS `usuario_menu`;
DROP TABLE IF EXISTS `contratos_empleado`;
DROP TABLE IF EXISTS `horarios_semanales`;
DROP TABLE IF EXISTS `empleados`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `menu_v`;
DROP TABLE IF EXISTS `menus`;
DROP TABLE IF EXISTS `dias_semana`;
DROP TABLE IF EXISTS `colegio`;
DROP TABLE IF EXISTS `colacion`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `colacion` (`id_colacion` int NOT NULL, `hora` time NOT NULL, `minutos` int NOT NULL, `activo` tinyint DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `colacion` VALUES
(1,'00:00:00',0,1,'2026-02-23 15:18:24'),
(2,'00:30:00',30,1,'2026-02-23 15:18:24'),
(3,'00:40:00',40,1,'2026-02-23 15:18:24'),
(4,'01:00:00',60,1,'2026-02-23 15:18:24');

CREATE TABLE `colegio` (`id_colegio` int NOT NULL, `nom_colegio` varchar(60) NOT NULL, `rza_colegio` varchar(50) NOT NULL, `nco_colegio` varchar(150) NOT NULL, `dir_colegio` varchar(50) NOT NULL, `rbd_colegio` varchar(10) NOT NULL, `id_dependencia` int NOT NULL, `id_comuna` int NOT NULL, `tel_colegio` varchar(10) NOT NULL, `web_colegio` varchar(60) NOT NULL, `bd` varchar(30) NOT NULL, `orden` int NOT NULL, `email_entrevista` varchar(100) NOT NULL, `num_r_educacion` int NOT NULL, `ano_r_educacion` int NOT NULL, `ip` varchar(15) NOT NULL, `email_comunicaciones` varchar(100) NOT NULL, `sexo` int NOT NULL DEFAULT 0, `multi_cole` varchar(2) NOT NULL DEFAULT 'no', `identificador` varchar(20) NOT NULL, `rut_colegio` varchar(12) NOT NULL, `correo_contrato` varchar(50) NOT NULL, `url_pagina` varchar(255) DEFAULT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
INSERT INTO `colegio` VALUES
(1,'Colegio Cordillera de las Condes','SEDUC Spa y Compañia CPA Cuatro','Colegio Cordillera','Los Pumas Nro 12015','8902-8',2,290,'4295900','http://www.colegiocordillera.cl','seduc',4,'info@colegiocordillera.cl',40,1983,'200.111.15.3','comunicacion@colegiocordillera.cl',1,'no','CBHgdA8z7j74n3933pKQ','79848720-5','cm.mail.com','cordillera.php'),
(8,'Colegio Tabancura','SEDUC Spa y Compañia CPA Dos','Colegio Tabancura','Las Hualtatas Nro 10650','8862-5',2,308,'8976300','http://www.tabancura.cl','seduc',2,'consejodireccion@tabancura.cl',152,1986,'152.231.78.42','consejodireccion@tabancura.cl',1,'no','64N93dwf6k76P3N9y858','83332100-5','administradorColegioTabancura@gmail.com','tabancura.php'),
(9,'Colegio Los Andes de Vitacura','SEDUC Spa y Compañia CPA Uno','Colegio Los Andes','San Damian Nro 0100','8871-4',5,308,'232232500','http://www.colegiolosandes.cl/','seduc',1,'entrevista@colegiolosandes.cl',10118,1979,'152.231.77.98','colegiolosandes@colegiolosandes.cl',2,'no','uoB53uIn51qb1ad4537u','70054800-7','administacionColegiolosandes@gmail.com','losAndess.php'),
(10,'Colegio Los Alerces','SEDUC Spa y Compañia CPA Cinco','Colegio Los Alerces','El Radal Nro 437','24979-3',4,291,'228207900','http://www.colegiolosalerces.cl/','seduc',5,'colegioalerces@siae.cl',3127,1996,'186.67.49.138','colegiolosalerces@colegiolosalerces.cl',2,'no','RxLNwR61Vee02s2VzKyi','87152200-6','lupejorquera@gmail.com','losAlerces.php'),
(11,'Colegio Huelén','SEDUC Spa y Compañia CPA Tres','Colegio Huelen ','Av. Santa María Nro 6.480','8953-2',308,308,'','http://www.colegiohuelen.cl/','seduc',3,'entrevista@huelen.cl',5171,1978,'152.231.77.114','comunicaciones@huelen.cl',2,'no','2sc922imu2eFvyvsX53B','87042000-5','administradorCOLEGIOHUELEN@gmail.com','hulen.php'),
(12,'Colegio Cantagallo','SEDUC Spa y Compañia CPA Siete','Colegio Cantagallo',' Av. Monseñor Escrivá de Balaguer Nro 13.322','12345-6',0,290,'0','http://colegiocantagallo.cl/','seduc',10,'secretaria@colegiocantagallo.cl',0,0,'190.82.87.210','secretaria@colegiocantagallo.cl',1,'si','j6i8IdpDB7jsp52yE3Iw','76328035-7','cm.jorquerag@gmail.com',NULL),
(13,'Colegio Huinganal','SEDUC Spa y Compañia CPA Seis','Colegio Huinganal','Av. Monseñor Adolfo Rodríguez Nro 13210','20311-4',0,308,'225921720','http://www.colegiohuinganal.cl/','seduc',7,'huinganal@siae.cl',3523,2014,'','comunicaciones@colegiohuinganal.cl',1,'no','KH0RWJv5N7P4HcL333WK','76232345-1','cm.jorquerag@gmail.com','huinganal.php'),
(14,'Colegio Prueba','Colegio Prueba Ltda','Colegio Prueba','direccion','9999-9',1,290,'111111','','prueba',1,'soporte@siae.cl',1999,2011,'','soporte@siae.cl',0,'no','3K67M9z8801aF3YNt6c0','','administradorColegioPrueba@gmail.com','colegioPrueba.php'),
(15,'Seduc SpA','Seduc SpA','Seduc','Las Hualtatas 10030','9876-5',3,290,'224380300','www.seduc.cl','seduc',0,'seduc.informa@seduc.cl',0,0,'186.67.42.46','seduc.informa@seduc.cl',0,'no','024Jw16809k8gopl85fJ','','cm.jorquerag@gmail.com','seduc.php'),
(17,'Valle Alegre','Valle Alegre','Valle Alegre',' ','11111-1',0,290,'0','http://www.valegre.cl/','seduc',11,'secretaria@valegre.cl',0,0,'190.82.87.210','secretaria@valegre.cl',1,'no','tte45asashw22346hh','','administra@gmail.com',NULL);

CREATE TABLE `dias_semana` (`id_dia` int NOT NULL, `clave` varchar(20) NOT NULL, `nombre` varchar(50) NOT NULL, `prefijo` varchar(10) NOT NULL, `orden` int NOT NULL, `activo` tinyint DEFAULT 1) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `dias_semana` VALUES
(1,'lunes','Lunes','lun',1,1),(2,'martes','Martes','mar',2,1),(3,'miercoles','Miércoles','mie',3,1),(4,'jueves','Jueves','jue',4,1),(5,'viernes','Viernes','vie',5,1),(6,'sabado','Sabado','sab',6,1),(7,'domingo','Domingo','dom',7,1);

CREATE TABLE `empleados` (`id_empleado` int NOT NULL, `codigo` varchar(30) NOT NULL, `run` varchar(12) NOT NULL, `id_colegio` int NOT NULL, `nombres` varchar(80) NOT NULL, `apellido_paterno` varchar(60) NOT NULL, `apellido_materno` varchar(60) NOT NULL, `email` varchar(120) DEFAULT NULL, `telefono` varchar(30) DEFAULT NULL, `activo` tinyint NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NULL DEFAULT NULL, `foto` varchar(255) DEFAULT NULL, `genero` int NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `contratos_empleado` (`id_contrato` int NOT NULL, `id_empleado` int NOT NULL, `fecha_inicio` date NOT NULL, `fecha_fin` date DEFAULT NULL, `horas_semanales_cron` int NOT NULL, `horas_lectivas` int NOT NULL DEFAULT 0, `horas_no_lectivas` int NOT NULL DEFAULT 0, `min_colacion_diaria` int NOT NULL DEFAULT 0, `observacion` varchar(255) DEFAULT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `horarios_semanales` (`id_horario` int NOT NULL, `id_empleado` int NOT NULL, `id_contrato` int NOT NULL, `dia` enum('LUN','MAR','MIE','JUE','VIE','SAB','DOM') NOT NULL, `man_ini` time DEFAULT NULL, `man_fin` time DEFAULT NULL, `tar_ini` time DEFAULT NULL, `tar_fin` time DEFAULT NULL, `activo` tinyint NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `empleados` VALUES
(2001,'COR01_001','20000101-2',1,'Juan','Perez','Gonzalez','juan.perez.cor01_001@demo.cl','900002001',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2002,'COR01_002','20000102-3',1,'Maria','Soto','Rojas','maria.soto.cor01_002@demo.cl','900002002',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2003,'COR01_003','20000103-4',1,'Pedro','Munoz','Tapia','pedro.munoz.cor01_003@demo.cl','900002003',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2004,'COR01_004','20000104-5',1,'Ana','Vera','Contreras','ana.vera.cor01_004@demo.cl','900002004',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2005,'COR01_005','20000105-6',1,'Luis','Torres','Salinas','luis.torres.cor01_005@demo.cl','900002005',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2006,'TAB08_001','20000801-9',8,'Juan','Perez','Gonzalez','juan.perez.tab08_001@demo.cl','900002006',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2007,'TAB08_002','20000802-0',8,'Maria','Soto','Rojas','maria.soto.tab08_002@demo.cl','900002007',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2008,'TAB08_003','20000803-1',8,'Pedro','Munoz','Tapia','pedro.munoz.tab08_003@demo.cl','900002008',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2009,'TAB08_004','20000804-2',8,'Ana','Vera','Contreras','ana.vera.tab08_004@demo.cl','900002009',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2010,'TAB08_005','20000805-3',8,'Luis','Torres','Salinas','luis.torres.tab08_005@demo.cl','900002010',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2011,'AND09_001','20000901-0',9,'Juan','Perez','Gonzalez','juan.perez.and09_001@demo.cl','900002011',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2012,'AND09_002','20000902-1',9,'Maria','Soto','Rojas','maria.soto.and09_002@demo.cl','900002012',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2013,'AND09_003','20000903-2',9,'Pedro','Munoz','Tapia','pedro.munoz.and09_003@demo.cl','900002013',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2014,'AND09_004','20000904-3',9,'Ana','Vera','Contreras','ana.vera.and09_004@demo.cl','900002014',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2015,'AND09_005','20000905-4',9,'Luis','Torres','Salinas','luis.torres.and09_005@demo.cl','900002015',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2016,'ALE10_001','20001001-1',10,'Juan','Perez','Gonzalez','juan.perez.ale10_001@demo.cl','900002016',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2017,'ALE10_002','20001002-2',10,'Maria','Soto','Rojas','maria.soto.ale10_002@demo.cl','900002017',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2018,'ALE10_003','20001003-3',10,'Pedro','Munoz','Tapia','pedro.munoz.ale10_003@demo.cl','900002018',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2019,'ALE10_004','20001004-4',10,'Ana','Vera','Contreras','ana.vera.ale10_004@demo.cl','900002019',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2020,'ALE10_005','20001005-5',10,'Luis','Torres','Salinas','luis.torres.ale10_005@demo.cl','900002020',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2021,'HUE11_001','20001101-2',11,'Juan','Perez','Gonzalez','juan.perez.hue11_001@demo.cl','900002021',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2022,'HUE11_002','20001102-3',11,'Maria','Soto','Rojas','maria.soto.hue11_002@demo.cl','900002022',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2023,'HUE11_003','20001103-4',11,'Pedro','Munoz','Tapia','pedro.munoz.hue11_003@demo.cl','900002023',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2024,'HUE11_004','20001104-5',11,'Ana','Vera','Contreras','ana.vera.hue11_004@demo.cl','900002024',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2025,'HUE11_005','20001105-6',11,'Luis','Torres','Salinas','luis.torres.hue11_005@demo.cl','900002025',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2026,'CAN12_001','20001201-3',12,'Juan','Perez','Gonzalez','juan.perez.can12_001@demo.cl','900002026',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2027,'CAN12_002','20001202-4',12,'Maria','Soto','Rojas','maria.soto.can12_002@demo.cl','900002027',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2028,'CAN12_003','20001203-5',12,'Pedro','Munoz','Tapia','pedro.munoz.can12_003@demo.cl','900002028',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2029,'CAN12_004','20001204-6',12,'Ana','Vera','Contreras','ana.vera.can12_004@demo.cl','900002029',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2030,'CAN12_005','20001205-7',12,'Luis','Torres','Salinas','luis.torres.can12_005@demo.cl','900002030',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2031,'HUI13_001','20001301-4',13,'Juan','Perez','Gonzalez','juan.perez.hui13_001@demo.cl','900002031',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2032,'HUI13_002','20001302-5',13,'Maria','Soto','Rojas','maria.soto.hui13_002@demo.cl','900002032',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2033,'HUI13_003','20001303-6',13,'Pedro','Munoz','Tapia','pedro.munoz.hui13_003@demo.cl','900002033',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2034,'HUI13_004','20001304-7',13,'Ana','Vera','Contreras','ana.vera.hui13_004@demo.cl','900002034',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2035,'HUI13_005','20001305-8',13,'Luis','Torres','Salinas','luis.torres.hui13_005@demo.cl','900002035',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2036,'VAL17_001','20001701-8',17,'Juan','Perez','Gonzalez','juan.perez.val17_001@demo.cl','900002036',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2037,'VAL17_002','20001702-9',17,'Maria','Soto','Rojas','maria.soto.val17_002@demo.cl','900002037',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2038,'VAL17_003','20001703-0',17,'Pedro','Munoz','Tapia','pedro.munoz.val17_003@demo.cl','900002038',1,'2026-03-31 18:00:00',NULL,NULL,1),
(2039,'VAL17_004','20001704-1',17,'Ana','Vera','Contreras','ana.vera.val17_004@demo.cl','900002039',1,'2026-03-31 18:00:00',NULL,NULL,2),
(2040,'VAL17_005','20001705-2',17,'Luis','Torres','Salinas','luis.torres.val17_005@demo.cl','900002040',1,'2026-03-31 18:00:00',NULL,NULL,1);
INSERT INTO `contratos_empleado` VALUES
(3001,2001,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa COR 001','2026-03-31 18:00:00'),
(3002,2002,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa COR 002','2026-03-31 18:00:00'),
(3003,2003,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa COR 003','2026-03-31 18:00:00'),
(3004,2004,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa COR 004','2026-03-31 18:00:00'),
(3005,2005,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa COR 005','2026-03-31 18:00:00'),
(3006,2006,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa TAB 001','2026-03-31 18:00:00'),
(3007,2007,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa TAB 002','2026-03-31 18:00:00'),
(3008,2008,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa TAB 003','2026-03-31 18:00:00'),
(3009,2009,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa TAB 004','2026-03-31 18:00:00'),
(3010,2010,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa TAB 005','2026-03-31 18:00:00'),
(3011,2011,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa AND 001','2026-03-31 18:00:00'),
(3012,2012,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa AND 002','2026-03-31 18:00:00'),
(3013,2013,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa AND 003','2026-03-31 18:00:00'),
(3014,2014,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa AND 004','2026-03-31 18:00:00'),
(3015,2015,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa AND 005','2026-03-31 18:00:00'),
(3016,2016,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa ALE 001','2026-03-31 18:00:00'),
(3017,2017,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa ALE 002','2026-03-31 18:00:00'),
(3018,2018,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa ALE 003','2026-03-31 18:00:00'),
(3019,2019,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa ALE 004','2026-03-31 18:00:00'),
(3020,2020,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa ALE 005','2026-03-31 18:00:00'),
(3021,2021,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUE 001','2026-03-31 18:00:00'),
(3022,2022,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUE 002','2026-03-31 18:00:00'),
(3023,2023,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUE 003','2026-03-31 18:00:00'),
(3024,2024,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUE 004','2026-03-31 18:00:00'),
(3025,2025,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUE 005','2026-03-31 18:00:00'),
(3026,2026,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa CAN 001','2026-03-31 18:00:00'),
(3027,2027,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa CAN 002','2026-03-31 18:00:00'),
(3028,2028,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa CAN 003','2026-03-31 18:00:00'),
(3029,2029,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa CAN 004','2026-03-31 18:00:00'),
(3030,2030,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa CAN 005','2026-03-31 18:00:00'),
(3031,2031,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUI 001','2026-03-31 18:00:00'),
(3032,2032,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUI 002','2026-03-31 18:00:00'),
(3033,2033,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUI 003','2026-03-31 18:00:00'),
(3034,2034,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUI 004','2026-03-31 18:00:00'),
(3035,2035,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa HUI 005','2026-03-31 18:00:00'),
(3036,2036,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa VAL 001','2026-03-31 18:00:00'),
(3037,2037,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa VAL 002','2026-03-31 18:00:00'),
(3038,2038,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa VAL 003','2026-03-31 18:00:00'),
(3039,2039,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa VAL 004','2026-03-31 18:00:00'),
(3040,2040,'2026-03-31',NULL,2400,1800,600,60,'Contrato jornada completa VAL 005','2026-03-31 18:00:00');
INSERT INTO `horarios_semanales` VALUES
(4001,2001,3001,'LUN','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4002,2001,3001,'MAR','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4003,2001,3001,'MIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4004,2001,3001,'JUE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4005,2001,3001,'VIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4006,2002,3002,'LUN','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4007,2002,3002,'MAR','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4008,2002,3002,'MIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4009,2002,3002,'JUE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4010,2002,3002,'VIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4011,2003,3003,'LUN','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4012,2003,3003,'MAR','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4013,2003,3003,'MIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4014,2003,3003,'JUE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4015,2003,3003,'VIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4016,2004,3004,'LUN','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4017,2004,3004,'MAR','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4018,2004,3004,'MIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4019,2004,3004,'JUE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4020,2004,3004,'VIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4021,2005,3005,'LUN','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4022,2005,3005,'MAR','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4023,2005,3005,'MIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4024,2005,3005,'JUE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00'),
(4025,2005,3005,'VIE','08:00:00','13:00:00','14:00:00','17:00:00',1,'2026-03-31 18:00:00');

CREATE TABLE `menus` (`id_menu` int NOT NULL, `codigo` varchar(50) NOT NULL, `nombre` varchar(100) NOT NULL, `ruta` varchar(255) NOT NULL, `icono` varchar(100) DEFAULT NULL, `orden` int NOT NULL DEFAULT 0, `estado` tinyint NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `menus` VALUES (1,'empleados','Empleados','index.php','bi-people-fill',10,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(2,'graficos','Graficos','grafico.php','bi-bar-chart-fill',20,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(3,'usuarios','Usuarios','usuarios.php','bi-person-plus-fill',30,1,'2026-03-11 00:48:56','2026-03-11 00:48:56');
CREATE TABLE `menu_v` (`id_menu` int NOT NULL, `codigo` varchar(50) NOT NULL, `nombre` varchar(100) NOT NULL, `url` varchar(255) NOT NULL, `icono` varchar(100) DEFAULT NULL, `descripcion` varchar(255) DEFAULT NULL, `orden` int NOT NULL DEFAULT 0, `visible` tinyint NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `menu_v` VALUES (1,'empleados','Empleados','index.php','bi-people-fill','Modulo principal de empleados',10,1,'2026-03-11 00:54:40','2026-03-11 00:54:40'),(2,'graficos','Graficos','grafico.php','bi-bar-chart-fill','Graficos y reportes',20,1,'2026-03-11 00:54:40','2026-03-11 00:54:40'),(3,'usuarios','Usuarios','usuarios.php','bi-person-plus-fill','Administracion de usuarios',30,1,'2026-03-11 00:54:40','2026-03-11 00:54:40');
CREATE TABLE `roles` (`id_rol` int NOT NULL, `codigo` varchar(40) NOT NULL, `nombre` varchar(80) NOT NULL, `descripcion` varchar(200) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `roles` VALUES (1,'SUPER_ADMIN','Super Administrador','Acceso total a todos los colegios'),(2,'ADMIN_HORAS','Administrador de Horas','Gestiona horas del colegio asignado');
CREATE TABLE `usuarios` (`id_usuario` int NOT NULL, `identificador` varchar(60) NOT NULL, `email` varchar(150) NOT NULL, `clave_hash` varchar(255) NOT NULL, `nombre` varchar(80) NOT NULL, `apellido_paterno` varchar(80) NOT NULL, `apellido_materno` varchar(80) DEFAULT NULL, `run` varchar(20) DEFAULT NULL, `telefono` varchar(25) DEFAULT NULL, `id_colegio` int DEFAULT NULL, `token_reinicio` varchar(255) DEFAULT NULL, `token_reinicio_expira` datetime DEFAULT NULL, `estado` tinyint NOT NULL DEFAULT 1, `intentos` int NOT NULL DEFAULT 0, `ultimo_login` datetime DEFAULT NULL, `created_at` datetime NOT NULL DEFAULT current_timestamp(), `updated_at` datetime DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
INSERT INTO `usuarios` VALUES
(1,'mgutierrez','mgutierrez@seduc.cl','123456','Manuel','Gutierrez','Gutierrez',NULL,NULL,1,NULL,NULL,1,0,'2026-03-11 09:42:21','2026-02-12 12:34:20','2026-03-11 09:42:21'),
(2,'cjorquera','cjorquera@seduc.cl','123456','Cristian','Jorquera','Gonzalez',NULL,NULL,1,NULL,NULL,1,0,'2026-03-31 14:45:53','2026-02-12 12:34:20','2026-03-31 14:45:53'),
(3,'roliva','roliva@seduc.cl','123456','Ramon','Oliva','Zenteno',NULL,NULL,1,NULL,NULL,1,0,'2026-03-10 22:08:01','2026-02-12 12:34:20','2026-03-10 22:08:01'),
(4,'arojas','arojas@seduc.cl','123456','Alejandro','Rojas','Schweitzer',NULL,NULL,1,NULL,NULL,1,0,'2026-03-31 15:27:17','2026-02-12 12:34:20','2026-03-31 15:27:17'),
(5,'fvalenzuela','fvalenzuela@seduc.cl','123456','Francisco ','Valenzuela','Valenzuela',NULL,NULL,15,NULL,NULL,1,0,'2026-03-31 15:45:34','2026-03-02 08:50:25','2026-03-31 15:45:34'),
(14,'GJorquera','cm.jorquerag@gmail.com','0dd07896ea07','CRISTIAN','jorquera','caicedo',NULL,'988302735',8,'0342527a3023655194fb3eb09b242785999f6e2a3fef271b6ce14da1c32b1bcf','2026-03-14 15:29:00',1,0,NULL,'2026-03-11 15:29:00','2026-03-11 15:29:00'),
(15,'admin_c1','admin_c1@demo.cl','123456','Admin','Cordillera','Demo',NULL,'910000015',1,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(16,'admin_c8','admin_c8@demo.cl','123456','Admin','Tabancura','Demo',NULL,'910000016',8,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(17,'admin_c9','admin_c9@demo.cl','123456','Admin','LosAndes','Demo',NULL,'910000017',9,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(18,'admin_c10','admin_c10@demo.cl','123456','Admin','LosAlerces','Demo',NULL,'910000018',10,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(19,'admin_c11','admin_c11@demo.cl','123456','Admin','Huelen','Demo',NULL,'910000019',11,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(20,'admin_c12','admin_c12@demo.cl','123456','Admin','Cantagallo','Demo',NULL,'910000020',12,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(21,'admin_c13','admin_c13@demo.cl','123456','Admin','Huinganal','Demo',NULL,'910000021',13,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL),
(22,'admin_c17','admin_c17@demo.cl','123456','Admin','ValleAlegre','Demo',NULL,'910000022',17,NULL,NULL,1,0,NULL,'2026-03-31 18:00:00',NULL);
CREATE TABLE `usuario_menu` (`id_usuario_menu` int NOT NULL, `id_usuario` int NOT NULL, `id_menu` int NOT NULL, `permitido` tinyint NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `usuario_menu` VALUES (1,4,1,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(2,4,2,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(3,2,1,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(4,2,2,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(5,5,1,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(6,5,2,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(7,1,1,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(8,1,2,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(9,3,1,1,'2026-03-11 00:48:56','2026-03-11 00:48:56'),(10,3,2,1,'2026-03-11 00:48:56','2026-03-11 00:48:56');
CREATE TABLE `usuario_menu_v` (`id_usuario_menu` int NOT NULL, `id_usuario` int NOT NULL, `id_menu` int NOT NULL, `permitido` tinyint NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), `updated_at` timestamp NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `usuario_menu_v` VALUES (1,4,1,1,'2026-03-11 01:01:48','2026-03-11 01:01:48'),(2,4,2,1,'2026-03-11 01:01:48','2026-03-11 01:01:48'),(3,4,3,0,'2026-03-11 01:01:48','2026-03-11 01:01:48'),(4,2,1,1,'2026-03-11 01:01:48','2026-03-11 01:01:48'),(5,2,2,1,'2026-03-11 01:01:48','2026-03-11 01:01:48'),(6,2,3,1,'2026-03-11 01:01:48','2026-03-11 01:01:48');
CREATE TABLE `usuario_rol_colegio` (`id` int NOT NULL, `id_usuario` int NOT NULL, `id_rol` int NOT NULL, `id_colegio` int DEFAULT NULL, `estado` tinyint NOT NULL DEFAULT 1, `created_at` datetime NOT NULL DEFAULT current_timestamp()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `usuario_rol_colegio` VALUES (1,2,1,8,1,'2026-02-12 12:42:44'),(2,1,2,8,1,'2026-02-12 12:42:44'),(3,3,2,8,1,'2026-02-12 12:42:44'),(4,4,2,8,1,'2026-02-12 12:42:44'),(5,5,1,15,1,'2026-03-04 17:44:47'),(14,14,2,8,1,'2026-03-11 15:29:00'),(15,15,2,1,1,'2026-03-31 18:00:00'),(16,16,2,8,1,'2026-03-31 18:00:00'),(17,17,2,9,1,'2026-03-31 18:00:00'),(18,18,2,10,1,'2026-03-31 18:00:00'),(19,19,2,11,1,'2026-03-31 18:00:00'),(20,20,2,12,1,'2026-03-31 18:00:00'),(21,21,2,13,1,'2026-03-31 18:00:00'),(22,22,2,17,1,'2026-03-31 18:00:00');

ALTER TABLE `colacion` ADD PRIMARY KEY (`id_colacion`);
ALTER TABLE `colegio` ADD PRIMARY KEY (`id_colegio`);
ALTER TABLE `dias_semana` ADD PRIMARY KEY (`id_dia`);
ALTER TABLE `empleados` ADD PRIMARY KEY (`id_empleado`), ADD UNIQUE KEY `codigo` (`codigo`), ADD UNIQUE KEY `run` (`run`);
ALTER TABLE `contratos_empleado` ADD PRIMARY KEY (`id_contrato`), ADD KEY `fk_contrato_empleado` (`id_empleado`);
ALTER TABLE `horarios_semanales` ADD PRIMARY KEY (`id_horario`), ADD UNIQUE KEY `uk_horario_unico` (`id_contrato`,`dia`);
ALTER TABLE `menus` ADD PRIMARY KEY (`id_menu`);
ALTER TABLE `menu_v` ADD PRIMARY KEY (`id_menu`);
ALTER TABLE `roles` ADD PRIMARY KEY (`id_rol`);
ALTER TABLE `usuarios` ADD PRIMARY KEY (`id_usuario`), ADD UNIQUE KEY `uk_identificador` (`identificador`), ADD UNIQUE KEY `uk_email` (`email`);
ALTER TABLE `usuario_menu` ADD PRIMARY KEY (`id_usuario_menu`);
ALTER TABLE `usuario_menu_v` ADD PRIMARY KEY (`id_usuario_menu`);
ALTER TABLE `usuario_rol_colegio` ADD PRIMARY KEY (`id`);

ALTER TABLE `colacion` MODIFY `id_colacion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
ALTER TABLE `colegio` MODIFY `id_colegio` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
ALTER TABLE `dias_semana` MODIFY `id_dia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `empleados` MODIFY `id_empleado` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2041;
ALTER TABLE `contratos_empleado` MODIFY `id_contrato` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3041;
ALTER TABLE `horarios_semanales` MODIFY `id_horario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4201;
ALTER TABLE `menus` MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `menu_v` MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `roles` MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `usuarios` MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
ALTER TABLE `usuario_menu` MODIFY `id_usuario_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `usuario_menu_v` MODIFY `id_usuario_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `usuario_rol_colegio` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

ALTER TABLE `contratos_empleado` ADD CONSTRAINT `fk_contrato_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE;
COMMIT;
