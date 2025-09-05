<?php

namespace App\Services\Booking;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AmadeusService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        $this->baseUrl   = config('services.amadeus.base_url');
        $this->apiKey    = config('services.amadeus.key');
        $this->apiSecret = config('services.amadeus.secret');
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
            throw new \Exception("Gagal cari penerbangan: ".$response->body());
        }

        return $response->json();
    }

    public function hotelListByGeo(float $latitude, float $longitude, int $radius = 5)
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get($this->baseUrl.'/v1/reference-data/locations/hotels/by-geocode', [
                'latitude'  => $latitude,
                'longitude' => $longitude,
                'radius'    => $radius,
            ]);

        if ($response->failed()) {
            throw new \Exception("Gagal ambil daftar hotel by geo: ".$response->body());
        }

        return $response->json();
    }

    public function hotelOffersByIds(array $hotelIds, int $adults = 1, ?string $checkIn = null, ?string $checkOut = null)
    {
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
            throw new \Exception("Gagal ambil offers hotel: ".$response->body());
        }

        return $response->json();
    }




}
