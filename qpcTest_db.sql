-- ============================================================
-- Base de données : qpcTest_db
-- Projet : Question pour un Champion — HESTIM 2025/2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS qpcTest_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE qpcTest_db;

-- ============================================================
-- TABLE : users
-- Stocke les joueurs inscrits
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    profile_pic   VARCHAR(255) DEFAULT NULL,
    score_total   INT UNSIGNED DEFAULT 0,

    -- Infos personnelles
    pays          VARCHAR(60)  DEFAULT NULL,
    age           TINYINT UNSIGNED DEFAULT NULL,   -- optionnel

    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : categories
-- Catégories de questions (Histoire, Géo, Sport...)
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom   VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catégories de base
INSERT INTO categories (nom) VALUES
    ('Histoire'),
    ('Géographie'),
    ('Sciences'),
    ('Sport'),
    ('Culture générale'),
    ('Informatique & Technologie'),
    ('Art & Littérature'),
    ('Cinéma & Musique'),
    ('Mathématiques');

-- ============================================================
-- TABLE : game_sessions
-- Une ligne = une partie jouée par un utilisateur
-- ============================================================
CREATE TABLE IF NOT EXISTS game_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    winner_id       INT UNSIGNED DEFAULT NULL,     -- null si solo ou abandon

    -- Résultat
    score           INT UNSIGNED DEFAULT 0,
    status          ENUM('active', 'finished', 'abandoned') DEFAULT 'active',

    -- Mode & difficulté
    game_mode       ENUM('solo', 'tournoi', 'rapidite', 'buzz') DEFAULT 'solo',
    difficulty      ENUM('facile', 'moyen', 'difficile') DEFAULT 'moyen',

    -- Temps
    started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at        TIMESTAMP NULL DEFAULT NULL,
    time_played     INT UNSIGNED DEFAULT 0,        -- durée totale en secondes

    -- Stats de la partie
    total_questions INT UNSIGNED DEFAULT 0,
    correct_answers INT UNSIGNED DEFAULT 0,
    wrong_answers   INT UNSIGNED DEFAULT 0,
    longest_streak  INT UNSIGNED DEFAULT 0,

    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : game_answers
-- Chaque réponse donnée pendant une partie
-- ============================================================
CREATE TABLE IF NOT EXISTS game_answers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id    INT UNSIGNED NOT NULL,
    question_id   VARCHAR(50)  NOT NULL,   -- ID depuis JSON ou future table SQL
    category_id   INT UNSIGNED DEFAULT NULL,
    chosen        VARCHAR(1)   NOT NULL,   -- 'a', 'b', 'c' ou 'd'
    correct       VARCHAR(1)   NOT NULL,   -- bonne réponse
    is_correct    TINYINT(1)   NOT NULL,
    time_taken    INT UNSIGNED DEFAULT 0,  -- secondes pour répondre
    hint_used     TINYINT(1)   DEFAULT 0,  -- indice utilisé ou non
    points_earned INT          DEFAULT 0,
    answered_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (session_id)  REFERENCES game_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : player_stats
