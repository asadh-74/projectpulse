<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Third-party API integration: public holidays from Nager.Date (free, no key).
 *
 * Nager.Date does not cover every country (Pakistan is not reliably supported), so for "PK"
 * the fixed national holidays are merged in locally. Moon-sighting holidays (Eid, Ashura)
 * change every year and are only shown when the API provides them.
 *
 * Responses are cached; failures are cached briefly so a dead API never slows every page load.
 */
class HolidayService
{
    public function __construct(private readonly string $country = 'PK')
    {
    }

    /** @return array<string, string> date (Y-m-d) => holiday name, from the API only */
    public function forYear(int $year): array
    {
        $key = "holidays.{$this->country}.{$year}";

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$this->country}");

            if (! $response->successful()) {
                Cache::put($key, [], now()->addHour());

                return [];
            }

            $holidays = collect($response->json())
                ->mapWithKeys(fn (array $h) => [$h['date'] => $h['localName'] ?? $h['name']])
                ->all();

            Cache::put($key, $holidays, now()->addDay());

            return $holidays;
        } catch (\Throwable $e) {
            report($e);
            Cache::put($key, [], now()->addMinutes(5));

            return [];
        }
    }

    /** Fixed-date national holidays that never move. @return array<string, string> */
    public function fixedFor(int $year): array
    {
        if ($this->country !== 'PK') {
            return [];
        }

        return [
            "{$year}-02-05" => 'Kashmir Solidarity Day',
            "{$year}-03-23" => 'Pakistan Day',
            "{$year}-05-01" => 'Labour Day',
            "{$year}-08-14" => 'Independence Day',
            "{$year}-11-09" => 'Iqbal Day',
            "{$year}-12-25" => 'Quaid-e-Azam Day',
        ];
    }

    /** API data wins; fixed holidays fill the gaps. @return array<string, string> */
    public function forYearMerged(int $year): array
    {
        return $this->forYear($year) + $this->fixedFor($year);
    }

    public function holidayOn(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = Carbon::parse($date);

        return $this->forYearMerged($date->year)[$date->toDateString()] ?? null;
    }

    /** @return Collection<int, array{date: string, name: string}> */
    public function upcoming(int $limit = 5): Collection
    {
        $today = today();

        return collect($this->forYearMerged($today->year) + $this->forYearMerged($today->year + 1))
            ->filter(fn ($name, $date) => $date >= $today->toDateString())
            ->sortKeys()
            ->take($limit)
            ->map(fn ($name, $date) => ['date' => $date, 'name' => $name])
            ->values();
    }
}
