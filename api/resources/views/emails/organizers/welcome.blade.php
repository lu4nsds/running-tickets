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
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bem-vindo ao Running Tickets</h1>
        </div>

        <div class="content">
            <p>Olá, <strong>{{ $user->name }}</strong>!</p>

            <p>
                Sua conta foi criada e você foi adicionado como
                <strong>{{ $user->pivot->role ?? 'membro' }}</strong>
                do organizador <strong>{{ $organizer->name }}</strong> no Running Tickets.
            </p>

            <p>Para acessar o sistema, você precisa primeiro ativar sua conta definindo sua senha:</p>

            <div style="text-align: center;">
                <a href="{{ $activationUrl }}" class="button">Ativar Minha Conta</a>
            </div>

            <div class="warning">
                <p style="margin: 0;"><strong>⏰ Atenção:</strong> Este link é válido por <strong>48 horas</strong>.</p>
                <p style="margin: 5px 0 0 0;">Após ativar sua conta, você poderá acessar o sistema normalmente com seu e-mail e a senha que definir.</p>
            </div>

            <p>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
            <p style="font-size: 12px; background: #f0f0f0; padding: 10px; word-break: break-all; border-radius: 4px;">
                {{ $activationUrl }}
            </p>

            <div class="info">
                <p style="margin: 0;"><strong>Suas informações de acesso:</strong></p>
                <p style="margin: 5px 0 0 0;">E-mail: <strong>{{ $user->email }}</strong></p>
            </div>
        </div>

        <div class="footer">
            <p>Equipe Running Tickets</p>
            <p>&copy; {{ date('Y') }} Running Tickets. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
