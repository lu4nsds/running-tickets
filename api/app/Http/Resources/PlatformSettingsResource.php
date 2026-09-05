<?php

namespace App\Http\Resources;

use App\Enums\PlatformSettingKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Achata o mapa chave/valor de `platform_settings` em um objeto plano,
 * expondo apenas as chaves conhecidas pelo enum.
 *
 * @property array<string, string|null> $resource
 */
class PlatformSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = (array) $this->resource;

        return [
            PlatformSettingKey::FEEDBACK_FORM_URL->value => $settings[PlatformSettingKey::FEEDBACK_FORM_URL->value] ?? null,
        ];
    }
}
