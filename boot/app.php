<?php
/** Here we are going to strap out boot on and start loading the routers */
use Framework\Router;

/** Get the error handling up and supress errors to the front */
require('../includes/errorhandling.php');
ini_set('display_errors', 0);
set_error_handler("errorHandler", E_ALL);
register_shutdown_function(function () {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
        // fatal error
        errorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
});

/** Includes for framework files - Classes */
require('../includes/router.php');
require('../includes/request.php');
require('../includes/model.php');
require('../includes/view.php');
require('../db/dbconnect.php');

/** Register Autoloader for APP namespace */
spl_autoload_register(function ($class) {
    $theInclude = dirname(dirname(__FILE__))."/".str_replace('\\', '/', $class).'.php';
    if (substr($class, 0, 3) === "App") {
        require($theInclude);
    }
});

// Start the session
session_start();

// Include the routes
$router = new Router(new \Framework\request());
include('../routes/web.php');
