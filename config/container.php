<?php

declare(strict_types=1);

use Capsule\Domain\Service\AuthService;
use App\Provider\PartnersProvider;
use App\Controller\AgendaController;
use App\Controller\ArticlesController;
use App\Controller\DashboardController;
use App\Controller\HomeController;
use App\Controller\LoginController;
use App\Controller\UserController;
use App\Provider\SidebarLinksProvider;
use App\Repository\AgendaRepository;
use App\Repository\ArticleRepository;
use App\Service\AgendaService;
use App\Service\ArticleService;
use App\Service\DashboardService;
use App\Service\HomeService;
use Capsule\Auth\PhpSessionReader;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\SessionReader;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Repository\UserRepository;
use Capsule\Domain\Service\PasswordService;
use Capsule\Domain\Service\SessionAuthService;
use Capsule\Domain\Service\UserService;
use Capsule\Http\Factory\ResponseFactory;
use Capsule\Http\Middleware\AuthRequiredMiddleware;
use Capsule\Http\Middleware\DebugHeaders;
use Capsule\Http\Middleware\ErrorBoundary;
use Capsule\Http\Middleware\LangMiddleware;
use Capsule\Http\Middleware\SecurityHeaders;
use Capsule\Infrastructure\Container\DIContainer;
// use Capsule\Infrastructure\Database\MariaDBConnection;
use Capsule\Infrastructure\Database\SqliteConnection;
use Capsule\View\FilesystemTemplateLocator;
use Capsule\View\MiniMustache;

