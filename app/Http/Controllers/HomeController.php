<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\ServiceType;
use App\Models\VehicleCategory;

class HomeController extends Controller
{
    public function index()
    {
        $cities = City::where('status', 'Active')->get();
        $serviceTypes = ServiceType::where('status', 'Active')->get();
        $vehicleCategories = VehicleCategory::where('status', 'Active')->get();

        return view('home', compact('cities', 'serviceTypes', 'vehicleCategories'));
    }
}
