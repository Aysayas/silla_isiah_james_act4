# Products CRUD with Authentication — Function/Code Reference

Companion doc for Laboratory Exercise No. 5 (Web Systems and Technologies 2).
Catalogues every non-trivial function/method used in the feature, what kind of
code it is, and why it was chosen. Written after the feature was built and
smoke-tested end to end against the local Docker stack (login, full CRUD,
validation errors, logout, and re-gating all verified with `curl` + direct
MySQL queries — see the session transcript for the exact commands).

## Database layer

### `AdminModel` / `ProductModel` (`app/models/AdminModel.php`, `app/models/ProductModel.php`)
**Type:** LavaLust `Model` subclasses using the base class's magic CRUD.
Declare `$table`, `$primary_key`, `$fillable`, `$guarded` only — no
hand-written SQL.

- `all()` → dispatches to `Model::_all()` (`scheme/kernel/Model.php`). Used in
  `ProductController::index()` to fetch every row for the list page.
- `find($id)` → dispatches to `Model::_find()`. Used in
  `ProductController::edit()` to load one row for the edit form.
- `find_by($column, $value)` → dispatches to `Model::_find_by()`. Used in
  `AuthController::authenticate()` to look up an admin row by username.
- `insert($data)` → dispatches to `Model::_insert()`. Used in
  `ProductController::store()`.
- `update($id, $data)` → dispatches to `Model::_update()`. Used in
  `ProductController::update()`.
- `delete($id)` → dispatches to `Model::_delete()` (hard delete — the handout
  doesn't ask for soft delete on products, unlike the earlier `users`
  reference implementation, so `$has_soft_delete` is intentionally left
  unset).

**Why:** the base `Model`'s `__call`/`__callStatic` magic (routing `insert()`
→ `_insert()`, etc.) already implements safe, parameterized CRUD — writing raw
SQL here would just duplicate it with more room for mistakes (e.g. SQL
injection via string concatenation).

## Validation

### `Form_validation::validate()` / `::get_errors()` (`scheme/libraries/Form_validation.php`)
**Type:** LavaLust library, loaded per-controller with
`$this->call->library('form_validation')`.

- `ProductController::validate_product()` and `AuthController::authenticate()`
  call `$this->form_validation->validate([...])` with pipe-delimited rules
  (`required`, `max_length[100]`, `numeric`). Each rule token maps to a method
  on `Form_validation` (`required()`, `max_length()`, `numeric()`, ...),
  looked up via `method_exists()` inside `Form_validation::rules()`.
- `get_errors()` returns the flat array of message strings rendered by
  `Login.php` / `ProductForm.php` on a failed submit.

**Why:** matches the exact pattern already established in the repo's
`UserController::validate_user()` — no new validation approach was invented.

## Authentication

### `password_hash()` / `password_verify()` (PHP built-ins)
**Type:** native PHP functions (`password.php` extension, bundled by default).

- `password_hash($password, PASSWORD_DEFAULT)` was used once, out-of-band, to
  seed the `admins` table's test row (bcrypt under the hood).
- `password_verify($submitted, $admin['password'])` in
  `AuthController::authenticate()` checks the login form's password against
  the stored hash.

**Why:** these are the standard, non-reinventable way to hash/verify
passwords in PHP — never roll your own hashing or compare plaintext.

### `Session` library (`scheme/libraries/Session.php`)
**Type:** LavaLust library, autoloaded via `$autoload['libraries']` in
`app/config/autoload.php` (added `'session'` alongside the pre-existing
`'database'`), which makes `$this->session` available on every controller
automatically.

- `has_userdata('admin_id')` — checked in `AuthController::login()` (skip the
  form if already logged in) and in `AuthMiddleware::handle()` (gate check).
- `set_userdata($key, $value)` — stores `admin_id` / `admin_username` on
  successful login.
- `regenerate_on_login()` — rotates the session ID right after a successful
  login (session-fixation defense), called *after* `set_userdata()` since PHP's
  underlying `session_regenerate_id()` carries the current `$_SESSION` array
  forward to the new ID.
- `sess_destroy()` — used in `AuthController::logout()`.

**Why:** session-based authentication was one of the handout's explicitly
allowed mechanisms ("LavaLust middleware", "session-based authentication", or
"any other appropriate mechanism") — using the framework's own Session
library instead of raw `$_SESSION` calls keeps it consistent with the rest of
the app and gets the security touches (fixation defense, HMAC-backed
integrity) for free.

### `AuthMiddleware` (`app/middlewares/AuthMiddleware.php`)
**Type:** custom class implementing LavaLust's middleware contract (a plain
class with a `handle($next)` method — there's no interface to implement,
`scheme/kernel/Middleware.php`'s `resolve()` just calls `->handle($next)` on
whatever object is registered).

- Loads the Session library directly via the global `load_class('Session',
  'libraries')` helper (`scheme/kernel/Routine.php`) rather than
  `$this->call->library()`, because middleware runs **before** any controller
  is instantiated (`scheme/kernel/Router.php::execute_callback()` runs the
  middleware pipeline, then calls the controller as the pipeline's final
  step) — there is no `$this->call` yet. `load_class()` caches by object name
  in the framework's `Registry`, so this returns the same `Session` instance a
  controller's autoloaded `$this->session` would.
- On an unauthenticated request, calls `redirect('/login')` and then
  `return`s immediately — see the gotcha below.

**Why:** the handout explicitly names "LavaLust Middleware" as the first
suggested mechanism, and gating at the routing layer (rather than repeating a
session check at the top of every `ProductController` method) keeps the
controller code focused on CRUD only.

## Routing / wiring

