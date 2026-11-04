<?php

namespace Lliure;

use Lliure\Auth\Auth;
use Lliure\Auth\Session;
use Lliure\Menu\MenuBuilder;
use Lliure\Menu\MenuItem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    private static ?self $instance = null;
    private array $config = [];
    private ?Environment $twig = null;
    private array $apps = [];
    private bool $dbConnected = false;
    private ?MenuBuilder $menuBuilder = null;
    private string $basePath = '';
    private array $panelConfig = [];

    private function __construct()
    {
        $this->loadAutoloader();
        $this->loadConfig();
        $this->initDatabase();
        $this->initTwig();
        $this->menuBuilder = MenuBuilder::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function url(string $path): string
    {
        return $this->basePath . $path;
    }

    private function loadAutoloader(): void
    {
        // [spike] Modo embarcado: quando o Bootstrap sobe dentro de outro processo
        // (o Vimu v1), o autoloader já está carregado e não existe vendor/ próprio
        // para exigir. Só carrega se ninguém carregou antes.
        if (class_exists(\Composer\Autoload\ClassLoader::class, false)) {
            return;
        }

        $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new \RuntimeException('vendor/autoload.php não encontrado. Rode composer install na raiz.');
        }
        require_once $autoloadPath;
    }

    private function loadConfig(): void
    {
        $configDir = dirname(__DIR__) . '/config/';

        foreach (['app', 'database'] as $file) {
            $path = $configDir . $file . '.php';
            if (file_exists($path)) {
                $this->config[$file] = require $path;
            }
        }

        if (isset($this->config['app']['timezone'])) {
            date_default_timezone_set($this->config['app']['timezone']);
        }
    }

    private function initDatabase(): void
    {
        if (!isset($this->config['database'])) {
            return;
        }

        // [spike] Coexistência v1↔v2: se o processo já tem conexão dibi aberta (o
        // Vimu conecta em panel/etc/bdconf.php), REUSA. Um segundo dibi::connect()
        // substitui self::$connection e troca o banco debaixo dos models de quem
        // conectou antes — é a colisão que inviabiliza rodar os dois juntos.
        if (\dibi::isConnected()) {
            $this->dbConnected = true;
            return;
        }

        // [spike] Falha de conexão não pode ser silenciosa: o catch anterior
        // transformava credencial errada em 500 mudo, sem rastro.
        \dibi::connect($this->config['database']);
        $this->dbConnected = true;
    }

    private function initTwig(): void
    {
        $viewsDir = dirname(__DIR__) . '/views';
        $loader = new FilesystemLoader($viewsDir);

        $debug = $this->config['app']['debug'] ?? false;

        // Sem cache o Twig recompila o template a cada requisicao. Medido:
        // 105 ms na pagina de cifra, contra 7 ms com o cache quente.
        $cacheTwig = $debug ? false : dirname(__DIR__, 2) . '/cache/twig';
        if ($cacheTwig && !is_dir($cacheTwig)) {
            @mkdir($cacheTwig, 0775, true);
        }

        $this->twig = new Environment($loader, [
            'cache' => $cacheTwig,
            'debug' => $debug,
            // com debug=false o auto_reload vem desligado por padrao, e o
            // Twig passaria a ignorar template alterado ate limpar o cache
            'auto_reload' => true,
        ]);
    }

    public function initTwigGlobals(): void
    {
        $auth = Auth::getInstance();
        $session = Session::getInstance();
        $currentUri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';

        // Strip basePath for route matching in templates
        $relativeUri = $currentUri;
        if ($this->basePath !== '' && str_starts_with($currentUri, $this->basePath)) {
            $relativeUri = substr($currentUri, strlen($this->basePath));
            $relativeUri = $relativeUri === '' || $relativeUri === false ? '/' : $relativeUri;
        }

        $this->twig->addGlobal('auth', $auth);
        $this->twig->addGlobal('currentUser', $auth->user());
        $this->twig->addGlobal('menuItems', $this->menuBuilder->toArray());
        $this->twig->addGlobal('currentUri', $relativeUri);
        $this->twig->addGlobal('basePath', $this->basePath);
        $this->twig->addGlobal('appName', $this->config['app']['name'] ?? 'Lliure');
        $this->twig->addGlobal('appVersion', $this->config['app']['version'] ?? '2.0.0');
        $this->twig->addGlobal('panel', $this->getPanelConfig());
        $this->twig->addGlobal('flash_error', $session->getFlash('error'));
        $this->twig->addGlobal('flash_success', $session->getFlash('success'));
    }

    public function registerApp(string $name, string $basePath): void
    {
        $this->apps[$name] = $basePath;

        $viewsPath = $basePath . '/views';
        if (is_dir($viewsPath)) {
            $this->twig->getLoader()->addPath($viewsPath, $name);
        }
    }

    public function registerServiceProvider(string $providerClass): void
    {
        $providerClass::boot($this);

        if (is_subclass_of($providerClass, ServiceProviderInterface::class)) {
            $providerClass::registerMenu($this->menuBuilder);
        }
    }

    public function registerMenu(): void
    {
        $this->menuBuilder->addItem(new MenuItem(
            label: 'Dashboard',
            url: '/dashboard',
            icon: 'fas fa-tachometer-alt',
            order: 0
        ));

        $this->menuBuilder->addItem(new MenuItem(
            label: 'Usuários',
            url: '/users',
            icon: 'fas fa-users',
            order: 1
        ));
    }

    public function setPanelConfig(string $key, mixed $value): void
    {
        $this->panelConfig[$key] = $value;
    }

    public function getPanelConfig(): array
    {
        $defaults = [
            'navbar_logo' => $this->getDefaultNavbarLogo(),
            'login_logo' => $this->getDefaultNavbarLogo(),
            'login_logo_mobile' => $this->getDefaultNavbarLogo(),
            'login_tagline' => 'Sistema de Gestao',
            'login_description' => 'Gerencie seus recursos de forma simples, rapida e intuitiva.',
            'login_color' => '#661a33',
            'favicon' => $this->basePath . '/favicon.ico',
        ];

        return array_merge($defaults, $this->panelConfig);
    }

    private function getDefaultNavbarLogo(): string
    {
        $logoPath = dirname(__DIR__) . '/assets/lliure-logo.svg';
        if (file_exists($logoPath)) {
            return file_get_contents($logoPath);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 177" fill="currentColor">'
            . '<path d="M0 1c2,0 4,-1 6,-1 2,0 4,0 6,0 1,0 3,0 5,0 3,0 4,1 6,1l0 137c0,4 1,8 1,10 1,3 2,5 4,6 1,1 2,2 4,2 2,1 4,1 6,1 1,0 3,0 5,0 2,0 3,-1 5,-1 1,6 2,12 2,18 -3,1 -5,1 -8,2 -4,0 -6,0 -9,0 -5,0 -9,-1 -13,-2 -4,-1 -7,-3 -10,-5 -3,-3 -6,-6 -7,-10 -2,-5 -3,-10 -3,-16l0 -142zm61 0c2,0 4,-1 6,-1 2,0 4,0 6,0 2,0 4,0 6,0 2,0 4,1 6,1l0 137c0,4 0,8 1,10 1,3 2,5 3,6 1,1 3,2 5,2 2,1 4,1 6,1 1,0 3,0 4,0 2,0 4,-1 5,-1 1,6 2,12 2,18 -2,1 -5,1 -8,2 -3,0 -6,0 -9,0 -4,0 -8,-1 -12,-2 -4,-1 -8,-3 -11,-5 -3,-3 -5,-6 -7,-10 -2,-5 -3,-10 -3,-16l0 -142zm70 70l-16 0c0,-1 0,-2 -1,-4 0,-2 0,-3 0,-5 0,-1 0,-3 0,-5 1,-1 1,-3 1,-4l38 0 0 121c-1,1 -3,1 -5,1 -2,0 -4,0 -6,0 -2,0 -4,0 -6,0 -2,0 -4,0 -5,-1l0 -103zm-6 -45c0,-2 0,-4 0,-6 0,-2 0,-4 0,-6 0,-2 0,-4 0,-6 0,-2 0,-4 0,-6 2,0 5,0 7,0 2,-1 4,-1 6,-1 2,0 4,0 6,1 3,0 5,0 7,0 0,2 0,4 0,6 0,2 0,4 0,6 0,2 0,4 0,6 0,2 0,4 0,6 -2,1 -4,1 -7,1 -2,0 -4,0 -6,0 -2,0 -4,0 -6,0 -2,0 -5,0 -7,-1zm56 27c2,0 4,-1 6,-1 2,0 4,0 6,0 1,0 3,0 6,0 2,0 4,1 6,1l0 66c0,8 0,14 2,19 1,5 3,9 6,12 3,3 6,5 10,6 5,1 9,2 15,2 8,0 15,-1 20,-3l0 -102c2,0 4,-1 6,-1 2,0 4,0 6,0 2,0 4,0 6,0 2,0 4,1 5,1l0 118c-5,1 -12,3 -19,4 -8,1 -16,2 -24,2 -8,0 -15,-1 -22,-2 -7,-1 -13,-4 -18,-8 -5,-4 -9,-10 -12,-17 -3,-8 -5,-17 -5,-30l0 -67zm128 0c2,0 4,-1 5,-1 2,0 4,0 5,0 2,0 4,0 5,0 2,0 4,1 5,1 1,2 1,5 2,9 0,4 0,7 0,10 4,-5 8,-10 13,-14 6,-4 13,-6 21,-6 2,0 3,0 4,0 2,0 3,1 4,1 0,1 0,3 0,4 1,2 1,3 1,5 0,2 0,4 -1,6 0,2 0,4 -1,6 -1,0 -2,0 -3,0 -2,0 -3,0 -4,0 -4,0 -8,0 -12,1 -4,2 -7,4 -10,7 -3,3 -6,7 -8,12 -1,5 -2,12 -2,20l0 60c-2,1 -4,1 -6,1 -2,0 -4,0 -6,0 -2,0 -3,0 -6,0 -2,0 -4,0 -6,-1l0 -121zm97 66c0,14 4,23 10,30 7,6 16,9 29,9 11,0 22,-2 32,-6 1,2 2,5 3,8 1,4 1,7 2,11 -6,2 -11,3 -18,5 -6,1 -13,1 -20,1 -11,0 -21,-1 -29,-4 -8,-4 -14,-8 -19,-13 -5,-6 -9,-13 -11,-20 -3,-8 -4,-16 -4,-26 0,-9 1,-17 3,-25 3,-8 6,-14 11,-20 5,-6 11,-10 18,-14 7,-3 15,-5 25,-5 9,0 16,2 22,4 7,3 12,7 16,12 4,5 8,11 10,18 2,7 3,14 3,22 0,2 0,5 0,7 0,3 0,5 -1,6l-82 0zm60 -17c0,-4 -1,-9 -2,-13 -1,-4 -3,-7 -5,-10 -2,-3 -5,-6 -9,-7 -3,-2 -8,-3 -12,-3 -10,0 -17,3 -23,9 -5,5 -8,14 -9,24l60 0z"/>'
            . '<polygon points="533,127 600,177 533,177"/>'
            . '</svg>';
    }

    public function getConfig(string $section = null): mixed
    {
        if ($section === null) {
            return $this->config;
        }
        return $this->config[$section] ?? null;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getApps(): array
    {
        return $this->apps;
    }

    public function getMenuBuilder(): MenuBuilder
    {
        return $this->menuBuilder;
    }

    public function isDbConnected(): bool
    {
        return $this->dbConnected;
    }
}
