<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifique seu Email - Running Tickets',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email',
            with: [
                'verificationUrl' => $this->getVerificationUrl(),
            ]
        );
    }

    /**
     * Get the email verification URL
     */
    private function getVerificationUrl(): string
    {
        $frontendUrl = config('app.client_url', 'http://localhost:5173');
        
        // Criar signed URL com 60 minutos de validade
        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );
        
        // Extrair apenas os parâmetros da URL assinada
        $parsedUrl = parse_url($signedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        
        // Construir URL do frontend com os parâmetros
        return $frontendUrl . '/email/verificar/' . $this->user->id . '/' . sha1($this->user->email) . '?' . http_build_query([
            'expires' => $queryParams['expires'] ?? '',
            'signature' => $queryParams['signature'] ?? '',
        ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
