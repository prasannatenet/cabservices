<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $indianFirstNames = [
            'Rahul', 'Amit', 'Vikram', 'Sanjay', 'Rajesh', 'Sandeep', 'Praveen', 'Anil',
            'Sunil', 'Alok', 'Nikhil', 'Rohit', 'Deepak', 'Manish', 'Pradeep', 'Kumar',
            'Akhil', 'Amitabh', 'Arjun', 'Aryan', 'Danish', 'Gaurav', 'Hemant', 'Jatin',
            'Karan', 'Mahesh', 'Naveen', 'Om', 'Prashant', 'Ravi', 'Sahil', 'Tarun',
            'Varun', 'Vinod', 'Yogesh', 'Birender', 'Harpreet', 'Gurpreet', 'Harish',
            'Krishan', 'Lal', 'Mohan', 'Naresh', ' Prakash', 'Suresh', 'Bikram', 'Anand',
            'Mohammad', 'Abdul', 'Mustafa', 'Rashid', 'Karim', 'Salim', 'Javed', 'Fahim',
            'Afsar', 'Nawab', 'Reyes', 'Jesuraja', 'Pandian', 'Kabilan', 'Thamizh', 'Surya',
            'Karthik', 'Ganesh', 'Murugan', 'Subramaniam', 'Venkatesh', 'Ramesh', 'Siva',
            'Elango', 'Prakash', 'Mariappan', 'Govind', 'Durai', 'Pandiyan', 'Perumal',
            'Devraj', 'Nadar', 'Pillai', 'Menon', 'Nair', 'Pandiarajan', 'Senthil',
            'Balaji', 'Mukesh', 'Shankar', 'Devi', 'Lakshmi', 'Parvati', 'Kamala',
            'Saravanan', 'Bala', 'Daniel', 'Raj', 'Peter', 'Albert', 'Simon', 'Lucian',
        ];

        $indianLastNames = [
            'Sharma', 'Verma', 'Gupta', 'Singh', 'Kumar', 'Rao', 'Pandey', 'Joshi',
            'Chatterjee', 'Banerjee', 'Mukherjee', 'Das', 'Roy', 'Sarkar', 'Bhattacharya',
            'Mehra', 'Malhotra', 'Kapoor', 'Bansal', 'Agnihotri', 'Asthana', 'Bagchi',
            'Baid', 'Chakraborty', 'Chawla', 'Chopra', 'Dash', 'Dhingra', 'Fotedar',
            'Ganeriwal', 'Gaur', 'Goyal', 'Gugale', 'Handa', 'Harinarayana', 'Iyer',
            'Jain', 'Jha', 'Kalra', 'Kamboj', 'Kapoor', 'Kar', 'Kaul', 'Khemka',
            'Khopade', 'Kothari', 'Kulkarni', 'Kundra', 'Lal', 'Lamba', 'Malhotra',
            'Mehta', 'Miglani', 'Mishra', 'Mookerji', 'Nag', 'Nair', 'Nayar', 'Nene',
            'Pai', 'Pandey', 'Pasricha', 'Patel', 'Pathak', 'Pawar', 'Pillai', 'Podder',
            'Pundir', 'Pushpawati', 'Rai', 'Rana', 'Raste', 'Reddy', 'Sah', 'Sahay',
            'Sakhuja', 'Samanta', 'Saraf', 'Sekhon', 'Sharma', 'Shekhar', 'Shukla',
            'Singh', 'Srivastava', 'Subramaniam', 'Suri', 'Thakur', 'Trivedi', 'Tyagi',
            'Vaidya', 'Valecha', 'Varma', 'Vashist', 'Verma', 'Vijay', 'Yadav', 'Yadav',
        ];

        $firstName = $indianFirstNames[array_rand($indianFirstNames)];
        $lastName = $indianLastNames[array_rand($indianLastNames)];
        $name = $firstName.' '.$lastName;

        return [
            'name' => $name,
            'phone' => fake()->phoneNumber('+91##########'),
            'whatsapp' => fake()->phoneNumber('+91##########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'license_number' => strtoupper(fake()->bothify('DL-########')),
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years')->format('Y-m-d'),
            'current_city_id' => City::factory(),
            'status' => 'Available',
        ];
    }
}
