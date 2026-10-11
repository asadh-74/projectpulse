<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Third-party API integration: public holidays from Nager.Date (free, no key).
 * Responses are cached for 24h; failures degrade gracefully to "no holidays".
 */
class HolidayService
{
    public function __construct(private readonly string $country = 'PK')
    {
    }

    /** @return array<string, string> date (Y-m-d) => holiday name */
    public function forYear(int $year): array
    {
        $key = "holidays.{$this->country}.{$year}";

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)->retry(2, 200, throw: false)
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$this->country}");

            if (! $response->successful()) {
                return [];
            }

            $holidays = collect($response->json())
                ->mapWithKeys(fn (array $h) => [$h['date'] => $h['localName'] ?? $h['name']])
                ->all();

            Cache::put($key, $holidays, now()->addDay());

            return $holidays;
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public function holidayOn(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = Carbon::parse($date);

        return $this->forYear($date->year)[$date->toDateString()] ?? null;
    }

    /** @return Collection<int, array{date: string, name: string}> */
    public function upcoming(int $limit = 5): Collection
    {
        $today = today();

        return collect($this->forYear($today->year) + $this->forYear($today->year + 1))
            ->filter(fn ($name, $date) => $date >= $today->toDateString())
            ->sortKeys()
            ->take($limit)
            ->map(fn ($name, $date) => ['date' => $date, 'name' => $name])
            ->values();
    }
}
