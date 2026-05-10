<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;
    private string $tenantId;
    private string $apiKey;
    private int $timeout;
    private bool $enabled;

    public function __construct()
    {
        $this->enabled   = (bool) config('whatsapp.enabled');
        $this->baseUrl   = rtrim(config('whatsapp.base_url'), '/');
        $this->tenantId  = config('whatsapp.tenant_id');
        $this->apiKey    = config('whatsapp.api_key', '');
        $this->timeout   = config('whatsapp.timeout', 15);
    }

    /**
     * Envia mensagem de texto para um número via WhatsApp.
     * Retorna false silenciosamente se o gateway estiver desabilitado.
     */
    public function send(string $phone, string $message): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $normalized = $this->normalizePhone($phone);

        try {
            $response = $this->http()
                ->post("{$this->baseUrl}/tenants/{$this->tenantId}/messages/send", [
                    'phone'   => $normalized,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                Log::warning('WhatsApp: falha ao enviar mensagem', [
                    'phone'  => $normalized,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp: erro ao enviar mensagem', [
                'phone'   => $normalized,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Inicia a sessão do WhatsApp e retorna QR code se necessário.
     */
    public function connect(): array
    {
        $response = $this->http()
            ->post("{$this->baseUrl}/tenants/{$this->tenantId}/session/connect");

        return $response->json() ?? ['status' => 'error', 'qr' => null];
    }

    /**
     * Retorna o status atual da sessão.
     */
    public function status(): array
    {
        try {
            $response = $this->http()
                ->get("{$this->baseUrl}/tenants/{$this->tenantId}/session/status");

            return $response->json() ?? ['status' => 'closed', 'qr' => null];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'qr' => null];
        }
    }

    /**
     * Encerra a sessão do WhatsApp.
     */
    public function disconnect(): bool
    {
        try {
            $response = $this->http()
                ->delete("{$this->baseUrl}/tenants/{$this->tenantId}/session");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp: erro ao desconectar sessão', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Normaliza o número de telefone para o formato internacional brasileiro.
     * Remove caracteres não-numéricos e adiciona o DDI 55 se necessário.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Número com 10 ou 11 dígitos: adiciona DDI Brasil
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $headers = ['Accept' => 'application/json'];

        if (!empty($this->apiKey)) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        return Http::withHeaders($headers)->timeout($this->timeout);
    }
}
