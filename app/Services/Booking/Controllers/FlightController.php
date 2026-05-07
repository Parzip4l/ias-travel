<?php

namespace App\Services\Booking\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Booking\AmadeusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FlightController extends Controller
{
    protected $amadeus;

    public function __construct(AmadeusService $amadeus)
    {
        $this->amadeus = $amadeus;
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin'      => 'required|string|size:3',  // contoh: CGK
            'destination' => 'required|string|size:3',  // contoh: DPS
            'date'        => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $flights = $this->amadeus->flightSearch(
            $request->origin,
            $request->destination,
            $request->date,
            $request->get('adults', 1)
        );

        return response()->json($flights);
    }
}
