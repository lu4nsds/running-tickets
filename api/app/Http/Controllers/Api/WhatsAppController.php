<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;

class WhatsAppController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp) {}

    public function connect(): JsonResponse
    {
        $result = $this->whatsApp->connect();

        return new JsonResponse(['data' => $result]);
    }

    public function status(): JsonResponse
    {
        $result = $this->whatsApp->getStatus();

        return new JsonResponse(['data' => $result]);
    }

    public function disconnect(): JsonResponse
    {
        $this->whatsApp->disconnect();

        return new JsonResponse(null, 204);
    }
}
