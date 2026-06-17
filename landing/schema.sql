-- Schema exacto de ipstream_db
-- Extraído del backup de producción

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `landing_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `whatsapp` varchar(50) NOT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `plan_interest` varchar(50) DEFAULT 'Radio Online',
  `status` enum('new','contacted','negotiation','converted','cancelled') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `trial_started_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `lead_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `monthly_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `commerce_order` varchar(100) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `flow_token` varchar(255) DEFAULT NULL,
  `flow_payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flow_payment_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `commerce_order` (`commerce_order`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks every monthly payment for a subscription.';

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `author` varchar(255) DEFAULT 'IPStream',
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `published_at` (`published_at`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commerce_order` varchar(100) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `flow_token` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `plan_type` enum('radio','tv') NOT NULL,
  `billing_type` enum('monthly','annual') NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','active','cancelled','failed') DEFAULT 'pending',
  `flow_status` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `flow_order` varchar(255) DEFAULT NULL,
  `flow_payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flow_payment_data`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `commerce_order` (`commerce_order`),
  KEY `idx_commerce_order` (`commerce_order`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commerce_order` varchar(100) NOT NULL,
  `event_type` enum('order_created','payment_sent','webhook_received','payment_confirmed','service_activated','error') NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_commerce_order` (`commerce_order`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_key` varchar(50) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `monthly_price` int(11) DEFAULT NULL,
  `annual_price` int(11) DEFAULT NULL,
  `billing_note` varchar(255) DEFAULT NULL,
  `demo_url` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_key` (`plan_key`),
  KEY `idx_category` (`category_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores product plans and their prices.';

CREATE TABLE IF NOT EXISTS `plan_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Categories for organizing plans';

CREATE TABLE IF NOT EXISTS `tutorials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `difficulty` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Video tutorials for users';

CREATE TABLE IF NOT EXISTS `tutorial_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT 'blue',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Categories for organizing tutorials';

-- Foreign keys
ALTER TABLE `lead_logs`
  ADD CONSTRAINT `lead_logs_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `landing_leads` (`id`) ON DELETE CASCADE;

ALTER TABLE `monthly_payments`
  ADD CONSTRAINT `monthly_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL;

ALTER TABLE `plans`
  ADD CONSTRAINT `fk_plans_category` FOREIGN KEY (`category_id`) REFERENCES `plan_categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `tutorials`
  ADD CONSTRAINT `tutorials_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `tutorial_categories` (`id`) ON DELETE CASCADE;

-- Datos iniciales (categorías y planes)
INSERT IGNORE INTO `plan_categories` (`id`, `name`, `slug`, `description`, `icon`, `display_order`, `is_active`) VALUES
(8, 'Radio Online', 'radio', 'Planes de streaming de Audio', '', 1, 1),
(9, 'Tv Online', 'tv', 'Planes de Streaming de Video', '', 2, 1),
(10, 'Radio + Tv', 'radiotv', 'Planes de streaming de audio y video', '', 3, 1);

INSERT IGNORE INTO `plans` (`id`, `plan_key`, `plan_name`, `price`, `title`, `icon`, `image_url`, `description`, `features`, `monthly_price`, `annual_price`, `billing_note`, `demo_url`, `category_id`, `is_active`) VALUES
(28, 'radio_avanza', 'Radio Online Inicia', 9990, 'Radio Online Inicia', '', '/uploads/plans/plan_694f5ed088c8d.png', 'Descripcion', '[\"Link a Redes Sociales\",\"Sonicpanel 2026\",\"10gb Almacenamiento Auto DJ\",\"Soporte Tecnico\",\"Puedes transmitir en vivo desde ZaraRadio o Radioboss\",\"Perfil publico en www.hostreams.com\"]', 9990, 100000, 'IVA NO Incluido - Facturación mensual, sin contratos de permanencia.', 'https://hostreams.com/radio/radio-magica-fm', 8, 1),
(29, 'radio_profesional', 'Radio Online Profesional', 19990, 'Radio Online Profesional', '', '/uploads/plans/plan_694f5fc0bd259.png', 'descripcion de radio profesional', '[\"Sitio Web Profesional\",\"Seccion de Noticias, Podcast, VideoCast, Auspiciadores, Eventos, Ranking Musical, Link Redes Sociales\",\"Aplicacion PWA (Android - Iphone - PC)\",\"Sonicpanel 2026\",\"30gb Almacenamiento Auto DJ\",\"Soporte Tecnico\",\"Puedes transmitir en vivo desde ZaraRadio o Radioboss\",\"Dominio .cl o .com $9.990 ANUALES\",\"Perfil publico en www.hostreams.com\"]', 19990, 200000, 'IVA NO Incluido - Facturación mensual, sin contratos de permanencia.', 'https://clasicafm.cl/', 8, 1),
(30, 'tv_inicia', 'Tv Inicia', 14990, 'Tv Online Inicia', '', '/uploads/plans/plan_69605685691a0.png', 'Descripción:', '[\"Link a Redes Sociales\",\"VdoPanel 2026\",\"10gb Almacenamiento Auto DJ\",\"Soporte Tecnico\",\"Puedes transmitir en vivo desde OBS\",\"Perfil publico en www.hostreams.com\\/nombre_de_tu_canal\"]', 14990, 150000, 'IVA NO Incluido - Facturación mensual, sin contratos de permanencia.', 'https://hostreams.com/tv/san-pedro-conecta', 9, 1),
(31, 'tv_profesional', 'Tv Profesional', 24990, 'Tv Profesional', '', '/uploads/plans/plan_6960579549304.png', 'Descripción', '[\"Sitio Web Profesional\",\"Seccion de Noticias, Podcast, VideoCast, Auspiciadores, Eventos, Ranking Musical, Link Redes Sociales\",\"Aplicacion PWA (Android - Iphone - PC)\",\"VdoPanel 2026\",\"30gb Almacenamiento Auto DJ\",\"Soporte Tecnico\",\"Puedes transmitir en vivo desde OBS\",\"Dominio .cl o .com $9.990 ANUALES\",\"Perfil publico en www.hostreams.com\"]', 24990, 250000, 'IVA NO Incluido - Facturación mensual, sin contratos de permanencia.', 'https://sanpedroconecta.cl/', 9, 1),
(32, 'radio_mas_tv', 'Radio + Tv', 39990, 'Radio + Tv Online', '', '/uploads/plans/plan_696058b8eacd0.png', '', '[\"Sitio Web Profesional\",\"Seccion de Noticias, Podcast, VideoCast, Auspiciadores, Eventos, Ranking Musical, Link Redes Sociales\",\"Aplicacion PWA (Android - Iphone - PC)\",\"VdoPanel 2026 + SonicPanel 2026\",\"30gb Almacenamiento Auto DJ SonicPanel + 50Gb AutoDj VdoPanel\",\"Soporte Tecnico\",\"Puedes transmitir en vivo desde OBS, Zara Radio, Radioboss\",\"Dominio .cl o .com $9.990 ANUALES\",\"Perfil publico en www.hostreams.com\"]', 39990, 400000, 'IVA NO Incluido - Facturación mensual, sin contratos de permanencia.', 'https://sanpedroconecta.cl/', 10, 1);

INSERT IGNORE INTO `tutorial_categories` (`id`, `name`, `slug`, `color`, `display_order`) VALUES
(1, 'Primeros Pasos', 'primeros-pasos', 'blue', 1),
(2, 'Configuración', 'configuracion', 'green', 2),
(3, 'Streaming', 'streaming', 'purple', 3),
(4, 'Avanzado', 'avanzado', 'orange', 4);
