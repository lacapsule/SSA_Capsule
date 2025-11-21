<?php

declare(strict_types=1);

// ==========================================
// IMPORT FRAMEWORK
// ==========================================
use App\Modules\Galerie\GalerieController;
use App\Modules\Projet\ProjetController;
use Capsule\Contracts\TemplateLocatorInterface;
use Capsule\Domain\Service\AuthService;
use Capsule\Auth\PhpSessionReader;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\SessionReader;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Repository\UserRepository;
use Capsule\Domain\Service\PasswordService;
use Capsule\Domain\Service\UserService;
use Capsule\Http\Factory\ResponseFactory;
use Capsule\Http\Middleware\AuthRequiredMiddleware;
use Capsule\Http\Middleware\DebugHeaders;
use Capsule\Http\Middleware\ErrorBoundary;
use Capsule\Http\Middleware\LangMiddleware;
use Capsule\Http\Middleware\SecurityHeaders;
use Capsule\Infrastructure\Container\DIContainer;
use Capsule\View\FilesystemTemplateLocator;
use Capsule\View\MiniMustache;
use Capsule\Infrastructure\Database\SqliteConnection;
// use Capsule\Infrastructure\Database\MariaDBConnection;
// ==========================================
// IMPORT Applications
//  e
// ==========================================
use App\Modules\Agenda\AgendaController;
use App\Modules\Agenda\AgendaRepository;
use App\Modules\Agenda\AgendaService;
use App\Modules\Article\ArticleController;
use App\Modules\Article\ArticleRepository;
use App\Modules\Article\ArticleService;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Dashboard\DashboardService;
use App\Modules\Galerie\GalerieRepository;
use App\Modules\Galerie\GalerieService;
use App\Modules\Home\HomeController;
use App\Modules\Home\HomeService;
use App\Modules\Login\LoginController;
use App\Modules\User\UserController;
use App\Providers\PartnersProvider;
use App\Providers\SidebarLinksProvider;

return (function (): DIContainer {
    $c = new DIContainer();

    // ==========================================
    // CONFIGURATION
    // ==========================================
    $LENGTH_PASSWORD = 8;
    $isDev = true;   // -> Changer vers false en prod
    $https = false;  // -> Changer vers true en prod

    // ==========================================
    // BASE DE DONNÉES
    // ==========================================
    $c->set('pdo', fn () => SqliteConnection::getInstance());

    // ==========================================
    // MIDDLEWARES
    // ==========================================
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

    // ==========================================
    // SYSTÈME DE RÉPONSES ET VUES
    // ==========================================
    $c->set(ResponseFactoryInterface::class, fn () => new ResponseFactory());
    $c->set(SessionReader::class, fn () => new PhpSessionReader());

    // Configuration des templates
    $c->set(TemplateLocatorInterface::class, function () {
        $tplRoot = realpath(dirname(__DIR__) . '/templates');
        if ($tplRoot === false) {
            throw new \RuntimeException('Templates directory not found');
        }

        $map = [
            'page' => $tplRoot . '/modules',      // page:home/index
            'component' => $tplRoot . '/modules',      // component:dashboard/components/dash_agenda
            'partial' => $tplRoot . '/partials',     // partial:public/header
            'layout' => $tplRoot . '/layouts',      // layout:main, layout:dashboard
        ];

        foreach ($map as $ns => $dir) {
            if (!is_dir($dir)) {
                throw new \RuntimeException("Template namespace '{$ns}' directory missing: {$dir}");
            }
        }

        return new FilesystemTemplateLocator($map);
    });

    $c->set(ViewRendererInterface::class, function ($c) {
        $locator = $c->get(TemplateLocatorInterface::class);
        $engine = new MiniMustache($locator);

        return new class ($engine) implements ViewRendererInterface {
            public function __construct(private MiniMustache $m)
            {
            }

            /**
             * Render simple (utilisé pour layouts et pages complètes)
             */
            public function render(string $template, array $data = []): string
            {
                return $this->m->render($template, $data);
            }

            /**
             * RenderComponent = fragment sans wrapping
             */
            public function renderComponent(string $componentPath, array $data = []): string
            {
                // Normalisation: si pas de préfixe, ajouter 'component:'
                $logical = str_contains($componentPath, ':')
                    ? $componentPath
                    : 'component:' . ltrim($componentPath, '/');

                return $this->m->render($logical, $data);
            }
        };
    });

    // ==========================================
    // REPOSITORIES (Accès aux données)
    // ==========================================
    $c->set(UserRepository::class, fn ($c) => new UserRepository($c->get('pdo')));
    $c->set(ArticleRepository::class, fn ($c) => new ArticleRepository($c->get('pdo')));
    $c->set(AgendaRepository::class, fn ($c) => new AgendaRepository($c->get('pdo')));
    $c->set(GalerieRepository::class, fn () => new GalerieRepository());

    // ==========================================
    // SERVICES (Logique métier)
    // ==========================================
    $c->set(AuthService::class, fn ($c) => new AuthService(
        $c->get(UserRepository::class)
    ));

    $c->set(PasswordService::class, fn ($c) => new PasswordService(
        $c->get(UserRepository::class),
        $LENGTH_PASSWORD,
        []
    ));

    $c->set(UserService::class, fn ($c) => new UserService(
        $c->get(UserRepository::class)
    ));

    $c->set(ArticleService::class, fn ($c) => new ArticleService(
        $c->get(ArticleRepository::class)
    ));

    $c->set(AgendaService::class, fn ($c) => new AgendaService(
        $c->get(AgendaRepository::class)
    ));

    $c->set(HomeService::class, fn ($c) => new HomeService(
        $c->get(ArticleService::class),
        $c->get(PartnersProvider::class),
    ));

    $c->set(DashboardService::class, fn ($c) => new DashboardService(
        $c->get(UserService::class)
    ));

    $c->set(GalerieService::class, fn ($c) => new GalerieService(
        $c->get(GalerieRepository::class)
    ));

    // ==========================================
    // PROVIDERS (Fournisseurs de données)
    // ==========================================
    $c->set(PartnersProvider::class, fn () => new PartnersProvider());
    $c->set(SidebarLinksProvider::class, fn () => new SidebarLinksProvider());

    // ==========================================
    // CONTROLLERS (Gestion des pages)
    // ==========================================
    $c->set(LoginController::class, fn ($c) => new LoginController(
        $c->get(AuthService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(HomeController::class, fn ($c) => new HomeController(
        $c->get(HomeService::class),
        $c->get(ArticleService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(GalerieController::class, fn ($c) => new GalerieController(
        $c->get(GalerieService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));
    $c->set(ProjetController::class, fn ($c) => new ProjetController(
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(DashboardController::class, fn ($c) => new DashboardController(
        $c->get(DashboardService::class),
        $c->get(PasswordService::class),
        $c->get(SidebarLinksProvider::class),
        $c->get(GalerieService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(UserController::class, fn ($c) => new UserController(
        $c->get(UserService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(ArticleController::class, fn ($c) => new ArticleController(
        $c->get(ArticleService::class),
        $c->get(SidebarLinksProvider::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    $c->set(AgendaController::class, fn ($c) => new AgendaController(
        $c->get(AgendaService::class),
        $c->get(SidebarLinksProvider::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(ViewRendererInterface::class),
    ));

    return $c;
})();
