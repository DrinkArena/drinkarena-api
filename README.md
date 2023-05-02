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

#### Configure project & Launch

```sh
(drinkarena-api) $ composer install # If don't work run 'composer update' before and retry install
(drinkarena-api) $ php bin/console doctrine:database:create # Make sure that all session are disconnected
(drinkarena-api) $ php bin/console doctrine:schema:update --force
(drinkarena-api) $ php bin/console doctrine:fixtures:load -n
(drinkarena-api) $ php bin/console lexik:jwt:generate-keypair # Make sure openssl is installed
(drinkarena-api) $ symfony server:start
```
