<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: ApiProductController
 *
 * JSON CRUD for `products` (Lab Exercise 6). Every action requires a valid
 * access token (Authorization: Bearer <token>); writes additionally require
 * the scope derived from the user's role in the database (see
 * Api::scopes_for_role(): admin = read/write/delete, editor = read/write,
 * user = read).
 */
class ApiProductController extends Controller {

    // Columns a client may set. Anything else in the request body is ignored.
    private $writable = ['product_name', 'description', 'price', 'quantity'];

    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json; charset=UTF-8');
        $this->call->model('ProductModel');
    }

    // CORS preflight. The headers themselves are sent by the Api library's constructor.
    public function options($id = null)
    {
        http_response_code(204);
        exit;
    }

    // GET /api/products
    public function index()
    {
        $this->require_scope('read');

        $products = $this->db->raw('SELECT * FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

        $this->api->respond(['data' => array_map([$this, 'format'], $products)]);
    }

    // GET /api/products/{id}
    public function show($id)
    {
        $this->require_scope('read');

        $this->api->respond(['data' => $this->format($this->find_or_404($id))]);
    }

    // POST /api/products
    public function store()
    {
        $this->require_scope('write');

        $data = $this->validated_input(false);
        $id   = $this->ProductModel->insert($data);

        if (!$id) {
            $this->api->respond_error('Could not create the product.', 500);
        }

        $this->api->respond([
            'message' => 'Product created.',
            'data'    => $this->format($this->find_or_404($id)),
        ], 201);
    }

    // PUT /api/products/{id} replaces every field; PATCH only the fields sent.
    public function update($id)
    {
        $this->require_scope('write');
        $this->find_or_404($id);

        $partial = $_SERVER['REQUEST_METHOD'] === 'PATCH';
        $data    = $this->validated_input($partial);

        if ($data) {
            $this->ProductModel->update($id, $data);
        }

        $this->api->respond([
            'message' => 'Product updated.',
            'data'    => $this->format($this->find_or_404($id)),
        ]);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $this->require_scope('delete');
        $this->find_or_404($id);

        $this->ProductModel->delete($id);

        $this->api->respond(['message' => 'Product deleted.']);
    }

    private function require_scope($scope)
    {
        $payload = $this->api->require_jwt();

        if (!in_array($scope, $payload['scopes'] ?? [], true)) {
            $this->api->respond_error("Your account does not have '{$scope}' permission.", 403);
        }

        return $payload;
    }

    private function find_or_404($id)
    {
        $product = $this->ProductModel->find($id);

        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }

        return $product;
    }

    /**
     * Reads the JSON body and validates it against the products columns.
     * Responds 422 with per-field errors on failure.
     *
     * The raw JSON is used rather than $this->api->body(), which HTML-escapes
     * every string: "Salt & Pepper" would be stored as "Salt &amp; Pepper".
     * Escaping on output is the frontend's job (React does it by default).
     */
    private function validated_input($partial)
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body)) {
            $this->api->respond_error('Request body must be a JSON object.', 400);
        }

        $data   = array_intersect_key($body, array_flip($this->writable));
        $errors = [];

        if (!$partial || array_key_exists('product_name', $data)) {
            $data['product_name'] = trim((string) ($data['product_name'] ?? ''));
            if ($data['product_name'] === '') {
                $errors['product_name'] = 'Product name is required.';
            } elseif (mb_strlen($data['product_name']) > 100) {
                $errors['product_name'] = 'Product name must be at most 100 characters.';
            }
        }

        if (!$partial || array_key_exists('description', $data)) {
            $data['description'] = trim((string) ($data['description'] ?? ''));
        }

        if (!$partial || array_key_exists('price', $data)) {
            $price = $data['price'] ?? null;
            if (!is_numeric($price) || $price < 0 || $price >= 100000000) {
                $errors['price'] = 'Price must be a number between 0 and 99,999,999.99.';
            } else {
                $data['price'] = round((float) $price, 2);
            }
        }

        if (!$partial || array_key_exists('quantity', $data)) {
            $quantity = $data['quantity'] ?? null;
            if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                $errors['quantity'] = 'Quantity must be a whole number of 0 or more.';
            } else {
                $data['quantity'] = (int) $quantity;
            }
        }

        if ($errors) {
            $this->api->respond(['error' => 'Validation failed.', 'errors' => $errors, 'status' => 422], 422);
        }

        return $data;
    }

    // Casts DB strings to JSON numbers so the frontend gets 19.99, not "19.99".
    private function format($product)
    {
        return [
            'id'           => (int) $product['id'],
            'product_name' => $product['product_name'],
            'description'  => $product['description'],
            'price'        => (float) $product['price'],
            'quantity'     => (int) $product['quantity'],
            'created_at'   => $product['created_at'],
        ];
    }
}
