# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A [LavaLust](https://lavalust.netlify.app) 4.6.0 PHP MVC application (`app/`) running on a Docker stack (Nginx + PHP 8.3-FPM + MySQL 8.0 + phpMyAdmin + a Vite/Tailwind asset pipeline). `scheme/` is the vendored framework core — treat it as read-only unless the task specifically involves patching the framework itself.

## Commands

Everything runs through Docker Compose; there's no local PHP toolchain assumed.

```powershell
# Start the full stack (nginx, php, mysql, phpmyadmin, vite dev server)
docker-compose up -d
# or:
.\start.ps1

# Stop / rebuild after config changes
docker-compose down
docker-compose down -v          # also wipes the MySQL volume — destroys data
docker-compose up -d --build

# Tail logs
docker-compose logs -f php

# Composer / PHP inside the container
docker-compose exec php composer install
docker-compose exec php php -v

# MySQL access
docker-compose exec mysql mysql -u root -proot lavalust_db
docker-compose exec mysql mysqldump -u root -proot lavalust_db > backup.sql

# Frontend (Tailwind via Vite) — runs automatically in the `node` service under
# docker-compose, or standalone:
npm run dev      # dev server with HMR (localhost:5173)
npm run build    # production build -> public/build/ (read by the vite() helper
                  # in app/helpers/vite_helper.php when APP_ENV=production)
```

Access points: app at `http://localhost`, phpMyAdmin at `http://localhost:8080` (root/root), MySQL at `localhost:3306` (`lavalust_user`/`lavalust_pass`, DB `lavalust_db` locally — the actual dev DB for this project is `silla_isiah_james_act4_db`, see `app/config/database.php`).

There is no test suite and no linter configured in this repo.

### LavaLust CLI (`php lava <command>`, or `./lava` on Unix)

Run inside the php container (`docker-compose exec php php lava <command>`):

- `php lava serve` / `run [--port=]` — PHP built-in dev server (alternative to the Docker/nginx stack)
- `php lava make:controller|make:model|make:library|make:helper|make:middleware|make:view|make:language|make:config <name>` — scaffolds a file following this repo's conventions (see `console/cli.php` for exact templates)
- `php lava route:list` — parses `app/config/routes.php` and prints registered routes
- `php lava cache:clear` — clears `runtime/cache`
- `php lava key:generate` — generates and writes `APP_KEY` into `.env`
- `php lava env:check` — prints a redacted summary of `.env`

Custom commands can be dropped into `app/commands/` and are auto-discovered (see `autoload_commands()` in `console/cli.php`).

## Architecture

**Request flow:** `public/index.php` → `scheme/kernel/LavaLust.php` boots the framework → `Router` matches `app/config/routes.php` → dispatches to a `Controller` method → controller loads a `Model`/`Library`/`Helper` via `$this->call->...` and renders a `View`.

**`app/` vs `scheme/`:** `scheme/kernel/` holds the framework's base classes (`Controller`, `Model`, `Router`, `Invoker`, `Io`, `Database`, etc.) and `scheme/libraries/` the built-in libraries (Form_validation, Session, Api, Ember, Upload, Encryption, ...). Application code only lives in `app/` — controllers, models, views, config, migrations, helpers, middlewares.

### Controller conventions (`scheme/kernel/Controller.php`)

- Controllers extend `Controller`. Its constructor auto-loads the libraries/helpers listed in `app/config/autoload.php`, sets up `$this->call` (an `Invoker` used to lazily load things), and runs `before_action()`.
- Dynamic properties work via `__get`/`__set` magic on an internal `$properties` array — e.g. `$this->call->model('UserModel')` registers the model, then it's accessed as `$this->UserModel`. Same pattern for libraries: `$this->call->library('form_validation')` → `$this->form_validation`.
- `$this->call->view('ViewName', $data)` renders `app/views/ViewName.php`, extracting `$data` into local variables.
- `$this->io->post()` / similar reads request input (the `Io`/`Request` layer in `scheme/kernel/Io.php`, `Request.php`).
- `redirect()` comes from the `url` helper — must be explicitly loaded (`$this->call->helper('url')`) unless autoloaded.

### Model conventions (`scheme/kernel/Model.php`)

- Models extend `Model` and declare `protected $table`, `$primary_key = 'id'`, `$fillable = []`, `$guarded = ['id']`.
- CRUD and query-builder methods (`all()`, `find()`, `insert()`, `update()`, `delete()`, `soft_delete()`, `paginate()`, relationship helpers, transactions, `raw()`, `scope()`) are dispatched through `__call`/`__callStatic` magic onto internal `_method()` implementations — there is no need to hand-write basic CRUD in a model.
- **Soft delete**: set `protected $has_soft_delete = true;` on a model to opt in (falls back to the global `$config['soft_delete']` in `app/config/config.php`, which is `FALSE` by default). When enabled, `soft_delete($id)` sets the `deleted_at` column (configurable via `$soft_delete_column`, default `deleted_at`) instead of removing the row, and `all()`/`find()`/etc. automatically filter out soft-deleted rows unless called `with_deleted`. `UserModel` (`app/models/UserModel.php`) uses this.
- Mass-assignment guarding: with `$fillable` empty, the base class falls back to blacklist mode using `$guarded` — every posted field except the guarded ones is writable on insert/update.

### Form validation (`scheme/libraries/Form_validation.php`)

Load per-controller with `$this->call->library('form_validation')`, then:

```php
$this->form_validation->validate([
    'field_name' => 'required|max_length[100]|valid_email',
]);
$this->form_validation->get_errors(); // flat array of message strings after a failed validate()
```

Rules are pipe-delimited; a `rule[param]` syntax passes a parameter (e.g. `max_length[100]`). See `UserController::validate_user()` for the established pattern of validating before insert/update and re-rendering the form with `$data['errors']` + the posted values on failure.

### Config

- `app/config/routes.php` — `$router->get/post('/path', 'Controller::method')`.
- `app/config/database.php` — reads `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` from the environment.
- `app/config/config.php` — framework-wide settings: soft delete, timestamps, CSRF, sessions, cache, etc. (all opt-in, off by default).
- `app/config/autoload.php` — libraries/helpers/models loaded on every request; currently only `database` (library) and `vite` (helper) are autoloaded, so anything else (e.g. `form_validation`, `url`) must be loaded explicitly per controller.
- `app/migrations/` — DB-forge migration classes (`up()`/`down()`), but `$config['migration_enabled']` is `FALSE` in `app/config/migration.php` by default, so they don't run automatically. **The migration files can drift from the actual live schema** — when in doubt about real column names, check the live database directly (e.g. via phpMyAdmin) rather than trusting a migration file.

### Frontend (Tailwind + Vite)

`resources/js/app.js` / `resources/css/app.css` are the Vite entry points. `app/helpers/vite_helper.php` exposes a `vite()` helper that auto-switches between the dev server (`localhost:5173`, `APP_ENV != production`) and the built manifest in `public/build/.vite/manifest.json` (`APP_ENV=production`). It's opt-in per view — call `<?php echo vite('resources/js/app.js'); ?>` in a view's `<head>` to pull it in; the stock `Welcome::index` view doesn't use it.

### Deployment

`render.yaml` deploys to Render.com via the `Dockerfile` (a single container running Nginx + PHP-FPM under supervisord, listening on Render's `$PORT`). `docker-compose.yml` / `docker/mysql/init.sql` are local-dev only. Render has no managed MySQL — `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASSWORD` must point at an external MySQL host in production. Sessions default to `SESSION_DRIVER=database` on Render since the container filesystem is ephemeral.
