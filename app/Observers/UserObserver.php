<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserService;

class UserObserver
{

    public function __construct(protected UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle the "updated" event for the User model.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
        // Delegate the change tracking and batch processing to the service
        $this->userService->addChange($user);
    }
}
