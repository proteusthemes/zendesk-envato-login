Zendesk login with Envato API
=============================

[ ![Codeship Status for proteusthemes/zendesk-envato-login](https://codeship.com/projects/8c5f6860-bd22-0133-0fb0-4610616512f7/status?branch=master)](https://codeship.com/projects/136475)

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

#### Bought and supported themes, username

Along with the name and email address, this script will also send to Zendesk the list of the themes the user bought, the list of the themes that the user is entitled to get support for and the username. In order to save these info, you should manually create 3 fields in in Zendesk.

In Zendesk go to Settings > User Fields and add 2 Multi-line text fields. Make sure the **Field keys** are exactly:

- `bought_themes`
- `supported_themes`
- `tf_username`

### Requirements

PHP 5.5+ with curl.
