<?php

namespace Lliure\Controllers;

use Lliure\Bootstrap;

class DashboardController
{
    public function index(): void
    {
        $bootstrap = Bootstrap::getInstance();
        $twig = $bootstrap->getTwig();
        $config = $bootstrap->getConfig('app');

        echo $twig->render('dashboard.twig', [
            'appName' => $config['name'] ?? 'Lliure',
            'version' => $config['version'] ?? '2.0.0',
            'apps'    => $bootstrap->getApps(),
        ]);
    }
}
