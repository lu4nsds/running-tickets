<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'address',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'status',
    ];

    /**
     * Usuários de backoffice que administram este organizador
     */
    public function users()
    {
        return $this->belongsToMany(
            AdminUser::class,
            'organizer_users',
            'organizer_id',
            'admin_user_id'
        )->withPivot('role')->withTimestamps();
    }

    /**
     * Eventos pertencentes a este organizador
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Pedidos/Vendas deste organizador
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Conexão OAuth com o gateway de pagamento (Mercado Pago) usada no split.
     * Um organizador tem no máximo uma conta por gateway.
     */
    public function paymentAccount()
    {
        return $this->hasOne(PaymentGatewayAccount::class)
            ->where('gateway', \App\Enums\PaymentGateway::MERCADOPAGO->value);
    }

    /**
     * O organizador tem uma conta de gateway conectada e utilizável?
     */
    public function hasConnectedPaymentAccount(): bool
    {
        return (bool) $this->paymentAccount?->isConnected();
    }
}
