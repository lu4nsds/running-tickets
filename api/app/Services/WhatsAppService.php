<?php

namespace App\Services;

use RuntimeException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService extends AbstractIntegrationService
{
    private bool $enabled;
    private string $tenantUuid;

    public function __construct()
    {
        $this->baseUrl    = rtrim((string) config('whatsapp.gateway.base_url'));
        $this->enabled    = (bool) config('whatsapp.gateway.enabled');
        $this->timeout    = (int) config('whatsapp.gateway.timeout_seconds');
        $this->tenantUuid = (string) config('whatsapp.gateway.tenant_uuid');
        $this->headers    = [
            'X-Api-Key' => (string) config('whatsapp.gateway.api_key'),
        ];
    }

    // -------------------------------------------------------------------------
    // Session management
    // -------------------------------------------------------------------------

    /**
     * @return array{status: string, qr: string|null}
     */
    public function connect(): array
    {
        $response = $this->request()->post($this->url("tenants/{$this->tenantUuid}/session/connect"));

        if ($response->failed()) {
            Log::error('[WhatsApp] connect failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('WhatsApp gateway error on connect: ' . $response->status());
        }

        return (array) $response->json();
    }

    /**
     * @return array{status: string, qr: string|null}
     */
    public function getStatus(): array
    {
        $response = $this->request()->get($this->url("tenants/{$this->tenantUuid}/session/status"));

        if ($response->failed()) {
            Log::error('[WhatsApp] getStatus failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('WhatsApp gateway error on getStatus: ' . $response->status());
        }

        return (array) $response->json();
    }

    public function disconnect(): void
    {
        $this->request()->delete($this->url("tenants/{$this->tenantUuid}/session"));
    }

    // -------------------------------------------------------------------------
    // Messaging
    // -------------------------------------------------------------------------

    public function sendDocument(string $phone, string $storagePath, string $filename, string $caption = ''): bool
    {
        try {
            if (! $this->enabled) {
                return false;
            }

            $content = Storage::get($storagePath);

            if ($content === null) {
                Log::warning('[WhatsApp] PDF not found for document send', ['path' => $storagePath]);
                return false;
            }

            $response = $this->request()->post($this->url("tenants/{$this->tenantUuid}/messages/send-document"), [
                'phone'    => $phone,
                'filename' => $filename,
                'mimetype' => 'application/pdf',
                'data'     => base64_encode($content),
                'caption'  => $caption,
            ]);

            if ($response->failed()) {
                Log::warning('[WhatsApp] Failed to send document', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('[WhatsApp] Exception sending document', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function send(string $phone, string $message): bool
    {
        try {
            if (! $this->enabled) {
                return false;
            }

            $response = $this->request()->post($this->url("tenants/{$this->tenantUuid}/messages/send"), [
                'phone'   => $phone,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::warning('[WhatsApp] Failed to send message', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('[WhatsApp] Exception sending message', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
