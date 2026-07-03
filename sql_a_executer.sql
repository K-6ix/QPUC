-- ════════════════════════════════════════════════════════════════
--  QPC — Tables à ajouter (script idempotent : ré-exécutable sans risque)
--
--  À exécuter dans phpMyAdmin :
--   • EN LOCAL sur qpcTest_db (si pas déjà fait pour les 2 premières)
--   • SUR INFINITYFREE après avoir importé votre dump complet
-- ════════════════════════════════════════════════════════════════

-- 1. Historique des sessions d'entraînement (1 ligne par partie)
CREATE TABLE IF NOT EXISTS `training_sessions` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED NOT NULL,
  `category_id`     INT UNSIGNED NOT NULL,
  `score`           INT NOT NULL DEFAULT 0,
  `total_questions` INT NOT NULL DEFAULT 0,
  `correct_answers` INT NOT NULL DEFAULT 0,
  `wrong_answers`   INT NOT NULL DEFAULT 0,
  `success_rate`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `avg_time`        INT NOT NULL DEFAULT 0,
  `best_streak`     INT NOT NULL DEFAULT 0,
  `final_level`     ENUM('facile','moyen','difficile') NOT NULL DEFAULT 'moyen',
  `started_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`          ENUM('finished','abandoned') NOT NULL DEFAULT 'finished',
  PRIMARY KEY (`id`),
  KEY `idx_user`     (`user_id`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`)      ON DELETE CASCADE,
  CONSTRAINT `training_sessions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Stats cumulées d'entraînement (1 SEULE ligne par joueur → user_id UNIQUE)
CREATE TABLE IF NOT EXISTS `training_stats` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED NOT NULL,
  `total_sessions`  INT NOT NULL DEFAULT 0,
  `total_questions` INT NOT NULL DEFAULT 0,
  `total_correct`   INT NOT NULL DEFAULT 0,
  `total_time`      INT NOT NULL DEFAULT 0,
  `best_streak`     INT NOT NULL DEFAULT 0,
  `last_played`     DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  CONSTRAINT `training_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Déduplication des enveloppes de résultats (relais navigateur)
--    Chaque enveloppe signée n'est traitée qu'UNE fois même si les
--    2 (duel) ou 4 (championnat) joueurs la livrent tous.
CREATE TABLE IF NOT EXISTS `processed_matches` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_ref`    VARCHAR(64) NOT NULL,
  `processed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref` (`match_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
