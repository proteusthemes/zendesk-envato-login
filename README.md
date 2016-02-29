Zendesk login with Envato API
=============================

This project connects the [Zendesk single sign-on](https://support.zendesk.com/hc/en-us/articles/203663816-Setting-up-single-sign-on-with-JWT-JSON-Web-Token-) (SSO) with JSON Web Token (JWT) to [Envato API](). After implementing this, the users will be able to sign in to your Zendesk account only with their Envato accounts.

### Setup

When you clone the repo, you should install dependencies with [composer](https://getcomposer.org/):

```bash
$ composer install
```

The configuration is loaded using the [dotenv](https://github.com/vlucas/phpdotenv). Copy the `.env.example` file to `.env` and enter your real credentials:

- `ENVATO_CLIENT_ID`, `ENVATO_REDIRECT_URI`, `ENVATO_CLIENT_SECRET` - you get these info when you [register the app](https://build.envato.com/my-apps/) on Envato API. Check [this picture](http://www.awesomescreenshot.com/image/1037426/ab483c503a64259dd8efe21b950a7aae) which permissions you need.
- `ZENDESK_SHARED_SECRET`, `ZENDESK_SUBDOMAIN` - you get the shared secret in Zendesk Settings > Security > End-users > Single sign-on (SSO).
- `ZEL_DEBUG` - aka *Zendesk-Envato login debug*. If this is set to `true`, no redirection will be made back to Zendesk, but some debugging information will be printed out instead.

Once configured, point your Zendesk login to the `index.php` file at root of this repo. This file will handle redirect to Envato API, obtain the credentials and redirect the logged in user back to Zendesk.

#### Bought and supported themes

Along with the name and email address, this script will also send to Zendesk the list of the themes the user bought and the list of the themes that the user is entitled to get support for. In order to save these info, you should manually create 2 fields in in Zendesk.

In Zendesk go to Settings > User Fields and add 2 Multi-line text fields. Make sure the **Field keys** are exactly:

- `bought_themes`
- `supported_themes`

### Unit tests

The project is covered with tests, but due to the nature how the Envato API works, you need to obtain the `access_token` manually (see the debug mode above) and expose it as env variable when running the `phpunit` command:

```bash
$ envato_access_token=0FeF24T000000004P1IbHL1111ySmJ7f phpunit
```

Therefore this repo cannot be 100% automatically tested. If you have any idea, how this would be possible to achieve, let me know via issues.

### Requirements

PHP 5.5+ with curl.
