<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conexão OAuth de um organizador com um gateway de pagamento (hoje Mercado Pago).
 * Guarda as credenciais que permitem criar pagamentos em nome do organizador
 * (split nativo). Tokens são criptografados em repouso.
 */
class PaymentGatewayAccount extends Model
{
    use HasFactory;

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'organizer_id',
        'gateway',
        'provider_account_id',
        'access_token',
        'refresh_token',
        'public_key',
        'expires_at',
        'scopes',
        'status',
        'connected_at',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * A conexão está utilizável para criar pagamentos em nome do organizador?
     */
    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED
            && ! empty($this->access_token);
    }

    /**
     * O access token venceu (ou vence dentro da janela informada)?
     */
    public function isExpiring(int $withinSeconds = 0): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->subSeconds($withinSeconds)->isPast();
    }
}
