<?php

namespace App\Enums;

/**
 * Chaves de configuração global da plataforma (tabela `platform_settings`).
 *
 * Mantém as chaves tipadas: nada de string solta espalhada por controllers,
 * commands e jobs.
 */
enum PlatformSettingKey: string
{
    case FEEDBACK_FORM_URL = 'feedback_form_url';

    public function label(): string
    {
        return match ($this) {
            self::FEEDBACK_FORM_URL => 'Link do formulário de feedback',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
