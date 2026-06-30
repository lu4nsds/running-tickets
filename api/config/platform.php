<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Fee Rate
    |--------------------------------------------------------------------------
    |
    | Comissão da plataforma sobre o valor bruto de cada pedido pago,
    | expressa como fração (0.10 = 10%). O líquido do organizador é o bruto
    | menos esta comissão. As taxas do gateway (Mercado Pago) ficam por conta
    | da plataforma e não são descontadas do organizador.
    |
    */

    'fee_rate' => (float) env('PLATFORM_FEE_RATE', 0.10),

];
