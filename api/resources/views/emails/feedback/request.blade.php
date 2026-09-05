<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .button { display: inline-block; background: #4CAF50; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('brand/symbol-256-white.png') }}" alt="Running Tickets" width="56" height="56" style="display:block;margin:0 auto 12px;width:56px;height:56px;">
            <h1>🏁 Como foi sua experiência?</h1>
        </div>

        <div class="content">
            <p>Olá!</p>

            <p>
                Vimos que o seu ingresso para <strong>{{ $order->event->title }}</strong> foi
                validado na entrada do evento. Esperamos que tenha sido uma ótima corrida!
            </p>

            <div class="info-box">
                <h3 style="margin-top: 0;">Sua opinião ajuda muito</h3>
                <p style="margin-bottom: 0;">
                    Queremos saber como foi usar a Running Tickets — da compra do ingresso até
                    o check-in. São menos de 2 minutos e o que você responder orienta as
                    próximas melhorias da plataforma.
                </p>
            </div>

            <div style="text-align: center;">
                <a href="{{ $formUrl }}" class="button">Responder a pesquisa</a>
            </div>

            <p>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
            <p style="font-size: 12px; background: #f0f0f0; padding: 10px; word-break: break-all; border-radius: 4px;">
                {{ $formUrl }}
            </p>

            <p style="font-size: 13px; color: #666;">
                Pedido <strong>{{ $order->reference }}</strong>. Obrigado por correr com a gente! 🏃
            </p>
        </div>

        <div class="footer">
            <p>Equipe Running Tickets</p>
            <p>&copy; {{ date('Y') }} Running Tickets. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
