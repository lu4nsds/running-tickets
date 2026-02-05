<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Listar eventos públicos
     */
    public function index()
    {
        $events = Event::with([
            'categories',
            'ticketTypes' => function ($query) {
                $query->where('active', true);
            },
        ])
        ->orderBy('date_start')
        ->get();

        return response()->json($events);
    }
}
