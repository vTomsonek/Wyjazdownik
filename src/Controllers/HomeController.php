<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Database\Connection;
use Throwable;

final class HomeController extends Controller
{
    public function index(Request $request): never
    {
        $isDev = (string) config('app.env') === 'dev';

        $this->render('home/index', [
            'title'       => 'Wyjazdownik.pl - ogarnij wakacje ze znajomymi raz na zawsze',
            'description' => 'Polskie narzedzie do uzgadniania wspolnych wakacji w ekipie. Kazdy znajomy wypelnia ankiete, a wy razem ogladacie wspolny plan z rekomendacjami i rankingami.',
            'isDev'       => $isDev,
            'devData'     => $isDev ? $this->buildDevData() : null,
        ]);
    }

    public function health(Request $request): never
    {
        $this->json([
            'status' => 'ok',
            'env'    => (string) config('app.env'),
            'php'    => PHP_VERSION,
            'time'   => date('c'),
        ]);
    }

    private function buildDevData(): array
    {
        return [
            'appEnv'   => (string) config('app.env'),
            'appUrl'   => (string) config('app.url'),
            'phpVer'   => PHP_VERSION,
            'dbStatus' => $this->probeDatabase(),
        ];
    }

    private function probeDatabase(): array
    {
        try {
            $pdo = Connection::get();
            $tables = [
                'admins'                => 'Administratorow',
                'trips'                 => 'Wyjazdow',
                'participants'          => 'Uczestnikow',
                'participant_responses' => 'Odpowiedzi w ankiecie',
                'participant_map_pins'  => 'Pinezek na mapie',
            ];
            $counts = [];
            foreach ($tables as $table => $label) {
                $sql = 'SELECT COUNT(*) AS c FROM `' . $table . '`';
                $row = $pdo->query($sql)->fetch();
                $counts[$label] = (int) ($row['c'] ?? 0);
            }
            return ['ok' => true, 'message' => 'OK', 'counts' => $counts];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
