import { IsNotEmpty, IsOptional, IsString } from 'class-validator';

export class SendDocumentDto {
  @IsString()
  @IsNotEmpty()
  declare phone: string;

  @IsString()
  @IsNotEmpty()
  declare filename: string;

  @IsString()
  @IsNotEmpty()
  declare mimetype: string;

  @IsString()
  @IsNotEmpty()
  declare data: string;

  @IsString()
  @IsOptional()
  declare caption?: string;
}
