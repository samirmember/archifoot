# Procédure complète de déploiement sur O2Switch

Ce guide est adapté à ton architecture actuelle :

- **Front Angular** (`front/`)
- **API Symfony** (`server/`)
- **Base MariaDB/MySQL**

et à tes domaines :

- Front : `https://archifoot.signawebsolutions.com/`
- API : `https://api.archifoot.signawebsolutions.com/`

> Objectif : héberger le projet **hors `public_html`**, dans `/home/<cpanel_user>/archifoot`.

---

## 1) Pré-requis importants avant de commencer

1. Vérifie la version PHP disponible sur O2Switch.
   - Ton `server/composer.json` exige actuellement **PHP >= 8.5**.
   - Si O2Switch ne propose pas 8.5 (très probable), il faut soit :
     - passer l’API sur un hébergement/VM compatible,
     - ou ajuster les dépendances pour une version supportée par O2Switch.

2. Assure-toi d’avoir :
   - accès **cPanel**,
   - accès **SSH**,
   - un utilisateur DB + mot de passe,
   - un moyen de transférer les fichiers (Git, SFTP, rsync).

3. Active HTTPS pour les 2 sous-domaines (AutoSSL cPanel).

---

## 2) Arborescence cible recommandée

Dans ton home cPanel (exemple `/home/<cpanel_user>`), je recommande :

```text
/home/<cpanel_user>/archifoot/
  front-src/                  # code source Angular (optionnel en prod)
  api/                        # projet Symfony (code complet)
    public/                   # web root Symfony
  front-public/               # build Angular final servi en HTTP
```

Pourquoi c’est mieux :
- code source non exposé publiquement,
- séparation claire front/API,
- maintenance plus simple.

> Si tu veux absolument utiliser `/archifoot` comme docroot du front, mets directement le build Angular dedans (au lieu de `front-public/`).

---

## 3) Configuration des domaines dans cPanel

### A. Front principal

Dans **cPanel > Domains** :
- domaine/sous-domaine : `archifoot.signawebsolutions.com`
- **Document Root** :
  - soit `/home/<cpanel_user>/archifoot/front-public` (recommandé),
  - soit `/home/<cpanel_user>/archifoot` (ta préférence).

### B. API

Pour `api.archifoot.signawebsolutions.com` :
- code du projet dans `/home/<cpanel_user>/archifoot/api`
- **Document Root** à pointer vers :
  - `/home/<cpanel_user>/archifoot/api/public` (**recommandé Symfony**)

> C’est la meilleure pratique Symfony : seule `public/` doit être exposée.
> Tu gardes bien le projet dans `/archifoot/api`, mais le web root du sous-domaine cible `public/`.

---

## 4) Déploiement de l’API Symfony (`server` -> `/archifoot/api`)

### Étape 1 — Copier le code

Depuis ton poste local :

```bash
rsync -avz --delete server/ <cpanel_user>@<host-o2switch>:/home/<cpanel_user>/archifoot/api/
```

(ou via Git clone/pull côté serveur SSH)

### Étape 2 — Installer les dépendances PHP

En SSH sur O2Switch :

```bash
cd /home/<cpanel_user>/archifoot/api
composer install --no-dev --optimize-autoloader
```

### Étape 3 — Créer le `.env.local` de prod

Dans `/home/<cpanel_user>/archifoot/api/.env.local` :

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=CHANGE_ME_LONG_RANDOM
APP_API_KEY=CHANGE_ME_LONG_RANDOM

DATABASE_URL="mysql://DB_USER:DB_PASSWORD@127.0.0.1:3306/DB_NAME?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

CORS_ALLOW_ORIGIN='^https://archifoot\.signawebsolutions\.com$'
DEFAULT_URI=https://api.archifoot.signawebsolutions.com
```

### Étape 4 — Préparer le cache

```bash
cd /home/<cpanel_user>/archifoot/api
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### Étape 5 — Base de données

Selon ton process :

```bash
cd /home/<cpanel_user>/archifoot/api
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

ou importer ton dump SQL via phpMyAdmin/CLI.

### Étape 6 — Permissions

```bash
cd /home/<cpanel_user>/archifoot/api
mkdir -p var/cache var/log
chmod -R 775 var
```

---

## 5) Déploiement du front Angular (`front`)

### Étape 1 — Configurer l’URL API de prod

Dans `front/src/environments/environment.prod.ts`, mets :

```ts
api: {
  baseUrl: 'https://api.archifoot.signawebsolutions.com/api',
  apiKey: 'API_KEY_DE_PROD'
}
```

> Idéalement, évite une clé API statique côté front public ; si possible, passe à une auth par utilisateur (JWT/session).

### Étape 2 — Build production

En local (ou CI) :

```bash
cd front
npm ci
npx ng build --configuration production
```

### Étape 3 — Publier le build

Le build Angular est généré dans `front/dist/front/browser`.

Déploie ce contenu vers :
- `/home/<cpanel_user>/archifoot/front-public` (recommandé),
- ou `/home/<cpanel_user>/archifoot` (ta préférence).

Exemple :

```bash
rsync -avz --delete front/dist/front/browser/ <cpanel_user>@<host-o2switch>:/home/<cpanel_user>/archifoot/front-public/
```

---

## 6) Routing SPA Angular (indispensable)

Crée un `.htaccess` dans le dossier publié du front (`front-public/` ou `/archifoot`) :

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # Laisser passer les fichiers/dossiers existants
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]

  # Rediriger le reste vers index.html (Angular SPA)
  RewriteRule ^ index.html [L]
</IfModule>
```

---

## 7) Sécurité et bonnes pratiques (fortement recommandé)

1. **Ne laisse pas** les secrets de prod dans les fichiers versionnés.
2. Génére des clés longues pour `APP_SECRET` et `APP_API_KEY`.
3. Restreins CORS au domaine front exact.
4. Ajoute des headers de sécurité via `.htaccess` (HSTS, X-Frame-Options, etc.).
5. Sauvegardes automatiques DB + fichiers.
6. Mets en place un mini pipeline de déploiement (build + rsync) pour éviter les erreurs manuelles.

---

## 8) Vérifications après mise en ligne

1. Front :
   - `https://archifoot.signawebsolutions.com/` charge bien.
2. API :
   - `https://api.archifoot.signawebsolutions.com/api` répond.
3. CORS :
   - appels XHR front -> API sans erreur CORS.
4. Auth API key :
   - la clé envoyée par le front correspond à `APP_API_KEY` en prod.
5. Logs Symfony :
   - `/home/<cpanel_user>/archifoot/api/var/log/` sans erreurs critiques.

---

## 9) Proposition “encore meilleure” (si tu veux industrialiser)

- **CI/CD (GitHub Actions)** :
  - build Angular,
  - tests,
  - déploiement SSH automatique.
- **Environnements séparés** :
  - `staging.archifoot...` + `api-staging...`.
- **API Auth améliorée** :
  - abandon de la clé statique dans le front public,
  - JWT/session + rôles.
- **Monitoring** :
  - uptime checks + alertes email/Slack.

---

## 10) Résumé ultra-court

- Oui, tu peux héberger hors `public_html`.
- Garde le code API dans `/archifoot/api` mais pointe le docroot du sous-domaine vers `/archifoot/api/public`.
- Publie le build Angular dans un docroot front dédié (`/archifoot/front-public` recommandé).
- Configure `.env.local` prod + DB + CORS + API key.
- Vérifie HTTPS, CORS, et réécriture SPA Angular.
