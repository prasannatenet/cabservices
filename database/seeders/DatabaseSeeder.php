<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $cities = City::factory(5)->create();

        // Make some cities nearby
        foreach ($cities as $city) {
            $nearby = $cities->where('id', '!=', $city->id)->random(2);
            $city->nearbyCities()->attach($nearby->pluck('id')->toArray());
        }

        $services = [
            'Airport Transfer',
            'Local',
            'Outstation',
            'Round Trip',
            'Corporate',
        ];

        foreach ($services as $serviceName) {
            ServiceType::factory()->create(['name' => $serviceName]);
        }

        foreach ($cities as $city) {
            Vehicle::factory(5)->create(['city_id' => $city->id]);
            Driver::factory(5)->create(['current_city_id' => $city->id]);
        }
    }
}
