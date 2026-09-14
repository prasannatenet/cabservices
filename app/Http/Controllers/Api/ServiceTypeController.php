<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceTypeResource;
use App\Models\ServiceType;

class ServiceTypeController extends Controller
{
    public function index()
    {
        return ServiceTypeResource::collection(ServiceType::where('status', 'Active')->get());
    }
}
