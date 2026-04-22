<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    /**
     * Lista tickets do usuário autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['orderItem.order.event', 'orderItem.ticketType', 'orderItem.category'])
            ->whereHas('orderItem.order', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });

        // Filtro opcional por evento
        if ($request->has('event_id')) {
            $query->whereHas('orderItem.order', function ($q) use ($request) {
                $q->where('event_id', $request->input('event_id'));
            });
        }

        $tickets = $query->get();

        return response()->json([
            'data' => $tickets->map(function ($ticket) {
                return [
                    'ticket' => TicketResource::make($ticket),
                    'participant' => $ticket->orderItem->participant_data,
                    'event' => [
                        'id'         => $ticket->orderItem->order->event->id,
                        'title'      => $ticket->orderItem->order->event->title,
                        'date_start' => $ticket->orderItem->order->event->date_start,
                        'banner_url' => $ticket->orderItem->order->event->banner_full_url,
                        'city'       => $ticket->orderItem->order->event->city,
                        'state'      => $ticket->orderItem->order->event->state,
                    ],
                    'ticket_type' => [
                        'id'   => $ticket->orderItem->ticketType->id,
                        'name' => $ticket->orderItem->ticketType->name,
                    ],
                    'category' => $ticket->orderItem->category ? [
                        'id'       => $ticket->orderItem->category->id,
                        'name'     => $ticket->orderItem->category->name,
                        'distance' => $ticket->orderItem->category->distance,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Exibe informações de um ticket pelo código (Autenticado)
     */
    public function show(Request $request, string $code): JsonResponse
    {
        $ticket = Ticket::with(['orderItem.order.event', 'orderItem.ticketType', 'orderItem.category'])
            ->where('code', $code)
            ->firstOrFail();

        // Verifica se o ticket pertence ao usuário autenticado
        if ($ticket->orderItem->order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este ticket.',
            ], 403);
        }

        return response()->json([
            'ticket' => TicketResource::make($ticket),
            'participant' => $ticket->orderItem->participant_data,
            'event' => [
                'id' => $ticket->orderItem->order->event->id,
                'title' => $ticket->orderItem->order->event->title,
                'date_start' => $ticket->orderItem->order->event->date_start,
            ],
        ]);
    }

    /**
     * Download do QR Code do ticket (Autenticado)
     */
    public function downloadQr(Request $request, string $code): Response
    {
        $ticket = Ticket::with('orderItem.order.event')->where('code', $code)->firstOrFail();

        $user = $request->user();
        
        // Verifica permissão: dono do ticket, super admin ou organizador do evento
        $isOwner = $ticket->orderItem->order->user_id === $user->id;
        $isSuperAdmin = $user->hasRole('super_admin');
        $isOrganizer = $user->canAccessOrganizer($ticket->orderItem->order->event->organizer_id);
        
        if (!$isOwner && !$isSuperAdmin && !$isOrganizer) {
            abort(403, 'Você não tem permissão para acessar este ticket.');
        }

        if (!$ticket->qr_path || !Storage::exists($ticket->qr_path)) {
            abort(404, 'QR Code não encontrado');
        }

        $file = Storage::get($ticket->qr_path);
        $mimeType = Storage::mimeType($ticket->qr_path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    /**
     * Valida um ticket para check-in no evento (Apenas Organizador do Evento)
     */
    public function validate(Request $request, string $code): JsonResponse
    {
        $ticket = Ticket::with(['orderItem.order.event.organizer', 'orderItem.ticketType', 'orderItem.category'])
            ->where('code', $code)
            ->firstOrFail();

        $user = $request->user();
        $event = $ticket->orderItem->order->event;

        // Verifica se o usuário é super admin ou tem acesso ao organizador do evento
        if (!$user->hasRole('super_admin') && !$user->canAccessOrganizer($event->organizer_id)) {
            return response()->json([
                'message' => 'Você não tem permissão para validar tickets deste evento.',
            ], 403);
        }

        if (!$ticket->canBeUsed()) {
            return response()->json([
                'valid'        => false,
                'message'      => 'Ticket já foi utilizado ou está inválido.',
                'status'       => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'participant'  => $ticket->orderItem->participant_data,
                'ticket_type'  => [
                    'id'   => $ticket->orderItem->ticketType->id,
                    'name' => $ticket->orderItem->ticketType->name,
                ],
                'category' => $ticket->orderItem->category ? [
                    'id'   => $ticket->orderItem->category->id,
                    'name' => $ticket->orderItem->category->name,
                ] : null,
                'used_at' => $ticket->status->value === 'used'
                    ? $ticket->updated_at->toIso8601String()
                    : null,
            ], 422);
        }

        // Marca o ticket como usado
        $ticket->markAsUsed();

        return response()->json([
            'valid'       => true,
            'message'     => 'Ticket validado com sucesso!',
            'ticket'      => TicketResource::make($ticket->fresh()),
            'participant' => $ticket->orderItem->participant_data,
            'ticket_type' => [
                'id'   => $ticket->orderItem->ticketType->id,
                'name' => $ticket->orderItem->ticketType->name,
            ],
            'category' => $ticket->orderItem->category ? [
                'id'   => $ticket->orderItem->category->id,
                'name' => $ticket->orderItem->category->name,
            ] : null,
            'event' => [
                'id'    => $event->id,
                'title' => $event->title,
            ],
        ]);
    }
}
