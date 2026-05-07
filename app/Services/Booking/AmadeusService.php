<?php

namespace App\Services\Booking;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AmadeusService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $useDummyData;

    public function __construct()
    {
        $this->baseUrl   = config('services.amadeus.base_url');
        $this->apiKey    = config('services.amadeus.key');
        $this->apiSecret = config('services.amadeus.secret');
        $this->useDummyData = (bool) env('SERVICES_AMADEUS_USE_DUMMY', false);
    }

    private function getAccessToken()
    {
        return Cache::remember('amadeus_token', 1700, function () {
            $res = Http::asForm()->post($this->baseUrl.'/v1/security/oauth2/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->apiKey,
                'client_secret' => $this->apiSecret,
            ]);

            if ($res->failed()) {
                throw new \Exception("Gagal ambil token Amadeus: ".$res->body());
            }

            return $res->json()['access_token'];
        });
    }

    public function flightSearch($origin, $destination, $departureDate, $adults = 1)
    {
        if ($this->shouldUseDummyFlights()) {
            return $this->dummyFlightSearch($origin, $destination, $departureDate, $adults);
        }

        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get($this->baseUrl.'/v2/shopping/flight-offers', [
                    'originLocationCode'      => $origin,
                    'destinationLocationCode' => $destination,
                    'departureDate'           => $departureDate,
                    'adults'                  => $adults,
                    'currencyCode'            => 'IDR',
                    'max'                     => 5,
                ]);

            if ($response->failed()) {
                return $this->dummyFlightSearch($origin, $destination, $departureDate, $adults, [
                    'reason' => 'amadeus_request_failed',
                    'message' => $response->body(),
                ]);
            }

            return $response->json();
        } catch (\Throwable $th) {
            return $this->dummyFlightSearch($origin, $destination, $departureDate, $adults, [
                'reason' => 'amadeus_exception',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function hotelListByGeo(float $latitude, float $longitude, int $radius = 5)
    {
        if ($this->shouldUseDummyFlights()) {
            return $this->dummyHotelListByGeo($latitude, $longitude, $radius);
        }

        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get($this->baseUrl.'/v1/reference-data/locations/hotels/by-geocode', [
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                    'radius'    => $radius,
                ]);

            if ($response->failed()) {
                return $this->dummyHotelListByGeo($latitude, $longitude, $radius);
            }

            return $response->json();
        } catch (\Throwable $th) {
            return $this->dummyHotelListByGeo($latitude, $longitude, $radius);
        }
    }

    public function hotelOffersByIds(array $hotelIds, int $adults = 1, ?string $checkIn = null, ?string $checkOut = null)
    {
        if ($this->shouldUseDummyFlights()) {
            return $this->dummyHotelOffersByIds($hotelIds, $adults, $checkIn, $checkOut);
        }

        try {
            $token = $this->getAccessToken();

            $params = [
                'hotelIds' => implode(',', $hotelIds),
                'adults'   => $adults,
            ];

            if ($checkIn) {
                $params['checkInDate'] = $checkIn;
            }
            if ($checkOut) {
                $params['checkOutDate'] = $checkOut;
            }

            $response = Http::withToken($token)
                ->get($this->baseUrl.'/v3/shopping/hotel-offers', $params);

            if ($response->failed()) {
                return $this->dummyHotelOffersByIds($hotelIds, $adults, $checkIn, $checkOut);
            }

            return $response->json();
        } catch (\Throwable $th) {
            return $this->dummyHotelOffersByIds($hotelIds, $adults, $checkIn, $checkOut);
        }
    }




    private function shouldUseDummyFlights(): bool
    {
        return $this->useDummyData
            || blank($this->baseUrl)
            || blank($this->apiKey)
            || blank($this->apiSecret);
    }

    private function dummyFlightSearch($origin, $destination, $departureDate, $adults = 1, array $fallbackMeta = []): array
    {
        $date = Carbon::parse($departureDate);
        $origin = strtoupper($origin);
        $destination = strtoupper($destination);

        $carrierMap = [
            'GA' => 'Garuda Indonesia',
            'ID' => 'Batik Air',
            'JT' => 'Lion Air',
            'QG' => 'Citilink',
            'IU' => 'Super Air Jet',
        ];

        $templates = [
            ['carrier' => 'GA', 'departure_hour' => 7, 'duration_minutes' => 105, 'base_price' => 1450000, 'stops' => []],
            ['carrier' => 'ID', 'departure_hour' => 10, 'duration_minutes' => 120, 'base_price' => 1185000, 'stops' => []],
            ['carrier' => 'QG', 'departure_hour' => 13, 'duration_minutes' => 135, 'base_price' => 980000, 'stops' => ['SUB']],
            ['carrier' => 'JT', 'departure_hour' => 16, 'duration_minutes' => 110, 'base_price' => 1060000, 'stops' => []],
            ['carrier' => 'IU', 'departure_hour' => 19, 'duration_minutes' => 140, 'base_price' => 925000, 'stops' => ['YIA']],
        ];

        $offers = collect($templates)->map(function (array $template, int $index) use ($date, $origin, $destination, $adults) {
            $departure = $date->copy()->setTime($template['departure_hour'], $index * 5);
            $segments = [];
            $currentOrigin = $origin;
            $arrivalTime = $departure->copy();
            $segmentStops = $template['stops'];

            foreach ($segmentStops as $segmentIndex => $stopCode) {
                $segmentArrival = $arrivalTime->copy()->addMinutes(60 + ($segmentIndex * 10));
                $segments[] = $this->makeSegment(
                    $currentOrigin,
                    $stopCode,
                    $departure,
                    $segmentArrival,
                    $template['carrier'],
                    $index,
                    $segmentIndex
                );

                $currentOrigin = $stopCode;
                $arrivalTime = $segmentArrival->copy()->addMinutes(35);
                $departure = $arrivalTime->copy();
            }

            $finalArrival = $arrivalTime->copy()->addMinutes(
                max(45, $template['duration_minutes'] - (count($segmentStops) * 60))
            );

            $segments[] = $this->makeSegment(
                $currentOrigin,
                $destination,
                $departure,
                $finalArrival,
                $template['carrier'],
                $index,
                count($segmentStops)
            );

            $grandTotal = $template['base_price'] + (($adults - 1) * 275000);

            return [
                'type' => 'flight-offer',
                'id' => 'DUMMY-' . $origin . $destination . '-' . ($index + 1),
                'source' => 'DUMMY',
                'instantTicketingRequired' => false,
                'nonHomogeneous' => false,
                'oneWay' => true,
                'lastTicketingDate' => $date->toDateString(),
                'numberOfBookableSeats' => max(1, 9 - $index),
                'itineraries' => [[
                    'duration' => $this->toIsoDuration(
                        Carbon::parse($segments[0]['departure']['at']),
                        Carbon::parse(last($segments)['arrival']['at'])
                    ),
                    'segments' => $segments,
                ]],
                'price' => [
                    'currency' => 'IDR',
                    'grandTotal' => (string) $grandTotal,
                    'base' => (string) max(0, $grandTotal - 125000),
                ],
                'travelerPricings' => [],
            ];
        })->all();

        return [
            'meta' => [
                'dummy' => true,
                'origin' => $origin,
                'destination' => $destination,
                'departureDate' => $date->toDateString(),
                'adults' => (int) $adults,
                'fallback' => $fallbackMeta,
            ],
            'data' => $offers,
            'dictionaries' => [
                'carriers' => $carrierMap,
            ],
        ];
    }

    private function makeSegment(
        string $origin,
        string $destination,
        Carbon $departure,
        Carbon $arrival,
        string $carrierCode,
        int $offerIndex,
        int $segmentIndex
    ): array {
        return [
            'departure' => [
                'iataCode' => $origin,
                'at' => $departure->toIso8601String(),
            ],
            'arrival' => [
                'iataCode' => $destination,
                'at' => $arrival->toIso8601String(),
            ],
            'carrierCode' => $carrierCode,
            'number' => (string) (200 + ($offerIndex * 7) + $segmentIndex + 1),
            'aircraft' => [
                'code' => $segmentIndex % 2 === 0 ? '320' : '738',
            ],
            'duration' => $this->toIsoDuration($departure, $arrival),
            'id' => (string) ($offerIndex . $segmentIndex + 1),
            'numberOfStops' => 0,
        ];
    }

    private function toIsoDuration(Carbon $start, Carbon $end): string
    {
        $minutes = abs($start->diffInMinutes($end));
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return 'PT' . ($hours > 0 ? $hours . 'H' : '') . ($remainingMinutes > 0 ? $remainingMinutes . 'M' : '0M');
    }

    private function dummyHotelListByGeo(float $latitude, float $longitude, int $radius = 5): array
    {
        $hotels = [
            ['hotelId' => 'DUMMY-HOTEL-1', 'name' => 'Hotel Nusantara Prime', 'cityCode' => 'JKT'],
            ['hotelId' => 'DUMMY-HOTEL-2', 'name' => 'Garuda Business Hotel', 'cityCode' => 'JKT'],
            ['hotelId' => 'DUMMY-HOTEL-3', 'name' => 'Sakura Transit Stay', 'cityCode' => 'BDO'],
            ['hotelId' => 'DUMMY-HOTEL-4', 'name' => 'Samudra Executive Inn', 'cityCode' => 'SUB'],
            ['hotelId' => 'DUMMY-HOTEL-5', 'name' => 'Bali Sunset Residence', 'cityCode' => 'DPS'],
        ];

        return [
            'meta' => [
                'dummy' => true,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius,
            ],
            'data' => collect($hotels)->map(function (array $hotel, int $index) use ($latitude, $longitude) {
                return [
                    'hotelId' => $hotel['hotelId'],
                    'name' => $hotel['name'],
                    'cityCode' => $hotel['cityCode'],
                    'latitude' => round($latitude + ($index * 0.01), 6),
                    'longitude' => round($longitude + ($index * 0.01), 6),
                ];
            })->all(),
        ];
    }

    private function dummyHotelOffersByIds(array $hotelIds, int $adults = 1, ?string $checkIn = null, ?string $checkOut = null): array
    {
        $checkInDate = $checkIn ? Carbon::parse($checkIn) : now()->addDay();
        $checkOutDate = $checkOut ? Carbon::parse($checkOut) : $checkInDate->copy()->addDays(1);
        $nights = max(1, $checkInDate->diffInDays($checkOutDate));

        $hotelMap = collect($this->dummyHotelListByGeo(-6.2, 106.8, 5)['data'])->keyBy('hotelId');

        return [
            'meta' => [
                'dummy' => true,
                'adults' => $adults,
                'checkInDate' => $checkInDate->toDateString(),
                'checkOutDate' => $checkOutDate->toDateString(),
                'nights' => $nights,
            ],
            'data' => collect($hotelIds)->values()->map(function (string $hotelId, int $index) use ($hotelMap, $adults, $nights, $checkInDate, $checkOutDate) {
                $hotel = $hotelMap->get($hotelId, [
                    'hotelId' => $hotelId,
                    'name' => 'Dummy Hotel ' . ($index + 1),
                    'cityCode' => 'IDN',
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                ]);

                $nightlyRate = 550000 + ($index * 125000) + max(0, ($adults - 1) * 75000);
                $total = $nightlyRate * $nights;

                return [
                    'type' => 'hotel-offers',
                    'hotel' => $hotel,
                    'available' => true,
                    'offers' => [[
                        'id' => 'DUMMY-OFFER-' . ($index + 1),
                        'checkInDate' => $checkInDate->toDateString(),
                        'checkOutDate' => $checkOutDate->toDateString(),
                        'price' => [
                            'currency' => 'IDR',
                            'base' => (string) $nightlyRate,
                            'total' => (string) $total,
                        ],
                        'room' => [
                            'typeEstimated' => [
                                'category' => $index % 2 === 0 ? 'STANDARD_ROOM' : 'DELUXE_ROOM',
                            ],
                        ],
                    ]],
                ];
            })->all(),
        ];
    }
}
