<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;

class CityController extends Controller
{
    public function index()
    {
        return CityResource::collection(City::where('status', 'Active')->get());
    }

    public function nearby($id)
    {
        $city = City::findOrFail($id);

        return CityResource::collection($city->nearbyCities()->where('status', 'Active')->get());
    }
}
