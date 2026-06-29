<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d9534f; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .alert { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #d9534f; }
        .cta { display: inline-block; background: #4CAF50; color: white; padding: 14px 28px; border-radius: 4px; text-decoration: none; font-weight: bold; margin: 10px 0; }
        .reservation { background: #fff3cd; border: 1px solid #ffc107; padding: 12px; margin: 15px 0; border-radius: 4px; font-size: 14px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pagamento não aprovado</h1>
        </div>

        <div class="content">
            <div class="alert">
                <strong>Não foi possível processar seu pagamento.</strong>
                <p style="margin: 8px 0 0 0;">{{ $failureMessage }}</p>
            </div>

            <div class="info-box">
                <h3>Detalhes do Pedido</h3>
                <p><strong>Número:</strong> {{ $order->reference }}</p>
                <p><strong>Evento:</strong> {{ $order->event->title }}</p>
                <p><strong>Data:</strong> {{ $order->event->date_start->setLocalTimezone()->format('d/m/Y H:i') }}</p>
            </div>

            <p>Seus ingressos continuam reservados — você pode tentar pagar novamente clicando no botão abaixo:</p>

            <p style="text-align: center;">
                <a href="{{ $paymentUrl }}" class="cta">Efetuar Pagamento</a>
            </p>

            @if($reservedUntil)
                <div class="reservation">
                    <strong>Atenção:</strong> seus ingressos estão reservados até
                    <strong>{{ $reservedUntil->setLocalTimezone()->format('d/m/Y H:i') }}</strong>.
                    Após esse horário a reserva é liberada para outros compradores.
                </div>
            @endif

            <p style="font-size: 13px; color: #666;">
                Se preferir, acesse "Meus Ingressos" no nosso site para retomar o pagamento.
            </p>
        </div>

        <div class="footer">
            Running Tickets — este e-mail foi enviado automaticamente.
        </div>
    </div>
</body>
</html>
