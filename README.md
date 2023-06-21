# drinkarena-api

## Quickstart

### For Development

#### Requirements

- PHP/XAMPP >= 8.1
- Symfony CLI
- Composer
- OpenSSL ([Win64 Installer](https://slproweb.com/download/Win64OpenSSL-3_1_1.exe))

#### Git configuration

```sh
$ git clone git@github.com:DrinkArena/drinkarena-api.git
$ cd drinkarena-api
(drinkarena-api) $ git config user.name "<Prenom + Nom>"
(drinkarena-api) $ git config user.email "<Adresse Mail>"
```

> ⚠️ You need to configure the Mercure Hub and the Mail Provider in **.env** file for all features :
> **MAILER_DSN, MERCURE_URL, MERCURE_PUBLIC_URL, MERCURE_JWT_SECRET**

#### To be added to the Env file

`MAILER_DSN`=sendinblue+api://<YOUR_SENDINBLUE_API_KEY>@default

#### Configure project & Launch

```sh
(drinkarena-api) $ composer install # If don't work run 'composer update' before and retry install
(drinkarena-api) $ php bin/console doctrine:database:create # Make sure that all session are disconnected
(drinkarena-api) $ php bin/console doctrine:schema:update --force
(drinkarena-api) $ php bin/console doctrine:fixtures:load -n
(drinkarena-api) $ php bin/console lexik:jwt:generate-keypair # Make sure openssl is installed
(drinkarena-api) $ symfony server:start
```

## Available Routes

### Auth

- ``POST /api/v1/login_check``
- ``POST /api/v1/refresh_token``

### User

- ``GET /api/v1/users``
- ``GET /api/v1/user/{userId}``
- ``GET /api/v1/user/me``
- ``GET /api/v1/user/request-forgot-password``
- ``POST /api/v1/user``
- ``POST /api/v1/user/recover-password``

### GameRoom

- ``GET /api/v1/room/{roomId}``
- ``GET /api/v1/room/{roomId}/join``
- ``GET /api/v1/room/{roomId}/leave``
- ``GET /api/v1/room/{roomId}/pledge/next``
- ``GET /api/v1/room``
- ``POST /api/v1/room``

### Pledge

- ``DELETE /api/v1/pledge/{pledgeId}``
- ``POST /api/v1/pledge``

## API Documentation

See [OpenAPI PDF Doc](drinkarena-openapi-doc.pdf) (17/06/2023)
