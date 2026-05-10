<?php

namespace Tests\Unit\Services;

use App\Services\WhatsAppService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    // ── normalizePhone ───────────────────────────────────────────────────────

    public function test_normalizes_11_digit_phone(): void
    {
        $service = $this->makeService();
        $this->assertSame('5511999999999', $service->normalizePhone('11999999999'));
    }

    public function test_normalizes_10_digit_phone(): void
    {
        $service = $this->makeService();
        $this->assertSame('551199999999', $service->normalizePhone('1199999999'));
    }

    public function test_normalizes_formatted_phone(): void
    {
        $service = $this->makeService();
        $this->assertSame('5511999999999', $service->normalizePhone('(11) 99999-9999'));
    }

    public function test_passthrough_phone_with_country_code(): void
    {
        $service = $this->makeService();
        $this->assertSame('5511999999999', $service->normalizePhone('5511999999999'));
    }

    // ── send — disabled ──────────────────────────────────────────────────────

    public function test_send_returns_false_when_gateway_disabled(): void
    {
        Config::set('whatsapp.enabled', false);
        Http::fake(); // deve não ser chamado

        $service = $this->makeService();
        $result  = $service->send('11999999999', 'Olá!');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    // ── send — enabled ───────────────────────────────────────────────────────

    public function test_send_posts_to_gateway_when_enabled(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.base_url', 'http://gateway-test:3000');
        Config::set('whatsapp.tenant_id', 'test-tenant');

        Http::fake([
            'http://gateway-test:3000/tenants/test-tenant/messages/send' => Http::response(['ok' => true], 200),
        ]);

        $service = $this->makeService();
        $result  = $service->send('11999999999', 'Olá!');

        $this->assertTrue($result);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://gateway-test:3000/tenants/test-tenant/messages/send'
                && $request->method() === 'POST'
                && $request['phone'] === '5511999999999'
                && $request['message'] === 'Olá!';
        });
    }

    public function test_send_returns_false_on_gateway_error(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.base_url', 'http://gateway-test:3000');
        Config::set('whatsapp.tenant_id', 'test-tenant');

        Http::fake([
            'http://gateway-test:3000/tenants/test-tenant/messages/send' => Http::response(['error' => 'session not open'], 500),
        ]);

        $service = $this->makeService();
        $result  = $service->send('11999999999', 'Olá!');

        $this->assertFalse($result);
    }

    public function test_send_sends_x_api_key_header_when_configured(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.base_url', 'http://gateway-test:3000');
        Config::set('whatsapp.tenant_id', 'test-tenant');
        Config::set('whatsapp.api_key', 'my-secret-key');

        Http::fake([
            'http://gateway-test:3000/tenants/test-tenant/messages/send' => Http::response(['ok' => true], 200),
        ]);

        $service = $this->makeService();
        $service->send('11999999999', 'Teste');

        Http::assertSent(fn (Request $req) => $req->header('X-Api-Key')[0] === 'my-secret-key');
    }

    // ── connect / status ─────────────────────────────────────────────────────

    public function test_connect_returns_status_and_qr(): void
    {
        Config::set('whatsapp.base_url', 'http://gateway-test:3000');
        Config::set('whatsapp.tenant_id', 'test-tenant');

        Http::fake([
            'http://gateway-test:3000/tenants/test-tenant/session/connect' => Http::response([
                'status' => 'connecting',
                'qr'     => 'raw-qr-string',
            ], 200),
        ]);

        $service = $this->makeService();
        $result  = $service->connect();

        $this->assertSame('connecting', $result['status']);
        $this->assertSame('raw-qr-string', $result['qr']);
    }

    public function test_status_returns_closed_when_gateway_unreachable(): void
    {
        Config::set('whatsapp.base_url', 'http://gateway-test:3000');
        Config::set('whatsapp.tenant_id', 'test-tenant');

        Http::fake([
            'http://gateway-test:3000/tenants/test-tenant/session/status' => fn () => throw new \Exception('Connection refused'),
        ]);

        $service = $this->makeService();
        $result  = $service->status();

        $this->assertSame('error', $result['status']);
    }

    private function makeService(): WhatsAppService
    {
        return new WhatsAppService();
    }
}
