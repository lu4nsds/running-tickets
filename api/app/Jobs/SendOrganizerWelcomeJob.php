<?php

namespace App\Jobs;

use App\Mail\OrganizerWelcomeMail;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrganizerWelcomeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public User $user,
        public Organizer $organizer,
        public string $token,
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new OrganizerWelcomeMail($this->user, $this->organizer, $this->token));
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendOrganizerWelcomeJob failed', [
            'user_id'  => $this->user->id,
            'email'    => $this->user->email,
            'error'    => $e->getMessage(),
        ]);
    }
}
