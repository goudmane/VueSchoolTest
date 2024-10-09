<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendUserUpdatesBatchJob;
use Illuminate\Support\Facades\Queue;

class UserObserver
{
    protected $cacheKey = 'user_changes_batch';
    protected $lastBatchTimeKey = 'last_batch_time';
    protected $batchSizeThreshold = 1000;

    protected $cachedChanges = [];
    protected $currentChanges = [];
    protected $jobCount;
    private $logger;

    public function __construct()
    {
        $this->jobCount = Queue::size();
        $this->cachedChanges = Cache::get($this->cacheKey, []);

        $this->logger = Log::channel('observer');
    }

    /**
     * Handle the "updated" event for the User model.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
        $this->addChange($user);

        $this->cacheChanges();

        $this->sendBatchIfNeeded();
    }

    /**
     * Capture changes made to the User model.
     *
     * @param User $user
     * @return void
     */
    protected function addChange(User $user)
    {
        $change = [
            'email' => $user->email,
        ];

        if ($user->isDirty('name')) {
            $change['name'] = $user->name;
        }

        if ($user->isDirty('timezone')) {
            $change['time_zone'] = $user->timezone;
        }

        $this->currentChanges[] = $change;
    }

    /**
     * Store the changes in the cache.
     *
     * @return void
     */
    protected function cacheChanges()
    {
        $mergedChanges = array_merge($this->currentChanges, $this->cachedChanges);
        Cache::put($this->cacheKey, $mergedChanges, NULL);
        $this->cachedChanges = $mergedChanges;
    }

    /**
     * Check if the batch needs to be sent and dispatch if necessary.
     *
     * @return void
     */
    protected function sendBatchIfNeeded()
    {
        if ($this->jobCount === 0 || count($this->cachedChanges) >= $this->batchSizeThreshold) {
            $this->checkAndDispatch();
        }
    }

    /**
     * Check the timing conditions and dispatch the job if conditions are met.
     *
     * @return void
     */
    protected function checkAndDispatch()
    {
        $currentTime = now();
        $delay = null;
        $lastBatchTime = Cache::get($this->lastBatchTimeKey, $currentTime);

        if ( now() < $lastBatchTime->addSeconds(80)) {
            return;
        }

        if ($this->jobCount === 0) {
            $delay = $lastBatchTime->addSeconds(8);
            $this->logger->info('Batch dispatched immediately.');
        } else {
            $delay = $lastBatchTime->addSeconds(80);
            $this->logger->info('Batch delayed by 80 seconds due to active jobs.');
        }

        SendUserUpdatesBatchJob::dispatch($this->cachedChanges)->delay($delay);
        $this->logger->info('User List updates dispatched to Job.');
        Cache::put($this->lastBatchTimeKey, $currentTime);
        Cache::forget($this->cacheKey);
    }
}
