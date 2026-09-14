<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\VehicleCategory;
use App\Models\Vehicle;
use Illuminate\Support\Str;

echo "Wiping database...\n";
Schema::disableForeignKeyConstraints();

$tables = [
    'bookings',
    'drivers',
    'vehicles',
    'vehicle_categories',
    'service_types',
    'cities',
    'city_city'
];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        DB::table($table)->truncate();
    }
}

$allTables = DB::select('SHOW TABLES');
foreach ($allTables as $t) {
    $tableName = (array) $t;
    $tableName = array_values($tableName)[0];
    
    if (in_array($tableName, ['users', 'migrations', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens', 'sessions'])) {
        continue;
    }
    
    if (!in_array($tableName, $tables)) {
        DB::table($tableName)->truncate();
    }
}
Schema::enableForeignKeyConstraints();
echo "App data wiped.\n";

$rajasthanCities = ['Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Ajmer'];
$categories = [
    ['name' => 'Hatchback', 'description' => 'Compact cars for city rides', 'base_rate' => 10],
    ['name' => 'Sedan', 'description' => 'Comfortable rides for small families', 'base_rate' => 15],
    ['name' => 'SUV', 'description' => 'Spacious cars for outstation trips', 'base_rate' => 25],
    ['name' => 'Luxury', 'description' => 'Premium cars for special occasions', 'base_rate' => 50],
    ['name' => 'MUV', 'description' => 'Multi-utility vehicles for large groups', 'base_rate' => 30],
];
$services = ['Airport Transfer', 'Local', 'Outstation', 'Round Trip', 'Corporate'];

echo "Creating Cities...\n";
$createdCities = [];
foreach ($rajasthanCities as $cityName) {
    $createdCities[] = City::factory()->create(['name' => $cityName, 'state' => 'Rajasthan', 'status' => 'Active']);
}

echo "Linking Nearby Cities...\n";
foreach ($createdCities as $city) {
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
for ($i = 0; $i < 5; $i++) {
    Driver::factory()->create(['status' => 'AVAILABLE', 'current_city_id' => $createdCities[$i]->id]);
    Vehicle::factory()->create(['vehicle_category_id' => $createdCategories[$i]->id, 'status' => 'AVAILABLE', 'city_id' => $createdCities[$i]->id]);
}

echo "Done! 5 cities, 5 categories, 5 services, 5 drivers, and 5 fleets seeded successfully.\n";