return (function (): DIContainer {
    $c = new DIContainer();
    $LENGTH_PASSWORD = 8;
    $isDev = true;   // -> Changer vers false en prod
    $https = false;  // -> Changer vers true en prod

    // --- Core deps ---
    // $c->set('pdo', fn () => MariaDBConnection::getInstance());
    $c->set('pdo', fn () => SqliteConnection::getInstance()); // FQCN global \PDO

    $c->set(DebugHeaders::class, fn ($c) => new DebugHeaders(
        res: $c->get(ResponseFactoryInterface::class),
        enabled: $isDev
    ));
    $c->set(ErrorBoundary::class, fn ($c) => new ErrorBoundary(
        $c->get(ResponseFactoryInterface::class),
        debug: $isDev,
        appName: 'SSA Website'
    ));
    $c->set(SecurityHeaders::class, fn () => new SecurityHeaders(
        dev: $isDev,
        https: $https
    ));

    $c->set(SessionReader::class, fn () => new PhpSessionReader());
    $c->set(LangMiddleware::class, fn () => new LangMiddleware());

    $c->set(AuthRequiredMiddleware::class, fn ($c) => new AuthRequiredMiddleware(
        session:         $c->get(SessionReader::class),
        res:             $c->get(ResponseFactoryInterface::class),
        requiredRole:    'admin',
        protectedPrefix: '/dashboard',
        whitelist:       ['/login', '/logout'],
        redirectTo:      '/login',
        sessionKey:      'admin',
        roleKey:         'role',
    ));

    $c->set(ResponseFactoryInterface::class, fn () => new ResponseFactory());
    $c->set(ViewRendererInterface::class, function () {
        $tplRoot = realpath(dirname(__DIR__) . '/templates');
        if ($tplRoot === false) {
            throw new \RuntimeException('Templates directory not found');
        }
        $map = [
            'page' => $tplRoot . '/pages',
            'component' => $tplRoot . '/components',
            'partial' => $tplRoot . '/partials',
            'admin' => $tplRoot . '/admin',
            'dashboard' => $tplRoot . '/dashboard',
            'layout' => $tplRoot, // layout:layout → templates/layout.tpl.php
        ];

        foreach ($map as $ns => $dir) {
            if (!is_dir($dir)) {
                throw new \RuntimeException("Template namespace '{$ns}' directory missing: {$dir}");
            }
        }

        $locator = new FilesystemTemplateLocator($map);
        $engine = new MiniMustache($locator);

        return new class ($engine) implements ViewRendererInterface {
            public function __construct(private MiniMustache $m)
            {
            }

            /** Page = avec layout */
            public function render(string $template, array $data = []): string
            {
                $content = $this->m->render($template, $data);

                return $this->m->render('layout:layout', $data + ['content' => $content]);
            }

            /** Component = fragment sans layout */
            public function renderComponent(string $componentPath, array $data = []): string
            {
                $logical = str_contains($componentPath, ':')
                    ? $componentPath
                    : 'component:' . ltrim($componentPath, '/');

                return $this->m->render($logical, $data);
            }
        };
    });

    // --- Repositories ---
    $c->set(ArticleRepository::class, fn ($c) => new ArticleRepository($c->get('pdo')));
    $c->set(UserRepository::class, fn ($c) => new UserRepository($c->get('pdo')));
    $c->set(AgendaRepository::class, fn ($c) => new AgendaRepository($c->get('pdo')));

    // --- Providers / Config ---
    // PartnersProvider peut recevoir un "seed" (config) si tu veux surcharger.
    $c->set(PartnersProvider::class, fn () => new PartnersProvider());

    // --- Services ---
    $c->set(ArticleService::class, fn ($c) => new ArticleService(
        $c->get(ArticleRepository::class)
    ));


    // ...

    $c->set(SessionAuthService::class, fn () => new SessionAuthService());

    $c->set(AuthService::class, fn ($c) => new AuthService(
        $c->get(\Capsule\Domain\Repository\UserRepository::class),
        $c->get(SessionAuthService::class)
    ));

    // Remplace l’ancien binding du LoginController :
    $c->set(\App\Controller\LoginController::class, fn ($c) => new \App\Controller\LoginController(
        $c->get(AuthService::class),
        $c->get(\Capsule\Contracts\ResponseFactoryInterface::class),
        $c->get(\Capsule\Contracts\ViewRendererInterface::class),
    ));

    $c->set(DashboardService::class, fn ($c) => new DashboardService(
        $c->get(\Capsule\Domain\Service\UserService::class)
    ));
    // HomeService agrège ArticleService + PartnersProvider
    $c->set(HomeService::class, fn ($c) => new HomeService(
        $c->get(ArticleService::class),
        $c->get(PartnersProvider::class),
    ));

    $c->set(UserService::class, fn ($c) => new UserService($c->get(UserRepository::class)));
    // --- Services ---
    $c->set(PasswordService::class, fn ($c) => new PasswordService(
        $c->get(UserRepository::class),
        $LENGTH_PASSWORD,
        []
    ));
    // Alias de compat si tu en as besoin ailleurs :
    $c->set('passwords', fn ($c) => $c->get(PasswordService::class));
    $c->set(AgendaService::class, fn ($c) => new AgendaService($c->get(AgendaRepository::class)));

    // --- Navigation ---
    $c->set(SidebarLinksProvider::class, fn () => new SidebarLinksProvider());

    // --- Controllers ---
    $c->set(AgendaController::class, fn ($c) => new AgendaController(
        $c->get(AgendaService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    // HomeController nécessite HomeService + ArticleService (pour /article/{id})
    $c->set(HomeController::class, fn ($c) => new HomeController(
        $c->get(HomeService::class),
        $c->get(ArticleService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(\App\Controller\LoginController::class, fn ($c) => new \App\Controller\LoginController(
        $c->get(AuthService::class),
        $c->get(\Capsule\Contracts\ResponseFactoryInterface::class),
        $c->get(\Capsule\Contracts\ViewRendererInterface::class),
    ));


    $c->set(DashboardController::class, fn ($c) => new DashboardController(
        $c->get(DashboardService::class),
        $c->get(\Capsule\Domain\Service\PasswordService::class),
        $c->get(SidebarLinksProvider::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(UserController::class, fn ($c) => new UserController(
        $c->get(UserService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(ArticlesController::class, fn ($c) => new ArticlesController(
        $c->get(ArticleService::class),
        $c->get(SidebarLinksProvider::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    return $c;
})();
