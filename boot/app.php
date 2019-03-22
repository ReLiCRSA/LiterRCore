<?php
/** Here we are going to strap out boot on and start loading the routers */
use Framework\Router;

/** Get the error handling up and supress errors to the front */
include('../includes/errorhandling.php');
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
include('../includes/router.php');
include('../includes/request.php');
include('../includes/model.php');
include('../includes/view.php');
include('../db/dbconnect.php');

/** Register Autoloader for APP namespace */
spl_autoload_register(function ($class) {
    $theInclude = dirname(dirname(__FILE__))."/".str_replace('\\', '/', $class).'.php';
    if (substr($class, 0, 3) === "App") {
        @include($theInclude);
    }
});

// Start the session
session_start();

// Include the routes
$router = new Router(new \Framework\request());
include('../routes/web.php');
