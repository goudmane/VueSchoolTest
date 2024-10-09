<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Utils\TimeZoneConfig;
use Faker\Factory as Faker;

class UpdateUserDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users with random names and timezones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $faker = Faker::create();
        $timezones = TimeZoneConfig::$timezones;

        $users = User::all();

        // Loop through each user and update their details
        foreach ($users as $user) {
            $user->name = $faker->firstName . ' ' . $faker->lastName;
            $user->timezone = $faker->randomElement($timezones);
            $user->save();

            $this->info('Updated User ID: ' . $user->id . ' - ' . $user->name . ' - ' . $user->timezone);
        }
    }
}
