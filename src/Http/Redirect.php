<?php

namespace Lliure\Http;

use Lliure\Auth\Session;
use Lliure\Bootstrap;
use Lliure\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;

class Redirect
{
    /**
     * Create a redirect Response with basePath prepended.
     */
    public static function response(string $url, int $status = 302): ResponseInterface
    {
        $bootstrap = Bootstrap::getInstance();
        $basePath = $bootstrap->getBasePath();

        // Prepend basePath if the URL is relative and doesn't already include it
        if (str_starts_with($url, '/') && !str_starts_with($url, $basePath . '/') && $url !== $basePath) {
            $url = $basePath . $url;
        }

        $response = new Response();
        return $response->withStatus($status)->withHeader('Location', $url);
    }

    /**
     * Create a redirect response to a full URL (no basePath prepend).
     */
    public static function away(string $url, int $status = 302): ResponseInterface
    {
        $response = new Response();
        return $response->withStatus($status)->withHeader('Location', $url);
    }

    /**
     * Flash a value to session before redirect.
     */
    public static function with(string $key, string $value): self
    {
        $session = Session::getInstance();
        $session->flash($key, $value);
        return new self();
    }

    public function then(string $url): ResponseInterface
    {
        return self::response($url);
    }
}
