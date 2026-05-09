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

    public function sitemap(Request $request): never
    {
        $base  = rtrim((string) config('app.url'), '/');
        $today = date('Y-m-d');
        $urls  = [
            ['loc' => $base . '/',            'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $base . '/admin/login', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($u['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . $today . "</lastmod>\n";
            $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";
        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml;
        exit;
    }

    public function robots(Request $request): never
    {
        $base = rtrim((string) config('app.url'), '/');
        $txt  = "User-agent: *\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /p/\n";
        $txt .= "Disallow: /summary/\n";
        $txt .= "Disallow: /zdrowie\n";
        $txt .= "Allow: /\n";
        $txt .= "Allow: /admin/login\n\n";
        $txt .= "Sitemap: " . $base . "/sitemap.xml\n";
        header('Content-Type: text/plain; charset=UTF-8');
        echo $txt;
        exit;
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
