<?php

require_once dirname(__DIR__) . '/src/Bootstrap.php';

use Lliure\Bootstrap;
use Lliure\Http\Message\ServerRequest;
use Lliure\Http\Message\Uri;
use Cifras\AppServiceProvider;

// Bootstrap
$bootstrap = Bootstrap::getInstance();
$bootstrap->setBasePath('/lliure');

// Create router
$router = new \Router\Router();

// Register routes (auth + dashboard)
(require dirname(__DIR__) . '/config/routes.php')($router);

// Register service providers (for menu items and app registration)
$bootstrap->registerServiceProvider(AppServiceProvider::class);
$bootstrap->registerMenu();
$bootstrap->initTwigGlobals();

// Build PSR-7 URI from $_SERVER (avoiding lliure/http-mensage uninitialized property bugs)
$scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = new Uri($scheme . '://' . $host . $requestUri);

// Build PSR-7 request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

$request = new ServerRequest($method, $uri, $headers, null, $protocol, $_SERVER);
$request = $request->withCookieParams($_COOKIE)
    ->withQueryParams($_GET)
    ->withParsedBody($_POST);

// Extract route path from URI, stripping basePath
$fullPath = $uri->getPath();
$basePath = '/lliure';
$routePath = $fullPath;
if (str_starts_with($fullPath, $basePath)) {
    $routePath = substr($fullPath, strlen($basePath));
}
$routePath = rtrim($routePath, '/') ?: '/';

// Root → internally route to /dashboard
if ($routePath === '/') {
    $routePath = '/dashboard';
}

// lliure/router matches against URI query component
$request = $request->withUri($uri->withQuery(ltrim($routePath, '/')));

// Dispatch
try {
    $response = $router->dispatch($request);
} catch (\Lliure\Http\Exception\HttpException $e) {
    http_response_code($e->getStatusCode());
    echo $e->getStatusCode() . ' — ' . $e->getMessage();
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo '500 — Internal Server Error';
    exit;
}

// Emit response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();