### `$router->group(['middleware' => 'auth'], function ($router) { ... })` (`app/config/routes.php`)
**Type:** LavaLust `Router::group()` (`scheme/kernel/Router.php`).

Wraps all six Products routes (`index`, `create`, `store`, `edit`, `update`,
`delete`) so every one of them — not just the four the handout names
explicitly (`/products`, `/products/create`, `/products/edit/{id}`,
`/products/delete/{id}`) — requires the `auth` middleware. The two POST
endpoints (`store`, `update`) were included even though the handout's list
only names their GET-form counterparts, since leaving `/products/store` or
`/products/update/{id}` open would let an unauthenticated client bypass the
gated forms entirely by POSTing directly.

### `app/config/middleware.php`
**Type:** LavaLust middleware config file, but wired up non-standardly — see
the two gotchas below.

```php
require_once APP_DIR . 'middlewares/AuthMiddleware.php';
$middleware_config['middlewares'] = ['auth' => new AuthMiddleware()];
get_config($middleware_config);
```

**Why the object, not a class name:** `Middleware::resolve()`
(`scheme/kernel/Middleware.php`) calls `$this->map[$middleware]->handle($next)`
directly on the config map's *value* — so the map must hold instantiated
objects, not class-name strings. There's also no autoloader scanning
`app/middlewares/` (confirmed against `scheme/kernel/LavaLust.php`), hence the
explicit `require_once`.

## Two framework gotchas found and worked around while building this

These aren't LavaLust bugs in the "broken feature" sense — they're rough
edges in how this particular scaffold wires middleware/redirects — but they
will bite the next person who touches this code if undocumented:

1. **`app/config/middleware.php` is never auto-loaded by the framework.**
   Unlike `config.php`, `autoload.php`, `database.php`, and `routes.php` (each
   has a dedicated loader — `get_config()`, `autoload_config()`,
   `database_config()`, and a direct `require_once` in `LavaLust.php`,
   respectively — all in `scheme/kernel/Routine.php` / `LavaLust.php`),
   nothing in `scheme/` ever requires `app/config/middleware.php`. It's
   `require_once`'d directly from the top of `app/config/routes.php`, which
   is itself required early enough (`scheme/kernel/LavaLust.php:199`, before
   `$router->initiate()` runs at the very end of that file) for the merge to
   land before routing dispatches.
2. **Don't name that file's config array `$config`.** `routes.php` runs in
   `LavaLust.php`'s global scope, which already holds a `Config` *object*
   (not an array) in `$config` (`$config = load_class('config', 'kernel');`,
   `LavaLust.php:148`). Since `require_once` shares the including scope's
   variables, `$config['middlewares'] = [...]` inside a file required from
   `routes.php` would try to array-index that `Config` object and fatal
   ("Cannot use object of type Config as array"). `middleware.php` uses
   `$middleware_config` instead and merges it in with `get_config($middleware_config)`.
3. **`redirect()` (`scheme/helpers/url_helper.php`) does not actually halt
   script execution**, despite taking an `$exit` parameter that defaults to
   `true`. Its last line is `($exit === true) ?? exit();` — since
   `$exit === true` evaluates to a non-null boolean, the `??` operator
   short-circuits and `exit()` is never reached. Every call site in this
   feature (`AuthMiddleware::handle()`, and the early-returns in
   `AuthController`) follows `redirect(...)` with an explicit `return;` to
   compensate — without it, a gated `ProductController` action would still
   run (and its output would render into a response whose headers already
   promised a redirect) even after the middleware "redirected" an
   unauthenticated request.

## Local environment fixes required to get this running

Not application code, but necessary to actually exercise the feature in this
Docker setup — worth knowing if you rebuild the stack from scratch:

- `runtime/sessions/` did not exist on disk (only `runtime/session/`, singular,
  did), so PHP's file-based session handler failed with
  `session_start(): open(...) failed: No such file or directory`. Created the
  directory (gitignored via the existing `runtime/*` rule, like the rest of
  `runtime/`).
- `app/config/config.php`'s `$config['index_page']` was set to `'index.php'`,
  which made every `redirect()`/`site_url()` call generate URLs like
  `/index.php/login`. This project's nginx config
  (`docker/nginx/nginx.conf`) already rewrites clean URLs to `index.php`
  transparently server-side, so the client-facing routes never include
  `index.php` — the config's own comment says to blank this out when using
  that kind of rewrite, so it was set to `''`.
- PHP's opcache (`docker/php/php.ini`, `opcache.revalidate_freq = 60`) can
  serve stale bytecode for up to 60s after an edit; a couple of test requests
  hit this during development. `docker-compose restart php` clears it
  immediately if a code change doesn't seem to be taking effect.

## Database

- `admins` table (`app/migrations/003_create_admins_table.php`) — login-only,
  deliberately separate from any business-data table.
- `products` table (`app/migrations/004_create_products_table.php`) — exact
  columns from the handout: `id`, `product_name`, `description`, `price`,
  `quantity`, `created_at`.
- Both migrations follow the existing `dbforge`-based pattern from
  `app/migrations/001_create_users_table.php` / `002_create_refresh_tokens_table.php`,
  but — like those — **won't run automatically**
  (`$config['migration_enabled']` is `FALSE` in `app/config/migration.php`).
  The equivalent raw SQL was run directly against the local database for
  development; the same SQL needs to be run against the Aiven database before
  deploying:

```sql
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username_unique (username)
);

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

Seed at least one admin login row (replace the hash with your own —
generate it with `php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"`):

```sql
INSERT INTO admins (username, password) VALUES ('admin', '<bcrypt hash>');
```

## Local test credentials

Seeded for local development only — **do not reuse in production/Aiven**:

- Username: `admin`
- Password: `admin123`
