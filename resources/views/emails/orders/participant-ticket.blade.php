<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .ticket-box { background: white; padding: 15px; margin: 10px 0; border: 2px solid #4CAF50; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Seu Ingresso Chegou!</h1>
        </div>
        
        <div class="content">
            <div class="success">
                <h2 style="margin-top: 0;">✅ Seu ingresso está garantido!</h2>
                <p>Olá <strong>{{ $orderItem->participant_data['name'] }}</strong>!</p>
                <p>Seu ingresso para o evento está anexado neste email.</p>
            </div>
            
            <div class="info-box">
                <h3>📋 Detalhes do Evento</h3>
                <p><strong>Evento:</strong> {{ $orderItem->order->event->title }}</p>
                <p><strong>Data:</strong> {{ $orderItem->order->event->date_start->format('d/m/Y H:i') }}</p>
                <p><strong>Local:</strong> {{ $orderItem->order->event->venue }}, {{ $orderItem->order->event->city }}</p>
            </div>
            
            <div class="info-box">
                <h3>🎫 Seu Ingresso</h3>
                <div class="ticket-box">
                    <p><strong>Participante:</strong> {{ $orderItem->participant_data['name'] }}</p>
                    <p><strong>Tipo:</strong> {{ $orderItem->ticketType->name }} - {{ $orderItem->category->name }}</p>
                    @if($orderItem->ticket)
                    <p style="font-family: monospace; background: #f0f0f0; padding: 8px; font-size: 12px;">
                        Código: {{ $orderItem->ticket->code }}
                    </p>
                    @endif
                </div>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h3 style="margin-top: 0;">📱 No dia do evento:</h3>
                <ul>
                    <li>Apresente o QR Code anexado neste email</li>
                    <li>Chegue com antecedência</li>
                    <li>Leve um documento com foto</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>Nos vemos no evento! 🏃‍♂️</p>
            <p>&copy; {{ date('Y') }} Running Tickets. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
