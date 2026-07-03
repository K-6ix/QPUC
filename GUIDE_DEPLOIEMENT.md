# 🚀 Guide de déploiement QPC — InfinityFree + Koyeb (100% gratuit)

**Architecture cible :**
```
┌─────────────────────┐         ┌──────────────────────┐
│  InfinityFree       │         │  Koyeb               │
│  (PHP + MySQL)      │◀───┐    │  (Node.js/Socket.io) │
│  Pages, auth, BDD   │    │    │  server.js (1v1 +    │
│  save_elo.php       │    │    │  championnat)        │
└─────────────────────┘    │    └──────────┬───────────┘
        ▲                  │               │ enveloppe signée HMAC
        │ POST enveloppe   │               ▼
        └──────────── Navigateur des joueurs (relais)
```
Les résultats de partie sont **signés** par Node (HMAC-SHA256) et livrés au PHP
par le navigateur des joueurs — car InfinityFree bloque les appels directs
serveur→serveur. Un joueur ne peut PAS tricher : modifier 1 caractère du
payload invalide la signature.

---

## Étape 0 — Préparer les secrets (5 min)

1. Génère une clé secrète forte. En local, dans un terminal :
   ```
   php -r "echo bin2hex(random_bytes(32));"
   ```
   (ou n'importe quel générateur de chaîne aléatoire de 64 caractères)
2. **Note-la quelque part** : tu vas la mettre à DEUX endroits (Koyeb + InfinityFree).

## Étape 1 — GitHub (obligatoire cahier des charges, et Koyeb déploie depuis là)

1. Le `.gitignore` fourni exclut `node_modules/`, les logs et **les dumps SQL**.
2. Pousse le projet :
   ```
   git add .
   git commit -m "Préparation déploiement (relais HMAC + config)"
   git push
   ```
3. ⚠️ Vérifie sur github.com qu'AUCUN fichier `qpctest_db*.sql` n'apparaît.
   (`db.php` et `qpc_secret.php` peuvent rester dans le repo **avec leurs
   valeurs locales par défaut** — les vraies valeurs de prod ne seront
   éditées QUE sur InfinityFree, jamais commitées.)

## Étape 2 — Koyeb (le serveur Node)

1. Va sur **koyeb.com** → *Sign up* avec ton compte **GitHub**.
   - Il est possible qu'une carte soit demandée si leur système n'arrive pas
     à te vérifier automatiquement. Si ça bloque et que tu ne veux pas de
     carte → passe au plan B Render (fin de ce guide, mêmes étapes).
2. *Create Web Service* → source **GitHub** → choisis ton repo QPC.
3. Configuration :
   - **Builder** : Buildpack (détection auto Node via package.json)
   - **Run command** : `npm start` (défini dans package.json → `node server.js`)
   - **Instance** : **Free** (512 MB / 0.1 vCPU)
   - **Region** : Frankfurt (le plus proche du Maroc)
   - **Port exposé** : 8000 par défaut chez Koyeb → notre serveur lit
     `process.env.PORT`, donc laisse la valeur proposée telle quelle.
   - **Health check** : chemin `/health` (route ajoutée dans server.js)
4. **Variables d'environnement** (section Environment variables) :
   | Nom | Valeur |
   |---|---|
   | `SERVER_KEY` | ta clé de l'étape 0 |
   | `DIRECT_SAVE` | `0` |
   | `CORS_ORIGINS` | l'URL de ton site InfinityFree, ex : `https://monqpc.fwh.is` (tu pourras la compléter après l'étape 3 — redéploie ensuite) |
5. *Deploy* → à la fin, note l'URL publique, du style
   `https://qpc-server-XXXX.koyeb.app`. Teste : ouvre `URL/health` → doit
   afficher `OK`.

## Étape 3 — InfinityFree (le site PHP + la base)

1. Sur ton compte InfinityFree (celui de LINKEY marche) → *Create Account*
   → choisis un sous-domaine (ex : `monqpc.fwh.is`).
2. **Base de données** : panneau → MySQL Databases → crée la base. Note :
   hôte (`sqlXXX.infinityfree.com`), nom de base, utilisateur, mot de passe.
