<?php

use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\VehicleCategory;
use App\Models\Vehicle;
use Illuminate\Support\Str;

$rajasthanCities = [
    'Jaipur',
    'Jodhpur',
    'Udaipur',
    'Kota',
    'Ajmer'
];

$categories = [
    ['name' => 'Hatchback', 'description' => 'Compact cars for city rides', 'base_rate' => 10],
    ['name' => 'Sedan', 'description' => 'Comfortable rides for small families', 'base_rate' => 15],
    ['name' => 'SUV', 'description' => 'Spacious cars for outstation trips', 'base_rate' => 25],
    ['name' => 'Luxury', 'description' => 'Premium cars for special occasions', 'base_rate' => 50],
    ['name' => 'MUV', 'description' => 'Multi-utility vehicles for large groups', 'base_rate' => 30],
];

$services = [
    'Airport Transfer',
    'Local',
    'Outstation',
    'Round Trip',
    'Corporate',
];

echo "Creating Cities...\n";
$createdCities = [];
foreach ($rajasthanCities as $cityName) {
    $createdCities[] = City::factory()->create([
        'name' => $cityName, 
        'state' => 'Rajasthan',
        'status' => 'Active'
    ]);
}

echo "Linking Nearby Cities...\n";
foreach ($createdCities as $city) {
    // Attach 2 random other cities as nearby
    $nearbyIds = collect($createdCities)->where('id', '!=', $city->id)->random(2)->pluck('id')->toArray();
    $city->nearbyCities()->attach($nearbyIds);
}

echo "Creating Categories...\n";
$createdCategories = [];
foreach ($categories as $cat) {
    $createdCategories[] = VehicleCategory::create($cat);
}

echo "Creating Services...\n";
foreach ($services as $serviceName) {
    ServiceType::factory()->create(['name' => $serviceName]);
}

echo "Creating Fleets & Drivers...\n";
// Create 5 Fleets and 5 Drivers
for ($i = 0; $i < 5; $i++) {
    // Create 1 driver
    Driver::factory()->create([
        'status' => 'AVAILABLE',
        'current_city_id' => $createdCities[$i]->id,
    ]);
    
    // Create 1 vehicle
    Vehicle::factory()->create([
        'vehicle_category_id' => $createdCategories[$i]->id,
        'status' => 'AVAILABLE',
        'city_id' => $createdCities[$i]->id,
    ]);
}

echo "Done! 5 cities, 5 categories, 5 services, 5 drivers, and 5 fleets seeded successfully.\n";
