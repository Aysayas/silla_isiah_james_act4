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
 * @since Version 4
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */
/*
|--------------------------------------------------------------------------
| Adding of middlewares
|--------------------------------------------------------------------------
|
| Used for adding middlewares
|
*/
// Middleware map: LavaLust's Middleware::resolve() calls ->handle($next) directly
// on the value here, so each entry must be an already-instantiated object, not a
// class name string. There's no autoloader for app/middlewares/, so require_once
// the class file first.
require_once APP_DIR . 'middlewares/AuthMiddleware.php';

// NOTE: deliberately NOT named $config. This file is require_once'd from
// app/config/routes.php, which itself runs in scheme/kernel/LavaLust.php's global
// scope — and LavaLust.php already assigns a *Config object* to the global $config
// at `$config = load_class('config', 'kernel');` (line ~148). require_once shares
// the caller's variable scope, so `$config['middlewares'] = [...]` here would try
// to array-index that Config object and fatal ("Cannot use object of type Config
// as array"). The framework's own config/*.php files dodge this by being included
// from inside a loader *function* (get_config(), database_config(), ...), which
// gives them a fresh local scope — this file has no such loader, so a distinct
// variable name is used instead.
$middleware_config['middlewares'] = [
    'auth' => new AuthMiddleware(),
];

// This file is NOT auto-loaded anywhere in scheme/kernel/ (verified: only
// config.php, autoload.php, database.php and routes.php have dedicated loaders).
// It's require_once'd directly from app/config/routes.php, and merged into the
// framework's global config here so Middleware.php's constructor (which reads
// get_config()['middlewares']) sees it before routing dispatches.
get_config($middleware_config);
