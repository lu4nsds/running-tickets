<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class AbstractIntegrationService
{
    protected string $baseUrl = '';
    protected array $headers  = [];
    protected int $timeout    = 15;

    protected function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withHeaders($this->headers)
            ->timeout($this->timeout);
    }

    protected function url(string $path): string
    {
        return "{$this->baseUrl}/{$path}";
    }
}
