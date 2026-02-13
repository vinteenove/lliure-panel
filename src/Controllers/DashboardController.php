<?php

namespace Lliure\Controllers;

use Lliure\Bootstrap;
use Psr\Http\Message\ServerRequestInterface;

class DashboardController
{
    public function index(ServerRequestInterface $request, array $vars): string
    {
        $bootstrap = Bootstrap::getInstance();
        $twig = $bootstrap->getTwig();

        return $twig->render('dashboard.twig', [
            'apps'       => $bootstrap->getApps(),
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
