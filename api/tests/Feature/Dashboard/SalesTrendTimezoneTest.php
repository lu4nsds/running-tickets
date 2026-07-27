<?php

namespace Tests\Feature\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\AdminDashboardController;
use App\Models\AdminUser;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * As datas são gravadas em UTC, mas os gráficos precisam agrupar pelo dia em
 * America/Sao_Paulo. Sem a conversão, toda venda feita entre 21h e 23h59 (BRT)
 * é contada no dia seguinte e diverge da tabela de pedidos, que exibe a data
 * já convertida para o fuso do navegador.
 */
class SalesTrendTimezoneTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): AdminUser
    {
        $user = AdminUser::factory()->create();
        \DB::table('roles')->insertOrIgnore(['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]);
        $role = \DB::table('roles')->where('slug', 'super_admin')->first();
        \DB::table('admin_user_roles')->insertOrIgnore(['admin_user_id' => $user->id, 'role_id' => $role->id]);

        return $user->fresh();
    }

    /**
     * Reproduz o agrupamento das séries temporais dos dashboards.
     *
     * Não exercita o endpoint porque tanto DashboardController quanto
     * AdminDashboardController usam funções exclusivas do MySQL
     * (JSON_UNQUOTE/JSON_EXTRACT e DATEDIFF/NOW) em outras partes da resposta,
     * e a suíte roda em SQLite — limitação pré-existente.
     */
    private function salesVelocityFor(Event $event): array
    {
        return Order::where('event_id', $event->id)
            ->where('status', OrderStatus::PAID->value)
            ->where('created_at', '>=', Order::localDaysAgo(6))
            ->select(
                Order::localDateExpression('created_at'),
                \DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function test_venda_do_fim_da_noite_conta_no_dia_local_e_nao_no_dia_utc(): void
    {
        // 26/07 00:30 UTC == 25/07 21:30 em America/Sao_Paulo.
        Carbon::setTestNow(Carbon::parse('2026-07-26 12:00:00', 'UTC'));

        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        Order::factory()->for($event)->for($organizer)->paid()->create([
            'created_at' => Carbon::parse('2026-07-26 00:30:00', 'UTC'),
            'total_cents' => 6600,
        ]);

        $velocity = collect($this->salesVelocityFor($event));

        $this->assertSame(['2026-07-25'], $velocity->pluck('date')->all());
        $this->assertSame(1, (int) $velocity->first()['orders']);
    }

    public function test_venda_de_madrugada_permanece_no_proprio_dia(): void
    {
        // 26/07 03:30 UTC == 26/07 00:30 local — não deve retroceder um dia.
        Carbon::setTestNow(Carbon::parse('2026-07-26 12:00:00', 'UTC'));

        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        Order::factory()->for($event)->for($organizer)->paid()->create([
            'created_at' => Carbon::parse('2026-07-26 03:30:00', 'UTC'),
            'total_cents' => 9900,
        ]);

        $velocity = collect($this->salesVelocityFor($event));

        $this->assertSame(['2026-07-26'], $velocity->pluck('date')->all());
    }

    public function test_janela_de_sete_dias_comeca_no_inicio_do_dia_local(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 12:00:00', 'UTC'));

        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();

        // 20/07 03:30 UTC == 20/07 00:30 local: primeiro dia da janela de 7 dias.
        Order::factory()->for($event)->for($organizer)->paid()->create([
            'created_at' => Carbon::parse('2026-07-20 03:30:00', 'UTC'),
            'total_cents' => 1000,
        ]);

        // 20/07 02:30 UTC == 19/07 23:30 local: já fora da janela.
        Order::factory()->for($event)->for($organizer)->paid()->create([
            'created_at' => Carbon::parse('2026-07-20 02:30:00', 'UTC'),
            'total_cents' => 1000,
        ]);

        $velocity = collect($this->salesVelocityFor($event));

        $this->assertSame(['2026-07-20'], $velocity->pluck('date')->all());
        $this->assertSame(1, (int) $velocity->first()['orders']);
    }

    public function test_periodo_do_mes_atual_nao_inclui_o_fim_do_mes_anterior(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00', 'UTC'));

        [$start, $end] = $this->resolveDateRange('current_month');

        // 01/07 00:00 local == 01/07 03:00 UTC.
        $this->assertSame('2026-07-01 03:00:00', $start->utc()->toDateTimeString());
        $this->assertSame('2026-07-11 02:59:59', $end->utc()->toDateTimeString());
    }

    private function resolveDateRange(string $preset): array
    {
        $method = new \ReflectionMethod(AdminDashboardController::class, 'resolveDateRange');
        $method->setAccessible(true);

        return $method->invoke(new AdminDashboardController, Request::create('/', 'GET', ['preset' => $preset]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