-- Statistiques globales cumulées par joueur
-- Mise à jour à chaque fin de partie
-- ============================================================
CREATE TABLE IF NOT EXISTS player_stats (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,

    -- Parties
    total_games         INT UNSIGNED DEFAULT 0,
    games_finished      INT UNSIGNED DEFAULT 0,
    games_abandoned     INT UNSIGNED DEFAULT 0,
    victories           INT UNSIGNED DEFAULT 0,
    defeats             INT UNSIGNED DEFAULT 0,

    -- Scores
    best_score          INT UNSIGNED DEFAULT 0,
    average_score       FLOAT        DEFAULT 0,
    winrate             FLOAT        DEFAULT 0,    -- % victoires (calculé à chaque MAJ)

    -- Réponses
    total_correct       INT UNSIGNED DEFAULT 0,
    total_wrong         INT UNSIGNED DEFAULT 0,
    best_streak         INT UNSIGNED DEFAULT 0,    -- meilleure série toutes parties

    -- Temps
    total_time_played   INT UNSIGNED DEFAULT 0,    -- secondes cumulées
    average_time_answer FLOAT        DEFAULT 0,    -- secondes moyennes par réponse

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : player_stats_by_category
-- Stats par catégorie pour le graphe de performances
-- Une ligne par joueur par catégorie
-- ============================================================
CREATE TABLE IF NOT EXISTS player_stats_by_category (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED NOT NULL,

    total_questions INT UNSIGNED DEFAULT 0,    -- nb de questions posées dans cette catégorie
    correct         INT UNSIGNED DEFAULT 0,    -- nb de bonnes réponses
    wrong           INT UNSIGNED DEFAULT 0,    -- nb de mauvaises réponses
    success_rate    FLOAT        DEFAULT 0,    -- % bonnes réponses (calculé à chaque MAJ)

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_player_category (user_id, category_id),
    FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : session_analytics
-- Données d'engagement : temps passé, heure de jeu, mode préféré
-- Une ligne par session utilisateur (pas forcément une partie)
-- ============================================================
CREATE TABLE IF NOT EXISTS session_analytics (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED DEFAULT NULL,     -- null si non connecté
    connected_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    disconnected_at TIMESTAMP NULL DEFAULT NULL,
    time_on_site    INT UNSIGNED DEFAULT 0,        -- secondes passées sur le site
    hour_of_day     TINYINT UNSIGNED DEFAULT NULL, -- heure de connexion (0-23)
    day_of_week     TINYINT UNSIGNED DEFAULT NULL, -- jour (0=lundi, 6=dimanche)

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : question_analytics
-- Difficulté réelle des questions basée sur les réponses
-- Une ligne par question (mise à jour à chaque réponse)
-- ============================================================
CREATE TABLE IF NOT EXISTS question_analytics (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id         VARCHAR(50) NOT NULL UNIQUE,  -- ID depuis JSON
    category_id         INT UNSIGNED DEFAULT NULL,

    times_shown         INT UNSIGNED DEFAULT 0,   -- nb de fois posée
    times_correct       INT UNSIGNED DEFAULT 0,   -- nb de bonnes réponses
    times_wrong         INT UNSIGNED DEFAULT 0,   -- nb de mauvaises réponses
    times_abandoned     INT UNSIGNED DEFAULT 0,   -- nb d'abandons sur cette question
    avg_time_answer     FLOAT        DEFAULT 0,   -- temps moyen pour répondre
    success_rate        FLOAT        DEFAULT 0,   -- % bonnes réponses (calculé)

    -- Classement automatique de difficulté réelle
    real_difficulty     ENUM('trop_facile', 'facile', 'moyen', 'difficile', 'trop_difficile') DEFAULT 'moyen',

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE : funnel_analytics
-- Combien de joueurs abandonnent à chaque étape
-- ============================================================
CREATE TABLE IF NOT EXISTS funnel_analytics (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    etape       ENUM(
                    'visite_accueil',
                    'visite_login',
                    'visite_regles',
                    'visite_apropos',
                    'login_reussi',
                    'login_echoue',
                    'inscription_reussie',
                    'lance_partie',
                    'partie_terminee',
                    'partie_abandonnee'
                ) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- VUE : leaderboard
-- Classement général avec taux de réussite
-- ============================================================
CREATE OR REPLACE VIEW leaderboard AS
    SELECT
        u.id,
        u.username,
        u.profile_pic,
        u.pays,
        u.score_total,
        ps.total_games,
        ps.victories,
        ps.best_score,
        ROUND(ps.winrate, 1)        AS winrate,
        ROUND(
            CASE WHEN (ps.total_correct + ps.total_wrong) > 0
                THEN ps.total_correct * 100.0 / (ps.total_correct + ps.total_wrong)
                ELSE 0
            END, 1
        )                           AS success_rate,
        RANK() OVER (ORDER BY u.score_total DESC) AS `rank`
    FROM users u
    LEFT JOIN player_stats ps ON ps.user_id = u.id
    ORDER BY u.score_total DESC
    LIMIT 100;

-- ============================================================
-- VUE : question_difficulty_report
-- Rapport sur les questions trop faciles / trop difficiles
-- ============================================================
CREATE OR REPLACE VIEW question_difficulty_report AS
    SELECT
        qa.question_id,
        c.nom           AS categorie,
        qa.times_shown,
        qa.success_rate,
        qa.avg_time_answer,
        qa.times_abandoned,
        qa.real_difficulty
    FROM question_analytics qa
    LEFT JOIN categories c ON c.id = qa.category_id
    ORDER BY qa.success_rate ASC;
