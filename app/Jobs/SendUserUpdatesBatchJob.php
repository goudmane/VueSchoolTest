<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendUserUpdatesBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $updatedUsers;
    private $logger;

    /**
     * Create a new job instance.
     *
     * @param array $updatedUsers
     * @return void
     */
    public function __construct(array $updatedUsers)
    {
        $this->updatedUsers = $updatedUsers;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $payload = [
            'batches' => [
                'subscribers' => $this->updatedUsers
            ]
        ];

        Log::channel('jobs')->info(json_encode($payload));
    }
}

