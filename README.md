# lliure-panel

The shared shell for lliure apps: bootstrap, layout, menu and login.
lliure-panel has no app inside it. Apps live at the root of each project,
each in its own repository, and lliure-panel gives them the panel they run in.

```
composer require vinteenove/lliure-panel:^2.0@alpha
```

## Why the version starts at 2

lliure-panel is the **second generation of lliure**.

The first generation is the monolithic panel, still at
[`vinteenove/lliure`](https://bitbucket.org/vinteenove/lliure) (Bitbucket),
with versions up to `v11.x`. There, apps live inside the panel (`app/`,
`opt/`, `api/`), under the `LliurePanel\` namespace. It still runs the Vimu
`panel/` and stays there until the last app is migrated.

The second generation turns that around: the panel becomes a shell and apps
live outside it. The change is too big to be "one more version" of the first
one, so it got its own repository, and the version starts at `v2`: not a new
project from scratch, but lliure in its second form.

Not to be confused with `lliure/lliure-panel` (`v0.x`), an older and unrelated
package.

### Where the code came from

1. **The `v2` branch of `vinteenove/lliure`** (February/March 2026): the
   skeleton of the second generation (Bootstrap, Auth, Menu, AdminLTE 4
   layout). The branch shared no history with `master`, and all of that
   history came along.
2. **Three copies that had drifted apart.** Two projects copied that branch
   into a `lliure/` folder and kept adjusting it on their own, and Vimu built a
   coexistence spike on top of it. In November 2026 the three were merged
   here: 30-day sessions, database config read from `etc/database.php`, the
   Twig cache, and the embedded mode (see below).

The first tag, `v2.0.0-alpha.1`, marks that meeting point.

## What changes between generations

| | 1st generation (`vinteenove/lliure`) | 2nd generation (`lliure-panel`) |
|---|---|---|
| Where apps live | Inside the panel (`app/`) | At the project root, each with its own repo |
| Role of lliure | The whole system | The shell: layout, menu, login |
| Namespace | `LliurePanel\` | `Lliure\` |
| Layout | AdminLTE 3 / Bootstrap 4 | AdminLTE 4 / Bootstrap 5 |
| Package | `lliure/lliure` | `vinteenove/lliure-panel` |

## Project layout

```
project/
├── composer.json      ← requires vinteenove/lliure-panel and merges the apps (merge-plugin)
├── etc/database.php   ← credentials (outside git)
├── vendor/vinteenove/lliure-panel/   ← this package, installed by composer
├── cache/twig/        ← Twig cache (clear it on every deploy)
├── cifras/            ← an app, with its own repository
└── site/              ← another app
```

`@alpha` is needed until `v2.0.0` exists, for projects with
`minimum-stability: stable`.

Apps are not published packages: each one is a checkout at the root, and
`wikimedia/composer-merge-plugin` merges their `composer.json` into the
project's (`"merge-plugin": {"include": ["cifras/composer.json"]}`).

Up to `v2.0.0-alpha.5`, lliure-panel was a clone in `lliure/` at the project
root, loaded by a hand-written autoloader. Those tags do not install through
composer: they require AdminLTE as a package, and it is not `stable`. Use
`v2.0.0-alpha.6` or later.

## Building an app

The reference is the [pipeline](https://github.com/vinteenove/pipeline), the
first app of this generation in production. The second one, Fiscon, followed
the same shape.

### Folders

```
cifras/
├── composer.json    ← just the app's autoload (no require of lliure-panel)
├── src/             ← AppServiceProvider, Controllers, Models
├── views/           ← Twig templates, used as @cifras/...
├── migrations/      ← 001_xxx.sql, 002_xxx.sql... idempotent
└── assets/          ← logo, favicon
```

```json
{
    "name": "vinteenove/cifras",
    "type": "library",
    "require": { "php": "^8.1" },
    "autoload": { "psr-4": { "Cifras\\": "src/" } }
}
```

The common dependencies (lliure-core, router, twig, dibi) already come with
lliure-panel. An app only declares what it uses that the project does not
already have.

### The provider

An app implements both interfaces. The routes one is optional in standalone
mode, but embedded mode only knows the app through it:

```php
use Lliure\Bootstrap;
use Lliure\Menu\MenuBuilder;
use Lliure\Menu\MenuItem;
use Lliure\RoutesProviderInterface;
use Lliure\ServiceProviderInterface;
use Router\Router;

final class AppServiceProvider implements ServiceProviderInterface, RoutesProviderInterface
{
    public static function boot(Bootstrap $bootstrap): void
    {
        // views as @cifras/...
        $bootstrap->registerApp('cifras', dirname(__DIR__));
    }

    public static function registerMenu(MenuBuilder $menu): void
    {
        $menu->addItem(new MenuItem(
            label: 'Cifras',
            url:   '/cifras',
            icon:  'fas fa-music',
            order: 10,
            // optional: hides the item from who cannot access it
            visible: fn(): bool => true,
        ));
        // Several items under one heading: $menu->addGroup('Heading', [$item, ...], 'fas fa-x', 0)
    }

    public static function routes(Router $router): void
    {
        $router->get('/',            [Controllers\Cifras::class, 'index']);
        $router->post('/cifras',     [Controllers\Cifras::class, 'criar']);
        $router->get('/cifras/{id}', [Controllers\Cifras::class, 'ver']);
    }
}
```

Routes use `\Router\Router` (package `lliure/router`). A controller receives
`(ServerRequestInterface $request, array $vars)` and returns a string (HTML)
or a `ResponseInterface`.

### Views

Every screen extends the panel layout; the rest is blocks:

```twig
{% extends "layout.twig" %}

{% block title %}Cifras{% endblock %}
{% block content_header %}Cifras{% endblock %}
{% block content %}
    ...
{% endblock %}
```

| Block | What it is for |
|---|---|
| `content` | the body of the screen |
| `content_header` | the heading above the body |
| `title` | the tab's `<title>` |
| `styles` | the screen's own CSS, inside `<head>` |
| `navbar_left`, `navbar_right` | extra items in the top bar |
| `body_class` | `<body>` classes (full screen, no menu...) |
| `sidebar`, `footer`, `user_panel` | replace or hide those parts; `{% block footer %}{% endblock %}` removes the footer |

To render: `Bootstrap::getInstance()->getTwig()->render('@cifras/index.twig', $data)`.

### Database

- **Every row belongs to an instance** (column `instance`), and every query
  filters by it. Ask one place in the app for the instance (in the pipeline,
  `Pipeline\Instancia::atual()`), never a constant spread around. Call it
  `instance`, not `account`: Vimu's core called it `account` until Nov 2026,
  and renaming it took three releases.
- **Migrations are separate from deploys.** The application user does not
  run DDL. The migration runs before publishing, and the code has to work
  both with it applied and without it (a new column is always optional in
  its first version).
- **`SET NAMES utf8mb4` in `boot()`** if the tables are utf8mb4. The
  connection Vimu opens is utf8, and emoji turn into `?`.

### In Vimu

Besides the app's repository, these go into `vinteenove/vimu`:

1. `src/Hosts/Cifras.php`, with the `AppConfig` (`provider`, `serviceId`,
   branding, login). It makes login, instance choice and SSO the same as in
   the other apps.
2. `cifras/composer.json` in the root `merge-plugin`, and `/cifras` in
   `.gitignore`.
3. A `case` in `deploy/deploy-app` and the release workflow in the app's
   repository.

The step by step, with the traps that already cost time, is in
`docs/deploy-dos-apps-v2.md` in the Vimu repository.

## Two ways to run it

**Standalone.** lliure-panel connects to the database with
`config/database.php` (which reads `etc/database.php` at the project root),
handles the session (30 days) and logs in against the `lliure_users` table
(`migrations/001_users.sql`).

The migration creates no user. The first one will be created by a welcome
screen, with a password chosen by whoever installs it. That screen does not
exist yet; until then, the first user goes straight into the database. Up to
`v2.0.0-alpha.1` the migration created an `admin` user with a fixed password.
On installations from that time, check whether that user exists and change
its password.

**Embedded.** lliure-panel runs inside another system that already has users,
a database and a session. That is the case of Vimu, where login, instance and
permissions stay with Vimu. In this mode:

- if the Composer autoloader is already loaded, Bootstrap does not look for a
  `vendor/` of its own;
- if a `dibi` connection is already open, it is reused. Connecting again
  would swap the database under whoever connected first;
- the host tells who is logged in with
  `Lliure\Auth\Auth::setIdentityResolver(fn() => ...)`, with no second login
  and no row in `lliure_users`;
- the host calls `Bootstrap::setProjectRoot($root)` **before** the first
  `getInstance()`. `cache/twig` and `vendor/autoload.php` are found from
  there. Without the call, the folder above the package is used, which inside
  `vendor/` is `vendor/vinteenove`: the cache would sit outside the folder the
  deploy clears, and a new layout would not show up.

In both modes, a database connection failure fails right away instead of
staying silent until the first query.

## Versions

- `v2.0.0-alpha.N` while the way apps integrate is still changing. Each alpha
  may break compatibility with the previous one.
- `v2.0.0` once the first app built entirely on this generation (Vimu's
  pipeline) is in production.

Each project picks its version through composer, and updating is each
project's decision:

```
composer update vinteenove/lliure-panel
composer update vinteenove/lliure-panel --with vinteenove/lliure-panel:2.0.0-alpha.6   # an exact tag
```

After updating, clear `cache/twig`: in production Twig does not re-read a
changed template.

In Vimu, publishing a release of this repository ships it. The workflow runs
`deploy-app lliure-panel <tag>` on the server, which does the
`composer update --with` for that tag and clears the cache. To roll back, use
"Run workflow" with the older tag (from `v2.0.0-alpha.6` on).

A new tag of this package has to work with the apps already in production:
they ship on different days. To rename something, keep the old name as a
`@deprecated` alias for one release.

## License

MIT.
