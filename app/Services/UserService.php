<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendUserUpdatesBatchJob;
use Illuminate\Support\Facades\Queue;

class UserService
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
     * Add user change to current batch.
     *
     * @param User $user
     */
    public function addChange(User $user)
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
        // Cache changes and check if batch needs to be dispatched
        $this->cacheChanges();
        $this->sendBatchIfNeeded();
    }

    /**
     * Store the changes in the cache.
     */
    protected function cacheChanges()
    {
        $mergedChanges = array_merge($this->currentChanges, $this->cachedChanges);
        Cache::put($this->cacheKey, $mergedChanges, NULL);
        $this->cachedChanges = $mergedChanges;
    }

    /**
     * Dispatch batch job if needed.
     */
    protected function sendBatchIfNeeded()
    {
        // FIX The urgent force to dispatch Job because of count($this->cachedChanges) >= $this->batchSizeThreshold
        $isUrgent = count($this->cachedChanges) >= $this->batchSizeThreshold;
        if ($this->jobCount === 0 || $isUrgent) {
            $this->checkAndDispatch($isUrgent);
        }
    }

    /**
     * Dispatch batch based on timing conditions.
     */
    protected function checkAndDispatch(bool $isUrgent)
    {
        $currentTime = now();
        $delay = null;
        $lastBatchTime = Cache::get($this->lastBatchTimeKey);

        $nextAllowedBatchTime = $lastBatchTime ? (clone $lastBatchTime)->addSeconds(80) : $currentTime;
        // Check existance of $lastBatchTime rate limit of 80 seconds
        if ( !$isUrgent && ($lastBatchTime && $currentTime < $nextAllowedBatchTime) ) {
            return;
        }

        // logs for debbuging and track the process
        $this->logger->info("lastBatchTime",[$lastBatchTime]);
        $this->logger->info("currentTime < nextAllowedBatchTime",[$currentTime, $nextAllowedBatchTime]);
        $this->logger->info("coount in cache", [count($this->cachedChanges)]);

        // Decide dispatch time based on job queue
        if ($this->jobCount === 0) {
            $delay = $currentTime->addSeconds(8);
            $this->logger->info('Batch dispatched immediately.');
        } else {
            //FIX
            $delay = $nextAllowedBatchTime;
            $this->logger->info('Batch delayed by 80 seconds due to active jobs.');
        }

        // Dispatch the job and reset cache
        // FIX replace $currentTime by $delay
        SendUserUpdatesBatchJob::dispatch($this->cachedChanges)->delay($delay);
        $this->logger->info('User List updates dispatched to Job.');
        Cache::put($this->lastBatchTimeKey, $delay);
        Cache::forget($this->cacheKey);
    }
}
