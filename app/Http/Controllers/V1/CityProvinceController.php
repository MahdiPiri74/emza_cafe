<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityCollection;
use App\Http\Resources\ProvinceCollection;
use App\Models\City;
use App\Models\Province;
use Illuminate\Http\Request;

class CityProvinceController extends Controller
{
    public function index()
    {
        $cities = City::all();
        $provinces = Province::all();

        return response()->json([
            'status' => 'success',
            'cities' => $cities,
            'provinces' => $provinces
        ],200);
    }
}
