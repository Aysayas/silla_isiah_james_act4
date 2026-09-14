<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: ProductController
 *
 * Handles the Products CRUD pages: listing, creating, editing, and deleting
 * rows in the `products` table. Every action here sits behind the 'auth'
 * middleware (see app/config/routes.php + app/middlewares/AuthMiddleware.php) —
 * this controller doesn't need to check the session itself.
 */
class ProductController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('ProductModel');
        $this->call->helper('url'); // needed for redirect()
        $this->call->library('form_validation');
    }

    // Validate the posted product fields against the products table columns
    private function validate_product()
    {
        return $this->form_validation->validate([
            'product_name' => 'required|max_length[100]',
            'description'  => 'required',
            'price'        => 'required|numeric',
            'quantity'     => 'required|numeric',
        ]);
    }

    // Show the list of all products
    public function index()
    {
        $data['products'] = $this->ProductModel->all();
        $this->call->view('Product', $data);
    }

    // Show the empty form for creating a new product
    public function create()
    {
        $this->call->view('ProductForm');
    }

    // Handle the create form submission
    public function store()
    {
        if (!$this->validate_product()) {
            $data['product'] = $this->io->post();
            $data['errors']  = $this->form_validation->get_errors();
            $this->call->view('ProductForm', $data);
            return;
        }

        $this->ProductModel->insert($this->io->post());
        redirect('/products');
    }

    // Show the form pre-filled with an existing product's data
    public function edit($id)
    {
        $data['product'] = $this->ProductModel->find($id);
        $this->call->view('ProductForm', $data);
    }

    // Handle the edit form submission
    public function update($id)
    {
        if (!$this->validate_product()) {
            $post = $this->io->post();
            $post['id'] = $id;
            $data['product'] = $post;
            $data['errors']  = $this->form_validation->get_errors();
            $this->call->view('ProductForm', $data);
            return;
        }

        $this->ProductModel->update($id, $this->io->post());
        redirect('/products');
    }

    // Delete a product
    public function delete($id)
    {
        $this->ProductModel->delete($id);
        redirect('/products');
    }
}
