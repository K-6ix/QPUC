/* ══════════════════════════════════════════════════════════════
   qpc-config.js — Configuration client (URL du serveur de jeu)

   ⚠️ APRÈS DÉPLOIEMENT : remplacez PROD_SERVER_URL ci-dessous par
   l'URL publique de votre serveur Node sur Koyeb, par exemple :
       https://qpc-server-kepler.koyeb.app
   (en HTTPS — Socket.io passera automatiquement en wss://)

   En local (localhost), rien à changer : le code détecte tout seul
   qu'il doit parler à http://localhost:3000.
   ══════════════════════════════════════════════════════════════ */
window.QPC_CONFIG = (function () {
    const PROD_SERVER_URL = 'https://qpc-server.onrender.com';

    const isLocal = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    return {
        SERVER_URL: isLocal ? 'http://localhost:3000' : PROD_SERVER_URL,
    };
})();
