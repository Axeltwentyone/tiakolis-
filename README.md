# Tiakolisé et fière

Site de précommandes + back office sur mesure (aux couleurs du site), en **Laravel 13** (PHP ≥ 8.3) avec **MySQL**.

| Adresse | Quoi |
|---|---|
| `/` | Le site (vue `resources/views/site.blade.php`, JS `resources/js/app.js`, CSS `resources/css/app.css`) |
| `/admin` | Le back office (Blade, `resources/views/admin`, `resources/css/admin.css`) : tableau de bord, précommandes, statuts, export Excel, pièces, prix, photos, **stock par taille**. Pensé pour le téléphone. |
| `GET /api/catalogue` | Pièces actives + stock restant par taille (lu par le site) |
| `POST /api/precommandes` | Crée la commande (référence `TEF-XXXXX` + jeton), réserve le stock, prévient l'équipe |
| `PUT /api/precommandes/{ref}` | « Modifier mes coordonnées » : met à jour la même commande (en-tête `X-Jeton`) |
| `POST /api/precommandes/{ref}/capture` | Capture du paiement Wave → visible dans le back office + e-mail à l'équipe avec la capture |

## Installation

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
# dans .env : DB_*, NOTIFICATION_EMAIL, ADMIN_EMAIL / ADMIN_PASSWORD, et l'envoi d'e-mails (MAIL_*)
php artisan migrate --seed      # tables + les 4 pièces + stock de départ + compte admin
php artisan storage:link        # photos envoyées depuis le back office
npm run build                   # ou `npm run dev` pendant le développement
php artisan serve               # http://localhost:8000
```

## Comment ça marche

- **Parcours client** (tiroir panier) : 1. panier + livraison Yango (Abidjan uniquement, course payée au livreur) → 2. coordonnées (WhatsApp, e-mail facultatif, quartier, commune) → 3. paiement Wave : le client envoie le montant au `WAVE_NUMERO` puis envoie sa capture depuis le site (ou par WhatsApp). La commande passe alors en « Capture à vérifier ».
- **Wave** : `WAVE_NUMERO` (affiché sur le site), `WAVE_LIEN` (lien de paiement Wave facultatif, `{montant}` remplacé par le total), `WHATSAPP_NUMERO` (par défaut le numéro Wave).
- **Captures** : rangées hors du dossier public (`storage/app/private/captures`), visibles seulement dans le back office une fois connecté.

- **Stock limité** : chaque taille de chaque pièce a un stock (`stocks`). Une précommande le décrémente dans une transaction verrouillée (on ne vend jamais plus que le stock). Passer une précommande en « Annulée » (ou la supprimer) remet les pièces en stock.
- **Prix** : toujours recalculés côté serveur, à partir de la base.
- **E-mails** : l'équipe (`NOTIFICATION_EMAIL` + chaque admin qui a répondu oui dans `admin:ajouter`) reçoit « Nouvelle commande », « Nouveau paiement Wave » (capture jointe) et « Paiement validé ». Le client (s'il a donné son e-mail) reçoit « Merci pour ta précommande » puis « C'est validé ! » quand un admin passe la commande en « Payée ». Les échecs d'envoi sont notés dans `storage/logs/laravel.log`. En local, `MAIL_MAILER=log` : les e-mails sont écrits dans `storage/logs/laravel.log`. En production, brancher un service SMTP (Brevo, Resend, Mailgun…).
- **Photos** : les photos d'origine sont dans `public/assets/`. Celles envoyées depuis le back office vont dans `storage/app/public/produits/`.
- **Anti-spam** : pot de miel + 5 précommandes par IP / 10 min. Connexion au back office : 5 essais par minute.
- **Couleurs et polices** : `resources/css/theme.css`, partagé par le site et le back office.
- **Tests** : `php artisan test` (API, stock, back office).

## Mise en ligne (GitHub → o2switch)

Les fichiers compilés (`public/build`) sont dans le dépôt : sur le serveur, il suffit de PHP + Composer, pas de Node.
Après chaque modification du CSS/JS en local : `npm run build`, puis commit.

**1. Base de données (cPanel → Bases de données MySQL)** : créer la base et un utilisateur, lui donner tous les privilèges.
o2switch préfixe les noms (ex. `moncompte_tiakolise`).

**2. PHP (cPanel → Sélectionner une version de PHP)** : PHP **8.3 ou plus**, extensions `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `intl`.
Dans les options : `upload_max_filesize = 10M`, `post_max_size = 12M` (captures Wave).

**3. Code (SSH)** :

```sh
# le dossier ~/tiakoliseetfier existe déjà (créé par cPanel avec le domaine) : on y récupère le dépôt
cd ~/tiakoliseetfier
git init -b main && git remote add origin https://github.com/Axeltwentyone/tiakolis-.git
git fetch origin && git checkout -f -t origin/main
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
nano .env   # voir ci-dessous
php artisan migrate --force --seed   # tables + 4 pièces + stock + compte admin (ou importer database/tiakolise.sql puis : php artisan db:seed --force)
php artisan storage:link
php artisan optimize
```

**4. Domaine (cPanel → Domaines)** : la racine du domaine est `~/tiakoliseetfier/public` (jamais `~/tiakoliseetfier` : le `.env` serait lisible).

**5. `.env` de production** :

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tiakoliseetfier.com
DB_HOST=localhost
DB_DATABASE=moncompte_tiakolise
DB_USERNAME=moncompte_tiakolise
DB_PASSWORD=…
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=brocolis.o2switch.net   # nom du serveur o2switch (le certificat de mail.tiakoliseetfier.com est à son nom)
MAIL_PORT=465
MAIL_USERNAME=no-reply@tiakoliseetfier.com
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=no-reply@tiakoliseetfier.com
NOTIFICATION_EMAIL=…              # reçoit les commandes et les paiements
WAVE_NUMERO="+225 07 88 11 72 61"
ADMIN_EMAIL=…                     # compte du back office créé par --seed
ADMIN_PASSWORD=…                  # long et unique
```

**Ajouter un admin** (ou changer un mot de passe) : `php artisan admin:ajouter`. Pour que plusieurs personnes reçoivent les e-mails de commande et de paiement : `NOTIFICATION_EMAIL=a@x.com,b@y.com,c@z.com`.

**6. Mettre à jour le site** après un `git push` :

```sh
cd ~/tiakoliseetfier && git pull && composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan optimize
```

**Délivrabilité** : activer SPF et DKIM pour le domaine (cPanel → Délivrabilité des e-mails), sinon les mails risquent d'arriver en spam.
