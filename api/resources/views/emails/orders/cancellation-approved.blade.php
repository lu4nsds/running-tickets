<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .reservation { background: #fff3cd; border: 1px solid #ffc107; padding: 12px; margin: 15px 0; border-radius: 4px; font-size: 14px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cancelamento aprovado</h1>
        </div>

        <div class="content">
            <div class="success">
                <strong>Sua solicitação de cancelamento foi aprovada.</strong>
                <p style="margin: 8px 0 0 0;">O estorno do valor pago foi processado.</p>
            </div>

            <div class="info-box">
                <h3>Detalhes do Pedido</h3>
                <p><strong>Número:</strong> {{ $order->reference }}</p>
                <p><strong>Evento:</strong> {{ $order->event->title }}</p>
                <p><strong>Valor estornado:</strong> {{ $amount }}</p>
            </div>

            <div class="reservation">
                <strong>Atenção:</strong> o prazo para o valor aparecer na sua fatura ou conta
                depende do Mercado Pago e da sua instituição financeira — em geral até alguns
                dias úteis para cartão de crédito.
            </div>

            <p style="font-size: 13px; color: #666;">
                Se tiver qualquer dúvida sobre o estorno, entre em contato com o nosso suporte.
            </p>
        </div>

        <div class="footer">
            Running Tickets — este e-mail foi enviado automaticamente.
        </div>
    </div>
</body>
</html>
