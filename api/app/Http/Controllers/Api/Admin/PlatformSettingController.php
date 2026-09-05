<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PlatformSettingKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Http\Resources\PlatformSettingsResource;
use App\Models\PlatformSetting;

class PlatformSettingController extends Controller
{
    public function index(): PlatformSettingsResource
    {
        return new PlatformSettingsResource(PlatformSetting::map());
    }

    /**
     * Atualização parcial: só as chaves presentes no payload são gravadas.
     */
    public function update(UpdatePlatformSettingsRequest $request): PlatformSettingsResource
    {
        foreach ($request->validated() as $key => $value) {
            PlatformSetting::setValue(PlatformSettingKey::from($key), $value);
        }

        return new PlatformSettingsResource(PlatformSetting::map());
    }
}
