<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: ApiAuthController
 *
 * JWT login for the React frontend (Lab Exercise 6). Tokens are issued and
 * validated by the autoloaded Api library (scheme/libraries/Api.php):
 * a 15-minute access token plus a 7-day refresh token stored hashed in
 * `refresh_tokens`.
 */
class ApiAuthController extends Controller {

    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json; charset=UTF-8');
    }

    // CORS preflight. The headers themselves are sent by the Api library's constructor.
    public function options()
    {
        http_response_code(204);
        exit;
    }

    // POST /api/auth/login  { "username": "...", "password": "..." }
    public function login()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 60);

        // Read the raw JSON rather than $this->api->body(): body() HTML-escapes
        // every string, which would break passwords containing & < > " '.
        $body     = json_decode(file_get_contents('php://input'), true) ?: [];
        $login    = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->api->respond_error('Username and password are required.', 422);
        }

        $user = $this->db->raw(
            'SELECT id, username, email, password, role FROM users
             WHERE (username = ? OR email = ?) AND deleted_at IS NULL LIMIT 1',
            [$login, $login]
        )->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            $this->api->respond_error('Invalid username or password.', 401);
        }

        $tokens = $this->api->issue_tokens([
            'id'   => $user['id'],
            'role' => $user['role'],
        ]);

        $this->api->respond([
            'user'   => $this->public_user($user),
            'tokens' => $tokens,
        ]);
    }

    // POST /api/auth/refresh  { "refresh_token": "..." }
    public function refresh()
    {
        $this->api->require_method('POST');
        $body = $this->api->body();

        if (empty($body['refresh_token'])) {
            $this->api->respond_error('refresh_token is required.', 422);
        }

        // Rotates the token: revokes the old refresh token and responds with a new pair.
        $this->api->refresh_access_token($body['refresh_token']);
    }

    // POST /api/auth/logout  { "refresh_token": "..." }
    // No access token required: holding the refresh token is proof enough to
    // revoke it, and logout must still work after the 15-minute access token expires.
    public function logout()
    {
        $this->api->require_method('POST');
        $body = $this->api->body();

        if (empty($body['refresh_token'])) {
            $this->api->respond_error('refresh_token is required.', 422);
        }

        $this->api->revoke_refresh_token($body['refresh_token']);

        $this->api->respond(['message' => 'Logged out.']);
    }

    // GET /api/auth/me
    public function me()
    {
        $payload = $this->api->require_jwt();

        $user = $this->db->raw(
            'SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1',
            [$payload['sub']]
        )->fetch(PDO::FETCH_ASSOC);

        $this->api->respond(['user' => $this->public_user($user)]);
    }

    private function public_user($user)
    {
        return [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ];
    }
}
