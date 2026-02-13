<?php

require_once dirname(__DIR__) . '/src/Bootstrap.php';

use Lliure\Bootstrap;
use Lliure\Router;

$bootstrap = Bootstrap::getInstance();

$router = new Router();
$router->loadRoutes(dirname(__DIR__) . '/config/routes.php');

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$router->dispatch($uri);
