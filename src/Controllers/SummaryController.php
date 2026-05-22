<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Database\Connection;
use App\Services\SummaryAggregator;

/**
 * Strona podsumowania publicznego (TV-friendly).
 * Dostep przez summary_public_token bez logowania.
 */
final class SummaryController extends Controller
{
    public function show(Request $request, array $args): never
    {
        $token = (string) $args['token'];
        $stmt = Connection::get()->prepare('SELECT * FROM trips WHERE summary_public_token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->notFound('Niewlasciwy link do podsumowania.');
        }

        // Zbuduj Trip object inline (bez Trip::find* zeby nie dorzucac findByToken)
        $trip = $this->buildTrip($row);
        $agg  = new SummaryAggregator($trip);

        $this->render('summary/index', [
            'title'       => $trip->name . ' - podsumowanie',
            'description' => 'Wspolny plan ekipy: ' . $trip->name,
            'trip'        => $trip,
            'agg'         => $agg,
        ], 'summary');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function buildTrip(array $row): \App\Models\Trip
    {
        return new \App\Models\Trip(
            id:                       (int) $row['id'],
            adminId:                  (int) $row['admin_id'],
            name:                     (string) $row['name'],
            slug:                     (string) $row['slug'],
            description:              $row['description'] !== null ? (string) $row['description'] : null,
            bannerImage:              $row['banner_image'] !== null ? (string) $row['banner_image'] : null,
            dateFrom:                 (string) $row['date_from'],
            dateTo:                   (string) $row['date_to'],
            calendarMode:             (string) $row['calendar_mode'],
            showIndividualResponses:  (bool) $row['show_individual_responses'],
            isActive:                 (bool) $row['is_active'],
            summaryPublicToken:       (string) $row['summary_public_token'],
            createdAt:                (string) $row['created_at'],
            updatedAt:                (string) $row['updated_at'],
            startName:                isset($row['start_name']) && $row['start_name'] !== null ? (string) $row['start_name'] : null,
            startLat:                 isset($row['start_lat']) && $row['start_lat'] !== null ? (float) $row['start_lat'] : null,
            startLng:                 isset($row['start_lng']) && $row['start_lng'] !== null ? (float) $row['start_lng'] : null,
        );
    }
}
