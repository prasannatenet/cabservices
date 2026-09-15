<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $drivers = [
            ['name' => 'Rahul Sharma', 'phone' => '+919876543210', 'whatsapp' => '+919876543210', 'email' => 'rahul.sharma@example.com', 'license_number' => 'DL-12345678', 'license_expiry' => '2028-12-31', 'current_city_id' => 1, 'status' => 'Available'],
            ['name' => 'Amit Verma', 'phone' => '+919876543211', 'whatsapp' => '+919876543211', 'email' => 'amit.verma@example.com', 'license_number' => 'DL-12345679', 'license_expiry' => '2028-11-30', 'current_city_id' => 1, 'status' => 'Available'],
            ['name' => 'Vikram Singh', 'phone' => '+919876543212', 'whatsapp' => '+919876543212', 'email' => 'vikram.singh@example.com', 'license_number' => 'DL-12345680', 'license_expiry' => '2029-01-15', 'current_city_id' => 1, 'status' => 'Available'],
            ['name' => 'Sanjay Patel', 'phone' => '+919876543213', 'whatsapp' => '+919876543213', 'email' => 'sanjay.patel@example.com', 'license_number' => 'DL-12345681', 'license_expiry' => '2027-06-20', 'current_city_id' => 1, 'status' => 'On Trip'],
            ['name' => 'Rajesh Kumar', 'phone' => '+919876543214', 'whatsapp' => '+919876543214', 'email' => 'rajesh.kumar@example.com', 'license_number' => 'DL-12345682', 'license_expiry' => '2028-03-10', 'current_city_id' => 1, 'status' => 'Available'],
            ['name' => 'Sandeep Gupta', 'phone' => '+919876543215', 'whatsapp' => '+919876543215', 'email' => 'sandeep.gupta@example.com', 'license_number' => 'DL-12345683', 'license_expiry' => '2029-08-25', 'current_city_id' => 1, 'status' => 'Unavailable'],
            ['name' => 'Praveen Mishra', 'phone' => '+919876543216', 'whatsapp' => '+919876543216', 'email' => 'praveen.mishra@example.com', 'license_number' => 'DL-12345684', 'license_expiry' => '2028-05-15', 'current_city_id' => 1, 'status' => 'Available'],
            ['name' => 'Anil Sharma', 'phone' => '+919876543217', 'whatsapp' => '+919876543217', 'email' => 'anil.sharma@example.com', 'license_number' => 'DL-12345685', 'license_expiry' => '2027-12-01', 'current_city_id' => 1, 'status' => 'On Trip'],
            ['name' => 'Sunil Yadav', 'phone' => '+919876543218', 'whatsapp' => '+919876543218', 'email' => 'sunil.yadav@example.com', 'license_number' => 'DL-12345686', 'license_expiry' => '2029-04-18', 'current_city_id' => 1, 'status' => 'Available'],
            ['name' => 'Gaurav Kapoor', 'phone' => '+919876543219', 'whatsapp' => '+919876543219', 'email' => 'gaurav.kapoor@example.com', 'license_number' => 'DL-12345687', 'license_expiry' => '2028-09-22', 'current_city_id' => 1, 'status' => 'Available'],
        ];

        foreach ($drivers as $driver) {
            Driver::create($driver);
        }
    }
}