3. **Import SQL** (phpMyAdmin du panneau) :
   1. importe ton **dump complet** local (celui régénéré, avec TOUTES les tables) ;
   2. puis exécute **`sql_a_executer.sql`** (tables training + `processed_matches`).
      Il est ré-exécutable sans risque (IF NOT EXISTS).
4. **Upload des fichiers** dans `htdocs/` (via le File Manager ou FileZilla) :
   - ✅ tous les `.php`, `.js` (client), `.css`, images, `questions.json`,
     le dossier `championship/`, `uploads/` (vide), `.htaccess`, `qpc-config.js`
   - ❌ PAS `node_modules/`, PAS `server.js`/`server-championship.js`,
     PAS `package.json`, PAS les dumps `.sql`
     (le `.htaccess` les bloque de toute façon si tu te trompes)
5. **Édite sur le serveur** (File Manager → Edit) :
   - `db.php` → les 4 identifiants MySQL de l'étape 2 ;
   - `qpc_secret.php` → ta clé de l'étape 0 (la MÊME que sur Koyeb) ;
   - `qpc-config.js` → remplace `https://VOTRE-APP.koyeb.app` par ton URL Koyeb.
6. Retourne sur **Koyeb** → complète `CORS_ORIGINS` avec
   `https://monqpc.fwh.is` (ton vrai domaine, en https, SANS slash final)
   → *Redeploy*.

## Étape 4 — UptimeRobot (optionnel : zéro endormissement)

Koyeb Free s'endort sans trafic mais se réveille en 1-5 s, et **ne s'endort
jamais tant que des joueurs sont connectés** (websocket maintenu). Si tu veux
qu'il ne dorme JAMAIS :
1. uptimerobot.com → compte gratuit → *Add Monitor* → type HTTP(s)
2. URL : `https://ton-app.koyeb.app/health` — intervalle **5 minutes**.

## Étape 5 — Checklist de test (dans l'ordre)

- [ ] `https://ton-app.koyeb.app/health` → `OK`
- [ ] Ton site InfinityFree s'ouvre, tu peux créer un compte + te connecter
- [ ] Mode entraînement : joue une session → le score apparaît au dashboard
      (= tables training OK)
- [ ] Duel 1v1 (2 navigateurs/2 appareils) : la partie se lance
      (= CORS + Socket.io OK)
- [ ] Fin de duel **classé** : l'ELO change au dashboard
      (= relais HMAC + `processed_matches` OK)
- [ ] Championnat 4 joueurs jusqu'au bout : classement + ELO sauvegardés
- [ ] Console navigateur (F12) : aucune erreur rouge CORS

## 🔧 Dépannage express

| Symptôme | Cause probable | Fix |
|---|---|---|
| Console : erreur CORS | `CORS_ORIGINS` absent/incorrect sur Koyeb | Mets l'origine EXACTE (https, sans slash final), redeploy |
| Lobby tourne dans le vide | `qpc-config.js` pas édité / mauvaise URL | Vérifie `PROD_SERVER_URL` |
| ELO pas sauvegardé, réponse "Signature invalide" | Clés différentes Koyeb ↔ qpc_secret.php | Recopie la MÊME clé des deux côtés |
| Réponse "Enveloppe expirée" | Horloge décalée (rare) | Rejoue ; si persistant, dis-le moi |
| "Table processed_matches doesn't exist" | `sql_a_executer.sql` pas exécuté sur InfinityFree | Exécute-le |
| Erreur 403 bizarre sur une page | Un fichier bloqué par `.htaccess` est appelé directement | Normal pour db/secret ; sinon dis-moi lequel |

## 🅱️ Plan B : Render (si Koyeb exige une carte)

Le code est **identique** pour Render, seules les étapes 2 changent :
render.com → sign up GitHub (sans carte) → *New Web Service* → repo →
Build `npm install`, Start `npm start`, Instance **Free** → mêmes variables
d'env (`SERVER_KEY`, `DIRECT_SAVE=0`, `CORS_ORIGINS`) → URL en `.onrender.com`
à mettre dans `qpc-config.js`. Render s'endort après 15 min (réveil 30-60 s) →
l'étape 4 UptimeRobot devient fortement recommandée.
