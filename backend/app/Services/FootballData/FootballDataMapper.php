<?php

namespace App\Services\FootballData;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Traduce los payloads de football-data.org a atributos de nuestros modelos.
 * Si football-data cambia su formato, solo se toca esta clase.
 */
class FootballDataMapper
{
    /** @param array<string, mixed> $c */
    public function competition(array $c): array
    {
        return [
            'id' => (int) $c['id'],
            'code' => $c['code'] ?? null,
            'name' => $c['name'],
            'type' => $c['type'] ?? null,
            'plan' => $c['plan'] ?? null,
            'emblem' => $c['emblem'] ?? null,
            'area_name' => Arr::get($c, 'area.name'),
            'area_code' => Arr::get($c, 'area.code'),
            'current_season_start' => $this->date(Arr::get($c, 'currentSeason.startDate')),
            'current_season_end' => $this->date(Arr::get($c, 'currentSeason.endDate')),
            'current_matchday' => Arr::get($c, 'currentSeason.currentMatchday'),
        ];
    }

    /** @param array<string, mixed> $t */
    public function team(array $t): array
    {
        return [
            'id' => (int) $t['id'],
            'name' => $t['name'],
            'short_name' => $t['shortName'] ?? null,
            'tla' => $t['tla'] ?? null,
            'crest' => $t['crest'] ?? null,
            'area_name' => Arr::get($t, 'area.name'),
            'address' => $t['address'] ?? null,
            'website' => $t['website'] ?? null,
            'founded' => $t['founded'] ?? null,
            'club_colors' => $t['clubColors'] ?? null,
            'venue' => $t['venue'] ?? null,
            'coach_name' => Arr::get($t, 'coach.name'),
        ];
    }

    /**
     * El plantel de /competitions/{id}/teams no siempre trae "shirtNumber":
     * se guarda si viene y queda en null si no (ver README, supuestos).
     *
     * @param  array<string, mixed>  $p
     */
    public function player(array $p, int $teamId): array
    {
        return [
            'id' => (int) $p['id'],
            'team_id' => $teamId,
            'name' => $p['name'] ?? trim(($p['firstName'] ?? '').' '.($p['lastName'] ?? '')),
            'position' => $p['position'] ?? null,
            'shirt_number' => isset($p['shirtNumber']) ? (int) $p['shirtNumber'] : null,
            'date_of_birth' => $this->date($p['dateOfBirth'] ?? null),
            'nationality' => $p['nationality'] ?? null,
        ];
    }

    /** @param array<string, mixed>|null $season */
    public function seasonLabel(?array $season): ?string
    {
        $start = $this->date($season['startDate'] ?? null);
        $end = $this->date($season['endDate'] ?? null);

        return $start && $end ? substr($start, 0, 4).'/'.substr($end, 0, 4) : null;
    }

    private function date(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }
}
