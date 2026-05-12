import type { WASocket } from '@whiskeysockets/baileys';

export type GatewayStatus = 'connecting' | 'open' | 'closed' | 'error';

export interface TenantSession {
  socket: WASocket | null;
  status: GatewayStatus;
  lastQr: string | null;
  isConnecting: boolean;
}

export interface SendMessageInput {
  phone: string;
  message: string;
}

export interface SendDocumentInput {
  phone: string;
  filename: string;
  mimetype: string;
  data: string;
  caption?: string;
}
