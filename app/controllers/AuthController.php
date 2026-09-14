<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: AuthController
 *
 * Login / logout for the admin session that gates the Products CRUD.
 * $this->session is available automatically because 'session' is in
 * $autoload['libraries'] (app/config/autoload.php).
 */
class AuthController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('AdminModel');
        $this->call->helper('url'); // needed for redirect()
        $this->call->library('form_validation');
    }

    // Show the login form (skip it if already logged in)
    public function login()
    {
        if ($this->session->has_userdata('admin_id')) {
            redirect('/products');
            return; // redirect() alone doesn't halt execution, see AuthMiddleware.php
        }

        $this->call->view('Login');
    }

    // Handle the login form submission
    public function authenticate()
    {
        $valid = $this->form_validation->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (!$valid) {
            $data['errors'] = $this->form_validation->get_errors();
            $this->call->view('Login', $data);
            return;
        }

        $post  = $this->io->post();
        $admin = $this->AdminModel->find_by('username', $post['username']);

        // password_verify() is PHP's built-in bcrypt check — pairs with
        // password_hash() used when the admin row was seeded.
        if (!$admin || !password_verify($post['password'], $admin['password'])) {
            $data['errors'] = ['Invalid username or password.'];
            $this->call->view('Login', $data);
            return;
        }

        $this->session->set_userdata('admin_id', $admin['id']);
        $this->session->set_userdata('admin_username', $admin['username']);
        // Rotates the session ID post-login to defend against session fixation.
        $this->session->regenerate_on_login();

        redirect('/products');
    }

    // Log out and destroy the session
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('/login');
    }
}
