<?php 
namespace App\Services\Booking\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Booking\Model\Province;
use App\Services\Booking\Model\Regency;
use App\Services\Booking\Model\District;
use App\Services\Booking\Model\Village;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function provinces()
    {
        return Province::select('id', 'name')->orderBy('name')->get();
    }

    public function regencies($province_id)
    {
        return Regency::select('id', 'name')->where('province_id', $province_id)->orderBy('name')->get();
    }

    public function districts($regency_id)
    {
        return District::select('id', 'name')->where('regency_id', $regency_id)->orderBy('name')->get();
    }

    public function villages($district_id)
    {
        return Village::select('id', 'name')->where('district_id', $district_id)->orderBy('name')->get();
    }
}
