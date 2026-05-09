<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Csrf;
use App\Helpers\Validator;
use App\Models\Trip;
use App\Services\AuthService;
use App\Services\SlugService;
use App\Services\UploadService;
use RuntimeException;

/**
 * CRUD wyjazdów dla admina.
 */
final class AdminTripsController extends Controller
{
    public function newTrip(Request $request): never
    {
        $this->render('admin/trip-form', [
            'title'        => 'Nowy wyjazd - Wyjazdownik.pl',
            'mode'         => 'new',
            'trip'         => null,
            'errors'       => $_SESSION['_form_errors'] ?? [],
            'old'          => $_SESSION['_form_old']    ?? [],
            'flashError'   => flash('error'),
        ], 'admin');
    }

    public function createTrip(Request $request): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();

        $data = $this->collectTripData($request);
        $errors = $this->validateTripData($data);

        if (!empty($errors)) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_old']    = $data;
            $this->redirect(url('/admin/trips/new'));
        }

        try {
            $bannerPath = UploadService::uploadBanner($_FILES['banner_image'] ?? []);
        } catch (RuntimeException $e) {
            $_SESSION['_form_errors'] = ['banner_image' => $e->getMessage()];
            $_SESSION['_form_old']    = $data;
            $this->redirect(url('/admin/trips/new'));
        }

        $slug = SlugService::unique($data['slug'] !== '' ? $data['slug'] : $data['name']);

        $trip = Trip::create([
            'admin_id'                  => $admin->id,
            'name'                      => $data['name'],
            'slug'                      => $slug,
            'description'               => $data['description'] !== '' ? $data['description'] : null,
            'banner_image'              => $bannerPath,
            'date_from'                 => $data['date_from'],
            'date_to'                   => $data['date_to'],
            'calendar_mode'             => $data['calendar_mode'],
            'show_individual_responses' => $data['show_individual_responses'],
            'is_active'                 => true,
        ]);

        unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
        flash('success', 'Wyjazd "' . $trip->name . '" został utworzony.');
        $this->redirect(url('/admin/trips/' . $trip->id . '/participants'));
    }

    public function editTrip(Request $request, array $args): never
    {
        $admin = (new AuthService())->currentAdmin();
        $trip = Trip::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($trip === null) {
            $this->notFound();
        }

        $this->render('admin/trip-form', [
            'title'        => 'Edycja: ' . $trip->name,
            'mode'         => 'edit',
            'trip'         => $trip,
            'errors'       => $_SESSION['_form_errors'] ?? [],
            'old'          => $_SESSION['_form_old']    ?? [],
            'flashError'   => flash('error'),
            'flashSuccess' => flash('success'),
        ], 'admin');
        unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
    }

    public function updateTrip(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $trip = Trip::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($trip === null) {
            $this->notFound();
        }

        $data = $this->collectTripData($request);
        $errors = $this->validateTripData($data);

        if (!empty($errors)) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_old']    = $data;
            $this->redirect(url('/admin/trips/' . $trip->id . '/edit'));
        }

        $update = [
            'name'                      => $data['name'],
            'description'               => $data['description'] !== '' ? $data['description'] : null,
            'date_from'                 => $data['date_from'],
            'date_to'                   => $data['date_to'],
            'calendar_mode'             => $data['calendar_mode'],
            'show_individual_responses' => $data['show_individual_responses'],
        ];

        // Slug edytowalny - jeśli admin go zmienił, regenerujemy unikalność
        if ($data['slug'] !== '' && $data['slug'] !== $trip->slug) {
            $update['slug'] = SlugService::unique($data['slug'], $trip->id);
        }

        // Banner upload (tylko gdy nowy plik)
        try {
            $newBanner = UploadService::uploadBanner($_FILES['banner_image'] ?? []);
            if ($newBanner !== null) {
                UploadService::delete($trip->bannerImage);
                $update['banner_image'] = $newBanner;
            }
        } catch (RuntimeException $e) {
            $_SESSION['_form_errors'] = ['banner_image' => $e->getMessage()];
            $_SESSION['_form_old']    = $data;
            $this->redirect(url('/admin/trips/' . $trip->id . '/edit'));
        }

        $trip->update($update);

        unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
        flash('success', 'Zapisano zmiany.');
        $this->redirect(url('/admin/trips/' . $trip->id . '/edit'));
    }

    public function deleteTrip(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $trip = Trip::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($trip === null) {
            $this->notFound();
        }

        UploadService::delete($trip->bannerImage);
        // Avatary uczestników kasujemy razem z dyskiem - DB rzuci CASCADE
        // (kosmetyka - można dorzucić w ETAPIE 8 cleanup)

        $name = $trip->name;
        $trip->delete();

        flash('success', 'Wyjazd "' . $name . '" został usunięty.');
        $this->redirect(url('/admin'));
    }

    /**
     * @return array<string,string|bool>
     */
    private function collectTripData(Request $request): array
    {
        return [
            'name'                      => trim((string) $request->input('name', '')),
            'slug'                      => trim((string) $request->input('slug', '')),
            'description'               => trim((string) $request->input('description', '')),
            'date_from'                 => (string) $request->input('date_from', ''),
            'date_to'                   => (string) $request->input('date_to', ''),
            'calendar_mode'             => (string) $request->input('calendar_mode', 'block_unavailable'),
            'show_individual_responses' => (bool) $request->input('show_individual_responses', false),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function validateTripData(array $data): array
    {
        $v = new Validator($data);
        $v->required('name', 'Podaj nazwę wyjazdu.')
          ->maxLength('name', 150)
          ->maxLength('slug', 160)
          ->maxLength('description', 2000)
          ->required('date_from', 'Podaj datę początkową.')
          ->date('date_from')
          ->required('date_to', 'Podaj datę końcową.')
          ->date('date_to')
          ->dateAfter('date_to', 'date_from', 'Data końcowa musi być po dacie początkowej.')
          ->in('calendar_mode', ['block_unavailable', 'select_preferred_weeks'], 'Niepoprawny tryb kalendarza.');
        return $v->errors();
    }
}
