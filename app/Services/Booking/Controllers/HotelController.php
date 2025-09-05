<?php

namespace App\Services\Booking\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Booking\AmadeusService;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    protected $amadeus;

    public function __construct(AmadeusService $amadeus)
    {
        $this->amadeus = $amadeus;
    }

    /**
     * Search hotel by city code
     */
    public function searchByGeo(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius'    => 'nullable|integer|min:1|max:50',
            'adults'    => 'nullable|integer|min:1',
            'checkIn'   => 'nullable|date',
            'checkOut'  => 'nullable|date|after:checkIn',
        ]);

        // Step 1: cari daftar hotel by geo
        $hotels = $this->amadeus->hotelListByGeo(
            $request->latitude,
            $request->longitude,
            $request->get('radius', 5),
        );

        if (empty($hotels['data'])) {
            return response()->json(['message' => 'Tidak ada hotel ditemukan'], 404);
        }

        // Ambil max 10 hotelIds dari hasil
        $hotelIds = collect($hotels['data'])->pluck('hotelId')->take(10)->toArray();

        // Step 2: ambil penawaran hotel berdasarkan IDs
        $offers = $this->amadeus->hotelOffersByIds(
            $hotelIds,
            $request->get('adults', 1),
            $request->get('checkIn'),
            $request->get('checkOut'),
        );

        return response()->json($offers);
    }


}
