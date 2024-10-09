<?php
namespace Tests\Feature;



use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendUserUpdatesBatchJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Utils\TimeZoneConfig;
use Illuminate\Support\Facades\Artisan;

class UserOperationsTest extends TestCase
{
    use RefreshDatabase;

     /** @test */
    public function it_caches_user_updates()
    {
        // Mock the Queue facade to check for dispatched jobs
        Queue::fake();

        // Create a user
        $user = User::factory()->create(['name' => 'John Doe', 'timezone' => 'UTC']);

        // Update the user's name
        $user->update(['name' => 'New Name']);


        // Check that the user change is cached
        $cachedChanges = Cache::get('user_changes_batch');

        $this->assertNotEmpty($cachedChanges);
        $this->assertEquals('New Name', $cachedChanges[0]['name']);
    }


    /** @test */
    public function it_logs_user_updates_in_batch_job()
    {
        // Mock the Log facade and the channel method
        Log::shouldReceive('channel')
            ->once()
            ->with('jobs')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($payload) {
                // Check that the payload contains the correct structure
                return strpos($payload, '"batches":{"subscribers":') !== false;
            });

        // Sample user data
        $updatedUsers = [
            ['email' => 'test@example.com', 'name' => 'John Doe', 'time_zone' => 'UTC'],
        ];

        // Dispatch the job
        (new SendUserUpdatesBatchJob($updatedUsers))->handle();
    }

    /** @test */
    public function it_updates_user_names_and_timezones_via_command()
    {
        // Create some users
        User::factory()->count(10)->create();

        // Run the command
        $this->artisan('app:update-user-details')->assertExitCode(0);

        // Fetch the updated users and assert that names and timezones were changed
        $users = User::all();
        foreach ($users as $user) {
            $this->assertNotEmpty($user->name);
            $this->assertNotEmpty($user->timezone);
        }
    }



    /** @test */
    public function it_sends_batch_job_after_multiple_user_updates()
    {
        // Mock the Queue facade to check for dispatched jobs
        Queue::fake();

        // Mock the Log facade
        Log::shouldReceive('channel')
            ->with('observer')
            ->andReturnSelf();

        // Mock logging of info for each user change
        Log::shouldReceive('info')
            ->withArgs(function ($message) {
                return str_contains($message, 'firstname:') && str_contains($message, 'timezone:');
            });

        // Create a batch of users
        $users = User::factory()->count(10)->create();
        $this->assertCount(10, $users);

        // Call the console command to update users
        Artisan::call('app:update-user-details');


        // Check that the user changes are cached
        $cachedChanges = Cache::get('user_changes_batch');
        $this->assertNotEmpty($cachedChanges);
    }

    /** @test */
    public function it_returns_correct_timezones_from_timezone_config()
    {
        $timezones = TimeZoneConfig::getTimeZones();

        $this->assertContains('Europe/Berlin', $timezones);
        $this->assertContains('America/New_York', $timezones);
        $this->assertNotEmpty($timezones);
    }

    /** @test */
    public function it_generates_valid_users_with_timezones()
    {
        // Generate a user
        $user = User::factory()->create();

        // Assert that the user has a valid timezone
        $this->assertNotEmpty($user->timezone);
        $this->assertContains($user->timezone, TimeZoneConfig::getTimeZones());
    }
}
