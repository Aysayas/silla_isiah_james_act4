<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/
/** @var object $router **/

// Loaded here (not via app/config/autoload.php's 'configs' autoload list) because
// this file is require_once'd by scheme/kernel/LavaLust.php before $router->initiate()
// runs, while the 'configs' autoload only fires later, inside a Controller's
// constructor — too late for middleware, which resolves before any Controller exists.
require_once APP_DIR . 'config/middleware.php';

$router->get('/', 'Welcome::index');

// Auth routes (public)
$router->get('/login', 'AuthController::login');           // show login form
$router->post('/login', 'AuthController::authenticate');   // handle login submit
$router->get('/logout', 'AuthController::logout');         // destroy session, redirect to login

// Products CRUD routes — gated by the 'auth' middleware (see app/config/middleware.php).
// The handout names /products, /products/create, /products/edit/{id}, /products/delete/{id}
// as protected; the matching POST endpoints (store/update) are protected alongside their
// GET form counterparts since leaving them open would defeat the point of gating the forms.
$router->group(['middleware' => 'auth'], function ($router) {
    $router->get('/products', 'ProductController::index');                  // list all products
    $router->get('/products/create', 'ProductController::create');         // show create form
    $router->post('/products/store', 'ProductController::store');         // handle create submit
    $router->get('/products/edit/{id}', 'ProductController::edit');       // show edit form
    $router->post('/products/update/{id}', 'ProductController::update'); // handle edit submit
    $router->get('/products/delete/{id}', 'ProductController::delete'); // delete a product
});

