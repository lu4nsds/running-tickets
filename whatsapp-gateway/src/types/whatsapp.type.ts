export type GatewayStatus = 'connecting' | 'open' | 'closed' | 'error';

// Shape persisted in Redis (hash `${REDIS_PREFIX}:state:<tenant>`).
export interface SessionState {
  status: GatewayStatus;
  qr: string | null;
  reconnectAttempts: number;
}

export interface SendMessageInput {
  phone: string;
  // Text body for plain sends; doubles as the caption when a document is sent.
  message?: string;
  // Optional document attachment. When `data` (base64) is present the payload is
  // delivered as a document instead of plain text.
  filename?: string;
  mimetype?: string;
  data?: string;
  caption?: string;
}
