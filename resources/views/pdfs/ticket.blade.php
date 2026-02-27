<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ingresso - {{ $event->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        
        .ticket {
            width: 100%;
            padding: 30px;
            border: 3px solid #4CAF50;
            border-radius: 8px;
            background: #fff;
        }
        
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            margin-bottom: 20px;
        }
        
        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 8px;
        }
        
        .ticket-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 35%;
            padding: 6px 0;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 6px 0;
            color: #333;
        }
        
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .qr-code {
            width: 250px;
            height: 250px;
            margin: 15px auto;
            display: block;
        }
        
        .ticket-code {
            font-size: 16px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 10px 20px;
            border: 2px dashed #4CAF50;
            border-radius: 4px;
            display: inline-block;
            margin-top: 10px;
            letter-spacing: 2px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .warning-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .warning-list {
            list-style: none;
            padding-left: 0;
        }
        
        .warning-list li {
            padding: 4px 0;
            color: #856404;
        }
        
        .warning-list li:before {
            content: "✓ ";
            font-weight: bold;
            margin-right: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            font-size: 10px;
            color: #999;
        }
        
        .highlight {
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <div class="ticket-label">🎫 Ingresso</div>
            <div class="event-title">{{ $event->title }}</div>
        </div>
        
        <!-- Informações do Participante -->
        <div class="section">
            <div class="section-title">👤 Participante</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nome:</div>
                    <div class="info-value">{{ $participantName }}</div>
                </div>
                @if($participantCpf)
                <div class="info-row">
                    <div class="info-label">CPF:</div>
                    <div class="info-value">{{ $participantCpf }}</div>
                </div>
                @endif
                @if($participantEmail)
                <div class="info-row">
                    <div class="info-label">E-mail:</div>
                    <div class="info-value">{{ $participantEmail }}</div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Informações do Ingresso -->
        <div class="section">
            <div class="section-title">🎟️ Detalhes do Ingresso</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Tipo:</div>
                    <div class="info-value">{{ $ticketType->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Categoria:</div>
                    <div class="info-value">{{ $category->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pedido:</div>
                    <div class="info-value">{{ $order->reference }}</div>
                </div>
            </div>
        </div>
        
        <!-- Informações do Evento -->
        <div class="section">
            <div class="section-title">📅 Evento</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Data:</div>
                    <div class="info-value">{{ $event->date_start->format('d/m/Y') }} às {{ $event->date_start->format('H:i') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Local:</div>
                    <div class="info-value">{{ $event->venue }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cidade:</div>
                    <div class="info-value">{{ $event->city }}, {{ $event->state }}</div>
                </div>
                @if($event->address)
                <div class="info-row">
                    <div class="info-label">Endereço:</div>
                    <div class="info-value">{{ $event->address }}</div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- QR Code -->
        <div class="qr-section">
            <div class="section-title">📱 QR Code para Check-in</div>
            <img src="{{ $qrCodeBase64 }}" alt="QR Code" class="qr-code">
            <div class="ticket-code">{{ $ticket->code }}</div>
        </div>
        
        <!-- Instruções -->
        <div class="warning-box">
            <div class="warning-title">⚠️ Instruções Importantes</div>
            <ul class="warning-list">
                <li>Apresente este QR Code na entrada do evento</li>
                <li>Chegue com pelo menos 30 minutos de antecedência</li>
                <li>Leve um documento oficial com foto</li>
                <li>Guarde este ingresso até o dia do evento</li>
                <li>Não compartilhe este QR Code com terceiros</li>
            </ul>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Running Tickets - Sistema de Ingressos</p>
            <p>Ingresso gerado em {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
