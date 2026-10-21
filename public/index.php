<?php

declare(strict_types=1);

/**
 * FRONT CONTROLLER.
 * Bootstrap order per docs/01-architecture.md §4: paths → autoloader → env
 * → constants → helpers → error handler → timezone → session → routes → dispatch.
 */

// ---- 1. Path constants -----------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', APP_PATH . '/Config');
define('VIEWS_PATH', APP_PATH . '/Views');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ROUTES_PATH', ROOT_PATH . '/routes');

// ---- 2. Autoloader ----------------------------------------------------------
require APP_PATH . '/Core/Autoloader.php';
App\Core\Autoloader::register();
App\Core\Autoloader::addNamespace('App', APP_PATH);

// ---- 3. Environment ----------------------------------------------------------
App\Core\Env::load(ROOT_PATH . '/.env');

// ---- 4. Named constants -------------------------------------------------------
require CONFIG_PATH . '/constants.php';

// ---- 5. Helper functions -------------------------------------------------------
require APP_PATH . '/Helpers/functions.php';

// ---- 6. Error handling ---------------------------------------------------------
App\Core\Exceptions\Handler::register();

// ---- 7. Timezone ----------------------------------------------------------------
date_default_timezone_set((string) config('app.timezone'));

// ---- 8. Session -------------------------------------------------------------------
App\Core\Session::start();

// ---- 9. Routes + dispatch -----------------------------------------------------------
use App\Core\Request;
use App\Core\Router;

require ROUTES_PATH . '/web.php';
require ROUTES_PATH . '/auth.php';
require ROUTES_PATH . '/admin.php';

$request = Request::capture();
$response = Router::dispatch($request);
$response->send();
