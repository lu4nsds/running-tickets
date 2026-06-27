import { IsNotEmpty, IsOptional, IsString } from 'class-validator';

export class SendMessageDto {
  @IsString()
  @IsNotEmpty()
  declare phone: string;

  // Required for text sends; used as the document caption when an attachment is
  // present. Validation of "message OR document" lives in the service.
  @IsString()
  @IsOptional()
  declare message?: string;

  // Optional document attachment. When `data` (base64) is present the message is
  // sent as a document instead of plain text.
  @IsString()
  @IsOptional()
  declare filename?: string;

  @IsString()
  @IsOptional()
  declare mimetype?: string;

  @IsString()
  @IsOptional()
  declare data?: string;

  @IsString()
  @IsOptional()
  declare caption?: string;
}
