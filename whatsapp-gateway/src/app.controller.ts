import {
  Controller,
  Post,
  Get,
  Delete,
  Param,
  Body,
  HttpCode,
  HttpStatus,
  UnauthorizedException,
  BadRequestException,
  Headers,
} from '@nestjs/common';
import { AppService } from './app.service';

@Controller('tenants/:tenantId')
export class AppController {
  constructor(private readonly appService: AppService) {}

  // ── Auth guard ──────────────────────────────────────────────────────────────

  private guard(apiKey: string | undefined): void {
    const expected = process.env.WHATSAPP_API_KEY;
    if (expected && apiKey !== expected) {
      throw new UnauthorizedException('Invalid API key');
    }
  }

  // ── Session ─────────────────────────────────────────────────────────────────

  @Post('session/connect')
  async connect(
    @Param('tenantId') tenantId: string,
    @Headers('x-api-key') apiKey: string,
  ) {
    this.guard(apiKey);
    return this.appService.connect(tenantId);
  }

  @Get('session/status')
  async status(
    @Param('tenantId') tenantId: string,
    @Headers('x-api-key') apiKey: string,
  ) {
    this.guard(apiKey);
    return this.appService.status(tenantId);
  }

  @Get('session/qr')
  async qr(
    @Param('tenantId') tenantId: string,
    @Headers('x-api-key') apiKey: string,
  ) {
    this.guard(apiKey);
    const s = this.appService.status(tenantId);
    return { qr: (await s).qr };
  }

  @Delete('session')
  @HttpCode(HttpStatus.NO_CONTENT)
  async disconnect(
    @Param('tenantId') tenantId: string,
    @Headers('x-api-key') apiKey: string,
  ) {
    this.guard(apiKey);
    await this.appService.disconnect(tenantId);
  }

  // ── Messages ─────────────────────────────────────────────────────────────────

  @Post('messages/send')
  async send(
    @Param('tenantId') tenantId: string,
    @Headers('x-api-key') apiKey: string,
    @Body() body: { phone: string; message: string },
  ) {
    this.guard(apiKey);

    if (!body.phone || !body.message) {
      throw new BadRequestException('phone and message are required');
    }

    const phone = await this.appService.send(tenantId, body.phone, body.message);
    return { ok: true, phone };
  }
}
