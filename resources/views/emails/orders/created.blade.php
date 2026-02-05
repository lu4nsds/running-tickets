<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pedido Criado com Sucesso!</h1>
        </div>
        
        <div class="content">
            <h2>Olá!</h2>
            
            <p>Seu pedido foi criado com sucesso. Para garantir sua inscrição, complete o pagamento em até <strong>48 horas</strong>.</p>
            
            <div class="info-box">
                <h3>📋 Detalhes do Pedido</h3>
                <p><strong>Número:</strong> {{ $order->reference }}</p>
                <p><strong>Evento:</strong> {{ $order->event->title }}</p>
                <p><strong>Data:</strong> {{ $order->event->date_start->format('d/m/Y H:i') }}</p>
                <p><strong>Local:</strong> {{ $order->event->venue }}, {{ $order->event->city }}</p>
                <p><strong>Total:</strong> {{ $order->total_formatted }}</p>
            </div>
            
            <div class="info-box">
                <h3>👥 Participantes</h3>
                @foreach($order->items as $item)
                <p>
                    <strong>{{ $item->participant_data['name'] }}</strong><br>
                    {{ $item->ticketType->name }} - {{ $item->category->name }}
                </p>
                @endforeach
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $paymentUrl }}" class="button">💳 REALIZAR PAGAMENTO</a>
            </div>
            
            <p style="color: #999; font-size: 14px;">
                ⏰ Este link expira em 48 horas. Após o prazo, o pedido será cancelado automaticamente.
            </p>
        </div>
        
        <div class="footer">
            <p>Este é um email automático. Por favor, não responda.</p>
            <p>&copy; {{ date('Y') }} Running Tickets. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
