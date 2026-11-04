<?php

namespace Lliure;

use Router\Router;

/**
 * App que declara as próprias rotas.
 *
 * Opcional, ao lado do ServiceProviderInterface: no modo sozinho as rotas
 * costumam ficar no index.php do projeto (config/routes.php). No modo
 * embarcado, quem hospeda não conhece o app e só pode pedir a ele as rotas.
 * Por isso um app que roda embarcado implementa as duas interfaces.
 */
interface RoutesProviderInterface
{
    public static function routes(Router $router): void;
}
