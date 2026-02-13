<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizerRequest;
use App\Http\Requests\UpdateOrganizerRequest;
use App\Http\Resources\OrganizerResource;
use App\Models\Organizer;
use Illuminate\Http\Request;

class OrganizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $organizers = Organizer::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%")
            )
            ->when($request->status, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->withCount('events')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 6);

        return OrganizerResource::collection($organizers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizerRequest $request)
    {
        $organizer = Organizer::create($request->validated());

        return new OrganizerResource($organizer);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organizer $organizer)
    {
        $organizer->load(['users', 'events']);
        
        return new OrganizerResource($organizer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizerRequest $request, Organizer $organizer)
    {
        $organizer->update($request->validated());

        return new OrganizerResource($organizer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organizer $organizer)
    {
        $organizer->delete();

        return response()->json([
            'message' => 'Organizador deletado com sucesso.'
        ]);
    }
}
