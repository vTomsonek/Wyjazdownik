<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Csrf;
use App\Helpers\Validator;
use App\Models\Participant;
use App\Models\Trip;
use App\Services\AuthService;
use App\Services\UploadService;
use RuntimeException;

final class AdminParticipantsController extends Controller
{
    public function listParticipants(Request $request, array $args): never
    {
        $admin = (new AuthService())->currentAdmin();
        $trip = Trip::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($trip === null) $this->notFound();

        $this->render('admin/trip-participants', [
            'title'        => 'Uczestnicy: ' . $trip->name,
            'trip'         => $trip,
            'participants' => Participant::listForTrip($trip->id),
            'errors'       => $_SESSION['_form_errors'] ?? [],
            'old'          => $_SESSION['_form_old']    ?? [],
            'flashSuccess' => flash('success'),
            'flashError'   => flash('error'),
        ], 'admin');
        unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
    }

    public function createParticipant(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $trip = Trip::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($trip === null) $this->notFound();

        $nickname = trim((string) $request->input('nickname', ''));
        $v = new Validator(['nickname' => $nickname]);
        $v->required('nickname', 'Podaj ksywke.')->maxLength('nickname', 60);

        if (!$v->isValid()) {
            $_SESSION['_form_errors'] = $v->errors();
            $_SESSION['_form_old']    = ['nickname' => $nickname];
            $this->redirect(url('/admin/trips/' . $trip->id . '/participants'));
        }

        try {
            $avatarPath = UploadService::uploadAvatar($_FILES['avatar'] ?? []);
        } catch (RuntimeException $e) {
            $_SESSION['_form_errors'] = ['avatar' => $e->getMessage()];
            $_SESSION['_form_old']    = ['nickname' => $nickname];
            $this->redirect(url('/admin/trips/' . $trip->id . '/participants'));
        }

        $p = Participant::create([
            'trip_id'     => $trip->id,
            'nickname'    => $nickname,
            'avatar_path' => $avatarPath,
        ]);

        flash('success', 'Dodano uczestnika.');
        $this->redirect(url('/admin/trips/' . $trip->id . '/participants') . '#participant-' . $p->id);
    }

    public function editParticipant(Request $request, array $args): never
    {
        $admin = (new AuthService())->currentAdmin();
        $found = Participant::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($found === null) $this->notFound();
        [$participant, $trip] = $found;

        $this->render('admin/participant-form', [
            'title'        => 'Edycja uczestnika: ' . $participant->nickname,
            'trip'         => $trip,
            'participant'  => $participant,
            'errors'       => $_SESSION['_form_errors'] ?? [],
            'old'          => $_SESSION['_form_old']    ?? [],
            'flashSuccess' => flash('success'),
            'flashError'   => flash('error'),
        ], 'admin');
        unset($_SESSION['_form_errors'], $_SESSION['_form_old']);
    }

    public function updateParticipant(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $found = Participant::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($found === null) $this->notFound();
        [$participant, $trip] = $found;

        $nickname = trim((string) $request->input('nickname', ''));
        $v = new Validator(['nickname' => $nickname]);
        $v->required('nickname', 'Podaj ksywke.')->maxLength('nickname', 60);

        if (!$v->isValid()) {
            $_SESSION['_form_errors'] = $v->errors();
            $_SESSION['_form_old']    = ['nickname' => $nickname];
            $this->redirect(url('/admin/participants/' . $participant->id . '/edit'));
        }

        $update = ['nickname' => $nickname];

        // Color (radio - puste znaczy "auto" czyli null w DB)
        $colorInput = (string) $request->input('color', '');
        $palette    = \App\Services\MapColorService::palette();
        if ($colorInput === '') {
            $update['color'] = null;
        } elseif (in_array($colorInput, $palette, true)) {
            $update['color'] = $colorInput;
        }

        try {
            $avatarPath = UploadService::uploadAvatar($_FILES['avatar'] ?? []);
            if ($avatarPath !== null) {
                UploadService::delete($participant->avatarPath);
                $update['avatar_path'] = $avatarPath;
            }
        } catch (RuntimeException $e) {
            $_SESSION['_form_errors'] = ['avatar' => $e->getMessage()];
            $_SESSION['_form_old']    = ['nickname' => $nickname];
            $this->redirect(url('/admin/participants/' . $participant->id . '/edit'));
        }

        $participant = $participant->update($update);

        // Jesli admin zmienil kolor - propaguj do wszystkich pinezek uczestnika.
        if (array_key_exists('color', $update)) {
            $effectiveColor = \App\Services\MapColorService::forParticipant($participant);
            \App\Database\Connection::get()
                ->prepare('UPDATE participant_map_pins SET color = :c WHERE participant_id = :p')
                ->execute(['c' => $effectiveColor, 'p' => $participant->id]);
        }

        flash('success', 'Zapisano zmiany.');
        $this->redirect(url('/admin/participants/' . $participant->id . '/edit'));
    }

    public function deleteParticipant(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $found = Participant::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($found === null) $this->notFound();
        [$participant, $trip] = $found;

        UploadService::delete($participant->avatarPath);
        $name = $participant->nickname;
        $participant->delete();

        flash('success', 'Usunieto uczestnika "' . $name . '".');
        $this->redirect(url('/admin/trips/' . $trip->id . '/participants'));
    }

    /**
     * Zapisuje nowa kolejnosc uczestnikow (drag & drop). AJAX endpoint - JSON in/out.
     * Body: { "order": [12, 5, 8, ...] } - lista ID w docelowej kolejnosci.
     */
    public function reorderParticipants(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $trip = Trip::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($trip === null) $this->notFound();

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $order = $payload['order'] ?? null;

        if (!is_array($order) || empty($order)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Brak listy ID']);
            exit;
        }

        // Walidacja - wszystkie ID musza nalezec do tego tripu
        $validIds = array_map(static fn($p) => $p->id, Participant::listForTrip($trip->id));
        $cleanOrder = [];
        foreach ($order as $id) {
            $intId = (int) $id;
            if (in_array($intId, $validIds, true)) {
                $cleanOrder[] = $intId;
            }
        }

        Participant::reorderForTrip($trip->id, $cleanOrder);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'count' => count($cleanOrder)]);
        exit;
    }

    /**
     * Toggle ukrywania uczestnika w podsumowaniu. Dane zostaja w bazie -
     * mozna przywrocic. Uzyteczne np. zeby zobaczyc plan bez konkretnej osoby.
     */
    public function toggleHiddenParticipant(Request $request, array $args): never
    {
        Csrf::validate();
        $admin = (new AuthService())->currentAdmin();
        $found = Participant::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($found === null) $this->notFound();
        [$participant, $trip] = $found;

        $nowHidden = $participant->toggleHidden();

        flash('success', $nowHidden
            ? 'Ukryto "' . $participant->nickname . '" w podsumowaniu (dane zachowane, mozna przywrocic).'
            : 'Przywrocono "' . $participant->nickname . '" w podsumowaniu.');
        $this->redirect(url('/admin/trips/' . $trip->id . '/participants') . '#participant-' . $participant->id);
    }

    public function viewResponses(Request $request, array $args): never
    {
        $admin = (new AuthService())->currentAdmin();
        $found = Participant::findByIdForAdmin((int) $args['id'], $admin->id);
        if ($found === null) $this->notFound();
        [$participant, $trip] = $found;
        $this->redirect(url('/p/' . $participant->accessToken));
    }
}
