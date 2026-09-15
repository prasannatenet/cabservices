<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CityController extends Controller
{
    /**
     * List the cities this associate manages. Read-only: only the admin
     * creates, edits and deletes cities.
     */
    public function index(): View
    {
        $cities = auth()->user()->assignedCities()
            ->orderBy('name')
            ->get();

        return view('associate.cities.index', compact('cities'));
    }
}
