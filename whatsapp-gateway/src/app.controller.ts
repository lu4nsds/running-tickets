import {
  Body,
  Controller,
  Delete,
  Get,
  HttpCode,
  Param,
  ParseUUIDPipe,
  Post,
} from '@nestjs/common';
import { AppService } from '@src/app.service';
import { SendMessageDto } from '@src/dtos/send-message.dto';
import type { GatewayStatus } from '@src/types/whatsapp.type';

// `tenantUuid` é interpolado em chaves e padrões glob do Redis. Validar como UUID
// já na borda garante que nenhum metacaractere chegue lá — sem isso, um
// `DELETE /tenants/%2A/session` casaria com as chaves de todos os tenants e
// apagaria as credenciais de todo mundo.
const TenantUuid = (): ParameterDecorator =>
  Param('tenantUuid', new ParseUUIDPipe());

@Controller('tenants/:tenantUuid')
export class AppController {
  constructor(private readonly appService: AppService) {}

  @Post('session/connect')
  async connect(
    @TenantUuid() tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    return this.appService.connect(tenantUuid);
  }

  @Get('session/status')
  status(@TenantUuid() tenantUuid: string): Promise<{
    status: GatewayStatus;
    qr: string | null;
  }> {
    return this.appService.getStatus(tenantUuid);
  }

  @Get('session/qr')
  async qr(@TenantUuid() tenantUuid: string): Promise<{ qr: string | null }> {
    return { qr: (await this.appService.getStatus(tenantUuid)).qr };
  }

  @Delete('session')
  @HttpCode(204)
  async remove(@TenantUuid() tenantUuid: string): Promise<void> {
    return this.appService.removeSession(tenantUuid);
  }

  @Post('messages/send')
  async send(
    @TenantUuid() tenantUuid: string,
    @Body() body: SendMessageDto,
  ): Promise<{ ok: true; phone: string }> {
    return this.appService.sendMessage(tenantUuid, {
      phone: body.phone,
      message: body.message,
      filename: body.filename,
      mimetype: body.mimetype,
      data: body.data,
      caption: body.caption,
    });
  }
}
