# QPC — Pack Difficulté Adaptative (v3)

Cette version ajoute le **1v1 calibré ELO** et la section **"Comment ça marche"** sur about us.

## 6 fichiers

| Fichier | Action |
|---|---|
| `training.php` | **Remplace** — mode entraînement adaptatif (rolling window) |
| `server.js` | **Remplace** — pioche 1v1 par mix ELO (`getQuestionMix`) |
| `aboutus.php` | **Remplace** — ajoute la section "Sous le capot" |
| `save_training.php` | **Nouveau** — backend training |
| `questions.json` | **Remplace** — 499 questions |
| `db_training_schema.sql` | À passer une fois dans phpMyAdmin |

## Ce qui change techniquement

### Mode Entraînement (training.php)
- Pioche en temps réel selon les 5 dernières réponses
- Badge "Niveau IA" qui bouge en jeu
- Timer modulé ±20 % selon perf
- 15 questions par session
- Hook backend : `save_training.php` → tables `training_*`

### Mode 1v1 (server.js)
- Nouvelle fonction `getQuestionMix(elo)` avec **switch par paliers de 100 ELO**, retourne `{facile, moyen, difficile}` dont la somme = 1.0
- Nouvelle fonction `pickQuestionsByMix(pool, mix, total)` qui pioche selon les proportions
- Bug corrigé : `buildQuestions()` était appelée sans avgElo (tous les matchs piochaient comme à 1200). Maintenant on calcule la moyenne ELO des 2 joueurs présents.
- Log serveur visible : `🎯 ELO 1650 → cible {...} → tiré F:3/M:5/D:2`

### About us
- Nouvelle section avant le footer : "Une intelligence qui s'adapte à vous"
- 2 cartes côte à côte :
  - Mode Entraînement : schéma de la fenêtre glissante
  - Mode 1v1 : table visuelle ELO → mix avec barres de proportions
- Cohérent avec le theme dark/gold existant, responsive (empilé sur mobile <900px)

## Ordre d'installation

1. Backup de `qvpcPhpV2/` actuel.
2. Copier les 6 fichiers à la racine du projet.
3. Si pas encore fait : exécuter `db_training_schema.sql` dans phpMyAdmin (`qpcTest_db`).
4. **Redémarrer Node** (`server.js` a changé) :
   ```
   Ctrl+C dans le terminal node
   node server.js
   ```
5. Hard refresh navigateur (Ctrl+Shift+R).

## Tests

### Test training (5 min)
1. `training.php` → catégorie spécifique → 15 questions
2. Badge "Niveau IA" doit bouger pendant la partie
3. F12 console : `[QPC training] Session sauvegardée — id = X`

### Test 1v1 (10 min, deux navigateurs nécessaires)
1. Lance 2 navigateurs (un en privé/incognito), connecte 2 comptes différents
2. Crée une room 1v1, l'autre rejoint
3. Lance le match
4. Côté terminal node, tu dois voir : `🎯 ELO XXXX → cible {...} → tiré F:X/M:X/D:X`
5. Vérifie que la console correspond bien à la moyenne des ELO des 2 joueurs

### Test about us (instantané)
- Va sur `aboutus.php`
- Scroll jusqu'en bas (avant le footer)
- Section "Sous le capot" avec les 2 cartes visibles, badges adaptatifs animés en hover

## Prochaine étape suggérée

- Écran de fin training avec graph Chart.js de progression (vue `training_session_history`)
- Mode solo calibré ELO (appliquer le même système qu'en 1v1 sur game.html mode=solo)
