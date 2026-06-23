export type GatewayStatus = 'connecting' | 'open' | 'closed' | 'error';

// Shape persisted in Redis (hash `${REDIS_PREFIX}:state:<tenant>`).
export interface SessionState {
  status: GatewayStatus;
  qr: string | null;
  reconnectAttempts: number;
}

export interface SendMessageInput {
  phone: string;
  message: string;
}
