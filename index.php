<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

/* Load libraries */
require_once 'lib/SCSS/scss.inc.php';
require_once 'lib/JSqueeze/class/JSqueeze.php';
require_once 'lib/Twig/Autoloader.php';
Twig_Autoloader::register();

/* Autload core classes */
spl_autoload_register(function ($class_name) {
    require_once strtolower($class_name) . '.php';
});

/* Route */
$router = new Router();
$router->route();

/* Print */
$page = $router->getPage();
$page->render();
