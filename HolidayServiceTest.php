<?php

namespace Tests\Unit;

use App\Services\HolidayService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HolidayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_maps_the_api_response_and_caches_it(): void
    {
        Http::fake([
            'date.nager.at/*' => Http::response([
                ['date' => '2030-08-14', 'localName' => 'Independence Day', 'name' => 'Independence Day'],
                ['date' => '2030-12-25', 'localName' => 'Quaid-e-Azam Day', 'name' => 'Christmas Day'],
            ]),
        ]);

        $service = new HolidayService('PK');

        $this->assertSame('Independence Day', $service->holidayOn('2030-08-14'));
        $this->assertNull($service->holidayOn('2030-08-15'));
        $this->assertNull($service->holidayOn(null));

        Http::assertSentCount(1); // second lookup served from cache
    }

    public function test_it_degrades_gracefully_when_the_api_is_down(): void
    {
        Http::fake(['date.nager.at/*' => Http::response('error', 500)]);

        $this->assertSame([], (new HolidayService('PK'))->forYear(2031));
        $this->assertNull((new HolidayService('PK'))->holidayOn('2031-01-01'));
    }
}
