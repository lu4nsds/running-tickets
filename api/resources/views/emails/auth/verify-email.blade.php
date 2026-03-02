<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .button { display: inline-block; background: #4CAF50; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✉️ Bem-vindo ao Running Tickets!</h1>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $user->name }}</strong>!</p>
            
            <div class="success">
                <p style="margin: 0;"><strong>🎉 Sua conta foi criada com sucesso!</strong></p>
            </div>
            
            <p>Para garantir a segurança da sua conta e começar a usar o Running Tickets, precisamos verificar seu endereço de email.</p>
            
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verificar Meu Email</a>
            </div>
            
            <div class="info">
                <p style="margin: 0;"><strong>⏰ Atenção:</strong> Este link é válido por <strong>60 minutos</strong>.</p>
            </div>
            
            <p>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
            <p style="font-size: 12px; background: #f0f0f0; padding: 10px; word-break: break-all; border-radius: 4px;">
                {{ $verificationUrl }}
            </p>
            
            <p><strong>Após verificar seu email, você poderá:</strong></p>
            <ul>
                <li>✅ Comprar ingressos para eventos</li>
                <li>✅ Gerenciar seus pedidos</li>
                <li>✅ Acessar seus ingressos a qualquer momento</li>
                <li>✅ Receber notificações sobre seus eventos</li>
            </ul>
            
            <p style="color: #666; font-size: 14px; margin-top: 30px;">
                Se você não criou esta conta, pode ignorar este email com segurança.
            </p>
        </div>
        
        <div class="footer">
            <p>Nos vemos nos eventos! 🏃‍♂️</p>
            <p>&copy; {{ date('Y') }} Running Tickets. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
