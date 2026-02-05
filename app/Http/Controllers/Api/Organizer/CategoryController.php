<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories for an event.
     */
    public function index(Request $request, Event $event)
    {
        $categories = Category::where('event_id', $event->id)
            ->orderBy('name')
            ->paginate(20);

        return CategoryResource::collection($categories);
    }

    /**
     * Display the specified category.
     */
    public function show(Request $request, Event $event, Category $category)
    {
        // Verificar se a categoria pertence ao evento
        if ($category->event_id !== $event->id) {
            return response()->json([
                'message' => 'Esta categoria não pertence a este evento.'
            ], 404);
        }

        return new CategoryResource($category);
    }
}
