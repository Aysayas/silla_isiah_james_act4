<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: MigrationController
 *
 * Exposes the Migration library (scheme/libraries/Migration.php) through the
 * routes in app/config/routes.php. The `php lava migration <action>` command
 * (app/commands/Migration.php) calls these same routes via public/index.php.
 */
class MigrationController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        // These are plain GET routes (rollback-all, refresh, ...), so anyone who
        // can reach the site could wipe the database. Outside of CLI, only allow
        // them when not running in production (e.g. on Render).
        if (php_sapi_name() !== 'cli' && getenv('APP_ENV') === 'production') {
            http_response_code(403);
            exit('Migrations can only be run from the CLI in production.');
        }

        $this->call->library('migration');
    }

    public function create_migration($migration_class)
    {
        $this->migration->create_migration($migration_class);
    }

    public function migrate()
    {
        $this->migration->migrate();
    }

    public function rollback()
    {
        $this->migration->rollback();
    }

    public function rollback_all()
    {
        $this->migration->rollback_all();
    }

    public function refresh()
    {
        $this->migration->refresh();
    }

    public function status()
    {
        $this->migration->status();
    }
}
