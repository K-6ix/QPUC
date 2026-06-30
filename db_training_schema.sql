-- ════════════════════════════════════════════════════════════════
-- TRAINING — Schéma DB
--
-- Règle d'isolation : ces tables sont strictement séparées des
-- tables compétitives (game_sessions, player_stats, users.score_total).
-- Aucun trigger ni FK croisée vers les tables compétitives.
--
-- À exécuter dans phpMyAdmin (base qpcTest_db).
-- IF NOT EXISTS partout pour pouvoir relancer sans casse.
-- ════════════════════════════════════════════════════════════════

USE qpcTest_db;

-- ── 1. training_sessions ────────────────────────────────────────
-- Une ligne par session d'entraînement terminée (ou abandonnée).
CREATE TABLE IF NOT EXISTS training_sessions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    category_id     INT NOT NULL,
    score           INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    wrong_answers   INT NOT NULL DEFAULT 0,
    success_rate    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    avg_time        INT NOT NULL DEFAULT 0,          -- secondes / question
    best_streak     INT NOT NULL DEFAULT 0,
    final_level     ENUM('facile','moyen','difficile') DEFAULT 'moyen',
    started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('finished','abandoned') DEFAULT 'finished',
    FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_user_time (user_id, ended_at),
    INDEX idx_user_cat  (user_id, category_id)
);

-- ── 2. training_stats ────────────────────────────────────────────
-- Cumul par utilisateur (une seule ligne par user).
CREATE TABLE IF NOT EXISTS training_stats (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    total_sessions  INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    total_correct   INT NOT NULL DEFAULT 0,
    total_time      INT NOT NULL DEFAULT 0,          -- en secondes
    best_streak     INT NOT NULL DEFAULT 0,
    last_played     TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── 3. VIEW : training_session_history ───────────────────────────
-- Une session par ligne, numérotée par catégorie (pour le graph
-- "session 1, 2, 3..." de progression sur une catégorie donnée).
CREATE OR REPLACE VIEW training_session_history AS
SELECT
    ts.id               AS session_id,
    ts.user_id,
    ts.category_id,
    c.label             AS category_label,
    c.slug              AS category_slug,
    ts.score,
    ts.total_questions,
    ts.correct_answers,
    ts.success_rate,
    ts.avg_time,
    ts.final_level,
    ts.ended_at,
    ROW_NUMBER() OVER (
        PARTITION BY ts.user_id, ts.category_id
        ORDER BY ts.ended_at
    ) AS session_number
FROM training_sessions ts
LEFT JOIN categories c ON c.id = ts.category_id
WHERE ts.status = 'finished';

-- ── 4. VIEW : training_progress ──────────────────────────────────
-- Progression cumulative par (user, catégorie) — sert au dashboard
-- pour les recommandations de catégories faibles.
CREATE OR REPLACE VIEW training_progress AS
SELECT
    ts.user_id,
    ts.category_id,
    c.label                  AS category_label,
    c.slug                   AS category_slug,
    COUNT(*)                 AS sessions_count,
    SUM(ts.total_questions)  AS questions_answered,
    SUM(ts.correct_answers)  AS correct_total,
    ROUND(AVG(ts.success_rate), 2) AS avg_success_rate,
    MAX(ts.ended_at)         AS last_session
FROM training_sessions ts
LEFT JOIN categories c ON c.id = ts.category_id
WHERE ts.status = 'finished'
GROUP BY ts.user_id, ts.category_id, c.label, c.slug;
