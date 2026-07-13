<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d9534f; color: white; padding: 20px; text-align: center; }
        .alert { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #d9534f; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('brand/symbol-256-white.png') }}" alt="Running Tickets" width="56" height="56" style="display:block;margin:0 auto 12px;width:56px;height:56px;">
            <h1>Solicitação de cancelamento não aprovada</h1>
        </div>

        <div class="content">
            <div class="alert">
                <strong>Sua solicitação de cancelamento não foi aprovada.</strong>
                <p style="margin: 8px 0 0 0;">Seu pedido e seus ingressos continuam válidos.</p>
            </div>

            <div class="info-box">
                <h3>Detalhes do Pedido</h3>
                <p><strong>Número:</strong> {{ $order->reference }}</p>
                <p><strong>Evento:</strong> {{ $order->event->title }}</p>
            </div>

            @if($reason)
                <div class="info-box">
                    <h3>Motivo</h3>
                    <p>{{ $reason }}</p>
                </div>
            @endif

            <p style="font-size: 13px; color: #666;">
                Se tiver qualquer dúvida sobre esta decisão, entre em contato com o nosso suporte.
            </p>
        </div>

        <div class="footer">
            Running Tickets — este e-mail foi enviado automaticamente.
        </div>
    </div>
</body>
</html>
