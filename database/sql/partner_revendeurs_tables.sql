-- ============================================================
-- Module Partenaires revendeurs – création des tables
-- À exécuter dans MySQL si vous n'utilisez pas php artisan migrate
-- Ordre : 1) partners, 2) liaisons reservations/clients, 3) colonnes partners, 4) autres tables
-- Prérequis : tables users, reservations, clients, voyages existantes
-- ============================================================

-- ========== TABLE PARTNERS (compte partenaire / revendeur) ==========
CREATE TABLE IF NOT EXISTS `partners` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `raison_sociale` VARCHAR(255) NOT NULL,
  `nom_commercial` VARCHAR(255) NULL,
  `nom_responsable` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `telephone` VARCHAR(50) NOT NULL,
  `adresse` VARCHAR(255) NULL,
  `ville` VARCHAR(100) NULL,
  `code_postal` VARCHAR(20) NULL,
  `pays` VARCHAR(100) NULL,
  `ice` VARCHAR(50) NULL COMMENT 'Identifiant Commun Entreprise',
  `if` VARCHAR(50) NULL COMMENT 'Identifiant Fiscal',
  `rc` VARCHAR(50) NULL COMMENT 'Registre de Commerce',
  `document_path` VARCHAR(500) NULL COMMENT 'Pièce justificative',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, validated, rejected, suspended',
  `validated_at` TIMESTAMP NULL,
  `validated_by` BIGINT UNSIGNED NULL,
  `rejected_at` TIMESTAMP NULL,
  `rejected_reason` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partners_email_unique` (`email`),
  KEY `partners_status_index` (`status`),
  KEY `partners_user_id_foreign` (`user_id`),
  KEY `partners_validated_by_foreign` (`validated_by`),
  CONSTRAINT `partners_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partners_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== LIAISONS PARTENAIRE AVEC RESERVATIONS ET CLIENTS ==========
-- Réservations : ajout de partner_id (une réservation peut être faite par un partenaire)
ALTER TABLE `reservations` ADD COLUMN `partner_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `reservations` ADD INDEX `reservations_partner_id_index` (`partner_id`);
ALTER TABLE `reservations` ADD CONSTRAINT `reservations_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL;

-- Clients : ajout de partner_id (un client peut appartenir à un partenaire)
ALTER TABLE `clients` ADD COLUMN `partner_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `clients` ADD INDEX `clients_partner_id_index` (`partner_id`);
ALTER TABLE `clients` ADD CONSTRAINT `clients_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL;

-- (Si une colonne existe déjà, MySQL renverra une erreur : ignorer cette ligne et passer à la suivante.
--  Pour une base déjà migrée, commenter ou supprimer les blocs ALTER TABLE ci-dessus.)

-- ========== COLONNES SUPPLÉMENTAIRES SUR PARTNERS (revendeurs) ==========
-- Exécuter chaque ligne uniquement si la colonne n'existe pas encore (sinon erreur "Duplicate column").
ALTER TABLE `partners` ADD COLUMN `partner_type` VARCHAR(50) NULL COMMENT 'agence, commercial_independent, point_vente, apporteur_affaires, agence_etranger' AFTER `pays`;
ALTER TABLE `partners` ADD COLUMN `rib_iban` VARCHAR(100) NULL AFTER `document_path`;
ALTER TABLE `partners` ADD COLUMN `rib_bic` VARCHAR(20) NULL AFTER `rib_iban`;
ALTER TABLE `partners` ADD COLUMN `payment_mode` VARCHAR(50) NULL COMMENT 'virement, cheque, etc.' AFTER `rib_bic`;
ALTER TABLE `partners` ADD COLUMN `contract_path` VARCHAR(500) NULL AFTER `payment_mode`;

-- 2) Table partner_voyage_access (accès voyage par partenaire)
CREATE TABLE IF NOT EXISTS `partner_voyage_access` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_id` BIGINT UNSIGNED NOT NULL,
  `voyage_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_voyage_access_partner_id_voyage_id_unique` (`partner_id`, `voyage_id`),
  KEY `partner_voyage_access_partner_id_foreign` (`partner_id`),
  KEY `partner_voyage_access_voyage_id_foreign` (`voyage_id`),
  CONSTRAINT `partner_voyage_access_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partner_voyage_access_voyage_id_foreign` FOREIGN KEY (`voyage_id`) REFERENCES `voyages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Table partner_commission_rules (règles de commission)
CREATE TABLE IF NOT EXISTS `partner_commission_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_id` BIGINT UNSIGNED NULL,
  `voyage_id` BIGINT UNSIGNED NULL COMMENT 'null = tous les voyages',
  `type` VARCHAR(20) NOT NULL DEFAULT 'percent' COMMENT 'percent, fixed',
  `value` DECIMAL(10, 2) NOT NULL COMMENT 'pourcent ou montant fixe',
  `min_volume` INT NULL COMMENT 'nombre de ventes pour appliquer (optionnel)',
  `valid_from` DATE NULL,
  `valid_until` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `partner_commission_rules_partner_id_voyage_id_index` (`partner_id`, `voyage_id`),
  CONSTRAINT `partner_commission_rules_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Table partner_payouts (paiements aux partenaires)
CREATE TABLE IF NOT EXISTS `partner_payouts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, cancelled',
  `paid_at` TIMESTAMP NULL,
  `reference` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `partner_payouts_partner_id_status_index` (`partner_id`, `status`),
  CONSTRAINT `partner_payouts_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Table partner_commissions (commission par réservation)
CREATE TABLE IF NOT EXISTS `partner_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` BIGINT UNSIGNED NOT NULL,
  `partner_id` BIGINT UNSIGNED NOT NULL,
  `rule_id` BIGINT UNSIGNED NULL,
  `reservation_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(30) NOT NULL DEFAULT 'calculated' COMMENT 'calculated, pending, validated, paid, cancelled',
  `validated_at` TIMESTAMP NULL,
  `paid_at` TIMESTAMP NULL,
  `payout_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_commissions_reservation_id_unique` (`reservation_id`),
  KEY `partner_commissions_partner_id_status_index` (`partner_id`, `status`),
  CONSTRAINT `partner_commissions_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partner_commissions_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partner_commissions_payout_id_foreign` FOREIGN KEY (`payout_id`) REFERENCES `partner_payouts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Table partner_documents (documents partenaire)
CREATE TABLE IF NOT EXISTS `partner_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_id` BIGINT UNSIGNED NULL COMMENT 'null = document global',
  `type` VARCHAR(50) NOT NULL COMMENT 'contract, commission_grid, conditions, marketing',
  `name` VARCHAR(255) NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `partner_documents_partner_id_type_index` (`partner_id`, `type`),
  CONSTRAINT `partner_documents_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== RÉCAP DES LIAISONS (tables liées à partners) ==========
-- partners.user_id          -> users.id (compte login)
-- partners.validated_by     -> users.id (admin qui a validé)
-- reservations.partner_id   -> partners.id (résa faite par le partenaire)
-- clients.partner_id       -> partners.id (client du partenaire)
-- partner_voyage_access     -> partners + voyages (N-N : quels voyages le partenaire peut vendre)
-- partner_commission_rules  -> partners (optionnel) + voyages (optionnel)
-- partner_commissions       -> reservations + partners + partner_payouts (optionnel)
-- partner_payouts          -> partners
-- partner_documents        -> partners (optionnel, null = document global)
