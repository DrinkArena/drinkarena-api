# drinkarena-api

## Quickstart

### For Development

#### Requirements

- PHP >= 8.1
- Symfony CLI
- Composer

#### Git configuration

```sh
$ git clone git@github.com:DrinkArena/drinkarena-api.git
$ cd drinkarena-api
(drinkarena-api) $ git config user.name "<Prenom + Nom>"
(drinkarena-api) $ git config user.email "<Adresse Mail>"
```

> ⚠️ You need to configure the Mercure Hub and the Mail Provider in **.env** file for all features :
> **MAILER_DSN, MERCURE_URL, MERCURE_PUBLIC_URL, MERCURE_JWT_SECRET**

#### Configure project & Launch

```sh
(drinkarena-api) $ composer install # If don't work run 'composer update' before and retry install
(drinkarena-api) $ php bin/console doctrine:database:create # Make sure that all session are disconnected
(drinkarena-api) $ php bin/console doctrine:schema:update --force
(drinkarena-api) $ php bin/console doctrine:fixtures:load -n
(drinkarena-api) $ php bin/console lexik:jwt:generate-keypair # Make sure openssl is installed
(drinkarena-api) $ symfony server:start
```

> Lien d'installation d'OpenSSL pour windows si la commande **"lexik:jwt:generate-keypair"** ne fonctionne pas :
> https://slproweb.com/download/Win64OpenSSL-3_1_1.exe S'assurer que la commande **openssl** fonctionne dans le shell 
> après la procédure

## Available Routes

### GameRoom

- ``GET /api/v1/room/{roomId}``
- ``GET /api/v1/room/{roomId}/join``
- ``GET /api/v1/room/{roomId}/leave``
- ``GET /api/v1/room/{roomId}/pledge/next``
- ``GET /api/v1/room``
- ``POST /api/v1/room``

### Auth

- ``POST /api/v1/login_check``
- ``POST /api/v1/refresh_token``

### Pledge

- ``DELETE /api/v1/pledge/{pledgeId}``
- ``POST /api/v1/pledge``

### User

- ``GET /api/v1/users``
- ``GET /api/v1/user/{userId}``
- ``GET /api/v1/user/me``
- ``GET /api/v1/user/request-forgot-password``
- ``POST /api/v1/user``
- ``POST /api/v1/user/recover-password``