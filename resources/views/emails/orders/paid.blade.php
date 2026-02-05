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
            <h1>🎉 Pagamento Confirmado!</h1>
        </div>
        
        <div class="content">
            <div class="success">
                <h2 style="margin-top: 0;">✅ Seu ingresso está garantido!</h2>
                <p>Pagamento confirmado com sucesso. Seus ingressos estão anexados neste email.</p>
            </div>
            
            <div class="info-box">
                <h3>📋 Detalhes do Pedido</h3>
                <p><strong>Número:</strong> {{ $order->reference }}</p>
                <p><strong>Evento:</strong> {{ $order->event->title }}</p>
                <p><strong>Data:</strong> {{ $order->event->date_start->format('d/m/Y H:i') }}</p>
                <p><strong>Local:</strong> {{ $order->event->venue }}, {{ $order->event->city }}</p>
                <p><strong>Total Pago:</strong> {{ $order->total_formatted }}</p>
            </div>
            
            <div class="info-box">
                <h3>🎫 Seus Ingressos</h3>
                @foreach($order->items as $item)
                <div class="ticket-box">
                    <p><strong>{{ $item->participant_data['name'] }}</strong></p>
                    <p>{{ $item->ticketType->name }} - {{ $item->category->name }}</p>
                    @if($item->ticket)
                    <p style="font-family: monospace; background: #f0f0f0; padding: 8px; font-size: 12px;">
                        Código: {{ $item->ticket->code }}
                    </p>
                    @endif
                </div>
                @endforeach
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
