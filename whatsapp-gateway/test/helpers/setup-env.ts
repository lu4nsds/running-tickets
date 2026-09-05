// Valores determinísticos para as variáveis de ambiente obrigatórias do gateway
// durante os testes. Mantém os specs agnósticos (sem prefixo de produto hardcoded)
// e garante que redisPrefix()/GatewayConfig não falhem por env ausente. Usa `??=`
// para que um valor já definido (ex.: um spec que testa a ausência) prevaleça.
process.env.REDIS_PREFIX ??= 'test';
process.env.WHATSAPP_DEVICE_NAME ??= 'Test Device';
process.env.WHATSAPP_API_KEY ??= 'test-api-key';
