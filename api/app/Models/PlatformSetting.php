<?php

namespace App\Models;

use App\Enums\PlatformSettingKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuração global da plataforma, no formato chave/valor.
 *
 * A leitura é memoizada em cache porque estas configurações são lidas em
 * loops (ex.: command de feedback) e mudam raramente — toda escrita invalida
 * o cache.
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    private const CACHE_KEY = 'platform_settings';

    /**
     * Mapa completo chave => valor.
     *
     * @return array<string, string|null>
     */
    public static function map(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all(),
        );
    }

    /**
     * Lê uma configuração. String vazia é tratada como ausente, para que um
     * campo limpo no admin se comporte igual a um campo nunca preenchido.
     */
    public static function getValue(PlatformSettingKey $key, ?string $default = null): ?string
    {
        $value = self::map()[$key->value] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function setValue(PlatformSettingKey $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key->value],
            ['value' => $value],
        );

        Cache::forget(self::CACHE_KEY);
    }
}
