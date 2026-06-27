import {
  Body,
  Controller,
  Delete,
  Get,
  HttpCode,
  Param,
  Post,
} from '@nestjs/common';
import { AppService } from '@src/app.service';
import { SendMessageDto } from '@src/dtos/send-message.dto';
import type { GatewayStatus } from '@src/types/whatsapp.type';

@Controller('tenants/:tenantUuid')
export class AppController {
  constructor(private readonly appService: AppService) {}

  @Post('session/connect')
  async connect(
    @Param('tenantUuid') tenantUuid: string,
  ): Promise<{ status: GatewayStatus; qr: string | null }> {
    return this.appService.connect(tenantUuid);
  }

  @Get('session/status')
  status(@Param('tenantUuid') tenantUuid: string): Promise<{
    status: GatewayStatus;
    qr: string | null;
  }> {
    return this.appService.getStatus(tenantUuid);
  }

  @Get('session/qr')
  async qr(
    @Param('tenantUuid') tenantUuid: string,
  ): Promise<{ qr: string | null }> {
    return { qr: (await this.appService.getStatus(tenantUuid)).qr };
  }

  @Delete('session')
  @HttpCode(204)
  async remove(@Param('tenantUuid') tenantUuid: string): Promise<void> {
    return this.appService.removeSession(tenantUuid);
  }

  @Post('messages/send')
  async send(
    @Param('tenantUuid') tenantUuid: string,
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
