<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: UserController
 * 
 * Automatically generated via CLI.
 */
class UserController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('UserModel');
    }
    public function index()
    {
    }
    public function user()
    {
        $this->call->model('UserModel');
        
        $users = $this->UserModel->all();
        $data['users'] = $users;
        $this->call->view('User', $data);
    }
}