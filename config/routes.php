<?php

use Lliure\Controllers\DashboardController;

return [
    '/'          => [DashboardController::class, 'index'],
    '/dashboard' => [DashboardController::class, 'index'],
];
