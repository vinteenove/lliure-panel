<?php

namespace Lliure;

use Lliure\Menu\MenuBuilder;

interface ServiceProviderInterface
{
    public static function boot(Bootstrap $bootstrap): void;
    public static function registerMenu(MenuBuilder $menuBuilder): void;
}
