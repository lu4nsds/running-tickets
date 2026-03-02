<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
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
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request, Event $event)
    {
        $validated = $request->validated();
        $validated['event_id'] = $event->id;

        $category = Category::create($validated);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified category.
     */
    public function show(Event $event, Category $category)
    {
        // Verificar se a categoria pertence ao evento
        if ($category->event_id !== $event->id) {
            return response()->json([
                'message' => 'Esta categoria não pertence a este evento.'
            ], 404);
        }

        return new CategoryResource($category);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Event $event, Category $category)
    {
        // Verificar se a categoria pertence ao evento
        if ($category->event_id !== $event->id) {
            return response()->json([
                'message' => 'Esta categoria não pertence a este evento.'
            ], 404);
        }

        $validated = $request->validated();
        $category->update($validated);

        return new CategoryResource($category);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Event $event, Category $category)
    {
        // Verificar se a categoria pertence ao evento
        if ($category->event_id !== $event->id) {
            return response()->json([
                'message' => 'Esta categoria não pertence a este evento.'
            ], 404);
        }

        // Verificar se existem inscrições vinculadas
        $orderItemsCount = $category->orderItems()->count();
        
        if ($orderItemsCount > 0) {
            return response()->json([
                'message' => "Não é possível excluir esta categoria pois existem {$orderItemsCount} inscrições vinculadas.",
                'order_items_count' => $orderItemsCount
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoria excluída com sucesso.'
        ], 200);
    }
}
