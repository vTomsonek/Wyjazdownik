<?php
declare(strict_types=1);

/**
 * Wyjazdownik.pl - front controller.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AdminAuthController;
use App\Controllers\AdminController;
use App\Controllers\AdminTripsController;
use App\Controllers\AdminParticipantsController;
use App\Controllers\ParticipantMapController;
use App\Controllers\ParticipantWizardController;
use App\Controllers\SummaryController;
use App\Services\AuthService;

session_start();

$router  = new Router();
$request = new Request();

// ---------------------------------------------------------------------------
// Middleware: wymaga zalogowanego admina
// ---------------------------------------------------------------------------
$requireAdmin = static function (): void {
    (new AuthService())->requireAdmin();
};

// ---------------------------------------------------------------------------
// Public routes
// ---------------------------------------------------------------------------
$router->get('/',            [HomeController::class, 'index']);
$router->get('/zdrowie',     [HomeController::class, 'health']);
$router->get('/sitemap.xml', [HomeController::class, 'sitemap']);
$router->get('/robots.txt',  [HomeController::class, 'robots']);

// ---------------------------------------------------------------------------
// Admin auth
// ---------------------------------------------------------------------------
$router->get ('/admin/login',  [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login',  [AdminAuthController::class, 'sendMagicLink']);
$router->get ('/admin/auth',   [AdminAuthController::class, 'authenticate']);
$router->get ('/admin/logout', [AdminAuthController::class, 'logout']);

// ---------------------------------------------------------------------------
// Admin (chronione) - dashboard
// ---------------------------------------------------------------------------
$router->get('/admin', [AdminController::class, 'dashboard'], [$requireAdmin]);

// Admin - CRUD wyjazdów
$router->get ('/admin/trips/new',                 [AdminTripsController::class, 'newTrip'],     [$requireAdmin]);
$router->post('/admin/trips',                     [AdminTripsController::class, 'createTrip'],  [$requireAdmin]);
$router->get ('/admin/trips/{id:int}/edit',       [AdminTripsController::class, 'editTrip'],    [$requireAdmin]);
$router->post('/admin/trips/{id:int}/edit',       [AdminTripsController::class, 'updateTrip'],  [$requireAdmin]);
$router->post('/admin/trips/{id:int}/delete',     [AdminTripsController::class, 'deleteTrip'],  [$requireAdmin]);

// Admin - uczestnicy danego wyjazdu
$router->get ('/admin/trips/{id:int}/participants', [AdminParticipantsController::class, 'listParticipants'],  [$requireAdmin]);
$router->post('/admin/trips/{id:int}/participants', [AdminParticipantsController::class, 'createParticipant'], [$requireAdmin]);

// Admin - operacje na konkretnym uczestniku
$router->get ('/admin/participants/{id:int}/edit',      [AdminParticipantsController::class, 'editParticipant'],   [$requireAdmin]);
$router->post('/admin/participants/{id:int}/edit',      [AdminParticipantsController::class, 'updateParticipant'], [$requireAdmin]);
$router->post('/admin/participants/{id:int}/delete',    [AdminParticipantsController::class, 'deleteParticipant'], [$requireAdmin]);
$router->get ('/admin/participants/{id:int}/responses', [AdminParticipantsController::class, 'viewResponses'],     [$requireAdmin]);

// ---------------------------------------------------------------------------
// Wizard uczestnika
// ---------------------------------------------------------------------------
$router->get ('/p/{token:hex}',                   [ParticipantWizardController::class, 'welcome']);
$router->get ('/p/{token:hex}/wizard/{step:int}', [ParticipantWizardController::class, 'step']);
$router->post('/p/{token:hex}/save',              [ParticipantWizardController::class, 'save']);
$router->post('/p/{token:hex}/submit',            [ParticipantWizardController::class, 'submit']);
$router->get ('/p/{token:hex}/dziekujemy',        [ParticipantWizardController::class, 'thanks']);

// Mapa - endpointy AJAX dla kroku 10
$router->get ('/p/{token:hex}/map/pins',                  [ParticipantMapController::class, 'listPins']);
$router->post('/p/{token:hex}/map/pins',                  [ParticipantMapController::class, 'createPin']);
$router->post('/p/{token:hex}/map/pins/{id:int}/update',  [ParticipantMapController::class, 'updatePin']);
$router->post('/p/{token:hex}/map/pins/{id:int}/delete',  [ParticipantMapController::class, 'deletePin']);

// ---------------------------------------------------------------------------
// Placeholder routes - prawdziwe handlery w kolejnych etapach
// ---------------------------------------------------------------------------
$router->get('/summary/{token:hex}', [SummaryController::class, 'show']);

// ---------------------------------------------------------------------------
// Dispatch
// ---------------------------------------------------------------------------
try {
    $router->dispatch($request);
} catch (Throwable $e) {
    if (config('app.debug')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "BLAD: " . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
        exit;
    }
    error_log('[wyjazdownik] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    Response::serverError();
}
