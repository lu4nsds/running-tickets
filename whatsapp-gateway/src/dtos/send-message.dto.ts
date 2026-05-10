import { IsNotEmpty, IsString } from 'class-validator';

export class SendMessageDto {
  @IsString()
  @IsNotEmpty()
  declare phone: string;

  @IsString()
  @IsNotEmpty()
  declare message: string;
}
