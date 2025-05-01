<?php
namespace App\Services;

use App\Models\User;
use Faker\Factory as Faker;

class UserSeederService
{
    /**
     * Seed the database with a specified number of users.
     *
     * @param int $count Number of users to seed.
     * @return void
     */
    public function seedUsers($count)
    {
        $faker = Faker::create();

        for ($i = 0; $i < $count; $i++) {
            User::create([
                'name' => $faker->firstName,
                'lastname' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'), // Default password
            ]);
        }

        echo "Seeded $count users successfully.\n";
    }
}