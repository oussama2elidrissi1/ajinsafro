-- ============================================================
-- Module Hôtels – création des tables principales
-- À exécuter dans MySQL si vous ne passez pas par php artisan migrate
-- Prérequis : base MySQL utilisée par Laravel (connexion mysql)
-- ============================================================

-- 1) Table hotels
CREATE TABLE IF NOT EXISTS `hotels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `address` VARCHAR(255) NULL,
  `city` VARCHAR(120) NULL,
  `country` VARCHAR(120) NULL,
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  `main_image_path` VARCHAR(255) NULL,
  `rating_average` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-5',
  `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `amenities_summary` JSON NULL COMMENT 'Cache simple des principaux équipements',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Table hotel_images
CREATE TABLE IF NOT EXISTS `hotel_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `position` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hotel_images_hotel_id_foreign` (`hotel_id`),
  CONSTRAINT `hotel_images_hotel_id_foreign` FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Table hotel_amenities (catalogue des équipements)
CREATE TABLE IF NOT EXISTS `hotel_amenities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(80) NULL COMMENT 'Classe d’icône front (ex: bx bx-wifi)',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hotel_amenities_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Pivot hotel_amenity_hotel
CREATE TABLE IF NOT EXISTS `hotel_amenity_hotel` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id` BIGINT UNSIGNED NOT NULL,
  `hotel_amenity_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hotel_amenity_hotel_unique` (`hotel_id`,`hotel_amenity_id`),
  KEY `hotel_amenity_hotel_hotel_id_foreign` (`hotel_id`),
  KEY `hotel_amenity_hotel_amenity_id_foreign` (`hotel_amenity_id`),
  CONSTRAINT `hotel_amenity_hotel_hotel_id_foreign` FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  CONSTRAINT `hotel_amenity_hotel_amenity_id_foreign` FOREIGN KEY (`hotel_amenity_id`) REFERENCES `hotel_amenities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Table hotel_room_types
CREATE TABLE IF NOT EXISTS `hotel_room_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `code` VARCHAR(50) NULL,
  `capacity_adults` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `capacity_children` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de chambres de ce type',
  `base_price` DECIMAL(10,2) NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'MAD',
  `description` TEXT NULL,
  `amenities` JSON NULL COMMENT 'Options spécifiques à la chambre',
  `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hotel_room_types_hotel_id_foreign` (`hotel_id`),
  CONSTRAINT `hotel_room_types_hotel_id_foreign` FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Table hotel_reviews
CREATE TABLE IF NOT EXISTS `hotel_reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL COMMENT '1-5',
  `author_name` VARCHAR(120) NULL,
  `comment` TEXT NULL,
  `source` VARCHAR(50) NULL COMMENT 'internal, booking, google, etc.',
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hotel_reviews_hotel_id_visible_index` (`hotel_id`,`is_visible`),
  CONSTRAINT `hotel_reviews_hotel_id_foreign` FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

