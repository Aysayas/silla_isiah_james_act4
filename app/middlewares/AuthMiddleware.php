<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Loaded explicitly here (not via a Controller's $this->call->helper()) because
// this class runs standalone, before any controller is instantiated — see
// scheme/kernel/Router.php's execute_callback(), which runs middleware ahead of
// the controller callback. require_once is safe: url_helper.php's functions are
// each wrapped in function_exists() guards.
require_once SYSTEM_DIR . 'helpers/url_helper.php';

/**
 * Middleware: AuthMiddleware
 *
 * Gates the Products CRUD routes behind a logged-in admin session
 * (LavaLust's Session library, scheme/libraries/Session.php).
 *
 * Registered as an already-instantiated object in app/config/middleware.php,
 * because LavaLust's Middleware::resolve() (scheme/kernel/Middleware.php) calls
 * ->handle($next) directly on the config map's value rather than on a class name.
 */
class AuthMiddleware {

    /**
     * @param callable $next The next step in the middleware pipeline
     *                       (ultimately the matched controller action).
     */
    public function handle($next)
    {
        // load_class() caches by object name in the framework Registry, so this
        // returns the same Session instance a controller's autoloaded $this->session
        // would use, even though no controller exists yet at this point.
        $session = load_class('Session', 'libraries');

        if (! $session->has_userdata('admin_id')) {
            // NOTE: LavaLust's redirect() (scheme/helpers/url_helper.php) has a bug
            // where its $exit parameter never actually halts the script
            // (`($exit === true) ?? exit()` short-circuits on the non-null boolean
            // and never reaches exit()). We must `return` immediately ourselves to
            // stop the pipeline — otherwise $next() would still run the protected
            // controller action after the redirect header is sent.
            redirect('/login');
            return;
        }

        return $next();
    }
}
