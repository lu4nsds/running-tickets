<?php

namespace App\Mail;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Organizer $organizer,
        public string $token
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bem-vindo ao Running Tickets — {$this->organizer->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organizers.welcome',
            with: [
                'activationUrl' => $this->getActivationUrl(),
            ]
        );
    }

    private function getActivationUrl(): string
    {
        $adminUrl = config('app.admin_url', 'http://localhost:5174');
        return $adminUrl . '/ativar-conta?token=' . $this->token . '&email=' . urlencode($this->user->email);
    }

    public function attachments(): array
    {
        return [];
    }
}
