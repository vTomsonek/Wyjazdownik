<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Trip;
use App\Services\AuthService;

/**
 * Dashboard admina - lista wyjazdów.
 */
final class AdminController extends Controller
{
    public function dashboard(Request $request): never
    {
        $admin = (new AuthService())->currentAdmin();

        $this->render('admin/dashboard', [
            'title'        => 'Panel admina - Wyjazdownik.pl',
            'description'  => 'Panel zarządzania wyjazdami.',
            'admin'        => $admin,
            'trips'        => Trip::listForAdmin($admin->id),
            'flashSuccess' => flash('success'),
            'flashError'   => flash('error'),
        ], 'admin');
    }
}
