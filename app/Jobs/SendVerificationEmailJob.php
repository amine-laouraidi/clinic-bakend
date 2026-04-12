<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendVerificationEmailJob implements ShouldQueue
{
    use Queueable;
    public int $tries   = 3;
    public int $timeout = 30;
    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (
            $this->user instanceof MustVerifyEmail &&
            ! $this->user->hasVerifiedEmail()
        ) {
            $this->user->sendEmailVerificationNotification();
        }
    }
    public function failed(\Throwable $e): void
    {
        Log::error('Verification email failed', [
            'user_id' => $this->user->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
